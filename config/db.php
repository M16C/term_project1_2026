<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "clothing_store";

$conn = mysqli_connect($host, $user, $password, $dbname);

if (!$conn) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// กำหนด Base URL แบบ Dynamic รองรับการย้ายโฟลเดอร์
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/clothing_store/";
?>