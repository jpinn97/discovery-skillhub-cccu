<?php

// Include wp-load.php to access WordPress functions
require_once(ABSPATH . 'wp-load.php');

global $wpdb;
$wpdb->show_errors();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = get_current_user_id();

    // Build the form data object
    $form_data = array(
        'user_id' => $user_id,
        'title' => $_POST['title'],
        'firstname' => $_POST['firstname'],
        'surname' => $_POST['surname'],
        'university' => $_POST['university'],
        'degree_title' => $_POST['degree_title'],
        'subject' => $_POST['subject'],
        'date_obtained' => $_POST['date'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'summary' => $_POST['summary'],
        'cv_filename' => $_FILES['cv']['name'],
        'coverletter_filename' => $_FILES['coverletter']['name'],
        'linkedin' => $_POST['linkedin'],
        'website' => $_POST['website'],
    );


    // Check if a row with the given user_id already exists
    $existing_row = $wpdb->get_row("SELECT * FROM cv_info WHERE user_id = $user_id");

    if ($existing_row) {
        // Update the existing row
        $result = $wpdb->update("cv_info", $form_data, array('user_id' => $user_id));
    } else {
        // Insert a new row with the user_id
        $form_data['user_id'] = $user_id;
        $result = $wpdb->insert("cv_info", $form_data);
    }

    if ($result) {
        // Success message
    } else {
        // Error message
    }
}
