<?php
// api/admin_stock.php - Admin Stock Management API
require_once __DIR__ . '/cors.php';

$base_url = get_base_url();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'stats';
    if ($action === 'stats') {
        $stats = $conn->query("
            SELECT 
                COUNT(*) as total_products,
                SUM(CASE WHEN stock <= 5 AND stock > 0 THEN 1 ELSE 0 END) as low_stock,
                SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(stock) as total_units
            FROM products
        ")->fetch_assoc();

        json_response(['success' => true, 'stats' => $stats]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Handle Delete
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Invalid product ID'], 400);
        }

        // Get product image to delete
        $img_q = $conn->query("SELECT image FROM products WHERE id = $id");
        if ($row = $img_q->fetch_assoc()) {
            if (!empty($row['image'])) {
                $file = __DIR__ . '/../' . $row['image'];
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
        }

        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            json_response(['success' => true, 'message' => 'ลบสินค้าสำเร็จ']);
        } else {
            json_response(['success' => false, 'message' => 'ไม่สามารถลบสินค้าได้: ' . $conn->error], 500);
        }
    }

    // Handle Add & Update
    $name         = trim($_POST['name'] ?? '');
    $price        = (int)($_POST['price'] ?? 0);
    $stock        = (int)($_POST['stock'] ?? 0);
    $gender       = trim($_POST['gender'] ?? 'unisex');
    $category     = trim($_POST['category'] ?? 'men');
    $subcategory  = trim($_POST['subcategory'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $is_clearance = isset($_POST['is_clearance']) && $_POST['is_clearance'] == '1' ? 1 : 0;

    // Optional image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $dest_name = 'prod_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            $dest_path = __DIR__ . '/../uploads/' . $dest_name;
            if (move_uploaded_file($file['tmp_name'], $dest_path)) {
                $image_path = 'uploads/' . $dest_name;
            }
        }
    }

    if ($action === 'add') {
        if (empty($name) || $price < 0 || $stock < 0) {
            json_response(['success' => false, 'message' => 'กรุณากรอกชื่อสินค้า ราคา และจำนวนสต็อกให้ถูกต้อง'], 400);
        }

        $stmt = $conn->prepare("
            INSERT INTO products (name, description, gender, category, subcategory, is_clearance, price, stock, image)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssssiiis", $name, $description, $gender, $category, $subcategory, $is_clearance, $price, $stock, $image_path);

        if ($stmt->execute()) {
            json_response([
                'success' => true,
                'message' => 'เพิ่มสินค้าใหม่สำเร็จ',
                'product_id' => $stmt->insert_id
            ], 201);
        } else {
            json_response(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเพิ่มสินค้า: ' . $conn->error], 500);
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 || empty($name)) {
            json_response(['success' => false, 'message' => 'ข้อมูลสินค้าไม่ถูกต้อง'], 400);
        }

        if ($image_path !== null) {
            $stmt = $conn->prepare("
                UPDATE products 
                SET name = ?, description = ?, gender = ?, category = ?, subcategory = ?, is_clearance = ?, price = ?, stock = ?, image = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sssssiiisi", $name, $description, $gender, $category, $subcategory, $is_clearance, $price, $stock, $image_path, $id);
        } else {
            $stmt = $conn->prepare("
                UPDATE products 
                SET name = ?, description = ?, gender = ?, category = ?, subcategory = ?, is_clearance = ?, price = ?, stock = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sssssiiii", $name, $description, $gender, $category, $subcategory, $is_clearance, $price, $stock, $id);
        }

        if ($stmt->execute()) {
            json_response(['success' => true, 'message' => 'อัปเดตข้อมูลสินค้าสำเร็จ']);
        } else {
            json_response(['success' => false, 'message' => 'ไม่สามารถอัปเดตสินค้าได้: ' . $conn->error], 500);
        }
    }

    json_response(['success' => false, 'message' => 'Invalid action'], 400);
}
