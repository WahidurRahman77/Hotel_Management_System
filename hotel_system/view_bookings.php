<?php
session_start();
require 'db.php';

// Check if Admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$msg = '';

// Handle Booking Status Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $b_id = $conn->real_escape_string($_POST['b_id']);
    $new_status = $conn->real_escape_string($_POST['new_status']);
    
    $update_query = "UPDATE Booking SET b_status = '$new_status' WHERE b_id = '$b_id'";
    if ($conn->query($update_query) === TRUE) {
        $msg = "<div class='alert alert-success'><i class='ph ph-check-circle'></i> Booking #$b_id status updated to $new_status!</div>";
    } else {
        $msg = "<div class='alert alert-error'><i class='ph ph-warning-circle'></i> Error updating status: " . $conn->error . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bookings | Grand Premier Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        :root {
            --bg-body: #F4F7FE; --bg-card: #FFFFFF; --primary-dark: #0B1437;
            --primary-light: #4318FF; --accent-green: #05CD99; --accent-red: #EE5D50;
            --text-main: #2B3674; --text-muted: #A3AED0; --border-light: #E0E5F2;
            --shadow-soft: 0px 18px 40px rgba(112, 144, 176, 0.12);
            --transition: all 0.3s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); display: flex; min-height: 100vh; }

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

        .main-content { margin-left: 280px; padding: 40px 50px; width: calc(100% - 280px); }
        .header { margin-bottom: 40px; }
        .header h1 { font-size: 34px; font-weight: 700; color: var(--primary-dark); margin-bottom: 6px; }
        .header p { color: var(--text-muted); font-size: 15px; font-weight: 500; }

        .table-container { background: var(--bg-card); border-radius: 20px; padding: 24px; box-shadow: var(--shadow-soft); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th { text-align: left; padding: 16px 12px; color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid var(--border-light); }
        td { padding: 16px 12px; border-bottom: 1px solid var(--border-light); color: var(--primary-dark); font-weight: 600; font-size: 14px; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }

        .alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 30px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(5, 205, 153, 0.1); color: var(--accent-green); border: 1px solid rgba(5, 205, 153, 0.2); }
        .alert-error { background: rgba(238, 93, 80, 0.1); color: var(--accent-red); border: 1px solid rgba(238, 93, 80, 0.2); }

        .status-pill { padding: 6px 12px; border-radius: 30px; font-size: 12px; font-weight: 700; display: inline-block; text-align: center; }
        .status-pending { background: rgba(255, 181, 71, 0.1); color: #D48A1A; }
        .status-confirmed { background: rgba(5, 205, 153, 0.1); color: var(--accent-green); }
        .status-cancelled { background: rgba(238, 93, 80, 0.1); color: var(--accent-red); }

        .action-form { display: flex; gap: 10px; align-items: center; }
        .action-form select { padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-light); font-size: 13px; font-weight: 600; color: var(--primary-dark); outline: none; }
        .btn-update { background: var(--primary-light); color: white; padding: 8px 16px; border-radius: 8px; border: none; font-weight: 600; font-size: 13px; cursor: pointer; transition: var(--transition); }
        .btn-update:hover { box-shadow: 0px 4px 10px rgba(67, 24, 255, 0.2); transform: translateY(-1px); }
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
            <a href="manage_rooms.php" class="nav-item">
                <i class="ph ph-door"></i> Manage Rooms
            </a>
            <a href="view_bookings.php" class="nav-item active">
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
            <h1>Reservation Management</h1>
            <p>Review and update guest booking statuses.</p>
        </div>

        <?php echo $msg; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Guest Name</th>
                        <th>Room</th>
                        <th>Dates</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Added paid_amount and due_amount to SELECT, and fixed the JOIN _fk issues
                    $sql = "SELECT b.b_id, b.check_in, b.check_out, b.total_amount, b.paid_amount, b.due_amount, b.b_status, 
                                   g.g_f_name, g.g_l_name, 
                                   r.room_no 
                            FROM Booking b
                            JOIN Guest g ON b.g_id = g.g_id
                            JOIN Booking_Room br ON b.b_id = br.b_id
                            JOIN Room r ON br.room_id = r.room_id
                            ORDER BY b.b_date DESC";
                    
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            
                            $status_class = 'status-pending';
                            if ($row['b_status'] == 'Confirmed') $status_class = 'status-confirmed';
                            if ($row['b_status'] == 'Cancelled') $status_class = 'status-cancelled';

                            echo "<tr>
                                    <td>#{$row['b_id']}</td>
                                    <td>{$row['g_f_name']} {$row['g_l_name']}</td>
                                    <td>#{$row['room_no']}</td>
                                    <td><span style='font-size:12px; color:var(--text-muted);'>" . date('M d', strtotime($row['check_in'])) . " - " . date('M d', strtotime($row['check_out'])) . "</span></td>
                                    <td>\${$row['total_amount']}</td>
                                    <td style='color: #05CD99;'>\${$row['paid_amount']}</td>
                                    <td style='color: #EE5D50;'>\${$row['due_amount']}</td>
                                    <td><span class='status-pill {$status_class}'>{$row['b_status']}</span></td>
                                    <td>
                                        <form class='action-form' method='POST'>
                                            <input type='hidden' name='b_id' value='{$row['b_id']}'>
                                            <select name='new_status'>
                                                <option value='Pending' " . ($row['b_status'] == 'Pending' ? 'selected' : '') . ">Pending</option>
                                                <option value='Confirmed' " . ($row['b_status'] == 'Confirmed' ? 'selected' : '') . ">Confirmed</option>
                                                <option value='Cancelled' " . ($row['b_status'] == 'Cancelled' ? 'selected' : '') . ">Cancelled</option>
                                            </select>
                                            <button type='submit' name='update_status' class='btn-update'>Update</button>
                                        </form>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='9' style='text-align:center; padding: 30px; color: var(--text-muted);'>No bookings found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>