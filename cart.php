<?php
session_start();
require_once 'config/db.php';

// กำหนดโครงสร้างตะกร้าสินค้า
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// 1. เพิ่มสินค้าลงตะกร้า (Add to Cart)
if ($action === 'add') {
    $product_id = intval($_GET['id'] ?? ($_POST['id'] ?? 0));
    $qty = max(1, intval($_POST['quantity'] ?? 1));

    if ($product_id > 0) {
        $stmt = mysqli_prepare($conn, "SELECT id, name, price, stock, image FROM products WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($product) {
            if ($product['stock'] <= 0) {
                set_flash('error', 'ขออภัย สินค้า "' . $product['name'] . '" สินค้าหมดชั่วคราว');
            } else {
                $current_qty = $_SESSION['cart'][$product_id]['quantity'] ?? 0;
                $new_qty = min($product['stock'], $current_qty + $qty);

                $_SESSION['cart'][$product_id] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => (float)$product['price'],
                    'image' => $product['image'],
                    'stock' => (int)$product['stock'],
                    'quantity' => $new_qty
                ];

                set_flash('success', 'เพิ่ม "' . $product['name'] . '" ลงในตะกร้าเรียบร้อยแล้ว');
            }
        }
    }
    header("Location: cart.php");
    exit();
}

// 2. อัปเดตจำนวนสินค้า (Update Quantities)
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token()) {
        $quantities = $_POST['qty'] ?? [];
        foreach ($quantities as $id => $val) {
            $id = intval($id);
            $val = intval($val);
            if (isset($_SESSION['cart'][$id])) {
                if ($val <= 0) {
                    unset($_SESSION['cart'][$id]);
                } else {
                    $stock = $_SESSION['cart'][$id]['stock'];
                    $_SESSION['cart'][$id]['quantity'] = min($stock, $val);
                }
            }
        }
        set_flash('success', 'อัปเดตจำนวนสินค้าในตะกร้าเรียบร้อยแล้ว');
    }
    header("Location: cart.php");
    exit();
}

// 3. ลบสินค้าชิ้นเดียวออกจากตะกร้า (Remove Item)
if ($action === 'remove') {
    $product_id = intval($_GET['id'] ?? 0);
    if (isset($_SESSION['cart'][$product_id])) {
        $item_name = $_SESSION['cart'][$product_id]['name'];
        unset($_SESSION['cart'][$product_id]);
        set_flash('success', 'ลบ "' . $item_name . '" ออกจากตะกร้าแล้ว');
    }
    header("Location: cart.php");
    exit();
}

// 4. ล้างตะกร้าทั้งหมด (Clear Cart)
if ($action === 'clear') {
    $_SESSION['cart'] = [];
    set_flash('success', 'ล้างตะกร้าสินค้าเรียบร้อยแล้ว');
    header("Location: cart.php");
    exit();
}

// 5. สั่งซื้อสินค้าและบันทึกลง Database (Checkout)
if ($action === 'checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = 'cart.php';
        set_flash('error', 'กรุณาเข้าสู่ระบบก่อนทำการสั่งซื้อสินค้า');
        header("Location: login.php");
        exit();
    }

    if (!verify_csrf_token()) {
        set_flash('error', 'คำขอไม่ถูกต้อง (CSRF Token ไม่ถูกต้อง)');
        header("Location: cart.php");
        exit();
    }

    if (empty($_SESSION['cart'])) {
        set_flash('error', 'ไม่มีสินค้าในตะกร้า ไม่สามารถสั่งซื้อได้');
        header("Location: cart.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $payment_method = trim($_POST['payment_method'] ?? 'โอนเงินผ่านธนาคาร');
    
    // คำนวณยอดรวมสุทธิ
    $total_amount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += ($item['price'] * $item['quantity']);
    }

    // เริ่ม Transaction เพื่อความปลอดภัยของข้อมูลการเงินและสต็อก
    mysqli_begin_transaction($conn);

    try {
        // 1. บันทึกลงตาราง orders
        $stmt_order = mysqli_prepare($conn, "INSERT INTO orders (user_id, total_amount, payment_method, status) VALUES (?, ?, ?, 'Pending')");
        $total_int = (int)$total_amount;
        mysqli_stmt_bind_param($stmt_order, "iis", $user_id, $total_int, $payment_method);
        
        if (!mysqli_stmt_execute($stmt_order)) {
            throw new Exception("ไม่สามารถสร้างคำสั่งซื้อได้: " . mysqli_error($conn));
        }
        $order_id = mysqli_insert_id($conn);

        // 2. บันทึกแต่ละรายการลงตาราง order_items และตัดสต็อกใน products
        $stmt_item = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt_stock = mysqli_prepare($conn, "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

        foreach ($_SESSION['cart'] as $item) {
            $p_id = (int)$item['id'];
            $p_qty = (int)$item['quantity'];
            $p_price = (int)$item['price'];

            // บันทึก order_item
            mysqli_stmt_bind_param($stmt_item, "iiii", $order_id, $p_id, $p_qty, $p_price);
            if (!mysqli_stmt_execute($stmt_item)) {
                throw new Exception("ไม่สามารถบันทึกรายการสินค้าในคำสั่งซื้อได้");
            }

            // ตัดสต็อก
            mysqli_stmt_bind_param($stmt_stock, "iii", $p_qty, $p_id, $p_qty);
            mysqli_stmt_execute($stmt_stock);
        }

        // Commit transaction
        mysqli_commit($conn);

        // ล้างตะกร้า
        $_SESSION['cart'] = [];

        header("Location: cart.php?order_success=" . $order_id);
        exit();

    } catch (Exception $e) {
        mysqli_rollback($conn);
        set_flash('error', 'เกิดข้อผิดพลาดในการสั่งซื้อ: ' . $e->getMessage());
        header("Location: cart.php");
        exit();
    }
}

// เช็คว่ามีคำสั่งซื้อสำเร็จเพิ่งเสร็จสิ้นหรือไม่
$order_success_id = intval($_GET['order_success'] ?? 0);
$success_order = null;
if ($order_success_id > 0 && isset($_SESSION['user_id'])) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $order_success_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $success_order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

$flash_success = get_flash('success');
$flash_error = get_flash('error');

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-uppercase mb-0" style="letter-spacing: 1px;">
            <i class="fa-solid fa-cart-shopping me-2"></i>SHOPPING CART
        </h3>
        <a href="products.php" class="text-muted small text-decoration-none">
            <i class="fa-solid fa-arrow-left me-1"></i> เลือกซื้อสินค้าต่อ
        </a>
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

    <!-- หน้าต่างแสดงเมื่อสั่งซื้อสำเร็จ -->
    <?php if ($success_order): ?>
        <div class="bg-white p-5 border text-center my-4" style="border-color: var(--brand-secondary) !important;">
            <div class="text-success mb-3">
                <i class="fa-solid fa-circle-check display-3"></i>
            </div>
            <h3 class="fw-bold mb-2">สั่งซื้อสินค้าสำเร็จแล้ว!</h3>
            <p class="text-muted mb-4">
                รหัสคำสั่งซื้อของคุณคือ <strong>#ORD-<?php echo str_pad($success_order['id'], 5, '0', STR_PAD_LEFT); ?></strong><br>
                ยอดรวมสุทธิ: <strong class="text-dark fs-5"><?php echo format_price($success_order['total_amount']); ?></strong> | ช่องทางชำระเงิน: <strong><?php echo htmlspecialchars($success_order['payment_method']); ?></strong>
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="profile.php" class="btn btn-outline-dark rounded-0 px-4">ดูประวัติคำสั่งซื้อ</a>
                <a href="products.php" class="btn btn-brand-dark px-4">เลือกดูสินค้าต่อ</a>
            </div>
        </div>
    <?php elseif (!empty($_SESSION['cart'])): ?>
        <!-- แสดงรายการสินค้าในตะกร้า -->
        <div class="row g-4">
            <div class="col-lg-8">
                <form method="POST" action="cart.php?action=update">
                    <?php echo csrf_field(); ?>
                    <div class="table-responsive bg-white border" style="border-color: var(--brand-secondary) !important;">
                        <table class="table align-middle mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th class="ps-3">สินค้า</th>
                                    <th>ราคา</th>
                                    <th style="width: 140px;">จำนวน</th>
                                    <th>รวม</th>
                                    <th class="text-center">ลบ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $subtotal = 0;
                                    foreach ($_SESSION['cart'] as $id => $item):
                                        $item_total = $item['price'] * $item['quantity'];
                                        $subtotal += $item_total;
                                        $img_src = get_image_url($item['image']);
                                ?>
                                    <tr>
                                        <td class="ps-3 py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?php echo htmlspecialchars($img_src); ?>" class="border" style="width: 55px; height: 55px; object-fit: cover;">
                                                <div>
                                                    <h6 class="mb-0 fw-semibold"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    <span class="small text-muted">คงเหลือ: <?php echo $item['stock']; ?> ชิ้น</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-semibold"><?php echo format_price($item['price']); ?></td>
                                        <td>
                                            <input type="number" name="qty[<?php echo $id; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" class="form-control form-control-sm rounded-0 text-center" style="max-width: 80px;">
                                        </td>
                                        <td class="fw-bold" style="color: var(--brand-primary);"><?php echo format_price($item_total); ?></td>
                                        <td class="text-center">
                                            <a href="cart.php?action=remove&id=<?php echo $id; ?>" class="text-danger" title="ลบรายการนี้" onclick="return confirm('ต้องการลบสินค้านี้ออกจากตะกร้า?');">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
                            <a href="cart.php?action=clear" class="btn btn-sm btn-outline-danger rounded-0" onclick="return confirm('ยืนยันล้างตะกร้าสินค้าทั้งหมด?');">
                                <i class="fa-solid fa-trash me-1"></i> ล้างตะกร้า
                            </a>
                            <button type="submit" class="btn btn-sm btn-outline-dark rounded-0">
                                <i class="fa-solid fa-arrows-rotate me-1"></i> อัปเดตจำนวน
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- สรุปการสั่งซื้อ และ Checkout -->
            <div class="col-lg-4">
                <div class="bg-white p-4 border" style="border-color: var(--brand-secondary) !important;">
                    <h5 class="fw-bold text-uppercase mb-3 pb-2 border-bottom" style="letter-spacing: 1px;">สรุปคำสั่งซื้อ</h5>
                    
                    <div class="d-flex justify-content-between mb-2 small text-muted">
                        <span>จำนวนสินค้า</span>
                        <span><?php echo get_cart_count(); ?> ชิ้น</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small text-muted">
                        <span>ค่าจัดส่ง</span>
                        <span class="text-success fw-bold">ฟรี</span>
                    </div>

                    <hr style="border-color: var(--brand-secondary);">

                    <div class="d-flex justify-content-between mb-4">
                        <span class="fw-bold fs-5">ยอดรวมสุทธิ</span>
                        <span class="fw-bold fs-5" style="color: var(--brand-primary);"><?php echo format_price($subtotal); ?></span>
                    </div>

                    <!-- ฟอร์ม Checkout สั่งซื้อ -->
                    <form method="POST" action="cart.php?action=checkout">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-uppercase">ช่องทางการชำระเงิน <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select rounded-0" required>
                                <option value="โอนเงินผ่านธนาคาร">โอนเงินผ่านธนาคาร (Bank Transfer)</option>
                                <option value="เก็บเงินปลายทาง">เก็บเงินปลายทาง (Cash on Delivery)</option>
                                <option value="บัตรเครดิต/เดบิต">บัตรเครดิต / เดบิต</option>
                            </select>
                        </div>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button type="submit" class="btn btn-brand-dark w-100 py-3 fw-bold" style="letter-spacing: 1px;">
                                <i class="fa-solid fa-lock me-1"></i> CONFIRM ORDER (สั่งซื้อ)
                            </button>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-brand-dark w-100 py-3 fw-bold text-center d-block text-decoration-none" style="letter-spacing: 1px;">
                                <i class="fa-solid fa-right-to-bracket me-1"></i> เข้าสู่ระบบเพื่อสั่งซื้อ
                            </a>
                            <div class="text-center mt-2 small text-muted">ยังไม่มีบัญชี? <a href="register.php" class="text-dark">สมัครสมาชิก</a></div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- ตะกร้าว่างเปล่า -->
        <div class="bg-white p-5 border text-center my-4" style="border-color: var(--brand-secondary) !important;">
            <i class="fa-solid fa-cart-shopping fs-1 mb-3 opacity-25"></i>
            <h4 class="fw-bold mb-2">ตะกร้าสินค้าของคุณยังว่างอยู่</h4>
            <p class="text-muted small mb-4">คุณยังไม่ได้เพิ่มสินค้าใดๆ ลงในตะกร้าสินค้า</p>
            <a href="products.php" class="btn btn-brand-dark px-4 py-2">
                <i class="fa-solid fa-bag-shopping me-1"></i> ไปเลือกดูสินค้า (SHOP NOW)
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
