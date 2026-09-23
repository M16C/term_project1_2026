<?php
// api/orders.php - Order History, Details, and Admin Status Management
require_once __DIR__ . '/cors.php';

$base_url = get_base_url();

// Handle Status Update (Admin action)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (is_array($input)) {
        $_POST = array_merge($_POST, $input);
    }

    $action   = $_POST['action'] ?? '';
    $order_id = (int)($_POST['order_id'] ?? 0);
    $status   = trim($_POST['status'] ?? '');

    if ($action === 'update_status') {
        if ($order_id <= 0 || empty($status)) {
            json_response(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน'], 400);
        }

        $valid_statuses = ['Pending', 'Paid', 'Shipped', 'Completed', 'Cancelled'];
        if (!in_array($status, $valid_statuses)) {
            json_response(['success' => false, 'message' => 'สถานะไม่ถูกต้อง'], 400);
        }

        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $order_id);

        if ($stmt->execute()) {
            json_response([
                'success' => true,
                'message' => "อัปเดตสถานะออเดอร์ #$order_id เป็น '$status' สำเร็จ",
                'order_id' => $order_id,
                'status' => $status
            ]);
        } else {
            json_response(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปเดตสถานะ'], 500);
        }
    }

    json_response(['success' => false, 'message' => 'Invalid action'], 400);
}

// Single Order Detail
if (isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    $stmt = $conn->prepare("
        SELECT o.*, u.fullname, u.email, u.phone, u.username
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        json_response(['success' => false, 'message' => 'ไม่พบคำสั่งซื้อนี้'], 404);
    }

    $order['slip_url'] = !empty($order['slip_image']) ? $base_url . '/' . $order['slip_image'] : null;

    // Fetch order items
    $item_stmt = $conn->prepare("
        SELECT oi.*, p.name as product_name, p.image as product_image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $item_stmt->bind_param("i", $order_id);
    $item_stmt->execute();
    $items_res = $item_stmt->get_result();

    $items = [];
    while ($it = $items_res->fetch_assoc()) {
        $it['product_image_url'] = !empty($it['product_image']) ? $base_url . '/' . $it['product_image'] : null;
        $items[] = $it;
    }
    $order['items'] = $items;

    json_response(['success' => true, 'order' => $order]);
}

// Admin Order List
if (isset($_GET['admin']) && $_GET['admin'] == '1') {
    $status_filter = trim($_GET['status'] ?? '');
    $search = trim($_GET['search'] ?? '');

    $sql = "
        SELECT o.*, u.fullname, u.username, u.email, u.phone
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE 1=1
    ";
    $params = [];
    $types  = "";

    if ($status_filter !== '' && $status_filter !== 'all') {
        $sql .= " AND o.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }

    if ($search !== '') {
        $sql .= " AND (o.id = ? OR u.fullname LIKE ? OR u.username LIKE ?)";
        $search_id = (int)$search;
        $like = "%$search%";
        $params[] = $search_id;
        $params[] = $like;
        $params[] = $like;
        $types .= "iss";
    }

    $sql .= " ORDER BY o.id DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $orders = [];
    while ($row = $res->fetch_assoc()) {
        $row['slip_url'] = !empty($row['slip_image']) ? $base_url . '/' . $row['slip_image'] : null;
        $orders[] = $row;
    }

    // Also calculate KPI stats
    $kpi_res = $conn->query("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) as paid_orders,
            COALESCE(SUM(CASE WHEN status = 'Paid' THEN total_amount ELSE 0 END), 0) as total_revenue
        FROM orders
    ");
    $kpi = $kpi_res->fetch_assoc();

    json_response([
        'success' => true,
        'kpi' => $kpi,
        'total' => count($orders),
        'orders' => $orders
    ]);
}

// Customer Order History by user_id
$user_id = (int)($_GET['user_id'] ?? 0);
if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $orders = [];
    while ($row = $res->fetch_assoc()) {
        $row['slip_url'] = !empty($row['slip_image']) ? $base_url . '/' . $row['slip_image'] : null;

        // Fetch count of items
        $c_stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ?");
        $c_stmt->bind_param("i", $row['id']);
        $c_stmt->execute();
        $row['item_count'] = (int)$c_stmt->get_result()->fetch_assoc()['count'];

        $orders[] = $row;
    }

    json_response(['success' => true, 'total' => count($orders), 'orders' => $orders]);
}

json_response(['success' => false, 'message' => 'Missing parameter'], 400);
