<?php
// Include wp-load.php to access WordPress functions
require_once('/home/rhrrist/public_html/wp-load.php');

global $wpdb;
$wpdb->show_errors();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // get form data
    $user_id = get_current_user_id();
    $title = $_POST['title'];
    $firstname = $_POST['firstname'];
    $surname = $_POST['surname'];
    $university = $_POST['university'];
    $degree_title = $_POST['degree_title'];
    $subject = $_POST['subject'];
    $date_obtained = $_POST['date'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $summary = $_POST['summary'];
    $linkedin = $_POST['linkedin'];
    $website = $_POST['website'];
    $cv_filename = $_FILES['cv']['name'];
    $cv_tmpname = $_FILES['cv']['tmp_name'];
    $coverletter_filename = $_FILES['coverletter']['name'];
    $coverletter_tmpname = $_FILES['coverletter']['tmp_name'];

    // Build the form data object
    $form_data = array(
        'user_id' => $user_id,
        'title' => $title,
        'firstname' => $firstname,
        'surname' => $surname,
        'university' => $university,
        'degree_title' => $degree_title,
        'subject' => $subject,
        'date_obtained' => $date_obtained,
        'email' => $email,
        'phone' => $phone,
        'summary' => $summary,
        'cv_filename' => $cv_filename,
        'coverletter_filename' => $coverletter_filename,
        'linkedin' => $linkedin,
        'website' => $website,
    );

    // Check if a row with the given user_id already exists
    $existing_row = $wpdb->get_row("SELECT * FROM cv_info WHERE user_id = $user_id");

    if ($existing_row) {
        // Update the existing row
        $result = $wpdb->update("cv_info", $form_data, array('user_id' => $user_id));
    } else {
        // Insert a new row with the user_id
        $form_data['user_id'] = $form_data['user_id'] = $user_id;
        $result = $wpdb->insert("cv_info", $form_data);
    }


    // move uploaded files to server directory
    $uploads_dir = WP_CONTENT_DIR . '/uploads/cv-form/';
    move_uploaded_file($cv_tmpname, $uploads_dir . $cv_filename);
    move_uploaded_file($coverletter_tmpname, $uploads_dir . $coverletter_filename);

    if ($result) {
        // Success message
        echo "Form data submitted successfully!";
    } else {
        // Error message
        echo "Error submitting form data!";
    }
} else {
    echo "Invalid request method!";
}
