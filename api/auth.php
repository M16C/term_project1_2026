<?php
// api/auth.php - Authentication API (Login, Register, Edit Profile, Change Password)
require_once __DIR__ . '/cors.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Fallback to read JSON body if POST is JSON
$input_json = json_decode(file_get_contents('php://input'), true);
if (is_array($input_json)) {
    $_POST = array_merge($_POST, $input_json);
}
if (empty($action) && isset($_POST['action'])) {
    $action = $_POST['action'];
}

// 1. LOGIN
if ($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        json_response(['success' => false, 'message' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน'], 400);
    }

    $stmt = $conn->prepare("SELECT id, username, password, fullname, email, phone, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            unset($row['password']);
            json_response([
                'success' => true,
                'message' => 'เข้าสู่ระบบสำเร็จ',
                'user' => $row
            ]);
        }
    }

    json_response(['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'], 401);
}

// 2. REGISTER
if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');

    if (empty($username) || empty($password) || empty($fullname) || empty($email)) {
        json_response(['success' => false, 'message' => 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน'], 400);
    }

    // Check duplicate username or email
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        json_response(['success' => false, 'message' => 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว'], 409);
    }

    $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
    $role = 'user';
    $insert_stmt = $conn->prepare("INSERT INTO users (username, password, fullname, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
    $insert_stmt->bind_param("ssssss", $username, $hashed_pass, $fullname, $email, $phone, $role);

    if ($insert_stmt->execute()) {
        $new_user_id = $insert_stmt->insert_id;
        json_response([
            'success' => true,
            'message' => 'สมัครสมาชิกสำเร็จ',
            'user' => [
                'id' => $new_user_id,
                'username' => $username,
                'fullname' => $fullname,
                'email' => $email,
                'phone' => $phone,
                'role' => $role
            ]
        ], 201);
    } else {
        json_response(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'], 500);
    }
}

// 3. EDIT PROFILE
if ($action === 'update_profile') {
    $user_id  = (int)($_POST['user_id'] ?? 0);
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');

    if ($user_id <= 0 || empty($fullname) || empty($email)) {
        json_response(['success' => false, 'message' => 'กรุณากรอกชื่อ-นามสกุล และอีเมลให้ครบถ้วน'], 400);
    }

    // Check duplicate email for another user
    $chk_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $chk_email->bind_param("si", $email, $user_id);
    $chk_email->execute();
    if ($chk_email->get_result()->num_rows > 0) {
        json_response(['success' => false, 'message' => 'อีเมลนี้ถูกใช้งานโดยบัญชีอื่นแล้ว'], 409);
    }

    $upd = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ? WHERE id = ?");
    $upd->bind_param("sssi", $fullname, $email, $phone, $user_id);

    if ($upd->execute()) {
        // Fetch fresh user
        $u_stmt = $conn->prepare("SELECT id, username, fullname, email, phone, role FROM users WHERE id = ?");
        $u_stmt->bind_param("i", $user_id);
        $u_stmt->execute();
        $fresh_user = $u_stmt->get_result()->fetch_assoc();

        json_response([
            'success' => true,
            'message' => 'อัปเดตข้อมูลส่วนตัวสำเร็จ',
            'user' => $fresh_user
        ]);
    } else {
        json_response(['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลได้: ' . $conn->error], 500);
    }
}

// 4. CHANGE PASSWORD
if ($action === 'change_password') {
    $user_id      = (int)($_POST['user_id'] ?? 0);
    $old_password = trim($_POST['old_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');

    if ($user_id <= 0 || empty($old_password) || empty($new_password)) {
        json_response(['success' => false, 'message' => 'กรุณากรอกรหัสผ่านเดิมและรหัสผ่านใหม่'], 400);
    }

    if (strlen($new_password) < 4) {
        json_response(['success' => false, 'message' => 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 4 ตัวอักษร'], 400);
    }

    // Verify old password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        if (!password_verify($old_password, $row['password'])) {
            json_response(['success' => false, 'message' => 'รหัสผ่านเดิมไม่ถูกต้อง'], 400);
        }

        $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $upd_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd_stmt->bind_param("si", $new_hash, $user_id);

        if ($upd_stmt->execute()) {
            json_response(['success' => true, 'message' => 'เปลี่ยนรหัสผ่านสำเร็จเรียบร้อยแล้ว']);
        } else {
            json_response(['success' => false, 'message' => 'ไม่สามารถเปลี่ยนรหัสผ่านได้'], 500);
        }
    } else {
        json_response(['success' => false, 'message' => 'ไม่พบบัญชีผู้ใช้นี้'], 404);
    }
}

json_response(['success' => false, 'message' => 'Invalid action'], 400);
