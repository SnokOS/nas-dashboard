<?php
header('Content-Type: application/json');
require_once '../config/oauth.php';

echo json_encode([
    'google_enabled' => OAuthConfig::isGoogleEnabled(),
    'google_auth_url' => OAuthConfig::getGoogleAuthUrl()
]);
?>
