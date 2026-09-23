<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT id, fullname, username, email, phone, role FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// ดึงประวัติคำสั่งซื้อล่าสุดของผู้ใช้
$order_stmt = mysqli_prepare($conn, "SELECT id, total_amount, payment_method, status FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 5");
mysqli_stmt_bind_param($order_stmt, "i", $user_id);
mysqli_stmt_execute($order_stmt);
$orders_res = mysqli_stmt_get_result($order_stmt);

$flash_success = get_flash('success');
$flash_error = get_flash('error');

include 'includes/header.php';
?>

<div class="container my-5">
    <?php if ($flash_success): ?>
        <div class="alert alert-success rounded-0 py-2 small mb-4">
            <i class="fa-solid fa-circle-check me-1"></i> <?php echo htmlspecialchars($flash_success); ?>
        </div>
    <?php endif; ?>

    <?php if ($flash_error): ?>
        <div class="alert alert-danger rounded-0 py-2 small mb-4">
            <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($flash_error); ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- ข้อมูลโปรไฟล์ผู้ใช้ -->
        <div class="col-lg-5">
            <div class="bg-white p-4 p-md-5 border h-100" style="border-color: var(--brand-secondary) !important;">
                <h4 class="fw-bold mb-4 text-uppercase" style="letter-spacing: 1px;"><i class="fa-regular fa-user me-2"></i>MY PROFILE</h4>
                
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted small text-uppercase d-block">ชื่อ - นามสกุล</label>
                    <span class="fw-semibold fs-5"><?php echo htmlspecialchars($user['fullname'] ?: '-'); ?></span>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted small text-uppercase d-block">Username</label>
                    <span class="fw-semibold fs-5"><?php echo htmlspecialchars($user['username']); ?></span>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted small text-uppercase d-block">Email</label>
                    <span class="fw-semibold fs-5"><?php echo htmlspecialchars($user['email'] ?: '-'); ?></span>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted small text-uppercase d-block">เบอร์โทรศัพท์</label>
                    <span class="fw-semibold fs-5"><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></span>
                </div>

                <div class="mb-4 pb-3 border-bottom">
                    <label class="text-muted small text-uppercase d-block">สิทธิ์การใช้งาน (Role)</label>
                    <span class="badge bg-secondary"><?php echo htmlspecialchars(strtoupper($user['role'])); ?></span>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="edit_profile.php" class="btn btn-brand-dark"><i class="fa-solid fa-user-pen me-1"></i> แก้ไขข้อมูลส่วนตัว</a>
                    <a href="change_password.php" class="btn btn-outline-dark rounded-0"><i class="fa-solid fa-key me-1"></i> เปลี่ยนรหัสผ่าน</a>
                </div>
            </div>
        </div>

        <!-- ประวัติคำสั่งซื้อ -->
        <div class="col-lg-7">
            <div class="bg-white p-4 p-md-5 border h-100" style="border-color: var(--brand-secondary) !important;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold text-uppercase mb-0" style="letter-spacing: 1px;"><i class="fa-solid fa-bag-shopping me-2"></i>ประวัติคำสั่งซื้อ</h4>
                    <a href="cart.php" class="btn btn-sm btn-outline-dark rounded-0"><i class="fa-solid fa-cart-shopping me-1"></i> ตะกร้าของฉัน</a>
                </div>

                <?php if ($orders_res && mysqli_num_rows($orders_res) > 0): ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light small">
                                <tr>
                                    <th>รหัสคำสั่งซื้อ</th>
                                    <th>ยอดรวม</th>
                                    <th>การชำระเงิน</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($ord = mysqli_fetch_assoc($orders_res)): ?>
                                    <tr>
                                        <td class="fw-bold">#ORD-<?php echo str_pad($ord['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td class="fw-bold" style="color: var(--brand-primary);"><?php echo format_price($ord['total_amount']); ?></td>
                                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($ord['payment_method'] ?: 'โอนเงิน'); ?></span></td>
                                        <td>
                                            <?php if ($ord['status'] === 'Completed' || $ord['status'] === 'Paid'): ?>
                                                <span class="badge bg-success">ชำระแล้ว</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">รอดำเนินการ</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa-solid fa-receipt fs-1 mb-3 opacity-25"></i>
                        <p class="mb-3">คุณยังไม่มีประวัติคำสั่งซื้อ</p>
                        <a href="products.php" class="btn btn-sm btn-brand-dark">เลือกดูสินค้าและสั่งซื้อ</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>