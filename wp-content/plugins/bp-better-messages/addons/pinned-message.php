<?php
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'Better_Messages_Pinned_Message' ) ) {

    class Better_Messages_Pinned_Message
    {

        public static function instance()
        {

            static $instance = null;

            if (null === $instance) {
                $instance = new Better_Messages_Pinned_Message();
                $instance->setup_actions();
            }

            return $instance;
        }

        public function setup_actions(){
        }

    }
}
