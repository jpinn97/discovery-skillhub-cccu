<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profile_id = $_POST['user_id'];
    $profile_approval_status = $_POST['profile_approval_status'];

    $meta_key = 'profile_approval_status'; // Replace with the actual meta key

    update_user_meta($profile_id, $meta_key, $profile_approval_status);
}
