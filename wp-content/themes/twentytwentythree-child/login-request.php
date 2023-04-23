<?php
require_once(dirname(__FILE__) . '/../../../wp-load.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['user_email-6'];
    $password = $_POST['user_password-6'];

    // Get the user object based on the email
    $user = get_user_by('email', $email);

    // If the user exists in the WordPress database, set the auth cookie and log them in
    if ($user) {
        if (wp_check_password($password, $user->data->user_pass, $user->ID)) {
            wp_set_auth_cookie($user->ID, true);
            wp_set_current_user($user->ID, $user->user_login);
            wp_redirect(home_url('/user/'));
            exit();
        } else {
            // Redirect to the registration page
            wp_redirect(home_url('/register/'));
            exit();
        }
    } else {
        // The user does not exist in the WordPress database, proceed with the SOAP request
        try {
            // SOAP request parameters
            $url = 'https://discoverypark.evolutive.co.uk/services/wsForm.asmx';
            $soapAction = 'http://tempuri.org/LoginForm';
            $passcode = EVO_API_KEY;

            // SOAP request body
            $requestBody = <<<XML
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

            // Log the SOAP response
            error_log("SOAP Response: " . $response);

            // Check for errors
            if (curl_errno($ch)) {
                throw new Exception('Error: ' . curl_error($ch));
            }

            // Close cURL session
            curl_close($ch);

            // Load the SOAP response into a SimpleXML object
            $xml = simplexml_load_string($response);

            $profile_fields = array("sFirstname", "sSurname", "sEmail", "sPassword", "sUsername", "sTelephoneNo");
            $field_values = array();

            foreach ($profile_fields as $field) {
                $value = (string) $xml->xpath("//{$field}")[0] ?? null;
                $field_values[$field] = $value;
            }

            // Check if the <sEmail> and <sPassword> elements are present within the <User> element and if their values match the provided $email and $password
            if (strtolower($field_values['sEmail']) === strtolower($email) && $field_values['sPassword'] === $password) {

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

                // Set the auth cookie and log the user in
                wp_set_auth_cookie($user->ID, true);
                wp_set_current_user($user->ID, $user->user_login);
                wp_redirect(home_url('/user/'));
                exit();
            } else {
                // Redirect to the registration page
                wp_redirect(home_url('/register/'));
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
}
