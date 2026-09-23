</div><!-- ปิด container-fluid จาก header.php -->

<footer class="bg-white border-top mt-auto py-5" style="border-color: var(--brand-secondary) !important;">
    <div class="container">
        <div class="row g-4 justify-content-between mb-4">
            <!-- Brand & About -->
            <div class="col-lg-4 col-md-6">
                <h5 class="fw-bold tracking-wider mb-3 text-uppercase" style="letter-spacing: 2px;">CLOTHING STORE</h5>
                <p class="text-muted small mb-3" style="line-height: 1.8;">
                    เสื้อผ้าและเครื่องแต่งกายสไตล์มินิมอล ใส่สบาย เหมาะกับทุกโอกาส ออกแบบด้วยความใส่ใจในทุกรายละเอียด
                </p>
                <div class="d-flex gap-3 text-muted fs-5">
                    <a href="#" class="text-secondary"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" class="text-secondary"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="text-secondary"><i class="fa-brands fa-line"></i></a>
                    <a href="#" class="text-secondary"><i class="fa-brands fa-tiktok"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-3 col-6">
                <h6 class="fw-bold text-uppercase mb-3 small" style="letter-spacing: 1px;">หมวดหมู่สินค้า</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><a href="<?php echo $path_prefix; ?>products.php?cat=men" class="text-decoration-none text-muted">Men's Collection</a></li>
                    <li class="mb-2"><a href="<?php echo $path_prefix; ?>products.php?cat=women" class="text-decoration-none text-muted">Women's Collection</a></li>
                    <li class="mb-2"><a href="<?php echo $path_prefix; ?>products.php?cat=clearance" class="text-decoration-none text-muted">Clearance Sale</a></li>
                    <li class="mb-2"><a href="<?php echo $path_prefix; ?>products.php" class="text-decoration-none text-muted">All Products</a></li>
                </ul>
            </div>

            <!-- Customer Service -->
            <div class="col-lg-2 col-md-3 col-6">
                <h6 class="fw-bold text-uppercase mb-3 small" style="letter-spacing: 1px;">บริการลูกค้า</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><a href="<?php echo $path_prefix; ?>cart.php" class="text-decoration-none text-muted">ตะกร้าสินค้า</a></li>
                    <li class="mb-2"><a href="<?php echo $path_prefix; ?>profile.php" class="text-decoration-none text-muted">บัญชีของฉัน</a></li>
                    <li class="mb-2"><a href="#" class="text-decoration-none text-muted">การจัดส่งสินค้า</a></li>
                    <li class="mb-2"><a href="#" class="text-decoration-none text-muted">นโยบายการเปลี่ยนคืน</a></li>
                </ul>
            </div>

            <!-- Contact info -->
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold text-uppercase mb-3 small" style="letter-spacing: 1px;">ติดต่อเรา</h6>
                <ul class="list-unstyled small text-muted mb-0">
                    <li class="mb-2"><i class="fa-solid fa-location-dot me-2 text-dark"></i> 123 Fashion Street, Bangkok, Thailand</li>
                    <li class="mb-2"><i class="fa-solid fa-phone me-2 text-dark"></i> 02-123-4567</li>
                    <li class="mb-2"><i class="fa-solid fa-envelope me-2 text-dark"></i> contact@clothingstore.com</li>
                    <li class="mb-2"><i class="fa-solid fa-clock me-2 text-dark"></i> จันทร์ - ศุกร์: 09:00 - 18:00 น.</li>
                </ul>
            </div>
        </div>

        <hr style="border-color: var(--brand-secondary);">

        <div class="row align-items-center py-2">
            <div class="col-md-6 text-center text-md-start small text-muted">
                &copy; <?php echo date('Y'); ?> Clothing Store. All rights reserved.
            </div>
            <div class="col-md-6 text-center text-md-end small text-muted mt-2 mt-md-0">
                <span>Terms of Service</span> &bull; <span>Privacy Policy</span>
            </div>
        </div>
    </div>
</footer>

<!-- Toast Notification สำหรับ AJAX Cart -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
    <div id="cartToast" class="toast align-items-center text-bg-dark border-0 rounded-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i id="toastIcon" class="fa-solid fa-circle-check text-success fs-5"></i>
                <span id="toastMessage">เพิ่มสินค้าลงในตะกร้าเรียบร้อยแล้ว</span>
            </div>
            <a href="<?php echo $path_prefix; ?>cart.php" class="btn btn-sm btn-outline-light align-self-center me-2 rounded-0">ดูตะกร้า</a>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JavaScript Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Global Script สำหรับ AJAX Add to Cart -->
<script>
function showCartToast(message, isSuccess = true) {
    const toastEl = document.getElementById('cartToast');
    if (!toastEl) return;
    const msgEl = document.getElementById('toastMessage');
    const iconEl = document.getElementById('toastIcon');
    if (msgEl) msgEl.textContent = message;
    if (iconEl) {
        if (isSuccess) {
            iconEl.className = 'fa-solid fa-circle-check text-success fs-5';
        } else {
            iconEl.className = 'fa-solid fa-circle-exclamation text-danger fs-5';
        }
    }
    const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
    toast.show();
}

function updateCartBadge(count) {
    const badge = document.getElementById('nav-cart-badge');
    if (badge) {
        badge.textContent = count;
        if (count > 0) {
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    }
}

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.ajax-add-to-cart, a[href*="cart.php?action=add"]');
    if (btn) {
        e.preventDefault();
        const url = btn.getAttribute('href');
        const originalContent = btn.innerHTML;
        btn.style.pointerEvents = 'none';
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> กำลังเพิ่ม...';

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            btn.style.pointerEvents = '';
            btn.innerHTML = originalContent;
            showCartToast(data.message, data.success);
            if (data.cart_count !== undefined) {
                updateCartBadge(data.cart_count);
            }
        })
        .catch(err => {
            btn.style.pointerEvents = '';
            btn.innerHTML = originalContent;
            window.location.href = url;
        });
    }
});
</script>
</body>
</html>
