<?php
// Daftar di https://www.google.com/recaptcha/admin/create
// Pilih reCAPTCHA v2 "I'm not a robot", domain: localhost
define('RECAPTCHA_SITE_KEY',   '6LcgA8IsAAAAAJudLF_fD_mOOnzyzSUuhe6r53B2');
define('RECAPTCHA_SECRET_KEY', '6LcgA8IsAAAAAOuGK7U4sldkCOawSQ6kihIktbGA');

function verify_recaptcha($response) {
    if (empty($response)) return false;
    $data = http_build_query([
        'secret'   => RECAPTCHA_SECRET_KEY,
        'response' => $response,
    ]);
    $opts = ['http' => ['method' => 'POST', 'header' => 'Content-type: application/x-www-form-urlencoded', 'content' => $data]];
    $result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, stream_context_create($opts));
    $json = json_decode($result, true);
    return !empty($json['success']);
}
