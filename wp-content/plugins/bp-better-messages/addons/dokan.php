<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Better_Messages_Dokan' ) ) {

    class Better_Messages_Dokan
    {

        public static function instance()
        {

            static $instance = null;

            if (null === $instance) {
                $instance = new Better_Messages_Dokan();
            }

            return $instance;
        }

        public function __construct(){
            if( ! defined('BM_DEV') ) return;
            add_action( 'woocommerce_single_product_summary',   array( &$this, 'product_page_contact_button' ), 35 );
            add_action( 'dokan_settings_before_store_email', array( $this, 'store_settings_output' ), 10, 2 );
            add_filter( 'dokan_store_profile_settings_args', array( $this, 'store_settings_save' ), 10, 2 );
        }

        public function is_livechat_enabled( $store_id ){
            $store_info = dokan_get_store_info( $store_id );

            if( isset($store_info['bm_livechat']) && $store_info['bm_livechat'] === 'no' ){
                return false;
            } else {
                return true;
            }
        }

        public function store_settings_save( $dokan_settings, $store_id ){
            if( isset( $_POST['setting_bm_livechat'] ) && $_POST['setting_bm_livechat'] === 'no' ){
                $dokan_settings['bm_livechat'] = 'no';
            } else {
                $dokan_settings['bm_livechat'] = 'yes';
            }

            return $dokan_settings;
        }

        public function store_settings_output( $current_user, $profile_info ){
            $enable_livechat = isset($profile_info['bm_livechat']) && $profile_info['bm_livechat'] === 'yes' ? 'yes' : 'no'; ?>
            <div class="dokan-form-group">
                <label class="dokan-w3 dokan-control-label"><?php _ex( 'Live Chats', 'Marketplace Integrations', 'bp-better-messages' ); ?></label>
                <div class="dokan-w5 dokan-text-left">
                    <div class="checkbox">
                        <label>
                            <input type="hidden" name="setting_bm_livechat" value="no">
                            <input type="checkbox" name="setting_bm_livechat" value="yes" <?php checked( $enable_livechat, 'yes' ); ?>> <?php echo esc_html_x( 'Enable live chat in store', 'Marketplace Integrations', 'bp-better-messages'  ); ?>
                        </label>
                    </div>
                </div>
            </div>
            <?php
        }

        public function product_page_contact_button(){
            global $post;

            if( is_product() && $post && is_object( $post ) ) {
                $seller_id = $post->post_author;

                if( dokan_is_user_seller( $seller_id ) ){
                    $livechat_enabled = $this->is_livechat_enabled( $seller_id );
                    if( $livechat_enabled ){
                        //echo 'here';
                        echo do_shortcode('[better_messages_live_chat_button subject="wc_product_' . $post->ID . '"]');
                    }
                }
            }
        }
    }
}

