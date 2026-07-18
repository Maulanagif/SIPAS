<?php
/**
 * FILE: config/cloudinary.php
 * FUNGSI: Konfigurasi dan helper upload foto ke Cloudinary
 *
 * SETUP:
 * 1. Daftar di https://cloudinary.com (gratis)
 * 2. Dari Dashboard, ambil: Cloud name, API Key, API Secret
 * 3. Isi nilai di bawah (jangan commit API Secret ke repo publik)
 *
 * UPLOAD: Gunakan fungsi cloudinary_upload_foto($tmp_path) dari halaman yang
 *         sudah include config ini. Mengembalikan URL string atau false.
 */

if (!defined('CLOUDINARY_LOADED')) {
    define('CLOUDINARY_LOADED', true);

    // ========== ISI CREDENTIAL CLOUDINARY ANDA ==========
    // Dari CLOUDINARY_URL: cloudinary://api_key:api_secret@cloud_name
    define('CLOUDINARY_CLOUD_NAME', 'djxc8fjc2');
    define('CLOUDINARY_API_KEY', '682383555861162');
    define('CLOUDINARY_API_SECRET', 'vNdstGsPtYbiWDMccUBSD-t6KHM');
    // =====================================================
}

/**
 * Upload file gambar ke Cloudinary (signed upload).
 *
 * @param string $tmp_path Path file sementara (mis. $_FILES['foto']['tmp_name'])
 * @return string|false URL secure gambar jika berhasil, false jika gagal
 */
function cloudinary_upload_foto($tmp_path) {
    if (!is_uploaded_file($tmp_path) && !file_exists($tmp_path)) {
        return false;
    }
    $mime = mime_content_type($tmp_path);
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowed, true)) {
        return false;
    }
    $size = filesize($tmp_path);
    if ($size <= 0 || $size > 5 * 1024 * 1024) { // max 5MB
        return false;
    }

    $cloud_name = defined('CLOUDINARY_CLOUD_NAME') ? CLOUDINARY_CLOUD_NAME : '';
    $api_key    = defined('CLOUDINARY_API_KEY') ? CLOUDINARY_API_KEY : '';
    $api_secret = defined('CLOUDINARY_API_SECRET') ? CLOUDINARY_API_SECRET : '';
    if ($cloud_name === '' || $cloud_name === 'your_cloud_name' || $api_key === '' || $api_secret === '') {
        return false;
    }

    $timestamp = (string) time();
    // Signature: hanya parameter yang dikirim (kecuali file) - urut abjad, gabung dengan &, lalu sha1(string + api_secret)
    $params_to_sign = ['timestamp' => $timestamp];
    ksort($params_to_sign);
    $pairs = [];
    foreach ($params_to_sign as $k => $v) {
        $pairs[] = $k . '=' . $v;
    }
    $str = implode('&', $pairs);
    $signature = sha1($str . $api_secret);

    // Cloud name di URL biasanya lowercase
    $cloud_name_url = strtolower($cloud_name);
    $url = 'https://api.cloudinary.com/v1_1/' . $cloud_name_url . '/image/upload';
    $cfile = new CURLFile($tmp_path, $mime, 'foto.jpg');

    $post = [
        'file'      => $cfile,
        'api_key'   => $api_key,
        'timestamp' => $timestamp,
        'signature' => $signature,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_errno) {
        $GLOBALS['cloudinary_last_error'] = 'CURL: ' . $curl_error;
        return false;
    }
    if ($code !== 200 || !$res) {
        $data = json_decode($res, true);
        $msg = is_array($data) && isset($data['error']['message']) ? $data['error']['message'] : ($res ?: 'HTTP ' . $code);
        $GLOBALS['cloudinary_last_error'] = $msg;
        return false;
    }
    $data = json_decode($res, true);
    if (!isset($data['secure_url'])) {
        $GLOBALS['cloudinary_last_error'] = is_array($data) && isset($data['error']['message']) ? $data['error']['message'] : 'Respons tidak valid';
        return false;
    }
    return $data['secure_url'];
}
