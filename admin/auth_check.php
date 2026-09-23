<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่าได้ล็อกอินหรือยัง และ Role ต้องเป็น 'admin' เท่านั้น
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // ถ้าไม่ตรงเงื่อนไข ส่งกลับไปหน้า Login ทันที
    header("Location: ../login.php?error=access_denied");
    exit(); // หยุดการทำงานของสคริปต์ทันทีเพื่อความปลอดภัย
}
?>