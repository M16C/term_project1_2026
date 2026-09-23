<?php
include 'includes/header.php';

$cat = trim($_GET['cat'] ?? '');
$sub = trim($_GET['sub'] ?? '');

$sql = "SELECT * FROM products WHERE 1=1";
$params = [];
$types = "";

if (!empty($cat)) {
    $sql .= " AND category = ?";
    $params[] = $cat;
    $types .= "s";
}
if (!empty($sub)) {
    if (in_array(strtolower($sub), ['shirt', 'shirts'])) {
        $sql .= " AND (subcategory = 'shirts' OR subcategory = 'shirt')";
    } elseif (in_array(strtolower($sub), ['bottom', 'bottoms'])) {
        $sql .= " AND (subcategory = 'bottoms' OR subcategory = 'bottom')";
    } else {
        $sql .= " AND subcategory = ?";
        $params[] = $sub;
        $types .= "s";
    }
}

$sql .= " ORDER BY id DESC";
$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$flash_success = get_flash('success');
$flash_error = get_flash('error');
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-uppercase mb-1" style="letter-spacing: 1px;">
                <?php echo $cat ? htmlspecialchars(strtoupper($cat)) : 'ALL PRODUCTS'; ?>
            </h3>
            <p class="text-muted small mb-0">
                Subcategory: <?php echo $sub ? htmlspecialchars(strtoupper(str_replace('_', ' ', $sub))) : 'All'; ?>
            </p>
        </div>
        <a href="cart.php" class="btn btn-outline-dark btn-sm rounded-0">
            <i class="fa-solid fa-cart-shopping me-1"></i> ดูตะกร้าสินค้า (<?php echo get_cart_count(); ?>)
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

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <?php
                    $is_out_of_stock = ($row['stock'] <= 0);
                ?>
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm rounded-0">
                        <div style="aspect-ratio: 1/1; overflow: hidden; background-color: var(--brand-secondary);" class="position-relative">
                            <img src="<?php echo htmlspecialchars(get_image_url($row['image']), ENT_QUOTES, 'UTF-8'); ?>" class="w-100 h-100" style="object-fit: cover;">
                            <?php if ($is_out_of_stock): ?>
                                <span class="position-absolute top-0 end-0 bg-danger text-white small px-2 py-1 m-2 fw-semibold">
                                    สินค้าหมด
                                </span>
                            <?php elseif ($row['is_clearance']): ?>
                                <span class="position-absolute top-0 end-0 bg-dark text-white small px-2 py-1 m-2 fw-semibold">
                                    CLEARANCE
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-3 text-center d-flex flex-column">
                            <h6 class="fw-medium text-truncate mb-1" title="<?php echo htmlspecialchars($row['name']); ?>"><?php echo htmlspecialchars($row['name']); ?></h6>
                            <p class="fw-bold mb-3" style="color: var(--brand-primary);"><?php echo format_price($row['price']); ?></p>
                            
                            <div class="mt-auto">
                                <?php if ($is_out_of_stock): ?>
                                    <button class="btn btn-secondary w-100 rounded-0 disabled" disabled>Out of Stock</button>
                                <?php else: ?>
                                    <a href="cart.php?action=add&id=<?php echo $row['id']; ?>" class="btn btn-brand-dark w-100">
                                        <i class="fa-solid fa-cart-plus me-1"></i> Add to Cart
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5 text-muted">
                <i class="fa-solid fa-box-open fs-1 mb-3 opacity-50"></i>
                <p>ไม่พบสินค้าในหมวดหมู่นี้</p>
                <a href="products.php" class="btn btn-sm btn-outline-dark rounded-0">ดูสินค้าทั้งหมด</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>