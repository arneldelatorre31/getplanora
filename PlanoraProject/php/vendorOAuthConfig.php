<?php

$baseUrl = 'http://localhost/PlanoraProject/php/vendorOAuthCallback.php';

return [
    'google' => [
        'client_id' => '',
        'client_secret' => '',
        'redirect_uri' => $baseUrl . '?provider=google',
        'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'user_url' => 'https://www.googleapis.com/oauth2/v2/userinfo',
        'scope' => 'openid email profile',
    ],
    'facebook' => [
        'client_id' => '',
        'client_secret' => '',
        'redirect_uri' => $baseUrl . '?provider=facebook',
        'authorize_url' => 'https://www.facebook.com/v19.0/dialog/oauth',
        'token_url' => 'https://graph.facebook.com/v19.0/oauth/access_token',
        'user_url' => 'https://graph.facebook.com/me?fields=id,name,email',
        'scope' => 'email,public_profile',
    ],
];

?>
