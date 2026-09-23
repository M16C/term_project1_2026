<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// คำนวณ Base URL สำหรับให้ ลิงก์ เรียกใช้งานได้ถูกต้องทั้งหน้าปกติและในโฟลเดอร์ admin
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$script_dir = str_replace('\\', '/', dirname($script_name));
$is_admin = (strpos($script_dir, '/admin') !== false);
$path_prefix = $is_admin ? '../' : './';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clothing Store</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Font: Prompt -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --brand-primary: #B5A3A1;
            --brand-secondary: #DBCEC5;
            --brand-dark: #2D2828;
            --brand-light: #FAF8F5;
        }
        body {
            font-family: 'Prompt', sans-serif;
            background-color: var(--brand-light);
            color: var(--brand-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-wireframe {
            background-color: #FFFFFF;
            border-bottom: 1px solid var(--brand-secondary);
            padding: 15px 0;
        }
        .navbar-wireframe .navbar-brand {
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--brand-dark) !important;
        }
        .navbar-wireframe .nav-link {
            color: var(--brand-dark) !important;
            font-weight: 500;
            font-size: 0.88rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 8px 16px !important;
        }
        .navbar-wireframe .nav-link:hover, .navbar-wireframe .dropdown-item:hover {
            color: var(--brand-primary) !important;
            background-color: var(--brand-light);
        }
        .dropdown-menu {
            border: 1px solid var(--brand-secondary);
            border-radius: 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .btn-brand-dark {
            background-color: var(--brand-dark);
            color: #FFFFFF;
            border: none;
            border-radius: 0px;
            padding: 8px 20px;
            font-size: 0.88rem;
            letter-spacing: 1px;
        }
        .btn-brand-dark:hover {
            background-color: var(--brand-primary);
            color: #FFFFFF;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-wireframe sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $path_prefix; ?>index.php">LOGO</a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
            <ul class="navbar-nav align-items-center">
                <!-- MEN -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">MEN</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $path_prefix; ?>products.php?cat=men&sub=shirts">Shirts</a></li>
                        <li><a class="dropdown-item" href="<?php echo $path_prefix; ?>products.php?cat=men&sub=bottoms">Bottoms</a></li>
                        <li><a class="dropdown-item" href="<?php echo $path_prefix; ?>products.php?cat=men&sub=sport_utility">Sport Utility wear</a></li>
                        <li><a class="dropdown-item" href="<?php echo $path_prefix; ?>products.php?cat=men&sub=innerwear_socks">Innerwear & Socks</a></li>
                    </ul>
                </li>

                <!-- WOMEN -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">WOMEN</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $path_prefix; ?>products.php?cat=women&sub=shirts">Shirts</a></li>
                        <li><a class="dropdown-item" href="<?php echo $path_prefix; ?>products.php?cat=women&sub=bottoms">Bottoms</a></li>
                        <li><a class="dropdown-item" href="<?php echo $path_prefix; ?>products.php?cat=women&sub=sport_utility">Sport Utility wear</a></li>
                        <li><a class="dropdown-item" href="<?php echo $path_prefix; ?>products.php?cat=women&sub=innerwear_socks">Innerwear & Socks</a></li>
                    </ul>
                </li>

                <!-- CLEARANCE -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">CLEARANCE</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $path_prefix; ?>products.php?cat=clearance&sub=men">Men Clearance</a></li>
                        <li><a class="dropdown-item" href="<?php echo $path_prefix; ?>products.php?cat=clearance&sub=women">Women Clearance</a></li>
                    </ul>
                </li>

                <!-- ADMIN ONLY -->
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-danger fw-bold" href="#" data-bs-toggle="dropdown">ADMIN ONLY</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo $is_admin ? 'manage_stock.php' : 'admin/manage_stock.php'; ?>#add-stock-form"><i class="fa-solid fa-plus me-2"></i>Add Stock</a></li>
                            <li><a class="dropdown-item" href="<?php echo $is_admin ? 'manage_stock.php' : 'admin/manage_stock.php'; ?>#stock-list"><i class="fa-solid fa-pen-to-square me-2"></i>Update Stock</a></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo $is_admin ? 'manage_stock.php' : 'admin/manage_stock.php'; ?>#stock-list"><i class="fa-solid fa-trash me-2"></i>Delete Stock</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- RIGHT MENU (CART & ACCOUNT) -->
        <div class="d-flex align-items-center gap-3">
            <?php $cart_count = get_cart_count(); ?>
            <a href="<?php echo $path_prefix; ?>cart.php" class="text-dark fs-5 position-relative me-2" title="ตะกร้าสินค้า">
                <i class="fa-solid fa-cart-shopping"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                        <?php echo $cart_count; ?>
                    </span>
                <?php endif; ?>
            </a>

            <div class="dropdown">
                <a href="#" class="text-dark fs-5 dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fa-regular fa-user"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="dropdown-header">สวัสดี, <?php echo htmlspecialchars($_SESSION['username']); ?></li>
                        <li><a class="dropdown-item" href="<?php echo $path_prefix; ?>profile.php"><i class="fa-solid fa-id-card me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo $path_prefix; ?>edit_profile.php"><i class="fa-solid fa-user-pen me-2"></i>Edit Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo $path_prefix; ?>change_password.php"><i class="fa-solid fa-key me-2"></i>Change Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo $path_prefix; ?>logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                    <?php else: ?>
                        <li><a class="dropdown-item" href="<?php echo $path_prefix; ?>login.php"><i class="fa-solid fa-right-to-bracket me-2"></i>Login</a></li>
                        <li><a class="dropdown-item" href="<?php echo $path_prefix; ?>register.php"><i class="fa-solid fa-user-plus me-2"></i>Register</a></li>
                        <li><a class="dropdown-item" href="<?php echo $path_prefix; ?>forgot_password.php"><i class="fa-solid fa-lock me-2"></i>Forgot Password</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid px-0 flex-grow-1">