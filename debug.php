<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h3>PHP Info</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";

echo "<h3>Test DB Connection</h3>";
// Coba koneksi
$host = 'sql311.byet.cluster.com'; // ganti jika beda
$user = 'ezyro_42203110';
$pass = ''; // isi password vPanel kamu
$db   = 'ezyro_42203110_diskompelatihan';

$conn = @new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo "DB Error: " . $conn->connect_error . "<br>";
} else {
    echo "DB Connection: OK<br>";
    $tables = $conn->query("SHOW TABLES");
    echo "Tables: ";
    while($r = $tables->fetch_array()) echo $r[0] . ", ";
}

echo "<h3>Test include header</h3>";
try {
    require_once 'includes/header.php';
    echo "Header OK";
} catch(Exception $e) {
    echo "Header Error: " . $e->getMessage();
}
?>
