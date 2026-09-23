<?php
include 'includes/header.php';

$sql = "SELECT * FROM products ORDER BY id DESC LIMIT 8";
$result = mysqli_query($conn, $sql);
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
    /* Floating Action Circles at Bottom of Hero */
    .hero-action-bar {
        position: absolute;
        bottom: 25px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 20px;
    }
    .action-circle-btn {
        width: 48px;
        height: 48px;
        background-color: var(--brand-secondary);
        color: var(--brand-dark);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.2s ease;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    .action-circle-btn:hover {
        background-color: var(--brand-primary);
        color: #FFFFFF;
        transform: translateY(-3px);
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
        <span class="pill-badge">New Arrival</span>
        <p class="hero-quote mb-4">
            "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. 
            Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat."
        </p>
    </div>

    <!-- ปุ่มวงกลม 3 ไอคอนตาม Wireframe (Home, Search, User) -->
    <div class="hero-action-bar">
        <a href="index.php" class="action-circle-btn" title="หน้าแรก"><i class="fa-solid fa-house"></i></a>
        <a href="products.php" class="action-circle-btn" title="ค้นหาสินค้า"><i class="fa-solid fa-magnifying-glass"></i></a>
        <a href="login.php" class="action-circle-btn" title="บัญชีผู้ใช้"><i class="fa-regular fa-user"></i></a>
    </div>
</div>

<!-- Grid แสดงสินค้า -->
<div class="container my-5">
    <div class="text-center mb-5">
        <h4 class="fw-bold tracking-wider text-uppercase" style="letter-spacing: 2px;">สินค้าแนะนำ</h4>
        <div style="width: 50px; height: 2px; background-color: var(--brand-primary); margin: 10px auto;"></div>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="col">
                    <div class="card h-100 card-wireframe">
                        <div style="aspect-ratio: 1/1; overflow: hidden; background-color: var(--brand-secondary);">
                            <img src="<?php echo htmlspecialchars($row['image'] ?: 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=500'); ?>" 
                                 class="w-100 h-100" style="object-fit: cover;">
                        </div>
                        <div class="card-body p-3 text-center">
                            <h6 class="card-title fw-medium text-truncate mb-2"><?php echo htmlspecialchars($row['name']); ?></h6>
                            <p class="mb-0 fw-bold" style="color: var(--brand-primary);">฿<?php echo number_format($row['price'], 2); ?></p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5 text-muted">ยังไม่มีสินค้าในขณะนี้</div>
        <?php endif; ?>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>