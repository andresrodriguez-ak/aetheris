<?php
define('BASE_URL', '/aetheris/');
define('DEFAULT_CONTENT_IMG', 'uploads/defaults/default_content.webp');

if (!function_exists('avatarUrl')) {
    function avatarUrl($img) {
        if (empty($img)) return 'uploads/profiles/default.png';
        if (strpos($img, 'uploads/') === 0) return $img;
        return 'uploads/profiles/' . $img;
    }
}