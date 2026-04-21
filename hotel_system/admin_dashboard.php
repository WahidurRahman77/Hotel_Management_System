<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch dynamic data for the dashboard statistics
$rooms_count = $conn->query("SELECT COUNT(*) as total FROM Room")->fetch_assoc()['total'];
$guests_count = $conn->query("SELECT COUNT(*) as total FROM Guest")->fetch_assoc()['total'];

// Note: If you haven't created the Booking table yet, you can comment this next line out.
$bookings_count = $conn->query("SELECT COUNT(*) as total FROM Booking")->fetch_assoc()['total']; 
// Fallback if booking table is empty or missing during testing:
if(!$bookings_count) $bookings_count = 0; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Grand Premier</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        :root {
            --bg-body: #F4F7FE;
            --bg-card: #FFFFFF;
            --primary-dark: #0B1437;
            --primary-light: #4318FF;
            --accent-green: #05CD99;
            --accent-orange: #FFCE20;
            --accent-red: #EE5D50;
            --text-main: #2B3674;
            --text-muted: #A3AED0;
            --border-light: #E0E5F2;
            --shadow-soft: 0px 18px 40px rgba(112, 144, 176, 0.12);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* --- Premium Sidebar --- */
        .sidebar {
            width: 280px;
            background: var(--bg-card);
            border-right: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            padding: 30px 20px;
            z-index: 10;
        }

        .brand {
            text-align: center;
            margin-bottom: 50px;
        }

        .brand h2 {
            font-size: 26px;
            font-weight: 800;
            color: var(--primary-dark);
            letter-spacing: -0.5px;
        }

        .brand p {
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .nav-links {
            display: flex;
            flex-direction: column;
            gap: 10px;
            flex: 1;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            border-radius: 12px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: var(--transition);
        }

        .nav-item i {
            font-size: 22px;
            margin-right: 14px;
        }

        .nav-item:hover {
            background: rgba(67, 24, 255, 0.05);
            color: var(--primary-light);
        }

        .nav-item.active {
            background: var(--primary-light);
            color: white;
            box-shadow: 0px 10px 20px rgba(67, 24, 255, 0.2);
        }

        .logout-btn {
            background: rgba(238, 93, 80, 0.1);
            color: var(--accent-red);
            margin-top: auto;
        }

        .logout-btn:hover {
            background: var(--accent-red);
            color: white;
            box-shadow: 0px 10px 20px rgba(238, 93, 80, 0.2);
        }

        /* --- Main Content Layout --- */
        .main-content {
            margin-left: 280px;
            padding: 40px 50px;
            width: calc(100% - 280px);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 34px;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 6px;
        }

        .header p {
            color: var(--text-muted);
            font-size: 15px;
            font-weight: 500;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            padding: 8px 16px;
            border-radius: 30px;
            box-shadow: var(--shadow-soft);
            font-weight: 700;
            color: var(--primary-dark);
        }

        .admin-profile i {
            font-size: 24px;
            color: var(--primary-light);
        }

        /* --- Metric Cards --- */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .metric-card {
            background: var(--bg-card);
            padding: 24px;
            border-radius: 20px;
            box-shadow: var(--shadow-soft);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
        }

        .metric-card:hover {
            transform: translateY(-5px);
        }

        .metric-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .icon-blue { background: rgba(67, 24, 255, 0.1); color: var(--primary-light); }
        .icon-green { background: rgba(5, 205, 153, 0.1); color: var(--accent-green); }
        .icon-orange { background: rgba(255, 206, 32, 0.1); color: var(--accent-orange); }

        .metric-info p {
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .metric-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-dark);
        }

        /* --- Data Table --- */
        .table-container {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--shadow-soft);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-header h3 {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .btn-primary {
            background: var(--primary-light);
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .btn-primary:hover {
            box-shadow: 0px 8px 16px rgba(67, 24, 255, 0.2);
            transform: translateY(-2px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 16px;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-light);
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--border-light);
            color: var(--primary-dark);
            font-weight: 600;
            font-size: 15px;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* Status Pills */
        .status-pill {
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            display: inline-block;
        }

        .status-available { background: rgba(5, 205, 153, 0.1); color: var(--accent-green); }
        .status-booked { background: rgba(238, 93, 80, 0.1); color: var(--accent-red); }
        .status-maintenance { background: rgba(255, 206, 32, 0.1); color: #D4A000; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand">
            <h2>Grand Premier</h2>
            <p>Admin Workspace</p>
        </div>
        <div class="nav-links">
            <a href="admin_dashboard.php" class="nav-item active">
                <i class="ph ph-squares-four"></i> Dashboard Overview
            </a>
            <a href="manage_rooms.php" class="nav-item">
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
            <div>
                <p>Welcome back, Administrator</p>
                <h1>System Overview</h1>
            </div>
            <div class="admin-profile">
                <i class="ph ph-user-circle"></i> Admin
            </div>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon icon-blue">
                    <i class="ph ph-bed"></i>
                </div>
                <div class="metric-info">
                    <p>Total Rooms</p>
                    <h3><?php echo $rooms_count; ?></h3>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon icon-green">
                    <i class="ph ph-users"></i>
                </div>
                <div class="metric-info">
                    <p>Registered Guests</p>
                    <h3><?php echo $guests_count; ?></h3>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon icon-orange">
                    <i class="ph ph-receipt"></i>
                </div>
                <div class="metric-info">
                    <p>Total Bookings</p>
                    <h3><?php echo $bookings_count; ?></h3>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h3>Live Room Status</h3>
                <a href="manage_rooms.php" class="btn-primary">
                    <i class="ph ph-plus-circle"></i> Add New Room
                </a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Room No</th>
                        <th>Category</th>
                        <th>Price/Night</th>
                        <th>Current Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rooms = $conn->query("SELECT * FROM Room LIMIT 8");
                    if($rooms && $rooms->num_rows > 0) {
                        while($row = $rooms->fetch_assoc()) {
                            // Assign pill colors based on status
                            if ($row['r_status'] == 'Available') {
                                $status_class = 'status-available';
                            } elseif ($row['r_status'] == 'Booked') {
                                $status_class = 'status-booked';
                            } else {
                                $status_class = 'status-maintenance';
                            }
                            
                            echo "<tr>
                                    <td>#{$row['room_no']}</td>
                                    <td><span style='color: var(--text-muted); font-weight: 500;'>{$row['room_type']}</span></td>
                                    <td>\${$row['price']}</td>
                                    <td><span class='status-pill {$status_class}'>{$row['r_status']}</span></td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center; color: var(--text-muted); font-weight:500; padding: 30px;'>No rooms found in database.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>
