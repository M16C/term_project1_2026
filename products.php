<?php
include 'includes/header.php';

$cat = trim($_GET['cat'] ?? '');
$sub = trim($_GET['sub'] ?? '');
$q = trim($_GET['q'] ?? '');
$min_price = (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) ? floatval($_GET['min_price']) : '';
$max_price = (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) ? floatval($_GET['max_price']) : '';
$sort = trim($_GET['sort'] ?? 'newest');

$sql = "SELECT * FROM products WHERE 1=1";
$params = [];
$types = "";

// 1. กรองตาม Category
if (!empty($cat)) {
    if ($cat === 'clearance') {
        $sql .= " AND is_clearance = 1";
    } else {
        $sql .= " AND category = ?";
        $params[] = $cat;
        $types .= "s";
    }
}

// 2. กรองตาม Subcategory
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

// 3. ค้นหาด้วยคำค้นหา (Keyword Search)
if (!empty($q)) {
    $sql .= " AND (name LIKE ? OR subcategory LIKE ? OR description LIKE ?)";
    $q_wild = "%" . $q . "%";
    $params[] = $q_wild;
    $params[] = $q_wild;
    $params[] = $q_wild;
    $types .= "sss";
}

// 4. กรองตามช่วงราคา (Price Range)
if ($min_price !== '') {
    $sql .= " AND price >= ?";
    $params[] = $min_price;
    $types .= "d";
}
if ($max_price !== '') {
    $sql .= " AND price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

// 5. เรียงลำดับ (Sorting)
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY price ASC, id DESC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY price DESC, id DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY name ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY id DESC";
        break;
}

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_found = mysqli_num_rows($result);

$flash_success = get_flash('success');
$flash_error = get_flash('error');
?>

<div class="container my-5">
    <!-- Breadcrumb & Top Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="index.php" class="text-muted text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item"><a href="products.php" class="text-muted text-decoration-none">Products</a></li>
                    <?php if ($cat): ?>
                        <li class="breadcrumb-item active text-uppercase"><?php echo htmlspecialchars($cat); ?></li>
                    <?php endif; ?>
                    <?php if ($sub): ?>
                        <li class="breadcrumb-item active text-uppercase"><?php echo htmlspecialchars(str_replace('_', ' ', $sub)); ?></li>
                    <?php endif; ?>
                </ol>
            </nav>
            <h3 class="fw-bold text-uppercase mb-0" style="letter-spacing: 1px;">
                <?php 
                    if ($q) {
                        echo 'ผลการค้นหา: "' . htmlspecialchars($q) . '"';
                    } elseif ($cat) {
                        echo htmlspecialchars(strtoupper($cat));
                        if ($sub) echo ' - ' . htmlspecialchars(strtoupper(str_replace('_', ' ', $sub)));
                    } else {
                        echo 'ALL PRODUCTS';
                    }
                ?>
            </h3>
            <span class="text-muted small">พบสินค้าทั้งหมด <?php echo $total_found; ?> รายการ</span>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a href="cart.php" class="btn btn-outline-dark btn-sm rounded-0">
                <i class="fa-solid fa-cart-shopping me-1"></i> ดูตะกร้าสินค้า
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

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-3 border mb-4" style="border-color: var(--brand-secondary) !important;">
        <form method="GET" action="products.php" id="filterForm">
            <?php if ($cat): ?><input type="hidden" name="cat" value="<?php echo htmlspecialchars($cat); ?>"><?php endif; ?>
            <?php if ($sub): ?><input type="hidden" name="sub" value="<?php echo htmlspecialchars($sub); ?>"><?php endif; ?>

            <div class="row g-2 align-items-center">
                <!-- Search input -->
                <div class="col-md-4 col-sm-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light rounded-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="q" class="form-control rounded-0" placeholder="พิมพ์ชื่อสินค้า..." value="<?php echo htmlspecialchars($q); ?>">
                    </div>
                </div>

                <!-- Price Min / Max -->
                <div class="col-md-4 col-sm-6">
                    <div class="d-flex align-items-center gap-1">
                        <input type="number" name="min_price" class="form-control form-control-sm rounded-0" placeholder="ราคาต่ำสุด" value="<?php echo htmlspecialchars($min_price); ?>" min="0">
                        <span class="text-muted">-</span>
                        <input type="number" name="max_price" class="form-control form-control-sm rounded-0" placeholder="ราคาสูงสุด" value="<?php echo htmlspecialchars($max_price); ?>" min="0">
                    </div>
                </div>

                <!-- Sort By -->
                <div class="col-md-2 col-sm-6">
                    <select name="sort" class="form-select form-select-sm rounded-0" onchange="this.form.submit()">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>สินค้าใหม่ล่าสุด</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>ราคา: ต่ำ ➔ สูง</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>ราคา: สูง ➔ ต่ำ</option>
                        <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>ชื่อสินค้า A-Z</option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="col-md-2 col-sm-6 d-flex gap-1">
                    <button type="submit" class="btn btn-brand-dark btn-sm rounded-0 w-100">
                        <i class="fa-solid fa-filter me-1"></i> กรอง
                    </button>
                    <?php if ($q || $min_price !== '' || $max_price !== '' || $sort !== 'newest' || $cat || $sub): ?>
                        <a href="products.php" class="btn btn-outline-secondary btn-sm rounded-0" title="ล้างตัวกรองทั้งหมด">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- Active Filter Badges -->
        <?php if ($cat || $sub || $q || $min_price !== '' || $max_price !== ''): ?>
            <div class="mt-2 pt-2 border-top d-flex flex-wrap align-items-center gap-2">
                <span class="small text-muted">ตัวกรองที่ใช้งาน:</span>
                <?php if ($cat): ?>
                    <span class="badge bg-dark rounded-0 fw-normal">หมวด: <?php echo htmlspecialchars(strtoupper($cat)); ?></span>
                <?php endif; ?>
                <?php if ($sub): ?>
                    <span class="badge bg-secondary rounded-0 fw-normal">ประเภทย่อย: <?php echo htmlspecialchars($sub); ?></span>
                <?php endif; ?>
                <?php if ($q): ?>
                    <span class="badge bg-info text-dark rounded-0 fw-normal">ค้นหา: "<?php echo htmlspecialchars($q); ?>"</span>
                <?php endif; ?>
                <?php if ($min_price !== '' || $max_price !== ''): ?>
                    <span class="badge bg-light text-dark border rounded-0 fw-normal">
                        ราคา: ฿<?php echo number_format((float)$min_price); ?> - <?php echo $max_price !== '' ? '฿' . number_format((float)$max_price) : 'ไม่จำกัด'; ?>
                    </span>
                <?php endif; ?>
                <a href="products.php" class="small text-danger ms-2 text-decoration-none"><i class="fa-solid fa-xmark me-1"></i>ล้างทั้งหมด</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Product Grid -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <?php
                    $is_out_of_stock = ($row['stock'] <= 0);
                    $detail_link = "product_detail.php?id=" . $row['id'];
                ?>
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm rounded-0 product-card">
                        <!-- Product Image -->
                        <div style="aspect-ratio: 1/1; overflow: hidden; background-color: var(--brand-secondary);" class="position-relative">
                            <a href="<?php echo $detail_link; ?>">
                                <img src="<?php echo htmlspecialchars(get_image_url($row['image']), ENT_QUOTES, 'UTF-8'); ?>" class="w-100 h-100" style="object-fit: cover; transition: transform 0.3s ease;">
                            </a>
                            <?php if ($is_out_of_stock): ?>
                                <span class="position-absolute top-0 end-0 bg-danger text-white small px-2 py-1 m-2 fw-semibold">
                                    สินค้าหมด
                                </span>
                            <?php elseif (!empty($row['is_clearance'])): ?>
                                <span class="position-absolute top-0 end-0 bg-dark text-white small px-2 py-1 m-2 fw-semibold">
                                    CLEARANCE
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body p-3 text-center d-flex flex-column">
                            <span class="text-muted small text-uppercase mb-1" style="font-size: 0.75rem;">
                                <?php echo htmlspecialchars($row['category']); ?> &bull; <?php echo htmlspecialchars($row['subcategory']); ?>
                            </span>
                            <h6 class="fw-medium text-truncate mb-2">
                                <a href="<?php echo $detail_link; ?>" class="text-dark text-decoration-none" title="<?php echo htmlspecialchars($row['name']); ?>">
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </a>
                            </h6>
                            <p class="fw-bold mb-3" style="color: var(--brand-primary);"><?php echo format_price($row['price']); ?></p>
                            
                            <div class="mt-auto d-flex gap-2">
                                <a href="<?php echo $detail_link; ?>" class="btn btn-outline-dark btn-sm rounded-0 flex-grow-1" title="ดูรายละเอียดสินค้า">
                                    <i class="fa-regular fa-eye"></i> ดูสินค้า
                                </a>
                                <?php if ($is_out_of_stock): ?>
                                    <button class="btn btn-secondary btn-sm rounded-0 disabled" disabled title="สินค้าหมดชั่วคราว">
                                        <i class="fa-solid fa-ban"></i>
                                    </button>
                                <?php else: ?>
                                    <a href="cart.php?action=add&id=<?php echo $row['id']; ?>" class="btn btn-brand-dark btn-sm rounded-0 ajax-add-to-cart" title="เพิ่มลงตะกร้าทันที">
                                        <i class="fa-solid fa-cart-plus"></i>
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
                <h5>ไม่พบสินค้าที่ตรงกับเงื่อนไขการค้นหา</h5>
                <p class="small text-muted mb-4">ลองปรับเปลี่ยนคำค้นหา หรือกดปุ่มด้านล่างเพื่อดูสินค้าทั้งหมด</p>
                <a href="products.php" class="btn btn-brand-dark rounded-0 px-4">ดูสินค้าทั้งหมด</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.product-card:hover img {
    transform: scale(1.04);
}
</style>

<?php include 'includes/footer.php'; ?>