<?php
defined( 'ABSPATH' ) || exit;
$initialHeight = (int) apply_filters( 'bp_better_messages_max_height', Better_Messages()->settings['messagesHeight'] );
echo '<div class="bp-messages-chat-wrap" style="height: ' . $initialHeight . 'px" data-thread-id="' .  esc_attr($thread_id) . '" data-chat-id="'  . esc_attr($chat_id) . '">' . Better_Messages()->functions->container_placeholder() . '</div>';
