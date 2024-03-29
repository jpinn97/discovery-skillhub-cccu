<?php

require_once('/home/rhrrist/public_html/wp-load.php');


add_action('init', 'register_custom_menus');

function register_custom_menus()
{
    // test
    register_nav_menus(
        array(
            'logged-in-menu' => __('Logged-in Menu', 'twentytwentythree-child'),
            'visitor-menu' => __('Visitor Menu', 'twentytwentythree-child'),
        )
    );
}

add_shortcode('logged_in_out_menu', 'logged_in_out_menu_shortcode');

function logged_in_out_menu_shortcode($atts, $content = null)
{
    if (is_user_logged_in()) {
        $menu = 'logged-in-menu';
    } else {
        $menu = 'visitor-menu';
    }
    return wp_nav_menu(
        array(
            'menu' => $menu,
            'echo' => false
        )
    );
}

add_action('um_profile_header', 'um_profile_points', 10);

function um_profile_points()
{
    $user_id = get_current_user_id();
    // Get um_points for current user
    $um_points = get_user_meta($user_id, 'um_profile_points', true);

    // Calculate percentage of profile completeness
    $total_points = 100;
    $profile_completeness_percentage = round(($um_points / $total_points) * 100);

    // Output progress bar
    echo '<div class="um-profile-progress">';
    echo '<div class="um-profile-progress-bar" style="width: ' . $profile_completeness_percentage . '%;"></div>';
    echo '<div class="um-profile-progress-text">' . $profile_completeness_percentage . '% Complete . Fill in your profile to gain points!</div>';
    echo '</div>';
}

add_action('um_user_pre_updating_profile', 'check_profile', 10);

function check_profile()
{
    $um_points = 0;

    $user_id = get_current_user_id();

    $first_name = get_user_meta($user_id, 'first_name', true);
    if (!empty($first_name)) {
        $um_points += 10;
    }
    $last_name = get_user_meta($user_id, 'last_name', true);
    if (!empty($last_name)) {
        $um_points += 10;
    }
    $nickname = get_user_meta($user_id, 'nickname', true);
    if (!empty($nickname)) {
        $um_points += 10;
    }
    $user_bio = get_user_meta($user_id, 'description', true);
    if (!empty($user_bio)) {
        $um_points += 10;
    }
    $user_email = get_user_meta($user_id, 'user_email', true);
    if (!empty($user_email)) {
        $um_points += 10;
    }
    $university = get_user_meta($user_id, 'university', true);
    if (!empty($university)) {
        $um_points += 10;
    }
    $twitter = get_user_meta($user_id, 'twitter', true);
    if (!empty($twitter)) {
        $um_points += 10;
    }
    $linkedin = get_user_meta($user_id, 'linkedin', true);
    if (!empty($linkedin)) {
        $um_points += 10;
    }
    // Check if profile picture is set
    $profile_photo_path = WP_CONTENT_DIR . '/uploads/ultimatemember/' . $user_id . '/profile_photo.jpg';
    if (file_exists($profile_photo_path)) {
        $um_points += 10;
    }

    update_user_meta($user_id, 'um_profile_points', $um_points);
}


add_action('um_user_register', 'first_time_register', 10, 1);

function first_time_register()
{
    $user_id = get_current_user_id();
    $user_roles = get_userdata($user_id)->roles;
    // Check if the user has the "Member" role
    if (in_array('Member', $user_roles)) {
        // Add the um_profile_points meta tag with a value of 0
        add_user_meta($user_id, 'um_profile_points', 0);
    }
    // Check if the user has completed their profile
    check_profile();
}

function approved_tenant_list()
{
    global $wpdb;

    $query = "SELECT u.user_login
              FROM wpeb_users AS u
              INNER JOIN wpeb_usermeta AS um
              ON u.ID = um.user_id
              WHERE um.meta_key = 'wpeb_capabilities'
              AND um.meta_value LIKE '%um_tenant%'
              AND (SELECT meta_value FROM wpeb_usermeta WHERE user_id = u.ID AND meta_key = 'account_status') = 'approved'";

    $results = $wpdb->get_results($query);

    $approved_users = array();

    foreach ($results as $result) {
        $approved_users[] = $result->user_login;
    }

    return $approved_users;
}

function register_tenant()
{
    class evoEnquiry
    {

        const ROOT = 'NewDataSet';
        const ELEMENT = 'User';
        const HOST = 'http://discoverypark.evolutive.co.uk/services/wsForm.asmx?WSDL';

        private $passcode = '';
        private $soapParameters = array(
            'soap_version' => SOAP_1_2,
            'trace' => 1,
            'style' => SOAP_RPC,
            'use' => SOAP_ENCODED
        );

        public function __construct($passcode)
        {
            $this->passcode = $passcode;
        }

        public function addForm($data)
        {
            $response = $this->callSoapRequest(
                "AddFormXML",
                $this->getParameterList("sXML", $this->getXML($data))
            );
            var_dump($response);
        }

        private function callSoapRequest($method, $parameters)
        {
            if ($this->passcode != '' || $method) {
                $client = new SoapClient(evoEnquiry::HOST, $this->soapParameters);
                try {
                    $result = $client->__soapCall($method, $parameters);
                    $response = $client->__getLastResponse();
                } catch (SoapFault $fault) {
                    $response = $fault->faultstring;
                }
                echo '<div style="font-family:tahoma;font-size:13px;margin-bottom:30px;">';
                echo '<span style="font-weight:bold;" >' . $method . '</span>';
                echo '<br /><br />';
                echo '<span style="font-weight:bold;" >Parameters - </span>';
                var_dump($parameters);
                echo '<br /><br />';
                echo '<span style="font-weight:bold;" >Response - </span>';
                var_dump($response);
                echo '</div>';
                return $response;
            }
        }

        private function getXML($data)
        {
            $xmlString = '<' . evoEnquiry::ROOT . '>';
            $xmlString .= $this->buildItem($data);
            $xmlString .= '</' . evoEnquiry::ROOT . '>';
            return $xmlString;
        }

        private function buildItem($data)
        {
            $xmlString = '<' . evoEnquiry::ELEMENT . '>';
            foreach ($data as $field => $value)
                $xmlString .= '<' . $field . '>' . $value . '</' . $field . '>';
            $xmlString .= '</' . evoEnquiry::ELEMENT . '>';
            return $xmlString;
        }
        private function getParameterList($key, $value)
        {
            return array("Parameters" => array("sApiKey" => getenv('EVO_API_KEY'), $key => $value));
        }
    }

    $enquiry = array(
        'sCompanyName' => 'Test Company',
        'sTitle' => 'Test Title',
        'sFirstName' => 'Test First Name',
        'sSurname' => 'Test Surname',
        'sAddressBuilding' => 'Test Building',
        'sAddressSecondaryName' => '',
        'sAddressStreet' => 'Test Road',
        'sAddressDistrict' => 'Test District',
        'sAddressTown' => 'Test Town',
        'sAddressCounty' => 'Test County',
        'sAddressPostcode' => 'S35 2PG',
        'sTelephone' => '0114 2573645',
        'sEmail' => 'Test@Test.com',
        'lEnquiryType' => '0',
        'lCategoryIDs' => '99,102,958',
        'sComments'        => '',
        'bConsentMarketing' => 'true' // Marketing
    );

    $apiKey = EVO_API_KEY;

    $cEnquiry = new evoEnquiry($apiKey);
    $cEnquiry->addForm($enquiry);
}

add_action('wp_enqueue_scripts', 'enqueue_login_js');

function enqueue_login_js()
{
    if (is_page('login')) {
        wp_enqueue_script('login-script', get_template_directory_uri() . '/login.js', array('jquery'), '1.0', true);
    }
}

// Schedule an action if it's not already scheduled
if (!wp_next_scheduled('um_delete_inactive_users')) {
    wp_schedule_event(time(), 'daily', 'um_delete_inactive_users');
}

// Hook into the scheduled action
add_action('um_delete_inactive_users', 'um_delete_inactive_users_function');

// Function to delete inactive users
function um_delete_inactive_users_function()
{
    // Define your criteria for inactive users
    $inactive_days = 365; // Set the number of days to consider a user inactive

    // Calculate the cutoff date
    $cutoff_date = strtotime("-$inactive_days days");

    // Get all users
    $users = get_users();

    // Loop through users and delete inactive ones
    foreach ($users as $user) {
        $last_login = get_user_meta($user->ID, '_um_last_login', true);

        // If the user has never logged in or last logged in before the cutoff date, delete them
        if (empty($last_login) || $last_login < $cutoff_date) {
            wp_delete_user($user->ID);
        }
    }
}

function is_email_verified($user_id)
{
    // Get the account status for the given user ID
    $account_status = get_user_meta($user_id, 'account_status', true);

    // Check if the account status is awaiting email confirmation
    if ($account_status === 'awaiting_email_confirmation') {
        return false;
    }

    return true;
}

# Add redirect for unverified um_member
add_action('template_redirect', 'custom_um_member_email_verification_check');
function custom_um_member_email_verification_check()
{
    // Check if the user is logged in
    if (is_user_logged_in()) {
        // Get the current user ID
        $user_id = get_current_user_id();

        // Check if the user is an Ultimate Member (UM) member
        if (in_array('um_member', (array) get_userdata($user_id)->roles)) {

            // Get the email verification status
            $email_verification_status = is_email_verified($user_id);

            // If the email is not verified and the user is not on the email verification page, redirect the user
            if ($email_verification_status != 1 && !is_page('email-verification')) {
                // Replace the URL with the URL of the email verification page
                wp_redirect('/email-verification/');
                exit;
            }
        }
    }
}

# Ajax sends email verification request
add_action('wp_ajax_send_verification_email_um', 'custom_send_verification_email_um');
function custom_send_verification_email_um()
{
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $user_status = get_user_meta($user_id, 'account_status', true);

        if ($user_status === 'awaiting_email_confirmation') {
            $user_email = um_user('user_email');

            // Get the email template from Ultimate Member settings
            $email_template = UM()->options()->get('email_template');

            // Replace the necessary placeholders in the email template
            $email_content = um_convert_tags($email_template, array(
                'username' => um_user('display_name'),
                'login_url' => um_get_core_page('login'),
            ));

            // Set up the email headers
            $headers = array('Content-Type: text/html; charset=UTF-8');

            // Send the email using the WordPress wp_mail function
            $result = wp_mail($user_email, 'Verify Email', $email_content, $headers);

            // Log the result of the email sending
            error_log('Email sending result: ' . print_r($result, true));

            if ($result) {
                wp_send_json_success(array('message' => 'Verification email sent.'));
            } else {
                wp_send_json_error(array('message' => 'Email sending failed.'));
            }
        } else {
            wp_send_json_error(array('message' => 'User email already verified or not applicable.'));
        }
    } else {
        wp_send_json_error(array('message' => 'User not logged in.'));
    }
}

# Enqueue email-verify.js to email verification pagefunction enqueue_email_verify_script()
function enqueue_email_verify_script()
{
    // Check if the user is on the email-verification page
    if (is_page('email-verification')) {
        wp_enqueue_script(
            'email-verify',
            get_template_directory_uri() . '/email-verify.js',
            array('jquery'),
            '1.0',
            true
        );

        // Localize the script with the WordPress ajax URL
        wp_localize_script('email-verify', 'emailVerifyData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_email_verify_script');


// Add the profile_approval_status meta key for new members
add_action('user_register', 'custom_add_profile_approval_status', 10, 1);
function custom_add_profile_approval_status($user_id)
{
    // Get the user's role
    $user = get_userdata($user_id);

    // Check if the user has the "um_member" role
    if (in_array('um_member', $user->roles)) {
        // Add the 'profile_approval_status' meta key with the value 'Pending'
        update_user_meta($user_id, 'profile_approval_status', 'Pending');
    }
}

add_filter('manage_users_columns', 'custom_add_users_approval_status_column');
function custom_add_users_approval_status_column($columns)
{
    $columns['profile_approval_status'] = 'Profile Approval Status';
    return $columns;
}

add_action('manage_users_custom_column', 'custom_show_users_approval_status_column_content', 10, 3);
function custom_show_users_approval_status_column_content($value, $column_name, $user_id)
{
    if ($column_name == 'profile_approval_status') {
        $profile_approval_status = get_user_meta($user_id, 'profile_approval_status', true);
        if ($profile_approval_status) {
            return $profile_approval_status;
        } else {
            return 'N/A';
        }
    }
    return $value;
}

// Update the profile approval status when the user profile is updated
add_action('user_profile_update_errors', 'custom_update_profile_approval_status', 10, 3);

function custom_update_profile_approval_status($errors, $update, $user)
{
    var_dump($_REQUEST);
    // Check if the user has the "um_member" role and "profile_approval_status" field is present
    if (in_array('um_member', $user->roles) && isset($_POST['profile_approval_status'])) {
        $new_status = $_POST['profile_approval_status'];
        $old_status = get_user_meta($user->ID, 'profile_approval_status', true);
        if ($new_status != $old_status) {
            update_user_meta($user->ID, 'profile_approval_status', $new_status);
        }
    }
}

add_action('wp_ajax_update_um_account_status', 'update_um_account_status');
function update_um_account_status()
{
    if (isset($_POST['user_id']) && isset($_POST['account_status']) && current_user_can('edit_users')) {
        $user_id = intval($_POST['user_id']);
        $account_status = sanitize_text_field($_POST['account_status']);
        update_user_meta($user_id, 'account_status', $account_status);
        wp_send_json_success(array('message' => 'Account status updated.'));
    } else {
        wp_send_json_error(array('message' => 'Invalid request.'));
    }
}

add_filter('um_pre_shortcode_args_filter', 'custom_um_pre_shortcode_args', 10, 1);
function custom_um_pre_shortcode_args($args)
{
    // Check if the shortcode form_id is 7
    if (isset($args['form_id']) && intval($args['form_id']) === 7) {
        // Get the current user ID
        $user_id = get_current_user_id();

        // Get the user's profile_status
        $profile_status = get_user_meta($user_id, 'profile_status', true);

        // Check if the profile_status is not "Approved"
        if ($profile_status !== 'Approved') {
            // Prevent the shortcode from being executed by setting a non-existing template
            $args['template'] = 'no_profile';
        }
    }

    return $args;
}

add_action('um_profile_header', 'custom_profile_approval_status_form');

function custom_profile_approval_status_form()
{
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    $user_role = array_shift($user_roles);

    if ($user_role == 'administrator') {
?>
        <form id="profile_approval_status_form" action="<?php get_template_directory_uri() . '/profile_status_edit.php'; ?>" method="POST">>
            <input type="hidden" name="user_id" value="<?php um_profile_id(); ?>">
            <label for="profile_approval_status">Profile Approval Status:</label>
            <select name="profile_approval_status" id="profile_approval_status">
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
            </select>
            <input type="submit" value="Save Changes">
            <div id="profile_approval_status_message"></div>
        </form>
<?php
    }
}

add_action('um_submit_form_errors_hook_', 'um_custom_validate_postcode', 999, 1);
function um_custom_validate_postcode($args)
{
    $key = 'postcode'; // Replace 'postcode' with the actual key of your postcode field if different

    // Check if the postcode field exists
    if (!isset($args[$key])) {
        return;
    }

    // Check if postcode is on the list of valid Kent postcodes
    $valid_postcodes = array(
        'TN1', 'TN10', 'TN11', 'TN12', 'TN13', 'TN14', 'TN15', 'TN16', 'TN17', 'TN18', 'TN2', 'TN23', 'TN24', 'TN25', 'TN26', 'TN27', 'TN28', 'TN29', 'TN3', 'TN30', 'TN4', 'TN8', 'TN9', 'BR6', 'BR8', 'CT1', 'CT10', 'CT11', 'CT12', 'CT13', 'CT14', 'CT15', 'CT16', 'CT17', 'CT18', 'CT19', 'CT2', 'CT20', 'CT21', 'CT3', 'CT4', 'CT5', 'CT6', 'CT7', 'CT8', 'CT9', 'DA1', 'DA10', 'DA11', 'DA12', 'DA13', 'DA2', 'DA3', 'DA4', 'DA9', 'ME1', 'ME10', 'ME11', 'ME12', 'ME13', 'ME14', 'ME15', 'ME16', 'ME17', 'ME18', 'ME19', 'ME2', 'ME20', 'ME3', 'ME4', 'ME5', 'ME6', 'ME7', 'ME8', 'ME9'
    );
    // Extract the outward code from the full postcode
    $outward_code = strtoupper(preg_replace('/\s+/', '', $args[$key]));
    $outward_code = preg_replace('/[0-9][A-Z]{2}$/', '', $outward_code);

    // Validate if the postcode is from Kent
    if (!in_array($outward_code, $valid_postcodes)) {
        UM()->form()->add_error($key, __('Please enter a valid postcode in Kent.', 'ultimate-member'));
    }

    // Validate the postcode format
    if (!preg_match('/^[A-Z]{1,2}[0-9R][0-9A-Z]? [0-9][A-Z]{2}$/i', $args[$key])) {
        UM()->form()->add_error($key, __('Please enter a valid UK postcode.', 'ultimate-member'));
    }
}

add_action('wp_enqueue_scripts', 'enqueue_additional_resources');

function enqueue_additional_resources()
{
    wp_enqueue_style('bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css?ver=6.2');
    wp_enqueue_style('rajdhani', 'https://fonts.googleapis.com/css?family=Rajdhani%3A300%2C400%2C500%2C600%2C700&#038;display=swap&#038;ver=6.2');
    wp_enqueue_style('montserrat', 'https://fonts.googleapis.com/css?family=Montserrat%3A300%2C400%2C500%2C700&#038;display=swap&#038;ver=6.2');
    wp_enqueue_style('aos', 'https://discovery-park.co.uk/wp-content/themes/discoverypark/css/aos.css?ver=6.2');
    wp_enqueue_style('core', 'https://discovery-park.co.uk/wp-content/themes/discoverypark/style.css?ver=6.2');
    wp_enqueue_style('fontawesome', 'https://use.fontawesome.com/releases/v5.0.13/css/all.css?ver=6.2');
    wp_enqueue_style('owl', 'https://discovery-park.co.uk/wp-content/themes/discoverypark/css/owl.carousel.css?ver=6.2');
    wp_enqueue_style('owl_theme', 'https://discovery-park.co.uk/wp-content/themes/discoverypark/css/owl.theme.default.css?ver=6.2');
    wp_enqueue_style('themestyles', 'https://discovery-park.co.uk/wp-content/themes/discoverypark/css/style.css?ver=6.2');
    wp_enqueue_style('my-theme-style', get_stylesheet_uri());

    wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js');

    wp_enqueue_script('popper', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js');
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js');
    wp_enqueue_script('lottie', 'https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.6.4/lottie_svg.min.js?ver=6.2');
    wp_enqueue_script('waypoints', 'https://cdnjs.cloudflare.com/ajax/libs/waypoints/4.0.1/jquery.waypoints.js?ver=6.2');
    wp_enqueue_script('aos', 'https://discovery-park.co.uk/wp-content/themes/discoverypark/js/aos.js?ver=6.2');
    wp_enqueue_script('themefunctions', 'https://discovery-park.co.uk/wp-content/themes/discoverypark/js/theme_functions.js?ver=6.2');
    wp_enqueue_script('countup', 'https://discovery-park.co.uk/wp-content/themes/discoverypark/js/counterup2-1.0.1.min.js?ver=6.2');
    wp_enqueue_script('owl_js', 'https://discovery-park.co.uk/wp-content/themes/discoverypark/js/owl.carousel.js?ver=6.2');
    wp_enqueue_script('lax', 'https://cdn.jsdelivr.net/npm/lax.js?ver=6.2');

    if (is_page('skills-zone')) {
        wp_enqueue_script('jquery-validate', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js', array('jquery'), '1.19.5', true);
        wp_enqueue_script('jquery-validate-additionals', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/additional-methods.min.js', array('jquery'), '1.19.5', true);
        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
        wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
        wp_enqueue_script('cv-validation', get_template_directory_uri() . '/cv-validation.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('cv-post', get_template_directory_uri() . '/cv-post.js', array('jquery'), '1.0.0', true);
        // Pass the URL of the JSON file to your JavaScript
        wp_localize_script('cv-validation', 'information', array(
            'degreesJSON' => get_template_directory_uri() . '/degrees.json',
        ));
    }
}

add_action('wp_ajax_handle_cv_form_submission', 'handle_cv_form_submission');
add_action('wp_ajax_nopriv_handle_cv_form_submission', 'handle_cv_form_submission');

function handle_cv_form_submission()
{
    global $wpdb;

    // Get form data
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
        wp_send_json_success('Form data submitted successfully!');
    } else {
        wp_send_json_error('Error submitting form data!');
    }
    die();
}
