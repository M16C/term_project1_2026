<?php
session_start();
require_once 'config/db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $stmt = mysqli_prepare($conn, "SELECT id, username, password, role FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: admin/manage_stock.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            }
        }
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
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
    <!-- LOGO ด้านบนฟอร์มตาม Wireframe -->
    <div class="mb-4">
        <h4 class="fw-bold tracking-wider" style="letter-spacing: 2px;">LOGO</h4>
        <hr style="border-color: var(--brand-secondary);">
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="login-box shadow-sm">
                <div class="row g-5">
                    
                    <!-- ฝั่งซ้าย: LOGIN -->
                    <div class="col-md-7 login-divider">
                        <h4 class="fw-bold mb-1" style="letter-spacing: 1px;">LOGIN</h4>
                        <p class="text-muted small mb-4">Login with username or email and password</p>

                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2 small border-0 rounded-0 mb-3">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="login.php">
                            <div class="mb-4">
                                <label class="form-label small text-uppercase fw-semibold">Email / Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control input-wireframe" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small text-uppercase fw-semibold">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control input-wireframe" required>
                            </div>
                            <div class="text-end mb-4">
                                <a href="#" class="text-muted small text-decoration-none">Forgot password</a>
                            </div>
                            <button type="submit" class="btn btn-brand-dark px-4">Submit</button>
                        </form>
                    </div>

                    <!-- ฝั่งขวา: Create an account -->
                    <div class="col-md-5 d-flex flex-column justify-content-start">
                        <h4 class="fw-bold mb-3" style="letter-spacing: 1px;">Create an account</h4>
                        <p class="text-muted small mb-4" style="line-height: 1.6;">
                            If you create an account, it takes less time to go through checkout and complete your orders. Register today for free!
                        </p>
                        <div>
                            <a href="#" class="btn btn-brand-dark">Create an account</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>