<?php
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'Better_Messages_Guests' ) ):

    class Better_Messages_Guests
    {

        private $table;

        private $current_guest_id = false;

        public static function instance()
        {

            static $instance = null;

            if (null === $instance) {
                $instance = new Better_Messages_Guests();
            }

            return $instance;
        }

        public function __construct()
        {
            $this->table = bm_get_table('guests');

            $this->check_guest_auth();

            add_action('wp_enqueue_scripts', array($this, 'load_scripts'));
            #add_action('wp_footer', array($this, 'html_element'), 999);
            add_action('rest_api_init', array($this, 'rest_api_init'));
            add_filter('better_messages_rest_is_user_authorized', array($this, 'guest_auth'), 10, 2);
            add_filter('better_messages_rest_user_item', array($this, 'guest_user_item'), 10, 3);
            add_filter('better_messages_guest_user_id', array( $this, 'guest_user_id' ) );
        }

        public function guest_user_id( $user_id ){
            if( is_user_logged_in() ){
                return $user_id;
            }

            if( $this->current_guest_id ){
                return -1 * abs( $this->current_guest_id );
            }

            return $user_id;
        }

        public function check_guest_auth(){
            if( ! isset( $_SERVER['HTTP_BM_GUEST_ID'] ) || ! isset( $_SERVER['HTTP_BM_GUEST_SECRET'] ) ){
                return false;
            }

            $guest_id     = intval( $_SERVER['HTTP_BM_GUEST_ID'] );
            $guest_secret = strval( $_SERVER['HTTP_BM_GUEST_SECRET'] );

            $guest = $this->get_guest_user( $guest_id );

            if( $guest ) {
                if ($guest_secret === $guest->secret) {
                    $this->current_guest_id = $guest_id;

                    $this->update_last_active( $guest_id );
                    return true;
                }
            }

            return false;
        }

        public function update_last_active( int $guest_id ){
            global $wpdb;

            return $wpdb->query( $wpdb->prepare("UPDATE `" . bm_get_table('guests') . "` 
            SET last_active = CURRENT_TIMESTAMP()
            WHERE id = %d", $guest_id ) );
        }

        public function guest_auth($authorized, WP_REST_Request $request ){
            if( is_user_logged_in() ) {
                return $authorized;
            } else if( $this->current_guest_id ) {
                return true;
            } else {
                return $authorized;
            }
        }


        /**
         * @param int $user_id
         * @return array|BM_Guest_User|false|object|stdClass
         */
        public function get_guest_user( int $user_id ) {
            $user_id = abs( $user_id );

            $guest_user = wp_cache_get( 'guest_user_' . $user_id, 'bm_messages' );

            if( $guest_user ){
                return $guest_user;
            }

            global $wpdb;

            $query = $wpdb->prepare( "SELECT * FROM `" . bm_get_table('guests') . "` WHERE `id` = %d", $user_id );
            $guest_user = $wpdb->get_row( $query );

            if( $guest_user ){
                if( empty( $guest_user->name ) ){
                    $guest_user->name = _x('Anonymous User', 'Not logged-in user', 'bp-better-messages');
                }

                wp_cache_set( 'guest_user_' . $user_id, $guest_user, 'bm_messages' );

                return $guest_user;
            } else {
                return false;
            }
        }

        public function guest_user_item( $item, $user_id, $include_personal ){
            if( $user_id >= 0 ) {
                return $item;
            }

            $guest_user = $this->get_guest_user( $user_id );

            if( $guest_user ){
                $item['name'] = $guest_user->name;

                $item['avatar'] = Better_Messages()->url . 'assets/images/avatar.png';

                if( ! empty( $guest_user->email ) ){
                    $avatar = get_avatar_url( $guest_user->email );
                    if( $avatar ){
                        $item['avatar'] = $avatar;
                    }
                }

                $item['canBlock'] = false;
                $item['canVideo'] = false;
                $item['canAudio'] = false;
                $item['verified'] = false;
            }

            return $item;
        }

        public function rest_api_init(){
            register_rest_route( 'better-messages/v1', '/guests/register', array(
                'methods' => 'GET',
                'callback' => array( $this, 'register' ),
                'permission_callback' => function() {
                    return ! is_user_logged_in();
                },
            ) );
            register_rest_route( 'better-messages/v1', '/guests/update', array(
                'methods' => 'POST',
                'callback' => array( $this, 'update' ),
                'permission_callback' => array( Better_Messages_Rest_Api(), 'is_user_authorized' )
            ) );
        }

        public function random_string($length) {
            $key = '';
            $keys = array_merge(range(0, 9), range('a', 'z'));

            for ($i = 0; $i < $length; $i++) {
                $key .= $keys[array_rand($keys)];
            }

            return $key;
        }

        public function update( WP_REST_Request $request ){
            global $wpdb;

            $user_id = Better_Messages()->functions->get_current_user_id();

            if( $user_id >= 0 ){
                return new WP_Error(
                    'rest_forbidden',
                    _x( 'Sorry, you are not allowed to do that', 'Rest API Error', 'bp-better-messages' ),
                    array( 'status' => rest_authorization_required_code() )
                );
            }

            $guest_user = $this->get_guest_user( $user_id );
            if( ! $guest_user ){
                return new WP_Error(
                    'rest_forbidden',
                    _x( 'Sorry, you are not allowed to do that', 'Rest API Error', 'bp-better-messages' ),
                    array( 'status' => rest_authorization_required_code() )
                );
            }

            $data = [];

            $name = sanitize_text_field( $request->get_param('name') );

            if( ! empty( $name ) ){
                $data['name'] = $name;
            }


            $return = false;

            if( count( $data ) > 0 ) {
                $data['last_changed'] = Better_Messages()->functions->get_microtime();

                $result = $wpdb->update( bm_get_table('guests'), $data, [ 'id' => absint($user_id) ] );
                if ( false !== $result ) {
                    wp_cache_delete( 'guest_user_' . absint($user_id), 'bm_messages' );

                    $guest_user = $this->get_guest_user( $user_id );

                    $return = [
                        'id'     => (int) $guest_user->id,
                        'secret' => $guest_user->secret,
                        'name'   => $guest_user->name,
                        'email'  => $guest_user->email,
                        'user'   => Better_Messages()->functions->rest_user_item( $user_id )
                    ];

                    do_action( 'better_messages_user_updated', $user_id );
                }
            }

            return $return;
        }



        public function register( WP_REST_Request $request ){
            global $wpdb;

            $secret = $this->random_string( 30 );

            $generator = new \BetterMessages\RandomNameGenerator\Alliteration();

            $name = apply_filters( 'better_messages_generated_guest_name', $generator->getName() );

            $email = '';

            $result = $wpdb->insert( $this->table, [
                'secret' => $secret,
                'name'   => $name,
                'email'  => $email,
                'ip'     => $this->get_client_ip(),
            ] );

            if( $result ) {
                $guest_id = $wpdb->insert_id;

                return [
                    'id'     => $guest_id,
                    'secret' => $secret,
                    'name'   => $name,
                    'email'  => $email,
                    'user'   => Better_Messages()->functions->rest_user_item( $guest_id )
                ];
            }

            return false;
        }

        public function load_scripts()
        {
            if( is_user_logged_in() ) return;

            //Better_Messages()->enqueue_js();
            //Better_Messages()->enqueue_css();
        }

        public function html_element(){
            if( is_user_logged_in() ) return;

            echo '<div id="bm-guest-chat-container"></div>';
        }

        public function register_new_guest(){

        }

        public function get_client_ip(){
            $ip = '';

            if ( isset($_SERVER['HTTP_CLIENT_IP']) && ! empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && ! empty($_SERVER['HTTP_X_FORWARDED_FOR'] )) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else if( isset($_SERVER['REMOTE_ADDR']) && ! empty($_SERVER['REMOTE_ADDR'] ) ){
                $ip = $_SERVER['REMOTE_ADDR'];
            }

            return $ip;
        }
    }

endif;

function Better_Messages_Guests(){
    return Better_Messages_Guests::instance();
}
