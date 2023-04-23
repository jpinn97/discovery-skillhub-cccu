<?php
require_once(dirname(__FILE__) . '/../../../wp-load.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['username-6'];
    $password = $_POST['user_password-6'];
    try {
        // SOAP request parameters
        $url = 'https://discoverypark.evolutive.co.uk/services/wsForm.asmx';
        $soapAction = 'http://tempuri.org/LoginForm';
        $passcode = EVO_API_KEY;

        // SOAP request body
        $requestBody = <<<'XML'
        <?xml version="1.0" encoding="utf-8"?>
        <soap12:Envelope xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
            <soap12:Body>
                <LoginForm xmlns="http://tempuri.org/">
                    <sPasscode>{$passcode}</sPasscode>
                    <sEmailAddress>{$email}</sEmailAddress>
                    <sPassword>{$password}</sPassword>
                </LoginForm>
            </soap12:Body>
        </soap12:Envelope>
        XML;

        // Set SOAP headers
        $headers = array(
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: ' . $soapAction
        );

        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Execute the cURL request
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            throw new Exception('Error: ' . curl_error($ch));
        }

        // Close cURL session
        curl_close($ch);

        // Check the SOAP response to see if the login was successful
        $xml = simplexml_load_string($response);
        $xml->registerXPathNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
        $xml->registerXPathNamespace('tempuri', 'http://tempuri.org/');

        $result = $xml->xpath('//tempuri:LoginFormResponse/tempuri:LoginFormResult');

        if (isset($result[0]) && $result[0] == 'Success') {
            // Get the user object based on the email
            $user = get_user_by('email', $email);

            // If the user does not exist, create a new user in the WordPress database
            if (!$user) {
                // Use a random username or generate one based on the email address
                $username = 'user_' . wp_generate_password(6, false);

                // Create the new user
                $user_id = wp_create_user($username, $password, $email);

                // Check if the user was created successfully
                if (is_wp_error($user_id)) {
                    throw new Exception('Error: ' . $user_id->get_error_message());
                }

                // Set the user role
                $user = new WP_User($user_id);
                $user->set_role('um_tenant'); // Set the user role as needed
            }
            // If the user exists, set the auth cookie and log them in
            if ($user) {
                wp_set_auth_cookie($user->ID, true);
                wp_set_current_user($user->ID, $user->user_login);
                header("Location: registration-page.php");
                exit();
            }
        } else {
            $current_url = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            // If the SOAP request was not successful, redirect to the registration page
            header("Location: $current_url/register");
            exit();
        }
    } catch (Exception $e) {
        // Login failed (SOAP request failed or user creation failed)
        $error_message = $e->getMessage();
        echo $error_message;

        // Log the error message
        error_log("Error: " . $error_message);
    }
}
