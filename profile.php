<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT username, email, role FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="bg-white p-4 p-md-5 border" style="border-color: var(--brand-secondary) !important;">
                <h4 class="fw-bold mb-4 text-uppercase" style="letter-spacing: 1px;">MY PROFILE</h4>
                
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted small text-uppercase d-block">Username</label>
                    <span class="fw-semibold fs-5"><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <label class="text-muted small text-uppercase d-block">Email</label>
                    <span class="fw-semibold fs-5"><?php echo htmlspecialchars($user['email'] ?? 'Not set'); ?></span>
                </div>
                <div class="mb-4 pb-3 border-bottom">
                    <label class="text-muted small text-uppercase d-block">Role</label>
                    <span class="badge bg-secondary"><?php echo htmlspecialchars(strtoupper($user['role'])); ?></span>
                </div>

                <div class="d-flex gap-2">
                    <a href="edit_profile.php" class="btn btn-brand-dark">Edit Profile</a>
                    <a href="change_password.php" class="btn btn-outline-dark rounded-0">Change Password</a>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>