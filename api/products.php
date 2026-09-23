<?php
// api/products.php - Product Catalog & Details API
require_once __DIR__ . '/cors.php';

$base_url = get_base_url();

// Single product detail
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($product = $res->fetch_assoc()) {
        $product['price'] = (float)$product['price'];
        $product['stock'] = (int)$product['stock'];
        $product['is_clearance'] = (int)$product['is_clearance'];
        $product['image_url'] = !empty($product['image']) ? $base_url . '/' . $product['image'] : null;

        // Fetch related products
        $rel_stmt = $conn->prepare("SELECT id, name, price, image, stock FROM products WHERE (category = ? OR gender = ?) AND id != ? LIMIT 4");
        $rel_stmt->bind_param("ssi", $product['category'], $product['gender'], $id);
        $rel_stmt->execute();
        $rel_res = $rel_stmt->get_result();
        $related = [];
        while ($r = $rel_res->fetch_assoc()) {
            $r['price'] = (float)$r['price'];
            $r['stock'] = (int)$r['stock'];
            $r['image_url'] = !empty($r['image']) ? $base_url . '/' . $r['image'] : null;
            $related[] = $r;
        }
        $product['related'] = $related;

        json_response(['success' => true, 'product' => $product]);
    } else {
        json_response(['success' => false, 'message' => 'ไม่พบข้อมูลสินค้านี้'], 404);
    }
}

// Product list with search and filters
$search      = trim($_GET['search'] ?? $_GET['q'] ?? '');
$category    = trim($_GET['category'] ?? '');
$gender      = trim($_GET['gender'] ?? '');
$subcategory = trim($_GET['subcategory'] ?? '');
$min_price   = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$max_price   = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
$sort        = $_GET['sort'] ?? 'newest';

$sql = "SELECT * FROM products WHERE 1=1";
$params = [];
$types  = "";

if ($search !== '') {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

if ($category !== '') {
    if ($category === 'clearance') {
        $sql .= " AND is_clearance = 1";
    } else {
        $sql .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
    }
}

if ($gender !== '') {
    $sql .= " AND (gender = ? OR gender = 'unisex')";
    $params[] = $gender;
    $types .= "s";
}

if ($subcategory !== '') {
    $sql .= " AND subcategory = ?";
    $params[] = $subcategory;
    $types .= "s";
}

if ($min_price !== null) {
    $sql .= " AND price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if ($max_price !== null) {
    $sql .= " AND price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY price DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY name ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY id DESC";
        break;
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $row['price'] = (float)$row['price'];
    $row['stock'] = (int)$row['stock'];
    $row['is_clearance'] = (int)$row['is_clearance'];
    $row['image_url'] = !empty($row['image']) ? $base_url . '/' . $row['image'] : null;
    $products[] = $row;
}

json_response([
    'success' => true,
    'total' => count($products),
    'products' => $products
]);
