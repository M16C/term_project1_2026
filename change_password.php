<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = "คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง";
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
        } elseif (strlen($new_password) < 6) {
            $error = "รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
        } elseif ($new_password !== $confirm_password) {
            $error = "รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน";
        } else {
            // ตรวจสอบรหัสผ่านเดิม
            $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($res);

            if (!$user || !password_verify($current_password, $user['password'])) {
                $error = "รหัสผ่านเดิมไม่ถูกต้อง";
            } else {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_up = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_up, "si", $new_hash, $user_id);

                if (mysqli_stmt_execute($stmt_up)) {
                    set_flash('success', 'เปลี่ยนรหัสผ่านสำเร็จเรียบร้อยแล้ว');
                    header("Location: profile.php");
                    exit();
                } else {
                    $error = "เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน: " . mysqli_error($conn);
                }
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="bg-white p-4 p-md-5 border" style="border-color: var(--brand-secondary) !important;">
                <h4 class="fw-bold mb-3 text-uppercase" style="letter-spacing: 1px;"><i class="fa-solid fa-key me-2"></i>CHANGE PASSWORD</h4>
                <p class="text-muted small mb-4">กำหนดรหัสผ่านใหม่เพื่อความปลอดภัยของบัญชีคุณ</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-0 py-2 small mb-4">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="change_password.php">
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">รหัสผ่านปัจจุบัน <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control rounded-0" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">รหัสผ่านใหม่ <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" class="form-control rounded-0" placeholder="อย่างน้อย 6 ตัวอักษร" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-uppercase">ยืนยันรหัสผ่านใหม่ <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control rounded-0" placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" required>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="profile.php" class="btn btn-outline-secondary rounded-0 px-4">ยกเลิก</a>
                        <button type="submit" class="btn btn-brand-dark px-4"><i class="fa-solid fa-floppy-disk me-1"></i> บันทึกรหัสผ่านใหม่</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
