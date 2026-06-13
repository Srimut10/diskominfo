<?php
/**
 * Proses gambar: simpan dengan kualitas tinggi, resize jika terlalu besar
 * Tidak menaikkan resolusi (upscale) tapi memastikan tidak ada kompresi berlebihan
 *
 * @param string $src_path  Path file sumber (tmp file dari upload)
 * @param string $dest_path Path tujuan penyimpanan
 * @param int    $max_w     Lebar maksimal (0 = tidak dibatasi)
 * @param int    $max_h     Tinggi maksimal (0 = tidak dibatasi)
 * @param int    $quality   Kualitas JPEG/PNG (0-100, default 92)
 * @return bool
 */
function process_image($src_path, $dest_path, $max_w = 1920, $max_h = 1080, $quality = 92) {
    if (!extension_loaded('gd')) {
        // GD tidak tersedia, simpan langsung tanpa proses
        return copy($src_path, $dest_path);
    }

    $info = @getimagesize($src_path);
    if (!$info) return copy($src_path, $dest_path);

    $mime = $info['mime'];
    $orig_w = $info[0];
    $orig_h = $info[1];

    // Buat image resource dari file sumber
    $src = null;
    switch ($mime) {
        case 'image/jpeg': $src = @imagecreatefromjpeg($src_path); break;
        case 'image/png':  $src = @imagecreatefrompng($src_path);  break;
        case 'image/webp': $src = @imagecreatefromwebp($src_path); break;
        case 'image/gif':  $src = @imagecreatefromgif($src_path);  break;
        default:
            // Format tidak didukung GD (misal JFIF diperlakukan sebagai JPEG)
            $src = @imagecreatefromjpeg($src_path);
    }

    if (!$src) return copy($src_path, $dest_path);

    // Hitung ukuran output (tidak pernah upscale)
    $new_w = $orig_w;
    $new_h = $orig_h;

    if ($max_w > 0 && $orig_w > $max_w) {
        $ratio  = $max_w / $orig_w;
        $new_w  = $max_w;
        $new_h  = (int)round($orig_h * $ratio);
    }
    if ($max_h > 0 && $new_h > $max_h) {
        $ratio  = $max_h / $new_h;
        $new_h  = $max_h;
        $new_w  = (int)round($new_w * $ratio);
    }

    // Buat canvas baru
    $dst = imagecreatetruecolor($new_w, $new_h);

    // Pertahankan transparansi untuk PNG
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transparent);
    }

    // Resize dengan kualitas tinggi (IMAGETYPE_BICUBIC-like via imagecopyresampled)
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);

    // Deteksi ekstensi output
    $ext = strtolower(pathinfo($dest_path, PATHINFO_EXTENSION));

    // Simpan dengan kualitas tinggi
    $ok = false;
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
        case 'jfif':
            $ok = imagejpeg($dst, $dest_path, $quality);
            break;
        case 'png':
            // PNG quality: 0-9 (9 = max compress, 0 = no compress)
            // Kita pakai 1 agar file kecil tapi tidak merusak kualitas visual
            $ok = imagepng($dst, $dest_path, 1);
            break;
        case 'webp':
            $ok = imagewebp($dst, $dest_path, $quality);
            break;
        default:
            $ok = imagejpeg($dst, $dest_path, $quality);
    }

    imagedestroy($src);
    imagedestroy($dst);

    return $ok;
}
?>
