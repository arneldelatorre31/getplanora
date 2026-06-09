<?php

session_start();

$provider = strtolower($_GET['provider'] ?? '');
$config = include 'vendorOAuthConfig.php';

if (!isset($config[$provider])) {
    die("Unsupported OAuth provider.");
}

$providerConfig = $config[$provider];

if ($providerConfig['client_id'] === '' || $providerConfig['client_secret'] === '') {
    die(ucfirst($provider) . " OAuth is not configured yet. Add your app credentials in php/vendorOAuthConfig.php.");
}

$state = bin2hex(random_bytes(16));
$_SESSION['vendor_oauth_state'] = $state;
$_SESSION['vendor_oauth_provider'] = $provider;

$params = [
    'client_id' => $providerConfig['client_id'],
    'redirect_uri' => $providerConfig['redirect_uri'],
    'response_type' => 'code',
    'scope' => $providerConfig['scope'],
    'state' => $state,
];

if ($provider === 'google') {
    $params['access_type'] = 'online';
    $params['prompt'] = 'select_account';
}

header("Location: " . $providerConfig['authorize_url'] . '?' . http_build_query($params));
exit();

?>
