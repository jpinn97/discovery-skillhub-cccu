<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Debugging output
file_put_contents('debug.log', 'Script started' . PHP_EOL, FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debugging output
    file_put_contents('debug.log', 'Form data: ' . print_r($_POST, true) . PHP_EOL, FILE_APPEND);

    $email = $_POST['email'];
    $password = $_POST['password'];

    // SOAP request parameters
    $url = 'https://discoverypark.evolutive.co.uk/services/wsForm.asmx';
    $soapAction = 'http://tempuri.org/LoginForm';
    $passcode = 'R8t475pL';

    // SOAP request body
    $requestBody = <<<XML
    <?xml version="1.0" encoding="utf-8"?>
    <soap12:Envelope xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
        <soap12:Body>
            <LoginForm xmlns="http://tempuri.org/">
                <sPasscode>$passcode</sPasscode>
                <sEmailAddress>$email</sEmailAddress>
                <sPassword>$password</sPassword>
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
        echo 'Error: ' . curl_error($ch);
        exit();
    }

    // Close cURL session
    curl_close($ch);

    // Assuming that $response contains the XML string
    $xml = simplexml_load_string($response);

    // Check if the login was successful
    $success = (string) $xml->xpath("//bSuccess")[0] ?? null;
    if ($success == "true") {
        // If the login was successful, set the session variable and redirect to the dashboard
        $_SESSION['email'] = $email;
        $_SESSION['logged_in'] = true;

        header("Location: dashboard.php");
        exit();
    } else {
        // If the login failed, display an error message to the user
        $error_message = (string) $xml->xpath("//sMessage")[0] ?? "Invalid login credentials.";
        echo $error_message;
    }
}
