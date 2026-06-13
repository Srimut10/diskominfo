<?php
/**
 * Generate QR Code menggunakan phpqrcode library (offline, no API)
 */
function generate_qr($data, $filename_prefix = 'qr') {
    $dir = __DIR__ . '/../uploads/qr/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $fname = $filename_prefix . '_' . time() . '_' . rand(100,999) . '.png';
    $path  = $dir . $fname;
    $web   = 'uploads/qr/' . $fname;

    $lib = __DIR__ . '/phpqrcode.php';
    if (file_exists($lib)) {
        // Gunakan phpqrcode library
        ob_start();
        require_once $lib;
        ob_end_clean();
        // QRcode::png($data, $outfile, $level, $size, $margin)
        QRcode::png($data, $path, QR_ECLEVEL_M, 8, 2);
        if (file_exists($path)) return $web;
    }

    // Fallback: Google Charts API
    $url = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($data) . '&choe=UTF-8';
    $img = @file_get_contents($url);
    if ($img !== false) {
        file_put_contents($path, $img);
        return $web;
    }

    return null;
}
