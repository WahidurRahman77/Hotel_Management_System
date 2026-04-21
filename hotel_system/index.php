<?php
session_start();
require 'db.php';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $login_type = $_POST['login_type'];

    if ($login_type == 'admin') {
        $result = $conn->query("SELECT * FROM Admin WHERE email='$email'");
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['admin_id'] = $row['adm_id'];
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error = "Invalid Admin Password!";
            }
        } else {
            $error = "Admin account not found!";
        }
    } elseif ($login_type == 'guest') {
        $result = $conn->query("SELECT * FROM Guest WHERE g_email='$email'");
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['g_password'])) {
                $_SESSION['guest_id'] = $row['g_id'];
                header("Location: guest_dashboard.php");
                exit();
            } else {
                $error = "Invalid Guest Password!";
            }
        } else {
            $error = "Guest account not found!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hotel Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h2>Welcome Back</h2>
    
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="toggle-btn-group">
        <button class="toggle-btn active" id="btn-guest" onclick="switchTab('guest')">Guest Login</button>
        <button class="toggle-btn" id="btn-admin" onclick="switchTab('admin')">Admin Login</button>
    </div>

    <form id="guest-form" method="POST" action="">
        <input type="hidden" name="login_type" value="guest">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn">Login as Guest</button>
        <div class="links">
            <p>Don't have an account? <a href="register_guest.php">Register as Guest</a></p>
        </div>
    </form>

    <form id="admin-form" method="POST" action="" style="display: none;">
        <input type="hidden" name="login_type" value="admin">
        <div class="form-group">
            <label>Admin Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn">Login as Admin</button>
        <div class="links">
            <p>New Admin? <a href="register_admin.php">Register as Admin</a></p>
        </div>
    </form>
</div>

<script>
function switchTab(tab) {
    if (tab === 'guest') {
        document.getElementById('guest-form').style.display = 'block';
        document.getElementById('admin-form').style.display = 'none';
        document.getElementById('btn-guest').classList.add('active');
        document.getElementById('btn-admin').classList.remove('active');
    } else {
        document.getElementById('guest-form').style.display = 'none';
        document.getElementById('admin-form').style.display = 'block';
        document.getElementById('btn-admin').classList.add('active');
        document.getElementById('btn-guest').classList.remove('active');
    }
}
</script>

</body>
</html>
