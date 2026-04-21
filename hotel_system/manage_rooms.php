<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$msg = '';
$msgType = '';

// Handle Form Submission for Adding a Room
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_room'])) {
    $room_no = $conn->real_escape_string($_POST['room_no']);
    $room_type = $conn->real_escape_string($_POST['room_type']);
    $price = $conn->real_escape_string($_POST['price']);
    $r_status = $conn->real_escape_string($_POST['r_status']);

    // Check if room number already exists
    $check = $conn->query("SELECT * FROM Room WHERE room_no = '$room_no'");
    if ($check->num_rows > 0) {
        $msg = "Room number already exists!";
        $msgType = "error";
    } else {
        $sql = "INSERT INTO Room (room_no, room_type, price, r_status) 
                VALUES ('$room_no', '$room_type', '$price', '$r_status')";
        if ($conn->query($sql) === TRUE) {
            $msg = "Room successfully added to the system.";
            $msgType = "success";
        } else {
            $msg = "Database Error: " . $conn->error;
            $msgType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms | Grand Premier Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        /* Sharing the exact same root variables as the dashboard */
        :root {
            --bg-body: #F4F7FE;
            --bg-card: #FFFFFF;
            --primary-dark: #0B1437;
            --primary-light: #4318FF;
            --accent-green: #05CD99;
            --accent-red: #EE5D50;
            --text-main: #2B3674;
            --text-muted: #A3AED0;
            --border-light: #E0E5F2;
            --shadow-soft: 0px 18px 40px rgba(112, 144, 176, 0.12);
            --transition: all 0.3s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); display: flex; min-height: 100vh; }

        /* --- Sidebar (Identical to Dashboard) --- */
        .sidebar { width: 280px; background: var(--bg-card); border-right: 1px solid var(--border-light); display: flex; flex-direction: column; position: fixed; height: 100vh; padding: 30px 20px; z-index: 10; }
        .brand { text-align: center; margin-bottom: 50px; }
        .brand h2 { font-size: 26px; font-weight: 800; color: var(--primary-dark); letter-spacing: -0.5px; }
        .brand p { color: var(--text-muted); font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; margin-top: 5px; }
        .nav-links { display: flex; flex-direction: column; gap: 10px; flex: 1; }
        .nav-item { display: flex; align-items: center; padding: 14px 20px; border-radius: 12px; color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 15px; transition: var(--transition); }
        .nav-item i { font-size: 22px; margin-right: 14px; }
        .nav-item:hover { background: rgba(67, 24, 255, 0.05); color: var(--primary-light); }
        .nav-item.active { background: var(--primary-light); color: white; box-shadow: 0px 10px 20px rgba(67, 24, 255, 0.2); }
        .logout-btn { background: rgba(238, 93, 80, 0.1); color: var(--accent-red); margin-top: auto; }

        /* --- Main Content --- */
        .main-content { margin-left: 280px; padding: 40px 50px; width: calc(100% - 280px); }
        .header { margin-bottom: 40px; }
        .header h1 { font-size: 34px; font-weight: 700; color: var(--primary-dark); margin-bottom: 6px; }
        .header p { color: var(--text-muted); font-size: 15px; font-weight: 500; }

        /* --- Alerts --- */
        .alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 30px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(5, 205, 153, 0.1); color: var(--accent-green); border: 1px solid rgba(5, 205, 153, 0.2); }
        .alert-error { background: rgba(238, 93, 80, 0.1); color: var(--accent-red); border: 1px solid rgba(238, 93, 80, 0.2); }

        /* --- Form Container --- */
        .form-card { background: var(--bg-card); border-radius: 20px; padding: 30px; box-shadow: var(--shadow-soft); margin-bottom: 40px; }
        .form-card h3 { font-size: 20px; color: var(--primary-dark); margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        
        .input-group { display: flex; flex-direction: column; gap: 8px; }
        .input-group label { font-size: 14px; font-weight: 600; color: var(--text-main); }
        .input-group input, .input-group select { 
            padding: 14px 16px; 
            border-radius: 12px; 
            border: 1px solid var(--border-light); 
            background: #FAFCFE;
            font-size: 15px; 
            color: var(--primary-dark);
            outline: none;
            transition: var(--transition);
        }
        .input-group input:focus, .input-group select:focus { border-color: var(--primary-light); box-shadow: 0 0 0 4px rgba(67, 24, 255, 0.1); }
        
        .btn-submit { background: var(--primary-light); color: white; padding: 14px 24px; border-radius: 12px; border: none; font-weight: 600; font-size: 15px; cursor: pointer; transition: var(--transition); margin-top: 24px; width: 100%; display: flex; justify-content: center; align-items: center; gap: 8px; }
        .btn-submit:hover { box-shadow: 0px 8px 16px rgba(67, 24, 255, 0.2); transform: translateY(-2px); }

    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand">
            <h2>Grand Premier</h2>
            <p>Admin Workspace</p>
        </div>
        <div class="nav-links">
            <a href="admin_dashboard.php" class="nav-item">
                <i class="ph ph-squares-four"></i> Dashboard Overview
            </a>
            <a href="manage_rooms.php" class="nav-item active">
                <i class="ph ph-door"></i> Manage Rooms
            </a>
            <a href="view_bookings.php" class="nav-item">
                <i class="ph ph-calendar-check"></i> Bookings
            </a>
            <a href="view_guests.php" class="nav-item">
                <i class="ph ph-users"></i> Guest Directory
            </a>
        </div>
        <a href="logout.php" class="nav-item logout-btn">
            <i class="ph ph-sign-out"></i> Logout
        </a>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1>Manage Inventory</h1>
            <p>Add and configure new rooms for your guests.</p>
        </div>

        <?php if($msg): ?>
            <div class="alert alert-<?php echo $msgType; ?>">
                <i class="ph <?php echo $msgType == 'success' ? 'ph-check-circle' : 'ph-warning-circle'; ?>" style="font-size: 20px;"></i>
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <h3><i class="ph ph-plus-circle" style="color: var(--primary-light); font-size: 24px;"></i> Add New Room</h3>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="input-group">
                        <label>Room Number</label>
                        <input type="text" name="room_no" placeholder="e.g. 101, 102A" required>
                    </div>
                    
                    <div class="input-group">
                        <label>Room Category</label>
                        <select name="room_type" required>
                            <option value="" disabled selected>Select Category</option>
                            <option value="Single Room">Single Room</option>
                            <option value="Double Room">Double Room</option>
                            <option value="Deluxe Suite">Deluxe Suite</option>
                            <option value="Presidential Suite">Presidential Suite</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Price Per Night ($)</label>
                        <input type="number" step="0.01" name="price" placeholder="e.g. 150.00" required>
                    </div>

                    <div class="input-group">
                        <label>Initial Status</label>
                        <select name="r_status" required>
                            <option value="Available" selected>Available (Ready for booking)</option>
                            <option value="Maintenance">Under Maintenance</option>
                            <option value="Booked">Already Booked</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" name="add_room" class="btn-submit">
                    <i class="ph ph-floppy-disk"></i> Save Room to Database
                </button>
            </form>
        </div>
    </main>

</body>
</html>