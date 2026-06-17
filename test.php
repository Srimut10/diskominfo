<?php
$project_root_real = str_replace('\\', '/', realpath(dirname(__FILE__)));
$doc_root_real = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$project_web_path = trim(str_replace($doc_root_real, '', $project_root_real), '/');
$base_url = $protocol . '://' . $host . ($project_web_path ? '/' . $project_web_path . '/' : '/');

echo "<pre>";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "project_root_real: " . $project_root_real . "\n";
echo "doc_root_real: " . $doc_root_real . "\n";
echo "project_web_path: " . $project_web_path . "\n";
echo "base_url: " . $base_url . "\n";
echo "CSS URL: " . $base_url . "assets/css/style.css\n";
echo "</pre>";
echo "<link rel='stylesheet' href='" . $base_url . "assets/css/style.css'>";
echo "<p style='color:red'>Jika teks ini merah, CSS tidak terbaca</p>";
echo "<p class='btn-primary'>Jika ini punya style, CSS terbaca</p>";
?>
