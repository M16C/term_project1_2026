<?php
session_start();
require_once 'config/db.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($email) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
        mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashed_password);

        if (mysqli_stmt_execute($stmt)) {
            $success = "สมัครสมาชิกสำเร็จแล้ว! กรุณาเข้าสู่ระบบ";
        } else {
            $error = "ชื่อผู้ใช้หรืออีเมลนี้มีในระบบแล้ว";
        }
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="p-4 p-md-5 bg-white border" style="border-color: var(--brand-secondary) !important;">
                <h4 class="fw-bold mb-3 text-uppercase" style="letter-spacing: 1px;">CREATE AN ACCOUNT</h4>
                <p class="text-muted small mb-4">กรอกข้อมูลด้านล่างเพื่อลงทะเบียนเข้าใช้งาน</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-0 py-2 small"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success rounded-0 py-2 small"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="register.php">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">Username *</label>
                        <input type="text" name="username" class="form-control rounded-0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">Email *</label>
                        <input type="email" name="email" class="form-control rounded-0" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-uppercase">Password *</label>
                        <input type="password" name="password" class="form-control rounded-0" required>
                    </div>
                    <button type="submit" class="btn btn-brand-dark w-100">REGISTER</button>
                </form>
            </div>
        </div>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>