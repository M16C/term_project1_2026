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
    $sql .= " AND subcategory = ?";
    $params[] = $sub;
    $types .= "s";
}

$sql .= " ORDER BY id DESC";
$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
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
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm rounded-0">
                        <div style="aspect-ratio: 1/1; overflow: hidden; background-color: var(--brand-secondary);">
                            <img src="<?php echo htmlspecialchars($row['image'] ?: 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=500'); ?>" class="w-100 h-100" style="object-fit: cover;">
                        </div>
                        <div class="card-body p-3 text-center">
                            <h6 class="fw-medium text-truncate mb-2"><?php echo htmlspecialchars($row['name']); ?></h6>
                            <p class="fw-bold mb-3" style="color: var(--brand-primary);">฿<?php echo number_format($row['price'], 2); ?></p>
                            <a href="cart.php?action=add&id=<?php echo $row['id']; ?>" class="btn btn-brand-dark w-100">Add to Cart</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5 text-muted">
                <i class="fa-solid fa-box-open fs-1 mb-3 opacity-50"></i>
                <p>ไม่พบสินค้าในหมวดหมู่นี้</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>