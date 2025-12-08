<?php
$api_url = 'http://127.0.0.1/buzzjuice.net/streams/api/auth';
$username = 'drenkaby';
$password = 'cupidblack';
$server_key = 'd2c99a2e27e91439e54bdfc48c143119';

// Initialize cURL session
$ch = curl_init();

// Set cURL options for authentication request
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'username' => $username,
    'password' => $password,
    'server_key' => $server_key,
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the POST request for authentication
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Debug: Output the response and HTTP status code
echo "Authentication Response: $response\n";
echo "HTTP Status Code: $httpcode\n";

// Decode the JSON response
$response_data = json_decode($response, true);

// Check if authentication was successful
if (isset($response_data['api_status']) && $response_data['api_status'] == 200) {
    // Authentication was successful, now handle the redirection to the news feed
    $access_token = $response_data['access_token'];

    // Initialize cURL session for redirection
    $ch = curl_init();

    // Set cURL options for redirection
    curl_setopt($ch, CURLOPT_URL, $api_url . '?action=redirect&access_token=' . $access_token);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'server_key' => $server_key,
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in the output

    // Execute the POST request for redirection
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    curl_close($ch);

    // Debug: Output the response, HTTP status code, headers, and body
    echo "Redirection Response: $response\n";
    echo "HTTP Status Code: $httpcode\n";
    echo "Headers: $header\n";
    echo "Body: $body\n";

    // Check if the response contains the redirect to the news feed
    if (strpos($header, 'Location:') !== false) {
        echo "User successfully logged in and redirected to the news feed.";
    } else {
        echo "Failed to redirect to the news feed.";
    }
} else {
    // Authentication failed
    echo "Authentication failed: " . ($response_data['error_message'] ?? 'Unknown error');
}
?>