<?php
defined( 'ABSPATH' ) || exit;

class Better_Messages_Chats
{

    public static function instance()
    {

        // Store the instance locally to avoid private static replication
        static $instance = null;

        // Only run these methods if they haven't been run previously
        if ( null === $instance ) {
            $instance = new Better_Messages_Chats;
            $instance->setup_actions();
        }

        // Always return the instance
        return $instance;

        // The last metroid is in captivity. The galaxy is at peace.
    }

    public function setup_actions(){
        add_action( 'init',      array( $this, 'register_post_type' ) );
        add_action( 'save_post', array( $this, 'save_post' ), 1, 2 );

        add_shortcode( 'bp_better_messages_chat_room', array( $this, 'layout' ) );

        //add_action( 'messages_message_sent', array( $this, 'on_message_sent' ) );

        add_action( "save_post_bpbm-chat", array( $this, 'on_chat_update' ), 10, 3 );
        add_action( 'before_delete_post',  array( $this, 'on_chat_delete' ), 10, 1 );

        add_action( 'sync_auto_add_users', array( $this, 'sync_auto_add_users'), 10, 1 );

        add_action( 'user_register', array( $this, 'on_user_register' ), 10, 2 );
        add_action( 'add_user_role', array( $this, 'on_user_role_change' ), 10, 2 );
        add_action( 'set_user_role', array( $this, 'on_user_role_change' ), 10, 3 );

        add_action( 'rest_api_init',  array( $this, 'rest_api_init' ) );
        add_filter( 'better_messages_rest_thread_item', array( $this, 'rest_thread_item'), 10, 5 );

        add_filter('better_messages_thread_title', array( $this, 'chat_thread_title' ), 10, 3 );
    }

    /**
     * @param string $title
     * @param int $thread_id
     * @param BM_Thread $thread
     * @return string
     */
    public function chat_thread_title(string $title, int $thread_id, $thread ){
        $thread_type = Better_Messages()->functions->get_thread_type( $thread_id );
        if( $thread_type !== 'chat-room' ) return $title;

        $chat_id = (int) Better_Messages()->functions->get_thread_meta($thread_id, 'chat_id');
        $chat = get_post( $chat_id );

        if( $chat ){
            return $chat->post_title;
        }

        return $title;
    }

    public function rest_api_init(){
        register_rest_route( 'better-messages/v1', '/chat/(?P<id>\d+)/join', array(
            'methods' => 'POST',
            'callback' => array( $this, 'join_chat' ),
            'permission_callback' => array( Better_Messages_Rest_Api(), 'is_user_authorized' )
        ) );

        register_rest_route( 'better-messages/v1', '/chat/(?P<id>\d+)/leave', array(
            'methods' => 'POST',
            'callback' => array( $this, 'leave_chat' ),
            'permission_callback' => array( Better_Messages_Rest_Api(), 'is_user_authorized' )
        ) );
    }

    public function rest_thread_item( $thread_item, $thread_id, $thread_type, $include_personal, $user_id ){
        if( $thread_type !== 'chat-room'){
            return $thread_item;
        }

        $chat_id = (int) Better_Messages()->functions->get_thread_meta($thread_id, 'chat_id');
        $settings = $this->get_chat_settings( $chat_id );

        $recipients = Better_Messages()->functions->get_recipients( $thread_id );

        $is_participant = isset( $recipients[$user_id] );
        $auto_join = $settings['auto_join'] === '1';

        if( $include_personal && $auto_join && ! $is_participant ){
            $is_participant = $this->add_to_chat( $user_id, $chat_id );
        }

        $moderators =  (array) Better_Messages()->functions->get_moderators( $thread_id );

        $thread_item['chatRoom']['id']                   = (int) $chat_id;

        $template =  $settings['template'];
        $thread_item['chatRoom']['template']             = $template;
        $thread_item['chatRoom']['modernLayout']         = $settings['modernLayout'];

        $thread_item['chatRoom']['onlyJoinedCanRead']    = ( $settings['only_joined_can_read'] === '1' );
        $thread_item['chatRoom']['autoJoin']             = $auto_join;
        $thread_item['chatRoom']['enableFiles']          = ( $settings['enable_files'] === '1' );
        $thread_item['chatRoom']['guestAllowed']         = ( $settings['allow_guests_chat'] === '1' );

        $thread_item['moderators']                       = $moderators;

        $thread_item['chatRoom']['mustJoinMessage']      = $settings['must_join_message'];
        $thread_item['chatRoom']['joinButtonText']       = $settings['join_button_text'];
        $thread_item['chatRoom']['notAllowedText']       = $settings['not_allowed_text'];
        $thread_item['chatRoom']['notAllowedReplyText']  = $settings['not_allowed_reply_text'];
        $thread_item['chatRoom']['mustLoginText']        = $settings['must_login_text'];
        $thread_item['chatRoom']['loginButtonText']      = $settings['login_button_text'];
        $thread_item['chatRoom']['guestButtonText']        = $settings['guest_button_text'];


        if( $include_personal ) {
            $is_moderator = user_can( $user_id, 'manage_options') || Better_Messages()->functions->is_thread_moderator( $thread_id, $user_id ) ;

            $thread_item['chatRoom']['isJoined'] = $is_participant;
            $thread_item['chatRoom']['canJoin']  = $this->user_can_join($user_id, $chat_id);
            $thread_item['chatRoom']['hideParticipants'] = ( ! $is_moderator && $settings['hide_participants'] === '1' );

            if ( ! $is_participant ) {
                $thread_item['isHidden'] = (int) true;
                $thread_item['permissions']['canReply'] = false;
                $thread_item['permissions']['canMinimize'] = false;
                $thread_item['permissions']['canMuteThread'] = false;
                $thread_item['chatRoom']['hideParticipants'] = true;
            } else {
                $can_reply = $this->user_can_reply( $user_id, $chat_id );

                if( $is_moderator ){
                    $thread_item['restricted'] = Better_Messages()->moderation->get_restricted_users( $thread_id );
                }

                $thread_item['permissions']['canReply'] = $can_reply;

                if( ! $can_reply ){
                    if( count($thread_item['permissions']['canReplyMsg']) === 0 ) $thread_item['permissions']['canReplyMsg']['cant_reply_to_chat'] = $settings['not_allowed_reply_text'];
                }
            }
        }

        return $thread_item;
    }

    public function on_user_register( $user_id, $userdata = null ){
        $user = get_userdata( $user_id );
        $roles = $user->roles;

        $this->sync_roles_update( $roles );
    }

    public function on_user_role_change( $user_id, $role, $old_roles = [] ){
        $this->sync_roles_update( [ $role ] );
    }

    public function on_chat_update( $post_ID, $post, $update ){
        $thread_id = $this->get_chat_thread_id( $post_ID );

        $name = get_the_title( $post_ID );
        global $wpdb;

        $wpdb->update(
            bm_get_table('threads'),
            array(
                'subject'   => $name,
            ),
            array(
                'id' => $thread_id,
            ),
            array( '%s' ), array( '%d' )
        );
    }

    public function on_chat_delete( $post_ID ){
        $post = get_post( $post_ID );
        if( $post->post_type === 'bpbm-chat' ){
            $thread_id = $this->get_chat_thread_id( $post_ID );
            Better_Messages()->functions->erase_thread( $thread_id );
        }
    }

    public function on_message_sent( $message )
    {
        if( ! isset($message->thread_id) ) return false;

        $thread_id = $message->thread_id;
        $chat_id   = Better_Messages()->functions->get_thread_meta( $thread_id, 'chat_id' );

        if( ! $chat_id ) return false;
        global $wpdb;
        $wpdb->update(bm_get_table('recipients'), ['unread_count' => 0], ['thread_id' => $thread_id], ['%d'], ['%d']);
        Better_Messages()->hooks->clean_thread_cache( $thread_id );

        return true;
    }

    public function leave_chat( WP_REST_Request $request ){
        global $wpdb;

        $user_id = Better_Messages()->functions->get_current_user_id();
        $chat_id = intval($request->get_param('id'));

        $thread_id = $this->get_chat_thread_id( $chat_id );

        $result = false;

        $userIsParticipant = (bool) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM `" . bm_get_table('recipients') . "` WHERE `user_id` = %d AND `thread_id` = %d
        ", $user_id, $thread_id));

        if( $userIsParticipant ) {
            $result = (bool) $wpdb->delete(
                bm_get_table('recipients'),
                array(
                    'user_id'   => $user_id,
                    'thread_id' => $thread_id
                ),
                array( '%d', '%d' )
            );
        }

        Better_Messages()->hooks->clean_thread_cache( $thread_id );

        Better_Messages()->functions->delete_thread_meta( $thread_id, 'auto_add_hash' );

        do_action( 'better_messages_after_chat_left', $thread_id, $chat_id );
        do_action( 'better_messages_thread_updated', $thread_id );
        do_action( 'better_messages_info_changed',   $thread_id );

        $return = Better_Messages()->api->get_threads( [ $thread_id ], false, false );
        $return['result'] = $result;

        return $return;
    }

    public function join_chat( WP_REST_Request $request ){
        $user_id = Better_Messages()->functions->get_current_user_id();
        $chat_id = intval($request->get_param('id'));

        $is_joined = $this->add_to_chat( $user_id, $chat_id );

        $thread_id = $this->get_chat_thread_id( $chat_id );

        $return = Better_Messages()->api->get_threads( [ $thread_id ], false, false );

        $return['result'] = $is_joined;

        return $return;
    }

    public function add_to_chat( $user_id, $chat_id ){
        if( ! $this->user_can_join( $user_id, $chat_id ) ){
            return false;
        }

        $thread_id = $this->get_chat_thread_id( $chat_id );

        $result = Better_Messages()->functions->add_participant_to_thread( $thread_id, $user_id );

        do_action( 'better_messages_after_chat_join', $thread_id, $chat_id );
        do_action( 'better_messages_thread_updated', $thread_id );
        do_action( 'better_messages_info_changed',   $thread_id );

        return $result;
    }

    public function register_post_type(){
        $args = array(
            'public'               => false,
            'labels'               => [
                'name'          => _x( 'Chat Rooms', 'Chat Rooms', 'bp-better-messages' ),
                'singular_name' => _x( 'Chat Room', 'Chat Rooms', 'bp-better-messages' ),
                'add_new'       => _x( 'Create new Chat Room', 'Chat Rooms', 'bp-better-messages' ),
                'add_new_item'  => _x( 'Create new Chat Room', 'Chat Rooms', 'bp-better-messages' ),
                'edit_item'     => _x( 'Edit Chat Room', 'Chat Rooms', 'bp-better-messages' ),
                'new_item'      => _x( 'New Chat Room', 'Chat Rooms', 'bp-better-messages' ),
            ],
            'publicly_queryable'   => false,
            'show_ui'              => true,
            'show_in_menu'         => 'bp-better-messages',
            'menu_position'        => 1,
            'query_var'            => false,
            'capability_type'      => 'page',
            'has_archive'          => false,
            'hierarchical'         => false,
            'show_in_admin_bar'    => false,
            'show_in_nav_menus'    => false,
            'supports'             => array( 'title' ),
            'register_meta_box_cb' => array( $this, 'register_meta_box' )

        );

        register_post_type( 'bpbm-chat', $args );
    }

    public function register_meta_box(){
        add_meta_box(
            'bpbm-chat-settings',
            _x( 'Chat settings', 'Chat rooms settings page', 'bp-better-messages' ),
            array( $this, 'bpbm_chat_settings' )
        );
    }

    public function get_chat_settings( $chat_id ){
        $defaults = array(
            'only_joined_can_read'            => '0',
            'enable_chat_email_notifications' => '0',
            'can_join'                        => [],
            'can_reply'                       => [],
            'auto_add'                        => [],
            'template'                        => 'default',
            'modernLayout'                    => 'default',
            'auto_join'                       => '0',
            'enable_notifications'            => '0',
            'allow_guests'                    => '0',
            'allow_guests_chat'               => '0',
            'hide_participants'               => '0',
            'enable_files'                    => '0',
            'hide_from_thread_list'           => '1',
            'must_join_message'               => _x('You need to join this chat room to send messages', 'Chat rooms settings page', 'bp-better-messages'),
            'join_button_text'                => _x('Join chat room', 'Chat rooms settings page', 'bp-better-messages'),
            'not_allowed_text'                => _x('You are not allowed to join this chat room', 'Chat rooms settings page', 'bp-better-messages'),
            'not_allowed_reply_text'          => _x('You are not allowed to reply in this chat room', 'Chat rooms settings page', 'bp-better-messages'),
            'must_login_text'                 => _x('You need to login to website to send messages', 'Chat rooms settings page', 'bp-better-messages'),
            'login_button_text'               => _x('Login', 'Chat rooms settings page', 'bp-better-messages'),
            'guest_button_text'               => _x('Chat as Guest', 'Chat rooms settings page', 'bp-better-messages')
        );

        $args = get_post_meta( $chat_id, 'bpbm-chat-settings', true );

        if( empty($args) || ! is_array($args) ){
            $args = array();
        }

        return wp_parse_args( $args, $defaults );
    }

    public function save_post( $post_id, $post ){
        if( ! isset($_POST['bpbm_save_chat_nonce']) ){
            return $post->ID;
        }

        //Verify it came from proper authorization.
        if ( ! wp_verify_nonce($_POST['bpbm_save_chat_nonce'], 'bpbm-save-chat-settings-' . $post->ID ) ) {
            return $post->ID;
        }

        //Check if the current user can edit the post
        if ( ! current_user_can( 'manage_options' ) ) {
            return $post->ID;
        }

        if( isset( $_POST['bpbm'] ) && is_array($_POST['bpbm']) ){
            $settings = (array) $_POST['bpbm'];

            if ( ! isset( $settings['only_joined_can_read'] ) ) {
                $settings['only_joined_can_read'] = '0';
            }

            if ( ! isset( $settings['auto_join'] ) ) {
                $settings['auto_join'] = '0';
            }

            if ( ! isset( $settings['hide_participants'] ) ) {
                $settings['hide_participants'] = '0';
            }

            if ( ! isset( $settings['enable_chat_email_notifications'] ) ) {
                $settings['enable_chat_email_notifications'] = '0';
            }

            if ( ! isset( $settings['enable_files'] ) ) {
                $settings['enable_files'] = '0';
            }

            if ( ! isset( $settings['hide_from_thread_list'] ) ) {
                $settings['hide_from_thread_list'] = '0';
            }

            if ( ! isset( $settings['enable_notifications'] ) ) {
                $settings['enable_notifications'] = '0';
            }

            if ( ! isset( $settings['allow_guests'] ) ) {
                $settings['allow_guests'] = '0';
            }

            if ( ! isset( $settings['allow_guests_chat'] ) ) {
                $settings['allow_guests_chat'] = '0';
            }

            if ( ! isset( $settings['must_join_message'] ) || empty( $settings['must_join_message'] )  ) {
                $settings['must_join_message'] = _x('You need to join this chat room to send messages', 'Chat rooms settings page', 'bp-better-messages');
            } else {
                $settings['must_join_message'] = wp_kses( $settings['must_join_message'], 'user_description' );
            }

            if ( ! isset( $settings['join_button_text'] ) || empty( $settings['join_button_text'] )  ) {
                $settings['join_button_text'] = _x('Join chat room', 'Chat rooms settings page', 'bp-better-messages');
            } else {
                $settings['join_button_text'] = sanitize_text_field( $settings['join_button_text'] );
            }

            if ( ! isset( $settings['login_button_text'] ) || empty( $settings['login_button_text'] )  ) {
                $settings['login_button_text'] = _x('Login', 'Chat rooms settings page', 'bp-better-messages');
            } else {
                $settings['login_button_text'] = sanitize_text_field( $settings['login_button_text'] );
            }

            if ( ! isset( $settings['guest_button_text'] ) || empty( $settings['guest_button_text'] )  ) {
                $settings['guest_button_text'] = _x('Chat as Guest', 'Chat rooms settings page', 'bp-better-messages');
            } else {
                $settings['guest_button_text'] = sanitize_text_field( $settings['guest_button_text'] );
            }



            if ( ! isset( $settings['must_login_text'] ) || empty( $settings['must_login_text'] )  ) {
                $settings['must_login_text'] =  _x('You need to login to website to send messages', 'Chat rooms settings page', 'bp-better-messages');
            } else {
                $settings['must_login_text'] = wp_kses( $settings['must_login_text'], 'user_description' );
            }

            if ( ! isset( $settings['not_allowed_text'] ) || empty( $settings['not_allowed_text'] )  ) {
                $settings['not_allowed_text'] = _x('You are not allowed to join this chat room', 'Chat rooms settings page', 'bp-better-messages');
            } else {
                $settings['not_allowed_text'] = wp_kses( $settings['not_allowed_text'], 'user_description' );
            }

            if ( ! isset( $settings['not_allowed_reply_text'] ) || empty( $settings['not_allowed_reply_text'] )  ) {
                $settings['not_allowed_reply_text'] = _x('You are not allowed to reply in this chat room', 'Chat rooms settings page', 'bp-better-messages');
            } else {
                $settings['not_allowed_reply_text'] = wp_kses( $settings['not_allowed_reply_text'], 'user_description' );
            }

            update_post_meta( $post->ID, 'bpbm-chat-settings', $settings );

            $thread_id = $this->get_chat_thread_id( $post->ID );

            if( $settings['hide_from_thread_list'] === '1' ) {
                Better_Messages()->functions->update_thread_meta($thread_id, 'exclude_from_threads_list', '1');
            } else {
                Better_Messages()->functions->delete_thread_meta($thread_id, 'exclude_from_threads_list');
            }

            if( $settings['enable_notifications'] === '1' ) {
                Better_Messages()->functions->update_thread_meta($thread_id, 'enable_notifications', '1');
            } else {
                Better_Messages()->functions->delete_thread_meta($thread_id, 'enable_notifications');
            }


            if( isset( $settings['auto_add'] ) ) {
                update_post_meta( $post->ID, 'bpbm-chat-auto-add', $settings['auto_add'] );
            } else {
                delete_post_meta( $post->ID, 'bpbm-chat-auto-add' );
            }

            $this->sync_auto_add_users( $post->ID );

            do_action( 'better_messages_thread_updated', $thread_id );
            do_action( 'better_messages_info_changed',   $thread_id );
        }

    }

    public function bpbm_chat_settings( $post ){
        $roles = get_editable_roles();
        if(isset($roles['administrator'])) unset( $roles['administrator'] );

        wp_nonce_field( 'bpbm-save-chat-settings-' . $post->ID, 'bpbm_save_chat_nonce' );

        $settings = $this->get_chat_settings( $post->ID ); ?>
        <style type="text/css">
            .bp-better-messages-roles-list {
                max-height: 250px;
                overflow: auto;
                background: white;
                padding: 15px;
                border: 1px solid #ccc;
            }
        </style>
        <script type="text/javascript">
            jQuery('body').on('click', '.bpbm-select-all', function (event){
                event.preventDefault();

                var ul = jQuery(this).closest('ul');
                ul.find('input[type="checkbox"]').prop('checked', true);
            });

            jQuery('body').on('click', '.bpbm-unselect-all', function (event){
                event.preventDefault();

                var ul = jQuery(this).closest('ul');
                ul.find('input[type="checkbox"]').prop('checked', false);
            });

            jQuery('body').on('change', '#bpbm_hide_from_thread_list,#bpbm_allow_guests', function (event){
                updateCheckboxes();
            });

            jQuery(document).ready(function (){
                updateCheckboxes();
            });

            function updateCheckboxes(){
                var is_checked = jQuery('#bpbm_hide_from_thread_list').is(':checked');

                if( is_checked ){
                    jQuery('#bpbm_enable_notifications').attr('disabled', true);
                } else {
                    jQuery('#bpbm_enable_notifications').attr('disabled', false);
                }


                var guest_can_see = jQuery('#bpbm_allow_guests').is(':checked');

                if( guest_can_see ){
                    jQuery('#bpbm_allow_guests_chat').attr('disabled', false);
                } else {
                    jQuery('#bpbm_allow_guests_chat').attr('disabled', true);
                }
            }

        </script>
        <div style="margin: 20px 0">
            <label for="bpbm-shortcode" style="font-size: 13px;font-weight: bold"><?php _ex('Shortcode', 'Chat rooms settings page', 'bp-better-messages') ?></label>
            <input id="bpbm-shortcode" readonly="" type="text" style="width: 100%;" onclick="this.focus();this.select()" value='[bp_better_messages_chat_room id="<?php echo $post->ID; ?>"]'>
        </div>
        <div style="margin: 20px 0">
            <label for="bpbm_only_joined_can_read" style="font-size: 13px;font-weight: bold"><input id="bpbm_only_joined_can_read" type="checkbox" name="bpbm[only_joined_can_read]"  <?php checked( $settings[ 'only_joined_can_read' ], '1' ); ?> value="1" ><?php _ex('Only joined can read messages', 'Chat rooms settings page', 'bp-better-messages') ?></label>
        </div>

        <div style="margin: 20px 0">
            <label for="bpbm_auto_join" style="font-size: 13px;font-weight: bold"><input id="bpbm_auto_join" type="checkbox" name="bpbm[auto_join]"  <?php checked( $settings[ 'auto_join' ], '1' ); ?> value="1" ><?php _ex('Auto join users to this chat (when they visiting chat page)', 'Chat rooms settings page', 'bp-better-messages') ?></label>
        </div>

        <div style="margin: 20px 0">
            <label for="bpbm_hide_participants" style="font-size: 13px;font-weight: bold"><input id="bpbm_hide_participants" type="checkbox" name="bpbm[hide_participants]" <?php checked( $settings[ 'hide_participants' ], '1' ); ?> value="1" ><?php _ex('Hide participants list (not for admins)', 'Chat rooms settings page', 'bp-better-messages') ?></label>
        </div>

        <div style="margin: 20px 0">
            <label for="bpbm_enable_files" style="font-size: 13px;font-weight: bold"><input id="bpbm_enable_files" type="checkbox" name="bpbm[enable_files]" <?php checked( $settings[ 'enable_files' ], '1' ); ?> value="1" ><?php _ex('Enable file uploads', 'Chat rooms settings page', 'bp-better-messages') ?></label>
        </div>

        <div style="margin: 20px 0">
            <label for="bpbm_hide_from_thread_list" style="font-size: 13px;font-weight: bold"><input id="bpbm_hide_from_thread_list" type="checkbox" name="bpbm[hide_from_thread_list]" <?php checked( $settings[ 'hide_from_thread_list' ], '1' ); ?> value="1" ><?php _ex('Hide chat from conversation list', 'Chat rooms settings page', 'bp-better-messages') ?></label>
        </div>

        <div style="margin: 20px 0">
            <label for="bpbm_enable_notifications" style="font-size: 13px;font-weight: bold"><input id="bpbm_enable_notifications" type="checkbox" name="bpbm[enable_notifications]" <?php checked( $settings[ 'enable_notifications' ], '1' ); ?> value="1" ><?php _ex('Enable notifications via email & push notifications (when push enabled)', 'Chat rooms settings page', 'bp-better-messages') ?></label>
        </div>

        <div style="margin: 20px 0">
            <label for="bpbm_allow_guests" style="font-size: 13px;font-weight: bold"><input id="bpbm_allow_guests" type="checkbox" name="bpbm[allow_guests]" <?php checked( $settings[ 'allow_guests' ], '1' ); ?> value="1" ><?php _ex('Allow not logged-in users (guests) to see chat room', 'Chat rooms settings page', 'bp-better-messages') ?></label>
        </div>

        <div style="margin: 20px 0">
            <label for="bpbm_allow_guests_chat" style="font-size: 13px;font-weight: bold"><input id="bpbm_allow_guests_chat" type="checkbox" name="bpbm[allow_guests_chat]" <?php checked( $settings[ 'allow_guests_chat' ], '1' ); ?> value="1" ><?php _ex('Allow not logged-in users (guests) to send messages in chat room', 'Chat rooms settings page', 'bp-better-messages') ?></label>
        </div>

        <div style="margin: 20px 0">
            <label style="font-size: 13px;font-weight: bold"><?php _ex('Who can join to this chat room?', 'Chat rooms settings page', 'bp-better-messages') ?></label>
            <ul class="bp-better-messages-roles-list">
                <li><a href="#" class="bpbm-select-all"><?php _e('Select All', 'bp-better-messages') ?></a> | <a href="#" class="bpbm-unselect-all"><?php _e('Unselect All', 'bp-better-messages') ?></a></li>
                <?php foreach( $roles as $slug => $role ){ ?>
                    <li><input id="<?php echo $slug; ?>_1" type="checkbox" name="bpbm[can_join][]" value="<?php echo $slug; ?>" <?php if( in_array($slug, $settings['can_join']) ) echo 'checked'; ?>><label for="<?php echo $slug; ?>_1"><?php echo $role['name']; ?></label></li>
                <?php } ?>
            </ul>
        </div>

        <div style="margin: 20px 0">
            <label style="font-size: 13px;font-weight: bold"><?php _ex('Who can reply in this chat room?', 'Chat rooms settings page', 'bp-better-messages') ?></label>
            <ul class="bp-better-messages-roles-list">
                <li><a href="#" class="bpbm-select-all"><?php _e('Select All', 'bp-better-messages') ?></a> | <a href="#" class="bpbm-unselect-all"><?php _e('Unselect All', 'bp-better-messages') ?></a></li>
                <?php foreach( $roles as $slug => $role ){ ?>
                    <li><input id="<?php echo $slug; ?>_2" type="checkbox" name="bpbm[can_reply][]" value="<?php echo $slug; ?>" <?php if( in_array($slug, $settings['can_reply']) ) echo 'checked'; ?>><label for="<?php echo $slug; ?>_2"><?php echo $role['name']; ?></label></li>
                <?php } ?>
            </ul>
        </div>

        <div style="margin: 20px 0">
            <label style="font-size: 13px;font-weight: bold"><?php _ex('Auto add users with following roles to this chat room', 'Chat rooms settings page', 'bp-better-messages') ?></label>
            <p><?php _ex('Users will be added to chat room immediately after save of these settings. The following roles will not be able to leave the chat room.', 'Chat rooms settings page', 'bp-better-messages') ?></p>
            <ul class="bp-better-messages-roles-list">
                <li><a href="#" class="bpbm-select-all"><?php _e('Select All', 'bp-better-messages') ?></a> | <a href="#" class="bpbm-unselect-all"><?php _e('Unselect All', 'bp-better-messages') ?></a></li>
                <?php foreach( $roles as $slug => $role ){ ?>
                    <li><input id="<?php echo $slug; ?>_3" type="checkbox" name="bpbm[auto_add][]" value="<?php echo $slug; ?>" <?php if( in_array($slug, $settings['auto_add']) ) echo 'checked'; ?>><label for="<?php echo $slug; ?>_3"><?php echo $role['name']; ?></label></li>
                <?php } ?>
            </ul>
        </div>

        <table class="form-table">
            <tr>
                <th scope="row" style="width: 300px">
                    <?php _e( 'Messages styling', 'bp-better-messages' ); ?>
                </th>
                <td>
                    <fieldset>
                        <fieldset>
                            <label><input type="radio" name="bpbm[template]" value="default" <?php checked( $settings[ 'template' ], 'default' ); ?>><?php _e( 'Default', 'bp-better-messages' ); ?></label>
                            <br>
                            <label><input type="radio" name="bpbm[template]" value="standard" <?php checked( $settings[ 'template' ], 'standard' ); ?>><?php _e( 'Standard', 'bp-better-messages' ); ?></label>
                            <br>
                            <label><input type="radio" name="bpbm[template]" value="modern" <?php checked( $settings[ 'template' ], 'modern' ); ?>><?php _e( 'Modern', 'bp-better-messages' ); ?></label>
                        </fieldset>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row" style="width: 300px">
                    <?php _e( 'Modern messages layout', 'bp-better-messages' ); ?>
                </th>
                <td>
                    <fieldset>
                        <fieldset>
                            <label><input type="radio" name="bpbm[modernLayout]" value="default" <?php checked( $settings[ 'modernLayout' ], 'default' ); ?>>
                                <?php _e( 'Default', 'bp-better-messages' ); ?>
                            </label>
                            <br>
                            <label><input type="radio" name="bpbm[modernLayout]" value="left" <?php checked( $settings[ 'modernLayout' ], 'left' ); ?>>
                                <?php _e( 'My messages at left side', 'bp-better-messages' ); ?>
                            </label>
                            <br>
                            <label><input type="radio" name="bpbm[modernLayout]" value="right" <?php checked( $settings[ 'modernLayout' ], 'right' ); ?>>
                                <?php _e( 'My messages at right side', 'bp-better-messages' ); ?>
                            </label>
                            <br>
                            <label><input type="radio" name="bpbm[modernLayout]" value="leftAll" <?php checked( $settings[ 'modernLayout' ], 'leftAll' ); ?>>
                                <?php _e( 'All messages at left side', 'bp-better-messages' ); ?>
                            </label>
                        </fieldset>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row" style="width: 300px">
                    <?php _ex( 'Not Joined Message', 'Chat rooms settings page', 'bp-better-messages' ); ?>
                    <span style="color: gray;font-size: 12px;"><?php _e( 'HTML allowed', 'bp-better-messages' ); ?></span>

                </th>
                <td>
                    <input style="width: 100%" type="text" name="bpbm[must_join_message]" value="<?php esc_attr_e( $settings['must_join_message'] ); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row" style="width: 300px">
                    <?php _ex( 'Join Button Text', 'Chat rooms settings page', 'bp-better-messages' ); ?>
                </th>
                <td>
                    <input style="width: 100%" type="text" name="bpbm[join_button_text]" value="<?php esc_attr_e($settings['join_button_text']); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row" style="width: 300px">
                    <?php _ex( 'Not Allowed To Join Message', 'Chat rooms settings page', 'bp-better-messages' ); ?>
                    <span style="color: gray;font-size: 12px;"><?php _e( 'HTML allowed', 'bp-better-messages' ); ?></span>
                </th>
                <td>
                    <input style="width: 100%" type="text" name="bpbm[not_allowed_text]" value="<?php esc_attr_e($settings['not_allowed_text']); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row" style="width: 300px">
                    <?php _ex( 'Not Allowed To Reply Message', 'Chat rooms settings page', 'bp-better-messages' ); ?>
                    <span style="color: gray;font-size: 12px;"><?php _e( 'HTML allowed', 'bp-better-messages' ); ?></span>
                </th>
                <td>
                    <input style="width: 100%" type="text" name="bpbm[not_allowed_reply_text]" value="<?php esc_attr_e($settings['not_allowed_reply_text']); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row" style="width: 300px">
                    <?php _ex( 'Not Authorized Message', 'Chat rooms settings page', 'bp-better-messages' ); ?>
                    <span style="color: gray;font-size: 12px;"><?php _e( 'HTML allowed', 'bp-better-messages' ); ?></span>
                </th>
                <td>
                    <input style="width: 100%" type="text" name="bpbm[must_login_text]" value="<?php esc_attr_e($settings['must_login_text']); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row" style="width: 300px">
                    <?php _ex( 'Login Button Text', 'Chat rooms settings page', 'bp-better-messages' ); ?>
                </th>
                <td>
                    <input style="width: 100%" type="text" name="bpbm[login_button_text]" value="<?php esc_attr_e($settings['login_button_text']); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row" style="width: 300px">
                    <?php _ex( 'Guest Chat Button Text', 'Chat rooms settings page', 'bp-better-messages' ); ?>
                </th>
                <td>
                    <input style="width: 100%" type="text" name="bpbm[guest_button_text]" value="<?php esc_attr_e($settings['guest_button_text']); ?>">
                </td>
            </tr>


        </table>
    <?php
    }

    public function layout( $args ){
        $chat_id = $args['id'];

        if (defined('WP_DEBUG') && true === WP_DEBUG) {
            // some debug to add later
        } else {
            error_reporting(0);
        }

        $thread_id     = $this->get_chat_thread_id( $chat_id );

        if( ! $thread_id ) return false;

        $chat_settings = $this->get_chat_settings( $chat_id );

        if( ! is_user_logged_in() ){
            $allow_guests = $chat_settings['allow_guests'] === '1';
            if( ! $allow_guests ) {
                return Better_Messages()->functions->render_login_form();
            } else {
                Better_Messages()->enqueue_js();
                Better_Messages()->enqueue_css();

                add_action('wp_footer', array( Better_Messages_Customize(), 'header_output' ), 100);
            }
        }

        $this->sync_auto_add_users( $chat_id );

        global $bpbm_errors;
        $bpbm_errors = [];

        do_action('bp_better_messages_before_generation');

        do_action('bp_better_messages_before_chat', $chat_id, $thread_id );

        $path = apply_filters('bp_better_messages_views_path', Better_Messages()->path . '/views/');

        $is_mini = isset($_GET['mini']);

        $template = 'layout-chat-room.php';

        ob_start();

        $template = apply_filters( 'bp_better_messages_current_template', $path . $template, $template );

        if( ! Better_Messages()->functions->is_ajax() && count( $bpbm_errors ) > 0 ) {
            echo '<p class="bpbm-notice">' . implode('</p><p class="bpbm-notice">', $bpbm_errors) . '</p>';
        }

        if( $template !== false ) {
            Better_Messages()->functions->pre_template_include();
            include($template);
            Better_Messages()->functions->after_template_include();
        }

        $content = ob_get_clean();
        $content = str_replace( 'loading="lazy"', '', $content );

        $content = Better_Messages()->functions->minify_html( $content );

        do_action('bp_better_messages_after_chat', $chat_id, $thread_id);
        do_action('bp_better_messages_after_generation');
        return $content;
    }

    public function user_can_join( $user_id, $chat_id ){
        if( user_can( $user_id, 'manage_options') ) return true;

        $settings = $this->get_chat_settings( $chat_id );
        $thread_id = $this->get_chat_thread_id( $chat_id );

        $has_access = false;

        if( $user_id > 0 ) {
            $user = get_userdata($user_id);

            foreach ($user->roles as $role) {
                if (in_array($role, $settings['can_join'])) {
                    $has_access = true;
                }
            }
        } else {
            if( $settings['allow_guests_chat'] === '1' ){
                $has_access = true;
            }
        }

        return apply_filters( 'better_messages_chat_user_can_join', $has_access, $user_id, $chat_id, $thread_id );
    }

    public function user_can_reply( $user_id, $chat_id ){
        if( user_can( $user_id, 'manage_options') ) return true;
        $settings = $this->get_chat_settings( $chat_id );
        $thread_id = $this->get_chat_thread_id( $chat_id );

        $has_access = false;

        if( $user_id >= 0 ) {
            $user = get_userdata($user_id);

            foreach ($user->roles as $role) {
                if (in_array($role, $settings['can_reply'])) {
                    $has_access = true;
                }
            }
        } else {
            $has_access = true;
        }

        return Better_Messages()->functions->can_send_message_filter( $has_access, $user_id, $thread_id );
    }

    public function get_chat_thread_id( $chat_id ){
        global $wpdb;

        $thread_id = (int) $wpdb->get_var( $wpdb->prepare( "
        SELECT bm_thread_id 
        FROM `" . bm_get_table('threadsmeta') . "` 
        WHERE `meta_key` = 'chat_id' 
        AND   `meta_value` = %s
        ", $chat_id ) );

        if( $thread_id === 0 ) {
            $thread_id = false;
        } else {
            $messages_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*)  FROM `" . bm_get_table('threads') . "` WHERE `id` = %d", $thread_id));

            if( $messages_count === 0 ) {
                $thread_id = false;
            }
        }

        if( ! $thread_id ) {
            $chat = get_post($chat_id);
            if( ! $chat ) return false;

            $wpdb->query( $wpdb->prepare( "
            DELETE 
            FROM `" . bm_get_table('threadsmeta') . "` 
            WHERE `meta_key` = 'chat_id' 
            AND   `meta_value` = %s
            ", $chat_id ) );

            $name = get_the_title( $chat_id );

            $wpdb->insert(
                bm_get_table('threads'),
                array(
                    'subject' => $name,
                    'type'    => 'chat-room'
                )
            );

            $thread_id = $wpdb->insert_id;

            Better_Messages()->functions->update_thread_meta( $thread_id, 'chat_thread', true );
            Better_Messages()->functions->update_thread_meta( $thread_id, 'chat_id', $chat_id );
        }

        return $thread_id;
    }

    public function sync_roles_update( $roles = [] ){
        if( count( $roles ) === 0 ) return false;

        global $wpdb;

        $clauses = [];

        foreach( $roles as $role ){
            $clauses[] = $wpdb->prepare("( `postmeta`.`meta_key` = 'bpbm-chat-auto-add' AND `postmeta`.`meta_value` LIKE %s )", '%"' . $role . '"%');
        }

        $chat_ids = $wpdb->get_col("SELECT 
        `posts`.`ID`
        FROM {$wpdb->posts} posts
        INNER JOIN {$wpdb->postmeta} postmeta 
        ON ( `posts`.`ID` = `postmeta`.`post_id` ) 
        WHERE 1=1  
        AND ( " . implode(' OR ', $clauses ) . " ) 
        AND `posts`.`post_type` = 'bpbm-chat' 
        GROUP BY `posts`.ID");

        foreach( $chat_ids as $chat_id ){
            if( ! wp_get_scheduled_event( 'sync_auto_add_users', [ $chat_id ] ) ){
                wp_schedule_single_event( time(), 'sync_auto_add_users', [ $chat_id ] );
            }
        }
    }

    public function sync_auto_add_users( $chat_id ){
        $thread_id  = $this->get_chat_thread_id( $chat_id );

        if( ! $thread_id ) return false;

        $settings   = Better_Messages()->chats->get_chat_settings( $chat_id );

        if( count( $settings['auto_add'] ) === 0 ){
            return false;
        }

        global $wpdb;
        $cap_key = $wpdb->get_blog_prefix() . 'capabilities';

        $clauses = [];
        foreach( $settings['auto_add'] as $role ){
            $clauses[] = $wpdb->prepare("(`usermeta`.`meta_key` = %s AND `usermeta`.`meta_value` LIKE %s )", $cap_key, '%"' . $role . '"%');
        }

        $users_hash = $wpdb->get_var("SELECT MD5(GROUP_CONCAT(`users`.`ID`)) as users_hash
        FROM {$wpdb->users} `users` 
        INNER JOIN {$wpdb->usermeta} `usermeta`
        ON ( `users`.`ID` = `usermeta`.`user_id` ) 
        WHERE 1=1 
        AND ( " .  implode( ' OR ', $clauses ) . " )");

        $thread_hash = Better_Messages()->functions->get_thread_meta( $thread_id, 'auto_add_hash' );

        if( $users_hash === $thread_hash ){
            return false;
        }

        $users = $wpdb->get_col("SELECT `users`.`ID`
        FROM {$wpdb->users} `users` 
        INNER JOIN {$wpdb->usermeta} `usermeta`
        ON ( `users`.`ID` = `usermeta`.`user_id` ) 
        WHERE 1=1 
        AND ( " .  implode( ' OR ', $clauses ) . " )");

        $array = [];

        $recipients = Better_Messages()->functions->get_recipients( $thread_id );

        foreach( $users as $index => $member ){
            if( isset( $recipients[$member] ) ){
                unset( $recipients[$member] );
                continue;
            }

            if( $this->user_can_join( $member, $chat_id ) ) {
                $array[] = [
                    $member,
                    $thread_id,
                    0,
                    0,
                ];
            }
        }

        if( count($array) > 0 ) {
            $sql = "INSERT INTO " . bm_get_table('recipients') . "
            (user_id, thread_id, unread_count, is_deleted)
            VALUES ";

            $values = [];
            foreach ($array as $item) {
                $values[] = $wpdb->prepare( "(%d, %d, %d, %d)", $item );
            }

            $sql .= implode( ',', $values );

            $wpdb->query( $sql );
        }

        Better_Messages()->hooks->clean_thread_cache( $thread_id );
        Better_Messages()->functions->update_thread_meta( $thread_id, 'auto_add_hash', $users_hash );

        do_action( 'better_messages_thread_updated', $thread_id );
        do_action( 'better_messages_info_changed',   $thread_id );
    }
}

function Better_Messages_Chats()
{
    return Better_Messages_Chats::instance();
}
