<?php
session_start();
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// เริ่ม session ใหม่เพื่อส่ง flash message แจ้งเตือน
session_start();
require_once 'config/db.php';
set_flash('success', 'ออกจากระบบเรียบร้อยแล้ว');
header("Location: login.php");
exit();
?>