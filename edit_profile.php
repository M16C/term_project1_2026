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
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($fullname) || empty($email)) {
            $error = "กรุณากรอกชื่อ-นามสกุล และอีเมล";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "รูปแบบอีเมลไม่ถูกต้อง";
        } else {
            // ตรวจสอบว่าอีเมลซ้ำกับผู้อื่นหรือไม่
            $stmt_check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
            mysqli_stmt_bind_param($stmt_check, "si", $email, $user_id);
            mysqli_stmt_execute($stmt_check);
            if (mysqli_num_rows(mysqli_stmt_get_result($stmt_check)) > 0) {
                $error = "อีเมลนี้มีผู้ใช้งานอื่นในระบบแล้ว";
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE users SET fullname = ?, email = ?, phone = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "sssi", $fullname, $email, $phone, $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    set_flash('success', 'อัปเดตข้อมูลส่วนตัวเรียบร้อยแล้ว');
                    header("Location: profile.php");
                    exit();
                } else {
                    $error = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . mysqli_error($conn);
                }
            }
        }
    }
}

// ดึงข้อมูลปัจจุบัน
$stmt = mysqli_prepare($conn, "SELECT fullname, username, email, phone FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="bg-white p-4 p-md-5 border" style="border-color: var(--brand-secondary) !important;">
                <h4 class="fw-bold mb-3 text-uppercase" style="letter-spacing: 1px;"><i class="fa-solid fa-user-pen me-2"></i>EDIT PROFILE</h4>
                <p class="text-muted small mb-4">แก้ไขข้อมูลส่วนตัวของคุณ</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-0 py-2 small mb-4">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="edit_profile.php">
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">Username</label>
                        <input type="text" class="form-control rounded-0 bg-light" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <div class="form-text small">ชื่อผู้ใช้ไม่สามารถเปลี่ยนแปลงได้</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">ชื่อ - นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" name="fullname" class="form-control rounded-0" value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-uppercase">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control rounded-0" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-uppercase">เบอร์โทรศัพท์</label>
                        <input type="tel" name="phone" class="form-control rounded-0" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>

                    <div class="d-flex gap-2">
                        <a href="profile.php" class="btn btn-outline-secondary rounded-0 px-4">ยกเลิก</a>
                        <button type="submit" class="btn btn-brand-dark px-4"><i class="fa-solid fa-floppy-disk me-1"></i> บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
