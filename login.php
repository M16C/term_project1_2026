<?php
session_start();
require_once 'config/db.php';

// หากล็อกอินอยู่แล้ว ให้ redirect ไปหน้าเหมาะสม
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin/manage_stock.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = "";
if (isset($_GET['error']) && $_GET['error'] === 'access_denied') {
    $error = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณาเข้าสู่ระบบด้วยบัญชีผู้ดูแลระบบ (Admin)";
}
$success = get_flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = "คำขอไม่ถูกต้อง (CSRF Token ไม่ถูกต้อง) กรุณาลองใหม่อีกครั้ง";
    } else {
        $login_input = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!empty($login_input) && !empty($password)) {
            // ค้นหาได้ทั้ง Username หรือ Email
            $stmt = mysqli_prepare($conn, "SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
            mysqli_stmt_bind_param($stmt, "ss", $login_input, $login_input);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($result)) {
                // ตรวจสอบรหัสผ่านที่เข้ารหัสด้วย password_verify อย่างปลอดภัย
                if (password_verify($password, $user['password'])) {
                    // ป้องกัน Session Fixation Attack
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    // ตรวจสอบว่ามีหน้ารอ redirect อยู่หรือไม่ (เช่น มาจากตะกร้าสินค้า)
                    $redirect_url = $_SESSION['redirect_after_login'] ?? ($user['role'] === 'admin' ? 'admin/manage_stock.php' : 'index.php');
                    unset($_SESSION['redirect_after_login']);

                    header("Location: " . $redirect_url);
                    exit();
                }
            }
            $error = "ชื่อผู้ใช้/อีเมล หรือรหัสผ่านไม่ถูกต้อง";
        } else {
            $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
        }
    }
}

include 'includes/header.php';
?>

<style>
    .login-box {
        border: 1px solid var(--brand-secondary);
        background-color: #FFFFFF;
        padding: 40px;
        margin-top: 30px;
        margin-bottom: 50px;
    }
    .input-wireframe {
        border: none;
        border-bottom: 1px solid #7A7270;
        border-radius: 0;
        padding-left: 0;
        background: transparent;
    }
    .input-wireframe:focus {
        box-shadow: none;
        border-bottom: 2px solid var(--brand-primary);
    }
    .login-divider {
        border-right: 1px solid var(--brand-secondary);
    }
    @media (max-width: 767px) {
        .login-divider {
            border-right: none;
            border-bottom: 1px solid var(--brand-secondary);
            padding-bottom: 30px;
            margin-bottom: 30px;
        }
    }
</style>

<div class="container my-5">
    <div class="mb-4">
        <h4 class="fw-bold tracking-wider" style="letter-spacing: 2px;">LOGIN</h4>
        <hr style="border-color: var(--brand-secondary);">
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="login-box shadow-sm">
                <div class="row g-5">
                    
                    <!-- ฝั่งซ้าย: LOGIN FORM -->
                    <div class="col-md-7 login-divider">
                        <h4 class="fw-bold mb-1" style="letter-spacing: 1px;">LOGIN</h4>
                        <p class="text-muted small mb-4">เข้าสู่ระบบด้วย Username หรือ Email และรหัสผ่าน</p>

                        <?php if ($success): ?>
                            <div class="alert alert-success py-2 small border-0 rounded-0 mb-3">
                                <i class="fa-solid fa-circle-check me-1"></i> <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2 small border-0 rounded-0 mb-3">
                                <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="login.php">
                            <?php echo csrf_field(); ?>
                            <div class="mb-4">
                                <label class="form-label small text-uppercase fw-semibold">Email / Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control input-wireframe" placeholder="ระบุ username หรือ email" required autofocus>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small text-uppercase fw-semibold">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control input-wireframe" placeholder="ระบุรหัสผ่าน" required>
                            </div>
                            <div class="text-end mb-4">
                                <a href="forgot_password.php" class="text-muted small text-decoration-none">Forgot password?</a>
                            </div>
                            <button type="submit" class="btn btn-brand-dark px-4 py-2"><i class="fa-solid fa-right-to-bracket me-1"></i> Submit</button>
                        </form>
                    </div>

                    <!-- ฝั่งขวา: Create an account -->
                    <div class="col-md-5 d-flex flex-column justify-content-start">
                        <h4 class="fw-bold mb-3" style="letter-spacing: 1px;">Create an account</h4>
                        <p class="text-muted small mb-4" style="line-height: 1.8;">
                            สร้างบัญชีใหม่เพื่อความสะดวกรวดเร็วในการสั่งซื้อ ติดตามสถานะคำสั่งซื้อ และรับสิทธิพิเศษก่อนใคร สมัครฟรีวันนี้!
                        </p>
                        <div>
                            <a href="register.php" class="btn btn-brand-dark"><i class="fa-solid fa-user-plus me-1"></i> Create an account</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>