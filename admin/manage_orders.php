<?php
require_once 'auth_check.php';
require_once '../config/db.php';

// 1. จัดการอัปเดตสถานะคำสั่งซื้อ (Update Order Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!verify_csrf_token()) {
        set_flash('error', 'คำขอไม่ถูกต้อง (CSRF Token ไม่ถูกต้อง)');
        header("Location: manage_orders.php");
        exit();
    }

    $order_id = intval($_POST['order_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? '');
    $allowed_statuses = ['Pending', 'Paid', 'Shipped', 'Completed', 'Cancelled'];

    if ($order_id > 0 && in_array($new_status, $allowed_statuses)) {
        $stmt_up = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_up, "si", $new_status, $order_id);
        if (mysqli_stmt_execute($stmt_up)) {
            $status_th = ($new_status === 'Paid') ? 'ชำระเงินแล้ว (PAID)' : $new_status;
            set_flash('success', "อัปเดตสถานะคำสั่งซื้อ #ORD-" . str_pad($order_id, 5, '0', STR_PAD_LEFT) . " เป็น \"$status_th\" เรียบร้อยแล้ว");
        } else {
            set_flash('error', "เกิดข้อผิดพลาดในการอัปเดตสถานะ: " . mysqli_error($conn));
        }
    } else {
        set_flash('error', "ข้อมูลสถานะไม่ถูกต้อง");
    }

    // Post-Redirect-Get
    header("Location: manage_orders.php" . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit();
}

// 2. ตัวกรองสถานะและการค้นหา
$status_filter = trim($_GET['status'] ?? '');
$search_q = trim($_GET['q'] ?? '');

$sql = "
    SELECT o.*, u.fullname, u.username, u.email, u.phone 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE 1=1
";
$params = [];
$types = "";

if (!empty($status_filter)) {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search_q)) {
    $sql .= " AND (u.fullname LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR o.id = ?)";
    $q_wild = "%" . $search_q . "%";
    $search_id = intval(ltrim(str_ireplace('#ord-', '', $search_q), '0'));
    $params[] = $q_wild;
    $params[] = $q_wild;
    $params[] = $q_wild;
    $params[] = $search_id;
    $types .= "sssi";
}

$sql .= " ORDER BY o.id DESC";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);

// สถิติภาพรวมสำหรับ Dashboard Cards
$stat_total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders"))['c'] ?? 0;
$stat_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status = 'Pending'"))['c'] ?? 0;
$stat_completed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status = 'Completed' OR status = 'Paid'"))['c'] ?? 0;
$stat_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as s FROM orders WHERE status != 'Cancelled'"))['s'] ?? 0;

$flash_success = get_flash('success');
$flash_error = get_flash('error');

include '../includes/header.php';
?>

<div class="container my-5">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-uppercase">
                <i class="fa-solid fa-receipt me-2"></i>ADMIN ONLY - ORDER & PAYMENT MANAGEMENT
            </h3>
            <p class="text-muted small mb-0">ระบบตรวจสอบสลิปโอนเงิน (QR Code) และจัดการสถานะคำสั่งซื้อทั้งหมด</p>
        </div>
        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a href="manage_stock.php" class="btn btn-outline-dark btn-sm rounded-0">
                <i class="fa-solid fa-boxes-stacked me-1"></i> จัดการสต็อกสินค้า
            </a>
        </div>
    </div>

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

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="bg-white p-3 border rounded-0" style="border-color: var(--brand-secondary) !important;">
                <div class="text-muted small text-uppercase">คำสั่งซื้อทั้งหมด</div>
                <div class="fs-3 fw-bold"><?php echo number_format($stat_total_orders); ?></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="bg-white p-3 border rounded-0 border-warning" style="border-left: 4px solid #ffc107 !important;">
                <div class="text-muted small text-uppercase">รอตรวจสลิป (Pending)</div>
                <div class="fs-3 fw-bold text-warning"><?php echo number_format($stat_pending); ?></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="bg-white p-3 border rounded-0 border-success" style="border-left: 4px solid #198754 !important;">
                <div class="text-muted small text-uppercase">ชำระแล้ว (Paid / Done)</div>
                <div class="fs-3 fw-bold text-success"><?php echo number_format($stat_completed); ?></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="bg-white p-3 border rounded-0 border-primary" style="border-left: 4px solid var(--brand-dark) !important;">
                <div class="text-muted small text-uppercase">ยอดขายรวมสุทธิ</div>
                <div class="fs-3 fw-bold" style="color: var(--brand-primary);"><?php echo format_price($stat_revenue); ?></div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-3 border mb-4" style="border-color: var(--brand-secondary) !important;">
        <form method="GET" action="manage_orders.php" class="row g-2 align-items-center">
            <!-- Search -->
            <div class="col-md-5 col-sm-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light rounded-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="q" class="form-control rounded-0" placeholder="ค้นหาชื่อลูกค้า, อีเมล หรือเลขคำสั่งซื้อ..." value="<?php echo htmlspecialchars($search_q); ?>">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="col-md-4 col-sm-6">
                <select name="status" class="form-select form-select-sm rounded-0" onchange="this.form.submit()">
                    <option value="" <?php echo empty($status_filter) ? 'selected' : ''; ?>>-- ทุกสถานะ (All Statuses) --</option>
                    <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending (รอดำเนินการ / รอตรวจสลิป)</option>
                    <option value="Paid" <?php echo $status_filter === 'Paid' ? 'selected' : ''; ?>>Paid (ชำระเงินแล้ว)</option>
                    <option value="Shipped" <?php echo $status_filter === 'Shipped' ? 'selected' : ''; ?>>Shipped (จัดส่งแล้ว)</option>
                    <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed (สำเร็จสมบูรณ์)</option>
                    <option value="Cancelled" <?php echo $status_filter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled (ยกเลิก)</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="col-md-3 col-sm-12 d-flex gap-2">
                <button type="submit" class="btn btn-brand-dark btn-sm rounded-0 flex-grow-1">
                    <i class="fa-solid fa-filter me-1"></i> กรอง
                </button>
                <?php if (!empty($status_filter) || !empty($search_q)): ?>
                    <a href="manage_orders.php" class="btn btn-outline-secondary btn-sm rounded-0" title="ล้างตัวกรอง">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-white border table-responsive" style="border-color: var(--brand-secondary) !important;">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th>เลขที่</th>
                    <th>ลูกค้า</th>
                    <th>วันที่สั่งซื้อ</th>
                    <th>ยอดสุทธิ</th>
                    <th>สลิปโอนเงิน (Slip)</th>
                    <th>สถานะ</th>
                    <th class="text-center">ตรวจสอบ & ปรับสถานะ</th>
                    <th class="text-end">การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders_result && mysqli_num_rows($orders_result) > 0): ?>
                    <?php while ($ord = mysqli_fetch_assoc($orders_result)): ?>
                        <?php
                            $st = strtolower($ord['status'] ?? 'pending');
                            $badge_class = 'bg-secondary';
                            if ($st === 'pending') $badge_class = 'bg-warning text-dark';
                            elseif ($st === 'paid') $badge_class = 'bg-primary';
                            elseif ($st === 'shipped') $badge_class = 'bg-info text-dark';
                            elseif ($st === 'completed') $badge_class = 'bg-success';
                            elseif ($st === 'cancelled') $badge_class = 'bg-danger';

                            $has_slip = !empty($ord['slip_image']);
                            $slip_url = $has_slip ? '../' . ltrim($ord['slip_image'], '/') : '';
                        ?>
                        <tr>
                            <td>
                                <strong class="text-dark">#ORD-<?php echo str_pad($ord['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($ord['fullname'] ?: $ord['username']); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($ord['email']); ?></div>
                                <?php if (!empty($ord['phone'])): ?>
                                    <div class="small text-muted"><i class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($ord['phone']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted">
                                <?php echo !empty($ord['created_at']) ? date('d/m/Y H:i', strtotime($ord['created_at'])) : '-'; ?>
                            </td>
                            <td class="fw-bold" style="color: var(--brand-primary);">
                                <?php echo format_price($ord['total_amount']); ?>
                            </td>
                            <td>
                                <?php if ($has_slip): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-0 btn-view-slip py-1 px-2"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewSlipModal"
                                            data-slip-url="<?php echo htmlspecialchars($slip_url); ?>"
                                            data-order-id="<?php echo $ord['id']; ?>"
                                            data-customer="<?php echo htmlspecialchars($ord['fullname'] ?: $ord['username']); ?>"
                                            data-amount="<?php echo format_price($ord['total_amount']); ?>"
                                            data-status="<?php echo htmlspecialchars($ord['status']); ?>">
                                        <i class="fa-solid fa-receipt me-1"></i> ตรวจสลิป
                                    </button>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border small">ไม่มีสลิป</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $badge_class; ?> rounded-0 px-2 py-1">
                                    <?php echo htmlspecialchars(strtoupper($ord['status'] ?? 'PENDING')); ?>
                                </span>
                            </td>
                            <td style="min-width: 220px;">
                                <div class="d-flex flex-column gap-1">
                                    <!-- ปุ่มยืนยันชำระเงินด่วน เมื่อสถานะยังเป็น Pending -->
                                    <?php if ($ord['status'] === 'Pending'): ?>
                                        <form method="POST" action="manage_orders.php" onsubmit="return confirm('ยืนยันว่าตรวจสอบสลิปแล้ว และต้องการเปลี่ยนสถานะเป็น PAID?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                            <input type="hidden" name="status" value="Paid">
                                            <button type="submit" class="btn btn-success btn-sm rounded-0 w-100 py-1" style="font-size: 0.78rem;">
                                                <i class="fa-solid fa-circle-check me-1"></i> อนุมัติสลิป (Set Paid)
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Form ปรับสถานะอื่นๆ -->
                                    <form method="POST" action="manage_orders.php" class="d-flex gap-1 align-items-center">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                        <select name="status" class="form-select form-select-sm rounded-0" style="font-size: 0.78rem;">
                                            <option value="Pending" <?php echo $ord['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Paid" <?php echo $ord['status'] === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                            <option value="Shipped" <?php echo $ord['status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="Completed" <?php echo $ord['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="Cancelled" <?php echo $ord['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" class="btn btn-outline-dark btn-sm rounded-0" title="บันทึกสถานะ">
                                            <i class="fa-solid fa-floppy-disk"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                            <td class="text-end">
                                <a href="../order_detail.php?id=<?php echo $ord['id']; ?>" class="btn btn-outline-dark btn-sm rounded-0" title="ดูรายละเอียดใบเสร็จและรายการสินค้า">
                                    <i class="fa-solid fa-file-invoice me-1"></i> ใบเสร็จ
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-inbox fs-1 mb-3 opacity-25"></i>
                            <p class="mb-0">ไม่พบคำสั่งซื้อที่ตรงกับเงื่อนไข</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal สำหรับดูรูปภาพสลิปโอนเงิน (Slip Preview Modal) -->
<div class="modal fade" id="viewSlipModal" tabindex="-1" aria-labelledby="viewSlipModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-0">
            <div class="modal-header bg-light rounded-0">
                <h5 class="modal-title fw-bold" id="viewSlipModalLabel">
                    <i class="fa-solid fa-receipt me-2"></i>ตรวจสอบสลิปการโอนเงิน
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <!-- รูปสลิป -->
                    <div class="col-md-6 text-center">
                        <div class="border p-2 bg-light d-flex align-items-center justify-content-center" style="min-height: 350px;">
                            <img id="modalSlipImage" src="" alt="สลิปการโอนเงิน" class="img-fluid border shadow-sm" style="max-height: 450px; object-fit: contain;">
                        </div>
                        <div class="mt-2">
                            <a id="modalSlipFullLink" href="" target="_blank" class="small text-decoration-none">
                                <i class="fa-solid fa-up-right-from-square me-1"></i> เปิดดูรูปภาพต้นฉบับเต็มขนาด
                            </a>
                        </div>
                    </div>

                    <!-- ข้อมูลคำสั่งซื้อ และปุ่มอนุมัติ -->
                    <div class="col-md-6 d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="fw-bold mb-3 border-bottom pb-2" id="modalOrderTitle">คำสั่งซื้อ</h5>
                            <div class="mb-2">
                                <label class="small text-muted text-uppercase d-block">ชื่อลูกค้า</label>
                                <span class="fw-semibold fs-6" id="modalCustomerName">-</span>
                            </div>
                            <div class="mb-2">
                                <label class="small text-muted text-uppercase d-block">ยอดเงินที่ต้องชำระ</label>
                                <span class="fw-bold fs-4" style="color: var(--brand-primary);" id="modalTotalAmount">-</span>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted text-uppercase d-block">สถานะปัจจุบัน</label>
                                <span class="badge bg-secondary rounded-0 px-2 py-1 fs-6" id="modalCurrentStatus">-</span>
                            </div>

                            <div class="alert alert-warning small rounded-0">
                                <i class="fa-solid fa-circle-exclamation me-1"></i> กรุณาตรวจสอบยอดเงิน วันและเวลาในสลิปให้ตรงกับคำสั่งซื้อก่อนกดอนุมัติ
                            </div>
                        </div>

                        <!-- Form อนุมัติสถานะเป็น Paid ทันทีใน Modal -->
                        <form method="POST" action="manage_orders.php" id="modalApproveForm" class="mt-3">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="order_id" id="modalOrderId" value="">
                            <input type="hidden" name="status" value="Paid">
                            <button type="submit" class="btn btn-success rounded-0 w-100 py-3 fw-bold fs-6">
                                <i class="fa-solid fa-circle-check me-2"></i> อนุมัติการชำระเงิน (Confirm Paid)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light rounded-0">
                <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const slipModal = document.getElementById('viewSlipModal');
    if (slipModal) {
        slipModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const slipUrl = button.getAttribute('data-slip-url') || '';
            const orderId = button.getAttribute('data-order-id') || '';
            const customer = button.getAttribute('data-customer') || '-';
            const amount = button.getAttribute('data-amount') || '-';
            const status = button.getAttribute('data-status') || '-';

            document.getElementById('modalSlipImage').src = slipUrl;
            document.getElementById('modalSlipFullLink').href = slipUrl;
            document.getElementById('modalOrderTitle').textContent = 'คำสั่งซื้อ #ORD-' + String(orderId).padStart(5, '0');
            document.getElementById('modalCustomerName').textContent = customer;
            document.getElementById('modalTotalAmount').textContent = amount;
            document.getElementById('modalCurrentStatus').textContent = status.toUpperCase();
            document.getElementById('modalOrderId').value = orderId;
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
