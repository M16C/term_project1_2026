<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    set_flash('error', 'กรุณาเข้าสู่ระบบเพื่อดูรายละเอียดคำสั่งซื้อ');
    header("Location: login.php");
    exit();
}

$order_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if ($order_id <= 0) {
    set_flash('error', 'ไม่พบหมายเลขคำสั่งซื้อที่ระบุ');
    header("Location: " . ($is_admin ? "admin/manage_orders.php" : "profile.php"));
    exit();
}

// ตรวจสอบสิทธิ์การเข้าถึงคำสั่งซื้อ
if ($is_admin) {
    $stmt = mysqli_prepare($conn, "SELECT o.*, u.fullname, u.username, u.email, u.phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    mysqli_stmt_bind_param($stmt, "i", $order_id);
} else {
    $stmt = mysqli_prepare($conn, "SELECT o.*, u.fullname, u.username, u.email, u.phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
}

mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    set_flash('error', 'ไม่พบข้อมูลคำสั่งซื้อ หรือคุณไม่มีสิทธิ์เข้าถึงคำสั่งซื้อนี้');
    header("Location: " . ($is_admin ? "admin/manage_orders.php" : "profile.php"));
    exit();
}

// ดึงรายการสินค้าในคำสั่งซื้อ (order_items)
$stmt_items = mysqli_prepare($conn, "
    SELECT oi.*, p.name AS product_name, p.image AS product_image, p.category, p.subcategory 
    FROM order_items oi 
    LEFT JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
mysqli_stmt_bind_param($stmt_items, "i", $order_id);
mysqli_stmt_execute($stmt_items);
$items_res = mysqli_stmt_get_result($stmt_items);

$flash_success = get_flash('success');
$flash_error = get_flash('error');

include 'includes/header.php';
?>

<style>
/* Print Stylesheet สำหรับการพิมพ์ใบเสร็จอย่างสวยงาม */
@media print {
    body {
        background-color: #FFFFFF !important;
        color: #000000 !important;
        font-size: 12pt;
    }
    .navbar, footer, .no-print, .toast-container {
        display: none !important;
    }
    .container {
        max-width: 100% !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .receipt-card {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .badge {
        border: 1px solid #000 !important;
        color: #000 !important;
        background: transparent !important;
    }
}
</style>

<div class="container my-5">
    <!-- Top Action Bar (No Print) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 no-print">
        <div>
            <a href="<?php echo $is_admin ? 'admin/manage_orders.php' : 'profile.php'; ?>" class="btn btn-outline-secondary btn-sm rounded-0">
                <i class="fa-solid fa-arrow-left me-1"></i> <?php echo $is_admin ? 'กลับหน้ารายการสั่งซื้อ (Admin)' : 'กลับหน้าโปรไฟล์ของฉัน'; ?>
            </a>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <button onclick="window.print()" class="btn btn-brand-dark btn-sm rounded-0">
                <i class="fa-solid fa-print me-1"></i> พิมพ์ใบเสร็จ (Print Receipt)
            </button>
        </div>
    </div>

    <?php if ($flash_success): ?>
        <div class="alert alert-success rounded-0 py-2 small mb-4 no-print">
            <i class="fa-solid fa-circle-check me-1"></i> <?php echo htmlspecialchars($flash_success); ?>
        </div>
    <?php endif; ?>

    <?php if ($flash_error): ?>
        <div class="alert alert-danger rounded-0 py-2 small mb-4 no-print">
            <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo htmlspecialchars($flash_error); ?>
        </div>
    <?php endif; ?>

    <!-- Receipt Card -->
    <div class="bg-white p-4 p-md-5 border receipt-card" style="border-color: var(--brand-secondary) !important;">
        <!-- Receipt Header -->
        <div class="row align-items-start border-bottom pb-4 mb-4">
            <div class="col-sm-6">
                <h3 class="fw-bold tracking-wider text-uppercase mb-1" style="letter-spacing: 2px;">CLOTHING STORE</h3>
                <p class="text-muted small mb-0">123 Fashion Street, Bangkok, Thailand</p>
                <p class="text-muted small mb-0">Tel: 02-123-4567 | Email: contact@clothingstore.com</p>
            </div>
            <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                <h4 class="fw-bold text-uppercase mb-1" style="letter-spacing: 1px;">ใบเสร็จรับเงิน / คำสั่งซื้อ</h4>
                <div class="text-muted small">เลขที่คำสั่งซื้อ: <strong class="text-dark">#ORD-<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong></div>
                <div class="text-muted small">วันที่สั่งซื้อ: <?php echo !empty($order['created_at']) ? date('d/m/Y H:i น.', strtotime($order['created_at'])) : date('d/m/Y'); ?></div>
                <div class="mt-2">
                    <?php
                        $st = strtolower($order['status'] ?? 'pending');
                        $badge_class = 'bg-secondary';
                        if ($st === 'pending') $badge_class = 'bg-warning text-dark';
                        elseif ($st === 'paid') $badge_class = 'bg-primary text-white';
                        elseif ($st === 'shipped') $badge_class = 'bg-info text-dark';
                        elseif ($st === 'completed') $badge_class = 'bg-success text-white';
                        elseif ($st === 'cancelled') $badge_class = 'bg-danger text-white';
                    ?>
                    สถานะ: <span class="badge <?php echo $badge_class; ?> rounded-0 px-3 py-1"><?php echo htmlspecialchars(strtoupper($order['status'] ?? 'PENDING')); ?></span>
                </div>
            </div>
        </div>

        <!-- Customer & Payment Info -->
        <div class="row g-4 mb-4 pb-4 border-bottom">
            <div class="col-md-6">
                <h6 class="fw-bold text-uppercase small text-muted mb-2">ข้อมูลลูกค้า (Customer Details)</h6>
                <div class="fw-semibold fs-6"><?php echo htmlspecialchars($order['fullname'] ?: $order['username']); ?></div>
                <div class="small text-muted"><i class="fa-regular fa-envelope me-1"></i> <?php echo htmlspecialchars($order['email']); ?></div>
                <?php if (!empty($order['phone'])): ?>
                    <div class="small text-muted"><i class="fa-solid fa-phone me-1"></i> <?php echo htmlspecialchars($order['phone']); ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-md-end">
                <h6 class="fw-bold text-uppercase small text-muted mb-2">ข้อมูลการชำระเงิน (Payment Info)</h6>
                <div class="small mb-1">ช่องทางชำระเงิน: <strong class="text-dark"><?php echo htmlspecialchars($order['payment_method'] ?? 'PromptPay QR Code'); ?></strong></div>
                <div class="small text-muted mb-2">การจัดส่ง: Standard Delivery (ส่งฟรี)</div>

                <?php if (!empty($order['slip_image'])): ?>
                    <div class="mt-2">
                        <span class="small text-muted d-block mb-1">หลักฐานสลิปการโอน:</span>
                        <a href="<?php echo htmlspecialchars($order['slip_image']); ?>" target="_blank" class="d-inline-block border p-1 bg-light">
                            <img src="<?php echo htmlspecialchars($order['slip_image']); ?>" alt="สลิปการโอน" style="max-height: 80px; max-width: 140px; object-fit: contain;">
                        </a>
                        <div class="small mt-1 no-print">
                            <a href="<?php echo htmlspecialchars($order['slip_image']); ?>" target="_blank" class="text-decoration-none">
                                <i class="fa-solid fa-up-right-from-square me-1"></i> เปิดดูสลิปขนาดเต็ม
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($is_admin && $order['status'] === 'Pending'): ?>
                    <div class="mt-3 no-print">
                        <form method="POST" action="admin/manage_orders.php" onsubmit="return confirm('ยืนยันว่าตรวจสอบสลิปแล้ว และต้องการเปลี่ยนสถานะเป็น PAID?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <input type="hidden" name="status" value="Paid">
                            <button type="submit" class="btn btn-success btn-sm rounded-0">
                                <i class="fa-solid fa-circle-check me-1"></i> อนุมัติสลิปนี้ (Set as Paid)
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($order['status'] === 'Pending'): ?>
            <div class="alert alert-warning rounded-0 py-2 small mb-4 no-print">
                <i class="fa-solid fa-clock me-1"></i> <strong>สถานะคำสั่งซื้อ:</strong> แนบสลิปเรียบร้อยแล้ว รอผู้ดูแลระบบ (Admin) ตรวจสอบยอดเงินและปรับสถานะเป็น Paid
            </div>
        <?php elseif ($order['status'] === 'Paid' || $order['status'] === 'Completed'): ?>
            <div class="alert alert-success rounded-0 py-2 small mb-4 no-print">
                <i class="fa-solid fa-circle-check me-1"></i> <strong>การชำระเงินสำเร็จ:</strong> ผู้ดูแลระบบตรวจสอบสลิปและยืนยันการรับชำระเงินเรียบร้อยแล้ว
            </div>
        <?php endif; ?>

        <!-- Ordered Items Table -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 50%;">รายการสินค้า</th>
                        <th class="text-center" style="width: 15%;">ราคาต่อหน่วย</th>
                        <th class="text-center" style="width: 15%;">จำนวน</th>
                        <th class="text-end" style="width: 15%;">รวมเป็นเงิน</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $counter = 1;
                        $calc_total = 0;
                        while ($item = mysqli_fetch_assoc($items_res)): 
                            $line_subtotal = $item['price'] * $item['quantity'];
                            $calc_total += $line_subtotal;
                    ?>
                        <tr>
                            <td class="text-center text-muted"><?php echo $counter++; ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if (!empty($item['product_image'])): ?>
                                        <img src="<?php echo htmlspecialchars(get_image_url($item['product_image']), ENT_QUOTES, 'UTF-8'); ?>" 
                                             alt="" style="width: 48px; height: 48px; object-fit: cover; background: #eee;">
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($item['product_name'] ?? 'สินค้า'); ?></div>
                                        <?php if (!empty($item['category'])): ?>
                                            <div class="small text-muted text-uppercase" style="font-size: 0.75rem;">
                                                <?php echo htmlspecialchars($item['category']); ?> &bull; <?php echo htmlspecialchars($item['subcategory']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center"><?php echo format_price($item['price']); ?></td>
                            <td class="text-center fw-semibold"><?php echo $item['quantity']; ?></td>
                            <td class="text-end fw-semibold"><?php echo format_price($line_subtotal); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end fw-semibold">ยอดรวมสินค้า (Subtotal):</td>
                        <td class="text-end"><?php echo format_price($calc_total); ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end fw-semibold">ค่าจัดส่ง (Shipping):</td>
                        <td class="text-end text-success">ฟรี (฿0.00)</td>
                    </tr>
                    <tr class="table-light">
                        <td colspan="4" class="text-end fw-bold fs-6">ยอดสุทธิทั้งสิ้น (Grand Total):</td>
                        <td class="text-end fw-bold fs-5 text-dark" style="color: var(--brand-primary) !important;">
                            <?php echo format_price($order['total_amount']); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Receipt Footer Note -->
        <div class="mt-4 pt-3 border-top text-center text-muted small">
            <p class="mb-1">ขอขอบคุณที่เลือกซื้อสินค้ากับเรา หากมีข้อสงสัยเกี่ยวกับคำสั่งซื้อ กรุณาติดต่อฝ่ายบริการลูกค้า</p>
            <p class="mb-0 text-muted" style="font-size: 0.75rem;">เอกสารนี้ออกโดยระบบอัตโนมัติของ Clothing Store</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
