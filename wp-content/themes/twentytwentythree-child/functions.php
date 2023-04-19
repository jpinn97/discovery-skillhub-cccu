<?php
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
add_action('init', 'register_custom_menus');

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
add_shortcode('logged_in_out_menu', 'logged_in_out_menu_shortcode');

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

add_action('um_profile_header', 'um_profile_points', 10);

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

    if ($um_points > 0) {
        update_user_meta($user_id, 'um_profile_points', $um_points);
    }
}

add_action('um_profile_update', 'check_profile', 10);

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
add_action('um_user_register', 'first_time_register', 10, 1);

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
        'sComments'        => ''
    );

    $apiKey = getenv('EVO_API_KEY');

    $cEnquiry = new evoEnquiry($apiKey);
    $cEnquiry->addForm($enquiry);
}
