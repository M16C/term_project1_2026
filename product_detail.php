<?php
session_start();
require_once 'config/db.php';

$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    set_flash('error', 'ไม่พบสินค้านี้ในระบบ');
    header("Location: products.php");
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    set_flash('error', 'ไม่พบสินค้าที่คุณต้องการ');
    header("Location: products.php");
    exit();
}

// ดึงสินค้าที่เกี่ยวข้องในหมวดหมู่เดียวกัน (Related Products)
$stmt_rel = mysqli_prepare($conn, "SELECT * FROM products WHERE category = ? AND id != ? ORDER BY id DESC LIMIT 4");
mysqli_stmt_bind_param($stmt_rel, "si", $product['category'], $product_id);
mysqli_stmt_execute($stmt_rel);
$rel_result = mysqli_stmt_get_result($stmt_rel);

$is_out_of_stock = ($product['stock'] <= 0);
$flash_success = get_flash('success');
$flash_error = get_flash('error');

include 'includes/header.php';
?>

<div class="container my-5">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php" class="text-muted text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="products.php" class="text-muted text-decoration-none">Products</a></li>
            <li class="breadcrumb-item">
                <a href="products.php?cat=<?php echo urlencode($product['category']); ?>" class="text-muted text-decoration-none text-uppercase">
                    <?php echo htmlspecialchars($product['category']); ?>
                </a>
            </li>
            <?php if (!empty($product['subcategory'])): ?>
                <li class="breadcrumb-item">
                    <a href="products.php?cat=<?php echo urlencode($product['category']); ?>&sub=<?php echo urlencode($product['subcategory']); ?>" class="text-muted text-decoration-none text-uppercase">
                        <?php echo htmlspecialchars(str_replace('_', ' ', $product['subcategory'])); ?>
                    </a>
                </li>
            <?php endif; ?>
            <li class="breadcrumb-item active text-truncate" style="max-width: 250px;" aria-current="page">
                <?php echo htmlspecialchars($product['name']); ?>
            </li>
        </ol>
    </nav>

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

    <!-- Product Detail Section -->
    <div class="row g-5">
        <!-- ฝั่งซ้าย: รูปภาพสินค้าขนาดใหญ่ -->
        <div class="col-lg-6">
            <div class="position-relative bg-white border p-2" style="border-color: var(--brand-secondary) !important;">
                <div style="aspect-ratio: 1/1; overflow: hidden; background-color: var(--brand-secondary);">
                    <img id="mainProductImage" 
                         src="<?php echo htmlspecialchars(get_image_url($product['image']), ENT_QUOTES, 'UTF-8'); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="w-100 h-100" style="object-fit: cover;">
                </div>

                <?php if ($is_out_of_stock): ?>
                    <span class="position-absolute top-0 end-0 bg-danger text-white small px-3 py-1 m-3 fw-semibold">
                        สินค้าหมดชั่วคราว
                    </span>
                <?php elseif (!empty($product['is_clearance'])): ?>
                    <span class="position-absolute top-0 end-0 bg-dark text-white small px-3 py-1 m-3 fw-semibold">
                        CLEARANCE SALE
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- ฝั่งขวา: ข้อมูลสินค้าและฟอร์มสั่งซื้อ -->
        <div class="col-lg-6">
            <div class="d-flex flex-column h-100">
                <div class="mb-2">
                    <span class="badge bg-secondary rounded-0 text-uppercase me-2">
                        <?php echo htmlspecialchars($product['category']); ?>
                    </span>
                    <?php if (!empty($product['subcategory'])): ?>
                        <span class="badge bg-light text-dark border rounded-0 text-uppercase">
                            <?php echo htmlspecialchars(str_replace('_', ' ', $product['subcategory'])); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <h2 class="fw-bold mb-3" style="letter-spacing: 0.5px;">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h2>

                <div class="d-flex align-items-baseline gap-3 mb-3 pb-3 border-bottom">
                    <h3 class="fw-bold mb-0" style="color: var(--brand-primary); font-size: 2rem;">
                        <?php echo format_price($product['price']); ?>
                    </h3>
                    <div>
                        <?php if ($is_out_of_stock): ?>
                            <span class="badge bg-danger rounded-0"><i class="fa-solid fa-circle-xmark me-1"></i> สินค้าหมด</span>
                        <?php else: ?>
                            <span class="badge bg-success rounded-0"><i class="fa-solid fa-circle-check me-1"></i> มีสินค้าพร้อมส่ง (คงเหลือ <?php echo $product['stock']; ?> ชิ้น)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Form สั่งซื้อสินค้า -->
                <form action="cart.php?action=add" method="POST" id="detailAddToCartForm" class="mb-4">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">

                    <!-- เลือกขนาด (Size Selector) -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-uppercase d-flex justify-content-between">
                            <span>ขนาด (Select Size)</span>
                            <a href="#sizeGuideAccordion" class="text-muted text-decoration-none small" data-bs-toggle="collapse"><i class="fa-solid fa-ruler me-1"></i> ตารางไซส์</a>
                        </label>
                        <div class="d-flex gap-2" role="group" id="sizeSelector">
                            <?php foreach (['S', 'M', 'L', 'XL', 'XXL'] as $size): ?>
                                <input type="radio" class="btn-check" name="size" id="size_<?php echo $size; ?>" value="<?php echo $size; ?>" <?php echo $size === 'M' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-dark rounded-0 px-3 py-2 fw-medium" for="size_<?php echo $size; ?>">
                                    <?php echo $size; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- จำนวนสินค้า (Quantity Picker) -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-uppercase">จำนวน (Quantity)</label>
                        <div class="input-group" style="max-width: 160px;">
                            <button class="btn btn-outline-dark rounded-0 px-3" type="button" id="btnMinus" <?php echo $is_out_of_stock ? 'disabled' : ''; ?>>-</button>
                            <input type="number" name="quantity" id="quantityInput" class="form-control text-center rounded-0 fw-bold" value="1" min="1" max="<?php echo max(1, $product['stock']); ?>" <?php echo $is_out_of_stock ? 'disabled' : ''; ?>>
                            <button class="btn btn-outline-dark rounded-0 px-3" type="button" id="btnPlus" <?php echo $is_out_of_stock ? 'disabled' : ''; ?>>+</button>
                        </div>
                    </div>

                    <!-- ปุ่ม Add to Cart -->
                    <div class="d-grid gap-2">
                        <?php if ($is_out_of_stock): ?>
                            <button type="button" class="btn btn-secondary btn-lg rounded-0 disabled py-3" disabled>
                                <i class="fa-solid fa-ban me-2"></i> สินค้าหมดชั่วคราว
                            </button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-brand-dark btn-lg rounded-0 py-3 text-uppercase fw-semibold" id="btnDetailAddToCart">
                                <i class="fa-solid fa-cart-plus me-2"></i> เพิ่มลงในตะกร้าสินค้า
                            </button>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- ข้อมูลเพิ่มเติม / รายละเอียดสินค้า (Accordion) -->
                <div class="accordion rounded-0" id="productInfoAccordion">
                    <!-- รายละเอียด -->
                    <div class="accordion-item rounded-0 border-start-0 border-end-0">
                        <h2 class="accordion-header">
                            <button class="accordion-button rounded-0 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#descCollapse">
                                <i class="fa-solid fa-circle-info me-2"></i> รายละเอียดสินค้า (Description)
                            </button>
                        </h2>
                        <div id="descCollapse" class="accordion-collapse collapse show">
                            <div class="accordion-body small text-muted" style="line-height: 1.8;">
                                <?php if (!empty($product['description'])): ?>
                                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                                <?php else: ?>
                                    เสื้อผ้าสไตล์มินิมอล ออกแบบมาเพื่อให้สวมใส่ได้ง่ายในทุกๆ วัน ผลิตจากเนื้อผ้าคุณภาพดี ให้ความรู้สึกนุ่มสบาย ระบายอากาศได้ดีเยี่ยม ตัดเย็บด้วยความประณีต คงรูปสวยงามแม้ผ่านการซักหลายครั้ง เหมาะสำหรับทุกโอกาส
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ตารางขนาดไซส์ -->
                    <div class="accordion-item rounded-0 border-start-0 border-end-0" id="sizeGuideAccordion">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed rounded-0 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#sizeCollapse">
                                <i class="fa-solid fa-ruler-combined me-2"></i> ตารางขนาดไซส์ (Size Guide)
                            </button>
                        </h2>
                        <div id="sizeCollapse" class="accordion-collapse collapse">
                            <div class="accordion-body p-2">
                                <table class="table table-bordered table-sm small text-center mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Size</th>
                                            <th>รอบอก (นิ้ว)</th>
                                            <th>ความยาว (นิ้ว)</th>
                                            <th>ไหล่กว้าง (นิ้ว)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td><strong>S</strong></td><td>38</td><td>27</td><td>16.5</td></tr>
                                        <tr><td><strong>M</strong></td><td>40</td><td>28</td><td>17.5</td></tr>
                                        <tr><td><strong>L</strong></td><td>42</td><td>29</td><td>18.5</td></tr>
                                        <tr><td><strong>XL</strong></td><td>44</td><td>30</td><td>19.5</td></tr>
                                        <tr><td><strong>XXL</strong></td><td>46</td><td>31</td><td>20.5</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- นโยบายการจัดส่ง -->
                    <div class="accordion-item rounded-0 border-start-0 border-end-0">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed rounded-0 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#shippingCollapse">
                                <i class="fa-solid fa-truck-fast me-2"></i> นโยบายการจัดส่งและการเปลี่ยนคืน
                            </button>
                        </h2>
                        <div id="shippingCollapse" class="accordion-collapse collapse">
                            <div class="accordion-body small text-muted" style="line-height: 1.8;">
                                <ul class="mb-0 ps-3">
                                    <li>จัดส่งฟรีทั่วประเทศเมื่อสั่งซื้อครบ 990 บาทขึ้นไป</li>
                                    <li>ระยะเวลาจัดส่ง 1 - 3 วันทำการ (กทม.และปริมณฑล) และ 2 - 4 วันทำการ (ต่างจังหวัด)</li>
                                    <li>สามารถเปลี่ยนสินค้าได้ภายใน 14 วันหลังจากได้รับสินค้า (ในสภาพสมบูรณ์มีป้ายราคา)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- สินค้าที่เกี่ยวข้อง (Related Products) -->
    <?php if ($rel_result && mysqli_num_rows($rel_result) > 0): ?>
        <div class="mt-5 pt-5 border-top">
            <h4 class="fw-bold text-uppercase mb-4" style="letter-spacing: 1px;">
                สินค้าที่คุณอาจจะชอบ (RELATED PRODUCTS)
            </h4>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
                <?php while ($rel = mysqli_fetch_assoc($rel_result)): ?>
                    <?php 
                        $rel_out_of_stock = ($rel['stock'] <= 0); 
                        $rel_link = "product_detail.php?id=" . $rel['id'];
                    ?>
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm rounded-0 product-card">
                            <div style="aspect-ratio: 1/1; overflow: hidden; background-color: var(--brand-secondary);" class="position-relative">
                                <a href="<?php echo $rel_link; ?>">
                                    <img src="<?php echo htmlspecialchars(get_image_url($rel['image']), ENT_QUOTES, 'UTF-8'); ?>" class="w-100 h-100" style="object-fit: cover; transition: transform 0.3s ease;">
                                </a>
                                <?php if ($rel_out_of_stock): ?>
                                    <span class="position-absolute top-0 end-0 bg-danger text-white small px-2 py-1 m-2 fw-semibold">
                                        สินค้าหมด
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-3 text-center d-flex flex-column">
                                <h6 class="fw-medium text-truncate mb-1">
                                    <a href="<?php echo $rel_link; ?>" class="text-dark text-decoration-none">
                                        <?php echo htmlspecialchars($rel['name']); ?>
                                    </a>
                                </h6>
                                <p class="fw-bold mb-3" style="color: var(--brand-primary);"><?php echo format_price($rel['price']); ?></p>
                                
                                <div class="mt-auto d-flex gap-2">
                                    <a href="<?php echo $rel_link; ?>" class="btn btn-outline-dark btn-sm rounded-0 flex-grow-1">
                                        ดูสินค้า
                                    </a>
                                    <?php if (!$rel_out_of_stock): ?>
                                        <a href="cart.php?action=add&id=<?php echo $rel['id']; ?>" class="btn btn-brand-dark btn-sm rounded-0 ajax-add-to-cart" title="เพิ่มลงตะกร้า">
                                            <i class="fa-solid fa-cart-plus"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const qtyInput = document.getElementById('quantityInput');
    const btnMinus = document.getElementById('btnMinus');
    const btnPlus = document.getElementById('btnPlus');
    const maxStock = parseInt(qtyInput ? qtyInput.getAttribute('max') : 1) || 1;

    if (btnMinus && qtyInput) {
        btnMinus.addEventListener('click', function () {
            let current = parseInt(qtyInput.value) || 1;
            if (current > 1) {
                qtyInput.value = current - 1;
            }
        });
    }

    if (btnPlus && qtyInput) {
        btnPlus.addEventListener('click', function () {
            let current = parseInt(qtyInput.value) || 1;
            if (current < maxStock) {
                qtyInput.value = current + 1;
            }
        });
    }

    // Detail Add to Cart with AJAX
    const form = document.getElementById('detailAddToCartForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnDetailAddToCart');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> กำลังเพิ่มสินค้า...';

            const formData = new FormData(form);

            fetch('cart.php?action=add', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                showCartToast(data.message, data.success);
                if (data.cart_count !== undefined) {
                    updateCartBadge(data.cart_count);
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                form.submit();
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
