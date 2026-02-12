<?php
/**
 * OAuth Configuration
 * Google Sign-In Integration
 */

require_once __DIR__ . '/database.php';

class OAuthConfig {
    private static $settings = null;

    public static function getSettings() {
        if (self::$settings === null) {
            $db = getDB();
            $settings = $db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE category = 'oauth'");
            
            self::$settings = [];
            foreach ($settings as $setting) {
                self::$settings[$setting['setting_key']] = $setting['setting_value'];
            }
        }
        return self::$settings;
    }

    public static function isGoogleEnabled() {
        $settings = self::getSettings();
        return isset($settings['google_enabled']) && $settings['google_enabled'] === 'true';
    }

    public static function getGoogleClientId() {
        $settings = self::getSettings();
        return $settings['google_client_id'] ?? '';
    }

    public static function getGoogleClientSecret() {
        $settings = self::getSettings();
        return $settings['google_client_secret'] ?? '';
    }

    public static function getGoogleRedirectUri() {
        $settings = self::getSettings();
        if (!empty($settings['google_redirect_uri'])) {
            return $settings['google_redirect_uri'];
        }
        
        // Auto-generate redirect URI
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "$protocol://$host/nas-dashboard/api/oauth_callback.php";
    }

    public static function getGoogleAuthUrl() {
        if (!self::isGoogleEnabled()) {
            return null;
        }

        $client_id = self::getGoogleClientId();
        $redirect_uri = self::getGoogleRedirectUri();
        
        $params = [
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'access_type' => 'offline',
            'prompt' => 'select_account'
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    public static function exchangeCodeForToken($code) {
        $client_id = self::getGoogleClientId();
        $client_secret = self::getGoogleClientSecret();
        $redirect_uri = self::getGoogleRedirectUri();

        $data = [
            'code' => $code,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_POST, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public static function getUserInfo($access_token) {
        $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}
?>
