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

$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $http_host . "/clothing_store/";

// ตรวจสอบและเริ่ม session หากยังไม่ได้เริ่ม
function ensure_session_started() {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        @session_start();
    }
}

// Helper สำหรับดึง URL รูปภาพให้ถูกต้องทั้งแบบลิงก์ภายนอกและไฟล์ที่อัปโหลดในโฟลเดอร์ uploads
function get_image_url($image_path, $prefix = '') {
    if (empty($image_path)) {
        return 'https://via.placeholder.com/500x500?text=No+Image';
    }
    if (preg_match('/^https?:\/\//i', $image_path)) {
        return $image_path;
    }
    return $prefix . ltrim($image_path, '/');
}

// Helper สำหรับจัดรูปแบบราคาเงินบาท
function format_price($price) {
    return '฿' . number_format((float)$price, 2);
}

// Helper สร้าง CSRF Token
function csrf_token() {
    ensure_session_started();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Helper สร้าง input field ของ CSRF Token
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

// Helper ตรวจสอบ CSRF Token
function verify_csrf_token($token = null) {
    ensure_session_started();
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Helper ตั้งค่า Flash Message (แจ้งเตือนแบบครั้งเดียว)
function set_flash($type, $message) {
    ensure_session_started();
    $_SESSION['flash'][$type] = $message;
}

// Helper ดึง Flash Message มาแสดงและลบทันที
function get_flash($type) {
    ensure_session_started();
    if (isset($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    return null;
}

// Helper นับจำนวนสินค้าในตะกร้า
function get_cart_count() {
    ensure_session_started();
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += (int)($item['quantity'] ?? 0);
    }
    return $count;
}
?>