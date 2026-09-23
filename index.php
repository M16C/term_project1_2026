<?php
include 'includes/header.php';

$sql = "SELECT * FROM products ORDER BY id DESC LIMIT 8";
$result = mysqli_query($conn, $sql);

$flash_success = get_flash('success');
$flash_error = get_flash('error');
?>

<style>
    /* Wireframe Hero Banner */
    .wireframe-hero {
        background: linear-gradient(rgba(45, 40, 40, 0.45), rgba(45, 40, 40, 0.45)), 
                    url('https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1600') center/cover;
        min-height: 480px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FFFFFF;
        text-align: center;
        padding: 60px 20px;
    }
    .pill-badge {
        background-color: var(--brand-dark);
        color: #FFFFFF;
        border-radius: 50px;
        padding: 6px 20px;
        font-size: 0.8rem;
        display: inline-block;
        margin-bottom: 20px;
        letter-spacing: 1px;
    }
    .hero-quote {
        max-width: 680px;
        font-size: 0.88rem;
        line-height: 1.8;
        color: #F0EAE6;
        font-weight: 300;
        margin: 0 auto;
    }
    /* Product Card */
    .card-wireframe {
        border: 1px solid var(--brand-secondary);
        border-radius: 0;
        background: #FFFFFF;
        transition: all 0.3s ease;
    }
    .card-wireframe:hover {
        border-color: var(--brand-primary);
    }
</style>

<!-- Hero Section ตาม Wireframe -->
<div class="wireframe-hero mb-5">
    <div class="container">
        <span class="pill-badge">NEW ARRIVAL</span>
        <h2 class="fw-bold text-uppercase mb-3" style="letter-spacing: 2px;">MINIMALIST EVERYDAY WEAR</h2>
        <p class="hero-quote mb-4">
            ค้นพบเครื่องแต่งกายสไตล์มินิมอลที่ผสานความเรียบง่าย ความสบาย และคุณภาพไว้ในทุกชิ้น เหมาะสำหรับทุกวันของคุณ
        </p>
        <div>
            <a href="products.php" class="btn btn-brand-dark px-4 py-2 text-uppercase">EXPLORE COLLECTION</a>
        </div>
    </div>
</div>

<div class="container my-5">
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

    <!-- Grid แสดงสินค้าแนะนำ -->
    <div class="text-center mb-5">
        <h4 class="fw-bold tracking-wider text-uppercase" style="letter-spacing: 2px;">สินค้าแนะนำ (FEATURED)</h4>
        <div style="width: 50px; height: 2px; background-color: var(--brand-primary); margin: 10px auto;"></div>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <?php 
                    $is_out_of_stock = ($row['stock'] <= 0); 
                    $detail_link = "product_detail.php?id=" . $row['id'];
                ?>
                <div class="col">
                    <div class="card h-100 card-wireframe border-0 shadow-sm rounded-0">
                        <div style="aspect-ratio: 1/1; overflow: hidden; background-color: var(--brand-secondary);" class="position-relative">
                            <a href="<?php echo $detail_link; ?>">
                                <img src="<?php echo htmlspecialchars(get_image_url($row['image']), ENT_QUOTES, 'UTF-8'); ?>" 
                                     class="w-100 h-100" style="object-fit: cover; transition: transform 0.3s ease;">
                            </a>
                            <?php if ($is_out_of_stock): ?>
                                <span class="position-absolute top-0 end-0 bg-danger text-white small px-2 py-1 m-2 fw-semibold">
                                    สินค้าหมด
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-3 text-center d-flex flex-column">
                            <h6 class="card-title fw-medium text-truncate mb-1">
                                <a href="<?php echo $detail_link; ?>" class="text-dark text-decoration-none" title="<?php echo htmlspecialchars($row['name']); ?>">
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </a>
                            </h6>
                            <p class="mb-3 fw-bold" style="color: var(--brand-primary);"><?php echo format_price($row['price']); ?></p>
                            
                            <div class="mt-auto d-flex gap-2">
                                <a href="<?php echo $detail_link; ?>" class="btn btn-outline-dark btn-sm rounded-0 flex-grow-1" title="ดูรายละเอียดสินค้า">
                                    <i class="fa-regular fa-eye"></i> ดูสินค้า
                                </a>
                                <?php if ($is_out_of_stock): ?>
                                    <button class="btn btn-secondary btn-sm rounded-0 disabled" disabled title="สินค้าหมดชั่วคราว">
                                        <i class="fa-solid fa-ban"></i>
                                    </button>
                                <?php else: ?>
                                    <a href="cart.php?action=add&id=<?php echo $row['id']; ?>" class="btn btn-brand-dark btn-sm rounded-0 ajax-add-to-cart" title="เพิ่มลงตะกร้า">
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
                <i class="fa-solid fa-box-open fs-1 mb-3 opacity-25"></i>
                <p>ยังไม่มีสินค้าในขณะนี้</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="text-center mt-5">
        <a href="products.php" class="btn btn-outline-dark rounded-0 px-4 py-2 text-uppercase">
            ดูสินค้าทั้งหมด (VIEW ALL) <i class="fa-solid fa-arrow-right ms-1"></i>
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>