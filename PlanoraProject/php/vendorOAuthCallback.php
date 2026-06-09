<?php

session_start();

include 'connect.php';

$config = include 'vendorOAuthConfig.php';
$provider = strtolower($_GET['provider'] ?? '');
$state = $_GET['state'] ?? '';
$code = $_GET['code'] ?? '';

if (!isset($config[$provider])) {
    die("Unsupported OAuth provider.");
}

if ($state === '' || !hash_equals($_SESSION['vendor_oauth_state'] ?? '', $state)) {
    die("Invalid OAuth state.");
}

if ($code === '') {
    die("OAuth authorization was cancelled or failed.");
}

$providerConfig = $config[$provider];

function oauthPostJson($url, $params)
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        die("OAuth request failed: " . curl_error($ch));
    }

    curl_close($ch);
    return json_decode($response, true);
}

function oauthGetJson($url, $accessToken)
{
    $separator = str_contains($url, '?') ? '&' : '?';
    $ch = curl_init($url . $separator . 'access_token=' . urlencode($accessToken));

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        die("OAuth profile request failed: " . curl_error($ch));
    }

    curl_close($ch);
    return json_decode($response, true);
}

$token = oauthPostJson($providerConfig['token_url'], [
    'client_id' => $providerConfig['client_id'],
    'client_secret' => $providerConfig['client_secret'],
    'redirect_uri' => $providerConfig['redirect_uri'],
    'grant_type' => 'authorization_code',
    'code' => $code,
]);

if (empty($token['access_token'])) {
    die("OAuth token request failed.");
}

$profile = oauthGetJson($providerConfig['user_url'], $token['access_token']);
$email = trim($profile['email'] ?? '');
$name = trim($profile['name'] ?? ($profile['given_name'] ?? 'Planora Vendor'));

if ($email === '') {
    die("Your " . ucfirst($provider) . " account did not provide an email address.");
}

$stmt = $conn->prepare("SELECT * FROM vendors WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$isNewAccount = false;

if ($result->num_rows > 0) {
    $vendor = $result->fetch_assoc();
} else {
    $empty = '';
    $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO vendors (full_name, email, phone, business_name, address, password) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $email, $empty, $empty, $empty, $randomPassword);
    $stmt->execute();

    $vendor = [
        'id' => $stmt->insert_id,
        'full_name' => $name,
        'email' => $email,
    ];
    $isNewAccount = true;
}

$_SESSION['vendor_id'] = $vendor['id'];
$_SESSION['vendor_name'] = $vendor['full_name'];
$_SESSION['vendor_email'] = $vendor['email'];

unset($_SESSION['vendor_oauth_state'], $_SESSION['vendor_oauth_provider']);

if ($isNewAccount) {
    header("Location: ../vendor/index.php?new_account=1");
    exit();
}

header("Location: ../vendor/dashboard.php");
exit();

?>
