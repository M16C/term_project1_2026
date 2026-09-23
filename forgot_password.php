<?php
session_start();
require_once 'config/db.php';

$msg = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = "คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง";
    } else {
        $email = trim($_POST['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "กรุณากรอกอีเมลที่ถูกต้อง";
        } else {
            // ตรวจสอบว่ามีอีเมลในระบบหรือไม่
            $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($res) > 0) {
                $msg = "ระบบได้ส่งลิงก์สำหรับรีเซ็ตรหัสผ่านไปยังอีเมล $email เรียบร้อยแล้ว (สำหรับระบบทดสอบ กรุณาติดต่อผู้ดูแลระบบเพื่อเปลี่ยนรหัสผ่าน)";
            } else {
                $error = "ไม่พบบัญชีผู้ใช้งานที่ผูกกับอีเมลนี้ในระบบ";
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
                <h4 class="fw-bold mb-2 text-uppercase" style="letter-spacing: 1px;"><i class="fa-solid fa-key me-2"></i>FORGOT PASSWORD</h4>
                <p class="text-muted small mb-4">ระบุอีเมลที่คุณใช้สมัครสมาชิก เพื่อรับคำแนะนำในการรีเซ็ตรหัสผ่าน</p>

                <?php if ($msg): ?>
                    <div class="alert alert-success rounded-0 py-2 small mb-4">
                        <i class="fa-solid fa-circle-check me-1"></i> <?php echo htmlspecialchars($msg); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-0 py-2 small mb-4">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="forgot_password.php">
                    <?php echo csrf_field(); ?>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-uppercase">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control rounded-0" placeholder="user@example.com" required autofocus>
                    </div>

                    <button type="submit" class="btn btn-brand-dark w-100 py-2 mb-3">
                        <i class="fa-solid fa-paper-plane me-1"></i> SEND RESET LINK
                    </button>

                    <div class="text-center small">
                        <a href="login.php" class="text-muted text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i> กลับไปยังหน้าเข้าสู่ระบบ</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
