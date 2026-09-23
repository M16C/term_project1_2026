<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$msg = "";
$error = "";

// 1. เพิ่มสินค้าใหม่ (Add Stock)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'men');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $image = trim($_POST['image'] ?? '');

    if (!empty($name)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO products (name, category, price, stock, image) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssdis", $name, $category, $price, $stock, $image);
        if (mysqli_stmt_execute($stmt)) {
            $msg = "เพิ่มสินค้าใหม่เข้าสต็อกเรียบร้อยแล้ว";
        } else {
            $error = "เกิดข้อผิดพลาดในการเพิ่มสินค้า";
        }
    } else {
        $error = "กรุณากรอกชื่อสินค้า";
    }
}

// 2. แก้ไขข้อมูลสินค้า (Update Stock)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'men');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $image = trim($_POST['image'] ?? '');

    if ($id > 0 && !empty($name)) {
        $stmt = mysqli_prepare($conn, "UPDATE products SET name = ?, category = ?, price = ?, stock = ?, image = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssdisi", $name, $category, $price, $stock, $image, $id);
        if (mysqli_stmt_execute($stmt)) {
            $msg = "อัปเดตข้อมูลสินค้า (ID: #$id) เรียบร้อยแล้ว";
        } else {
            $error = "เกิดข้อผิดพลาดในการอัปเดตสินค้า";
        }
    } else {
        $error = "ข้อมูลไม่ถูกต้อง";
    }
}

// 3. ลบสินค้า (Delete Stock)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $delete_id = intval($_POST['id'] ?? 0);
    if ($delete_id > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        if (mysqli_stmt_execute($stmt)) {
            $msg = "ลบสินค้า (ID: #$delete_id) ออกจากสต็อกเรียบร้อยแล้ว";
        } else {
            $error = "เกิดข้อผิดพลาดในการลบสินค้า";
        }
    }
}

$result = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
include '../includes/header.php';
?>

<div class="container my-5">
    <h3 class="fw-bold mb-4 text-uppercase"><i class="fa-solid fa-boxes-stacked me-2"></i>ADMIN ONLY - STOCK MANAGEMENT</h3>

    <?php if ($msg): ?>
        <div class="alert alert-success rounded-0 py-2"><i class="fa-solid fa-circle-check me-2"></i><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger rounded-0 py-2"><i class="fa-solid fa-circle-exclamation me-2"></i><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <!-- ฟอร์มเพิ่มสินค้า (Add Stock Form) -->
    <div id="add-stock-form" class="bg-white p-4 border mb-5" style="border-color: var(--brand-secondary) !important;">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-plus me-2 text-success"></i>ADD STOCK</h5>
        <form method="POST" action="manage_stock.php" class="row g-3">
            <input type="hidden" name="action" value="add">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">ชื่อสินค้า *</label>
                <input type="text" name="name" class="form-control rounded-0" placeholder="ระบุชื่อสินค้า" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Category</label>
                <select name="category" class="form-select rounded-0">
                    <option value="men">Men</option>
                    <option value="women">Women</option>
                    <option value="clearance">Clearance</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">ราคา (บาท) *</label>
                <input type="number" step="0.01" name="price" class="form-control rounded-0" placeholder="0.00" required>
            </div>
            <div class="col-md-1">
                <label class="form-label small fw-semibold">จำนวน *</label>
                <input type="number" name="stock" class="form-control rounded-0" placeholder="0" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-brand-dark w-100"><i class="fa-solid fa-floppy-disk me-1"></i> SAVE</button>
            </div>
        </form>
    </div>

    <!-- ตารางแสดงรายการสินค้าทั้งหมด (Stock List) -->
    <div id="stock-list" class="table-responsive bg-white border" style="border-color: var(--brand-secondary) !important;">
        <div class="p-3 bg-light border-bottom fw-bold text-uppercase d-flex align-items-center justify-content-between">
            <span><i class="fa-solid fa-list me-2"></i>Stock Inventory</span>
            <span class="badge bg-dark"><?php echo $result ? mysqli_num_rows($result) : 0; ?> รายการ</span>
        </div>
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">ID</th>
                    <th>รูปภาพ</th>
                    <th>ชื่อสินค้า</th>
                    <th>Category</th>
                    <th>ราคา</th>
                    <th>สต็อก</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <?php
                            $id = $row['id'];
                            $name = $row['name'] ?? '';
                            $cat = $row['category'] ?? '';
                            $price = $row['price'] ?? 0;
                            $stock = $row['stock'] ?? 0;
                            $img = $row['image'] ?? '';
                            $img_src = !empty($img) ? $img : 'https://via.placeholder.com/40';
                        ?>
                        <tr>
                            <td class="ps-3 fw-bold">#<?php echo $id; ?></td>
                            <td>
                                <img src="<?php echo htmlspecialchars($img_src, ENT_QUOTES, 'UTF-8'); ?>" class="border" style="width: 40px; height: 40px; object-fit: cover;">
                            </td>
                            <td class="fw-semibold"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($cat ?: '-', ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td class="fw-bold" style="color: var(--brand-primary);">฿<?php echo number_format((float)$price, 2); ?></td>
                            <td>
                                <?php if ($stock <= 5): ?>
                                    <span class="badge bg-danger text-white"><?php echo (int)$stock; ?> (เหลือน้อย)</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo (int)$stock; ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <!-- ปุ่ม Update Stock -->
                                <button type="button" class="btn btn-sm btn-outline-dark rounded-0 btn-edit me-1" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editStockModal"
                                        data-id="<?php echo $id; ?>"
                                        data-name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-category="<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-price="<?php echo $price; ?>"
                                        data-stock="<?php echo $stock; ?>"
                                        data-image="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fa-solid fa-pen-to-square me-1"></i>Update
                                </button>

                                <!-- ปุ่ม Delete Stock -->
                                <form method="POST" action="manage_stock.php" onsubmit="return confirm('ยืนยันลบสินค้านี้ออกจากระบบ?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-0">
                                        <i class="fa-solid fa-trash me-1"></i>Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">ไม่มีข้อมูลสินค้าในระบบ</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal แก้ไขสต็อก (Update Stock Modal) -->
<div class="modal fade" id="editStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-0 border-0">
            <div class="modal-header bg-dark text-white rounded-0 py-3">
                <h5 class="modal-title fw-bold text-uppercase"><i class="fa-solid fa-pen-to-square me-2"></i>Update Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="manage_stock.php">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">ชื่อสินค้า *</label>
                            <input type="text" name="name" id="edit_name" class="form-control rounded-0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Category</label>
                            <select name="category" id="edit_category" class="form-select rounded-0">
                                <option value="men">Men</option>
                                <option value="women">Women</option>
                                <option value="clearance">Clearance</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">ราคา (บาท) *</label>
                            <input type="number" step="0.01" name="price" id="edit_price" class="form-control rounded-0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">จำนวนในสต็อก *</label>
                            <input type="number" name="stock" id="edit_stock" class="form-control rounded-0" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">URL รูปภาพ</label>
                            <input type="text" name="image" id="edit_image" class="form-control rounded-0" placeholder="https://...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-0">
                    <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-brand-dark rounded-0"><i class="fa-solid fa-floppy-disk me-1"></i>บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>

<!-- JavaScript ส่งข้อมูลไปยัง Modal สำหรับแก้ไขสต็อก -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editStockModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('edit_id').value = button.getAttribute('data-id') || '';
            document.getElementById('edit_name').value = button.getAttribute('data-name') || '';
            document.getElementById('edit_category').value = button.getAttribute('data-category') || 'men';
            document.getElementById('edit_price').value = button.getAttribute('data-price') || '0';
            document.getElementById('edit_stock').value = button.getAttribute('data-stock') || '0';
            document.getElementById('edit_image').value = button.getAttribute('data-image') || '';
        });
    }
});
</script>
</body>
</html>