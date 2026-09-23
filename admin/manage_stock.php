<?php
require_once 'auth_check.php';
require_once '../config/db.php';

// ฟังก์ชันสำหรับจัดการอัปโหลดรูปภาพไปยังโฟลเดอร์ uploads ของโปรเจกต์
function handle_image_upload($file_key, $fallback_url = '', $old_image = '') {
    $upload_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES[$file_key]) && is_uploaded_file($_FILES[$file_key]['tmp_name'])) {
        $file = $_FILES[$file_key];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์ (Code: ' . $file['error'] . ')'];
        }

        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!in_array($file_ext, $allowed_exts)) {
            return ['error' => 'รองรับเฉพาะไฟล์รูปภาพ (JPG, PNG, WEBP, GIF) เท่านั้น'];
        }

        $image_info = @getimagesize($file['tmp_name']);
        if ($image_info === false) {
            return ['error' => 'ไฟล์ที่อัปโหลดไม่ใช่รูปภาพที่ถูกต้อง'];
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            return ['error' => 'ขนาดไฟล์ต้องไม่เกิน 5MB'];
        }

        $new_filename = 'prod_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $file_ext;
        $target_file = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            // ลบรูปภาพเก่าหากอยู่ใน uploads/
            if (!empty($old_image) && strpos($old_image, 'uploads/') === 0) {
                $old_file_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $old_image);
                if (file_exists($old_file_path)) {
                    @unlink($old_file_path);
                }
            }
            return ['path' => 'uploads/' . $new_filename];
        } else {
            return ['error' => 'ไม่สามารถบันทึกไฟล์ลงโฟลเดอร์ uploads ได้'];
        }
    }

    if (!empty($fallback_url)) {
        return ['path' => $fallback_url];
    }

    return ['path' => $old_image];
}

// 1. เพิ่มสินค้าใหม่ (Add Stock)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!verify_csrf_token()) {
        set_flash('error', 'คำขอไม่ถูกต้อง (CSRF Token ไม่ถูกต้อง)');
        header("Location: manage_stock.php");
        exit();
    }

    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'men');
    
    $subcategory = trim($_POST['subcategory'] ?? '');
    if ($subcategory === '__custom__') {
        $subcategory = trim($_POST['custom_subcategory'] ?? '');
    }

    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $image_url_input = trim($_POST['image'] ?? '');

    $upload_result = handle_image_upload('image_file', $image_url_input);
    if (isset($upload_result['error'])) {
        set_flash('error', $upload_result['error']);
    } else {
        $image = $upload_result['path'];
        $is_clearance = ($category === 'clearance') ? 1 : 0;
        $gender = ($category === 'women') ? 'women' : (($category === 'men') ? 'men' : 'unisex');
        if ($category === 'clearance' && in_array($subcategory, ['men', 'women'])) {
            $gender = $subcategory;
        }

        if (!empty($name)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO products (name, category, subcategory, gender, is_clearance, price, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssssidis", $name, $category, $subcategory, $gender, $is_clearance, $price, $stock, $image);
            if (mysqli_stmt_execute($stmt)) {
                set_flash('success', "เพิ่มสินค้า \"$name\" เข้าสต็อกเรียบร้อยแล้ว");
            } else {
                set_flash('error', "เกิดข้อผิดพลาดในการเพิ่มสินค้า: " . mysqli_error($conn));
            }
        } else {
            set_flash('error', "กรุณากรอกชื่อสินค้า");
        }
    }
    // Post-Redirect-Get pattern ป้องกัน resubmit
    header("Location: manage_stock.php");
    exit();
}

// 2. แก้ไขข้อมูลสินค้า (Update Stock)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    if (!verify_csrf_token()) {
        set_flash('error', 'คำขอไม่ถูกต้อง (CSRF Token ไม่ถูกต้อง)');
        header("Location: manage_stock.php");
        exit();
    }

    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'men');

    $subcategory = trim($_POST['subcategory'] ?? '');
    if ($subcategory === '__custom__') {
        $subcategory = trim($_POST['custom_subcategory'] ?? '');
    }

    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $image_url_input = trim($_POST['image'] ?? '');
    $old_image = trim($_POST['old_image'] ?? '');

    $upload_result = handle_image_upload('image_file', $image_url_input, $old_image);
    if (isset($upload_result['error'])) {
        set_flash('error', $upload_result['error']);
    } else {
        $image = $upload_result['path'];
        $is_clearance = ($category === 'clearance') ? 1 : 0;
        $gender = ($category === 'women') ? 'women' : (($category === 'men') ? 'men' : 'unisex');
        if ($category === 'clearance' && in_array($subcategory, ['men', 'women'])) {
            $gender = $subcategory;
        }

        if ($id > 0 && !empty($name)) {
            $stmt = mysqli_prepare($conn, "UPDATE products SET name = ?, category = ?, subcategory = ?, gender = ?, is_clearance = ?, price = ?, stock = ?, image = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssssidisi", $name, $category, $subcategory, $gender, $is_clearance, $price, $stock, $image, $id);
            if (mysqli_stmt_execute($stmt)) {
                set_flash('success', "อัปเดตข้อมูลสินค้า (ID: #$id) เรียบร้อยแล้ว");
            } else {
                set_flash('error', "เกิดข้อผิดพลาดในการอัปเดตสินค้า: " . mysqli_error($conn));
            }
        } else {
            set_flash('error', "ข้อมูลไม่ถูกต้อง");
        }
    }
    header("Location: manage_stock.php");
    exit();
}

// 3. ลบสินค้า (Delete Stock)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!verify_csrf_token()) {
        set_flash('error', 'คำขอไม่ถูกต้อง (CSRF Token ไม่ถูกต้อง)');
        header("Location: manage_stock.php");
        exit();
    }

    $delete_id = intval($_POST['id'] ?? 0);
    if ($delete_id > 0) {
        $stmt_img = mysqli_prepare($conn, "SELECT image FROM products WHERE id = ?");
        mysqli_stmt_bind_param($stmt_img, "i", $delete_id);
        mysqli_stmt_execute($stmt_img);
        $res_img = mysqli_stmt_get_result($stmt_img);
        if ($row_img = mysqli_fetch_assoc($res_img)) {
            $img_del = $row_img['image'];
            if (!empty($img_del) && strpos($img_del, 'uploads/') === 0) {
                $del_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $img_del);
                if (file_exists($del_path)) {
                    @unlink($del_path);
                }
            }
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        if (mysqli_stmt_execute($stmt)) {
            set_flash('success', "ลบสินค้า (ID: #$delete_id) ออกจากสต็อกเรียบร้อยแล้ว");
        } else {
            set_flash('error', "เกิดข้อผิดพลาดในการลบสินค้า: " . mysqli_error($conn));
        }
    }
    header("Location: manage_stock.php");
    exit();
}

$flash_success = get_flash('success');
$flash_error = get_flash('error');

$result = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
include '../includes/header.php';
?>

<div class="container my-5">
    <h3 class="fw-bold mb-4 text-uppercase"><i class="fa-solid fa-boxes-stacked me-2"></i>ADMIN ONLY - STOCK MANAGEMENT</h3>

    <?php if ($flash_success): ?>
        <div class="alert alert-success rounded-0 py-2 small mb-4">
            <i class="fa-solid fa-circle-check me-2"></i><?php echo htmlspecialchars($flash_success); ?>
        </div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
        <div class="alert alert-danger rounded-0 py-2 small mb-4">
            <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo htmlspecialchars($flash_error); ?>
        </div>
    <?php endif; ?>

    <!-- ฟอร์มเพิ่มสินค้า (Add Stock Form) -->
    <div id="add-stock-form" class="bg-white p-4 border mb-5" style="border-color: var(--brand-secondary) !important;">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-plus me-2 text-success"></i>ADD STOCK</h5>
        <form method="POST" action="manage_stock.php" enctype="multipart/form-data" class="row g-3">
            <input type="hidden" name="action" value="add">
            <?php echo csrf_field(); ?>
            
            <div class="col-md-4">
                <label class="form-label small fw-semibold">ชื่อสินค้า *</label>
                <input type="text" name="name" class="form-control rounded-0" placeholder="ระบุชื่อสินค้า" required>
            </div>
            
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Category *</label>
                <select name="category" id="add_category" class="form-select rounded-0" onchange="updateSubcategories('add')">
                    <option value="men">Men</option>
                    <option value="women">Women</option>
                    <option value="clearance">Clearance</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-semibold">Subcategory *</label>
                <select name="subcategory" id="add_subcategory" class="form-select rounded-0" onchange="checkCustomSubcategory('add')">
                </select>
                <input type="text" name="custom_subcategory" id="add_custom_subcategory" class="form-control rounded-0 mt-2 d-none" placeholder="ระบุชื่อหมวดหมู่ย่อยเอง...">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold">ราคา (บาท) *</label>
                <input type="number" step="0.01" name="price" class="form-control rounded-0" placeholder="0.00" required>
            </div>

            <div class="col-md-1">
                <label class="form-label small fw-semibold">จำนวน *</label>
                <input type="number" name="stock" class="form-control rounded-0" placeholder="0" required>
            </div>

            <!-- ส่วนอัปโหลดรูปภาพ -->
            <div class="col-md-5">
                <label class="form-label small fw-semibold"><i class="fa-solid fa-upload me-1"></i> อัปโหลดรูปภาพสินค้า (เก็บลง Folder uploads) *</label>
                <input type="file" name="image_file" id="add_image_file" class="form-control rounded-0" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewImage(this, 'add_preview')">
                <div class="form-text small">รองรับ JPG, PNG, WEBP, GIF (สูงสุด 5MB)</div>
            </div>

            <div class="col-md-5">
                <label class="form-label small fw-semibold"><i class="fa-solid fa-link me-1"></i> หรือระบุเป็น URL รูปภาพ</label>
                <input type="text" name="image" id="add_image_url" class="form-control rounded-0" placeholder="https://images.unsplash.com/...">
                <div class="form-text small">กรณีต้องการวางลิงก์รูปภาพภายนอก</div>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-brand-dark w-100 py-2"><i class="fa-solid fa-floppy-disk me-1"></i> SAVE</button>
            </div>

            <div class="col-12 mt-2">
                <img id="add_preview" class="d-none border p-1" style="max-height: 90px; max-width: 90px; object-fit: cover;" alt="Preview">
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
                    <th>Subcategory</th>
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
                            $subcat = $row['subcategory'] ?? '';
                            $price = $row['price'] ?? 0;
                            $stock = $row['stock'] ?? 0;
                            $img = $row['image'] ?? '';
                            $img_src = get_image_url($img, '../');

                            $subcat_display = str_replace('_', ' ', $subcat);
                            if ($subcat === 'shirts') $subcat_display = 'Shirts';
                            elseif ($subcat === 'bottoms') $subcat_display = 'Bottoms';
                            elseif ($subcat === 'sport_utility') $subcat_display = 'Sport Utility';
                            elseif ($subcat === 'innerwear_socks') $subcat_display = 'Innerwear & Socks';
                            elseif ($subcat === 'men') $subcat_display = 'Men';
                            elseif ($subcat === 'women') $subcat_display = 'Women';
                        ?>
                        <tr>
                            <td class="ps-3 fw-bold">#<?php echo $id; ?></td>
                            <td>
                                <img src="<?php echo htmlspecialchars($img_src, ENT_QUOTES, 'UTF-8'); ?>" class="border" style="width: 45px; height: 45px; object-fit: cover;">
                            </td>
                            <td class="fw-semibold"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars(ucfirst($cat) ?: '-', ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><span class="badge bg-secondary-subtle text-secondary-emphasis border"><?php echo htmlspecialchars($subcat_display ?: '-', ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td class="fw-bold" style="color: var(--brand-primary);"><?php echo format_price($price); ?></td>
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
                                        data-subcategory="<?php echo htmlspecialchars($subcat, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-price="<?php echo $price; ?>"
                                        data-stock="<?php echo $stock; ?>"
                                        data-image="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-image-src="<?php echo htmlspecialchars($img_src, ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fa-solid fa-pen-to-square me-1"></i>Update
                                </button>

                                <!-- ปุ่ม Delete Stock -->
                                <form method="POST" action="manage_stock.php" onsubmit="return confirm('ยืนยันลบสินค้านี้ออกจากระบบ?');" style="display:inline;">
                                    <?php echo csrf_field(); ?>
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
                    <tr><td colspan="8" class="text-center py-4 text-muted">ไม่มีข้อมูลสินค้าในระบบ</td></tr>
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
            <form method="POST" action="manage_stock.php" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="old_image" id="edit_old_image">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">ชื่อสินค้า *</label>
                            <input type="text" name="name" id="edit_name" class="form-control rounded-0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Category</label>
                            <select name="category" id="edit_category" class="form-select rounded-0" onchange="updateSubcategories('edit')">
                                <option value="men">Men</option>
                                <option value="women">Women</option>
                                <option value="clearance">Clearance</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Subcategory</label>
                            <select name="subcategory" id="edit_subcategory" class="form-select rounded-0" onchange="checkCustomSubcategory('edit')">
                            </select>
                            <input type="text" name="custom_subcategory" id="edit_custom_subcategory" class="form-control rounded-0 mt-2 d-none" placeholder="ระบุหมวดหมู่ย่อยเอง...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">ราคา (บาท) *</label>
                            <input type="number" step="0.01" name="price" id="edit_price" class="form-control rounded-0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">จำนวนในสต็อก *</label>
                            <input type="number" name="stock" id="edit_stock" class="form-control rounded-0" required>
                        </div>

                        <!-- แก้ไขรูปภาพ -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold"><i class="fa-solid fa-image me-1"></i> รูปภาพสินค้า</label>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <img id="edit_preview" src="" class="border p-1" style="width: 70px; height: 70px; object-fit: cover;" alt="Current Image">
                                <div class="flex-grow-1">
                                    <label class="small text-muted mb-1 d-block">เลือกไฟล์เพื่อเปลี่ยนรูปภาพใหม่ (เก็บลง Folder uploads):</label>
                                    <input type="file" name="image_file" id="edit_image_file" class="form-control rounded-0" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewImage(this, 'edit_preview')">
                                </div>
                            </div>
                            <label class="small text-muted mb-1 d-block">หรือเปลี่ยนเป็น URL รูปภาพ:</label>
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

<!-- JavaScript จัดการ Subcategory, Preview รูปภาพ และ Modal -->
<script>
const subcategoryOptions = {
    men: [
        { value: 'shirts', label: 'Shirts (เสื้อ)' },
        { value: 'bottoms', label: 'Bottoms (กางเกง)' },
        { value: 'sport_utility', label: 'Sport Utility wear (ชุดกีฬา)' },
        { value: 'innerwear_socks', label: 'Innerwear & Socks (ชุดชั้นในและถุงเท้า)' }
    ],
    women: [
        { value: 'shirts', label: 'Shirts (เสื้อ)' },
        { value: 'bottoms', label: 'Bottoms (กางเกง)' },
        { value: 'sport_utility', label: 'Sport Utility wear (ชุดกีฬา)' },
        { value: 'innerwear_socks', label: 'Innerwear & Socks (ชุดชั้นในและถุงเท้า)' }
    ],
    clearance: [
        { value: 'men', label: 'Men Clearance (สินค้าลดราคา ชาย)' },
        { value: 'women', label: 'Women Clearance (สินค้าลดราคา หญิง)' },
        { value: 'shirts', label: 'Shirts (เสื้อลดราคา)' },
        { value: 'bottoms', label: 'Bottoms (กางเกงลดราคา)' },
        { value: 'sport_utility', label: 'Sport Utility wear' },
        { value: 'innerwear_socks', label: 'Innerwear & Socks' }
    ]
};

function updateSubcategories(prefix, selectedValue = '') {
    const catSelect = document.getElementById(prefix + '_category');
    const subSelect = document.getElementById(prefix + '_subcategory');
    const customInput = document.getElementById(prefix + '_custom_subcategory');
    if (!catSelect || !subSelect) return;

    const category = catSelect.value;
    const options = subcategoryOptions[category] || subcategoryOptions['men'];

    subSelect.innerHTML = '';
    let found = false;

    options.forEach(opt => {
        const optionEl = document.createElement('option');
        optionEl.value = opt.value;
        optionEl.textContent = opt.label;
        if (opt.value === selectedValue) {
            optionEl.selected = true;
            found = true;
        }
        subSelect.appendChild(optionEl);
    });

    const otherOption = document.createElement('option');
    otherOption.value = '__custom__';
    otherOption.textContent = '✏️ ระบุเอง (Other / Custom)...';
    subSelect.appendChild(otherOption);

    if (selectedValue && !found) {
        otherOption.selected = true;
        if (customInput) {
            customInput.value = selectedValue;
            customInput.classList.remove('d-none');
            customInput.required = true;
        }
    } else {
        if (customInput) {
            customInput.value = '';
            customInput.classList.add('d-none');
            customInput.required = false;
        }
    }
}

function checkCustomSubcategory(prefix) {
    const subSelect = document.getElementById(prefix + '_subcategory');
    const customInput = document.getElementById(prefix + '_custom_subcategory');
    if (!subSelect || !customInput) return;

    if (subSelect.value === '__custom__') {
        customInput.classList.remove('d-none');
        customInput.required = true;
        customInput.focus();
    } else {
        customInput.classList.add('d-none');
        customInput.required = false;
    }
}

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    updateSubcategories('add');

    const editModal = document.getElementById('editStockModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const category = button.getAttribute('data-category') || 'men';
            const subcategory = button.getAttribute('data-subcategory') || '';
            const imgVal = button.getAttribute('data-image') || '';
            const imgSrc = button.getAttribute('data-image-src') || '';

            document.getElementById('edit_id').value = button.getAttribute('data-id') || '';
            document.getElementById('edit_name').value = button.getAttribute('data-name') || '';
            document.getElementById('edit_category').value = category;
            
            updateSubcategories('edit', subcategory);

            document.getElementById('edit_price').value = button.getAttribute('data-price') || '0';
            document.getElementById('edit_stock').value = button.getAttribute('data-stock') || '0';
            
            document.getElementById('edit_old_image').value = imgVal;
            document.getElementById('edit_image').value = (imgVal.startsWith('http://') || imgVal.startsWith('https://')) ? imgVal : '';
            document.getElementById('edit_image_file').value = '';
            
            const editPreview = document.getElementById('edit_preview');
            if (editPreview) {
                editPreview.src = imgSrc || 'https://via.placeholder.com/70x70?text=No+Image';
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>