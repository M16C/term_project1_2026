<?php
// api/checkout.php - Checkout & PromptPay Slip Upload API
require_once __DIR__ . '/cors.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method Not Allowed'], 405);
}

// 1. Read JSON input body if present
$input_json = json_decode(file_get_contents('php://input'), true);
if (is_array($input_json)) {
    $_POST = array_merge($_POST, $input_json);
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
if ($user_id <= 0) {
    json_response(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อนทำการสั่งซื้อ'], 401);
}

// 2. Parse items
$items_raw = $_POST['items'] ?? '[]';
$items = is_string($items_raw) ? json_decode($items_raw, true) : $items_raw;

if (empty($items) || !is_array($items)) {
    json_response(['success' => false, 'message' => 'ไม่มีสินค้าในรายการสั่งซื้อ'], 400);
}

// 3. Process slip: Support both Base64 JSON and multipart $_FILES
$upload_dir = __DIR__ . '/../uploads/slips/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$slip_filename = null;
$target_path = null;

if (!empty($_POST['slip_base64'])) {
    // A) Process Base64 from mobile app (100% cross-platform reliable)
    $base64_str = $_POST['slip_base64'];
    // Strip metadata header if present (e.g. data:image/jpeg;base64,...)
    if (preg_match('#^data:image/(\w+);base64,#i', $base64_str, $matches)) {
        $ext = strtolower($matches[1]);
        $base64_str = substr($base64_str, strpos($base64_str, ',') + 1);
    } else {
        $ext = 'jpg';
    }

    $image_binary = base64_decode($base64_str);
    if ($image_binary === false || strlen($image_binary) < 10) {
        json_response(['success' => false, 'message' => 'ข้อมูลรูปภาพสลิป Base64 ไม่ถูกต้อง'], 400);
    }

    $slip_filename = 'slip_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $target_path = $upload_dir . $slip_filename;

    if (file_put_contents($target_path, $image_binary) === false) {
        json_response(['success' => false, 'message' => 'ไม่สามารถบันทึกไฟล์สลิปได้'], 500);
    }

} elseif (isset($_FILES['slip']) && $_FILES['slip']['error'] === UPLOAD_ERR_OK) {
    // B) Process traditional multipart file upload
    $file = $_FILES['slip'];
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_exts)) {
        json_response(['success' => false, 'message' => 'รูปแบบไฟล์สลิปไม่ถูกต้อง (รองรับเฉพาะ JPG, PNG, WEBP)'], 400);
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        json_response(['success' => false, 'message' => 'ขนาดไฟล์สลิปต้องไม่เกิน 10MB'], 400);
    }

    $slip_filename = 'slip_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $target_path = $upload_dir . $slip_filename;

    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        json_response(['success' => false, 'message' => 'ไม่สามารถบันทึกไฟล์สลิปได้'], 500);
    }
} else {
    json_response(['success' => false, 'message' => 'กรุณาแนบภาพสลิปหลักฐานการโอนเงิน (PromptPay)'], 400);
}

$slip_db_path = 'uploads/slips/' . $slip_filename;
$payment_method = 'PromptPay QR';
$status = 'Pending';

// 4. Begin atomic database transaction
$conn->begin_transaction();

try {
    // 4.1 Calculate total and verify stock
    $total_amount = 0;
    foreach ($items as $item) {
        $p_id = (int)($item['id'] ?? $item['product_id'] ?? 0);
        $qty  = (int)($item['quantity'] ?? $item['qty'] ?? 1);

        $chk_stmt = $conn->prepare("SELECT price, stock, name FROM products WHERE id = ? FOR UPDATE");
        $chk_stmt->bind_param("i", $p_id);
        $chk_stmt->execute();
        $prod = $chk_stmt->get_result()->fetch_assoc();

        if (!$prod) {
            throw new Exception("ไม่พบสินค้ารหัส #$p_id ในระบบ");
        }
        if ($prod['stock'] < $qty) {
            throw new Exception("สินค้า '{$prod['name']}' มีจำนวนไม่เพียงพอ (คงเหลือ {$prod['stock']} ชิ้น)");
        }

        $total_amount += (int)$prod['price'] * $qty;
    }

    // 4.2 Insert into orders table
    $order_stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, payment_method, status, slip_image) VALUES (?, ?, ?, ?, ?)");
    $order_stmt->bind_param("iisss", $user_id, $total_amount, $payment_method, $status, $slip_db_path);
    if (!$order_stmt->execute()) {
        throw new Exception("ไม่สามารถสร้างคำสั่งซื้อได้: " . $conn->error);
    }
    $order_id = $order_stmt->insert_id;

    // 4.3 Insert into order_items and decrement stock
    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stock_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($items as $item) {
        $p_id = (int)($item['id'] ?? $item['product_id'] ?? 0);
        $qty  = (int)($item['quantity'] ?? $item['qty'] ?? 1);

        // Fetch current price
        $p_query = $conn->query("SELECT price FROM products WHERE id = $p_id");
        $price = (int)$p_query->fetch_assoc()['price'];

        $item_stmt->bind_param("iiii", $order_id, $p_id, $qty, $price);
        $item_stmt->execute();

        $stock_stmt->bind_param("ii", $qty, $p_id);
        $stock_stmt->execute();
    }

    $conn->commit();

    json_response([
        'success' => true,
        'message' => 'สั่งซื้อสินค้าและแนบสลิปสำเร็จ รอดำเนินการตรวจสอบ',
        'order_id' => $order_id,
        'total_amount' => $total_amount,
        'status' => $status
    ], 201);

} catch (Exception $e) {
    $conn->rollback();
    // Delete uploaded slip if transaction fails
    if ($target_path && file_exists($target_path)) {
        @unlink($target_path);
    }
    json_response(['success' => false, 'message' => $e->getMessage()], 400);
}
