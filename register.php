<?php
session_start();
require_once 'config/db.php';

// หากล็อกอินอยู่แล้ว ให้ redirect
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = "คำขอไม่ถูกต้อง (CSRF Token ไม่ถูกต้อง) กรุณาลองใหม่อีกครั้ง";
    } else {
        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($fullname) || empty($username) || empty($email) || empty($password)) {
            $error = "กรุณากรอกข้อมูลในช่องที่มีเครื่องหมาย * ให้ครบถ้วน";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "รูปแบบอีเมลไม่ถูกต้อง";
        } elseif (strlen($password) < 6) {
            $error = "รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
        } elseif ($password !== $confirm_password) {
            $error = "รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน";
        } else {
            // ตรวจสอบว่ามี username หรือ email ซ้ำหรือไม่
            $stmt_check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? OR email = ?");
            mysqli_stmt_bind_param($stmt_check, "ss", $username, $email);
            mysqli_stmt_execute($stmt_check);
            $check_res = mysqli_stmt_get_result($stmt_check);

            if (mysqli_num_rows($check_res) > 0) {
                $error = "ชื่อผู้ใช้ (Username) หรืออีเมลนี้ มีผู้ใช้งานในระบบแล้ว";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "INSERT INTO users (fullname, username, email, phone, password, role) VALUES (?, ?, ?, ?, ?, 'user')");
                mysqli_stmt_bind_param($stmt, "sssss", $fullname, $username, $email, $phone, $hashed_password);

                if (mysqli_stmt_execute($stmt)) {
                    set_flash('success', 'สมัครสมาชิกสำเร็จเรียบร้อยแล้ว! กรุณาเข้าสู่ระบบ');
                    header("Location: login.php");
                    exit();
                } else {
                    $error = "เกิดข้อผิดพลาดในการลงทะเบียน: " . mysqli_error($conn);
                }
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="p-4 p-md-5 bg-white border" style="border-color: var(--brand-secondary) !important;">
                <h4 class="fw-bold mb-2 text-uppercase" style="letter-spacing: 1px;">CREATE AN ACCOUNT</h4>
                <p class="text-muted small mb-4">กรอกข้อมูลด้านล่างเพื่อลงทะเบียนเข้าสู่ระบบ Clothing Store</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-0 py-2 small mb-4">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php">
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">ชื่อ - นามสกุล (Full Name) <span class="text-danger">*</span></label>
                        <input type="text" name="fullname" class="form-control rounded-0" placeholder="สมชาย ใจดี" value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">ชื่อผู้ใช้ (Username) <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control rounded-0" placeholder="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">อีเมล (Email) <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control rounded-0" placeholder="user@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">เบอร์โทรศัพท์ (Phone)</label>
                        <input type="tel" name="phone" class="form-control rounded-0" placeholder="0812345678" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">รหัสผ่าน (Password) <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control rounded-0" placeholder="อย่างน้อย 6 ตัวอักษร" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-uppercase">ยืนยันรหัสผ่าน (Confirm Password) <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control rounded-0" placeholder="กรอกรหัสผ่านอีกครั้ง" required>
                    </div>

                    <button type="submit" class="btn btn-brand-dark w-100 py-2 mb-3">
                        <i class="fa-solid fa-user-plus me-1"></i> REGISTER
                    </button>

                    <div class="text-center small text-muted">
                        มีบัญชีผู้ใช้งานอยู่แล้ว? <a href="login.php" class="text-dark fw-bold text-decoration-none">เข้าสู่ระบบที่นี่</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>