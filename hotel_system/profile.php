<?php
session_start();
require 'db.php';
if (!isset($_SESSION['guest_id'])) {
    header("Location: index.php");
    exit();
}

$g_id = $_SESSION['guest_id'];
$msg = '';

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $g_phone = $conn->real_escape_string($_POST['g_phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $new_password = $_POST['new_password'];

    // Update Query Setup
    if (!empty($new_password)) {
        // If user wants to change password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE Guest SET g_phone = '$g_phone', address = '$address', g_password = '$hashed_password' WHERE g_id = '$g_id'";
    } else {
        // If password is left blank, only update other info
        $update_query = "UPDATE Guest SET g_phone = '$g_phone', address = '$address' WHERE g_id = '$g_id'";
    }

    if ($conn->query($update_query) === TRUE) {
        $msg = "<div class='alert alert-success'><i class='ph ph-check-circle'></i> Profile updated successfully!</div>";
    } else {
        $msg = "<div class='alert alert-error'><i class='ph ph-warning-circle'></i> Error updating profile: " . $conn->error . "</div>";
    }
}

// Fetch Current Guest Data (Fetch after update to get latest info)
$guest_data = $conn->query("SELECT * FROM Guest WHERE g_id = '$g_id'")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | Grand Premier</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        :root {
            --bg-body: #F4F7FE; --bg-card: #FFFFFF; --primary-dark: #0B1437;
            --primary-light: #4318FF; --accent-gold: #FFB547; --accent-green: #05CD99;
            --accent-red: #EE5D50; --text-main: #2B3674; --text-muted: #A3AED0;
            --border-light: #E0E5F2; --shadow-soft: 0px 18px 40px rgba(112, 144, 176, 0.12);
            --transition: all 0.3s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); display: flex; min-height: 100vh; }

        .sidebar { width: 280px; background: var(--bg-card); border-right: 1px solid var(--border-light); display: flex; flex-direction: column; position: fixed; height: 100vh; padding: 30px 20px; z-index: 10; }
        .brand { text-align: center; margin-bottom: 50px; }
        .brand h2 { font-size: 26px; font-weight: 800; color: var(--primary-dark); letter-spacing: -0.5px; }
        .brand span { color: var(--accent-gold); }
        .nav-links { display: flex; flex-direction: column; gap: 10px; flex: 1; }
        .nav-item { display: flex; align-items: center; padding: 14px 20px; border-radius: 12px; color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 15px; transition: var(--transition); }
        .nav-item i { font-size: 22px; margin-right: 14px; }
        .nav-item:hover { background: rgba(67, 24, 255, 0.05); color: var(--primary-light); }
        .nav-item.active { background: var(--primary-light); color: white; box-shadow: 0px 10px 20px rgba(67, 24, 255, 0.2); }
        .logout-btn { background: rgba(226, 232, 240, 0.5); color: #E31A1A; margin-top: auto; }

        .main-content { margin-left: 280px; padding: 40px 50px; width: calc(100% - 280px); }
        .header { margin-bottom: 40px; }
        .header h1 { font-size: 34px; font-weight: 700; color: var(--primary-dark); margin-bottom: 6px; }
        .header p { color: var(--text-muted); font-size: 15px; font-weight: 500; }

        .profile-card { background: var(--bg-card); border-radius: 20px; padding: 40px; box-shadow: var(--shadow-soft); max-width: 800px; }
        
        .alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 30px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(5, 205, 153, 0.1); color: var(--accent-green); border: 1px solid rgba(5, 205, 153, 0.2); }
        .alert-error { background: rgba(238, 93, 80, 0.1); color: var(--accent-red); border: 1px solid rgba(238, 93, 80, 0.2); }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .input-group { display: flex; flex-direction: column; gap: 8px; }
        .input-group.full-width { grid-column: span 2; }
        
        .input-group label { font-size: 14px; font-weight: 600; color: var(--text-main); }
        .input-group input, .input-group textarea { 
            padding: 14px 16px; border-radius: 12px; border: 1px solid var(--border-light); 
            background: #FAFCFE; font-size: 15px; color: var(--primary-dark); outline: none; transition: var(--transition);
        }
        .input-group input:focus, .input-group textarea:focus { border-color: var(--primary-light); box-shadow: 0 0 0 4px rgba(67, 24, 255, 0.1); }
        .input-group input[readonly] { background: #E0E5F2; cursor: not-allowed; color: var(--text-muted); }

        .btn-submit { background: var(--primary-light); color: white; padding: 14px 24px; border-radius: 12px; border: none; font-weight: 600; font-size: 15px; cursor: pointer; transition: var(--transition); margin-top: 30px; display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; max-width: 250px; }
        .btn-submit:hover { box-shadow: 0px 8px 16px rgba(67, 24, 255, 0.2); transform: translateY(-2px); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand">
            <h2>Grand<span>Premier</span></h2>
        </div>
        <div class="nav-links">
            <a href="guest_dashboard.php" class="nav-item">
                <i class="ph ph-squares-four"></i> Explore Rooms
            </a>
            <a href="my_bookings.php" class="nav-item">
                <i class="ph ph-calendar-check"></i> My Bookings
            </a>
            <a href="profile.php" class="nav-item active">
                <i class="ph ph-user-circle"></i> Profile Setting
            </a>
        </div>
        <a href="logout.php" class="nav-item logout-btn">
            <i class="ph ph-sign-out"></i> Logout
        </a>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1>Profile Settings</h1>
            <p>Update your personal information and security details.</p>
        </div>

        <div class="profile-card">
            <?php echo $msg; ?>

            <form method="POST" action="">
                <div class="form-grid">
                    <div class="input-group">
                        <label>First Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($guest_data['g_f_name']); ?>" readonly>
                    </div>
                    <div class="input-group">
                        <label>Last Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($guest_data['g_l_name']); ?>" readonly>
                    </div>
                    <div class="input-group full-width">
                        <label>Email Address</label>
                        <input type="email" value="<?php echo htmlspecialchars($guest_data['g_email']); ?>" readonly>
                    </div>

                    <div class="input-group full-width">
                        <h3 style="margin-top: 20px; font-size: 18px; color: var(--primary-dark); border-bottom: 1px solid var(--border-light); padding-bottom: 10px;">Editable Information</h3>
                    </div>

                    <div class="input-group">
                        <label>Phone Number</label>
                        <input type="text" name="g_phone" value="<?php echo htmlspecialchars($guest_data['g_phone']); ?>" required>
                    </div>
                    <div class="input-group">
                        <label>New Password (Leave blank to keep current)</label>
                        <input type="password" name="new_password" placeholder="••••••••">
                    </div>
                    <div class="input-group full-width">
                        <label>Home Address</label>
                        <textarea name="address" rows="3" required><?php echo htmlspecialchars($guest_data['address']); ?></textarea>
                    </div>
                </div>
                
                <button type="submit" name="update_profile" class="btn-submit">
                    <i class="ph ph-floppy-disk"></i> Save Changes
                </button>
            </form>
        </div>
    </main>

</body>
</html>