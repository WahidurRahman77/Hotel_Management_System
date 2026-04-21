<?php
session_start();
require 'db.php';
if (!isset($_SESSION['guest_id'])) {
    header("Location: index.php");
    exit();
}

$g_id = $_SESSION['guest_id'];
$guest = $conn->query("SELECT g_f_name FROM Guest WHERE g_id = '$g_id'")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | Grand Premier</title>
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

        .table-container { background: var(--bg-card); border-radius: 20px; padding: 24px; box-shadow: var(--shadow-soft); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 16px; color: var(--text-muted); font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-light); }
        td { padding: 20px 16px; border-bottom: 1px solid var(--border-light); color: var(--primary-dark); font-weight: 600; font-size: 14px; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }

        .room-details { display: flex; align-items: center; gap: 15px; }
        .room-icon { background: rgba(67, 24, 255, 0.1); color: var(--primary-light); width: 45px; height: 45px; border-radius: 12px; display: flex; justify-content: center; align-items: center; font-size: 24px; }
        .room-info h4 { font-size: 15px; margin-bottom: 4px; }
        .room-info p { font-size: 13px; color: var(--text-muted); font-weight: 500; }

        .date-badge { background: #FAFCFE; border: 1px solid var(--border-light); padding: 8px 12px; border-radius: 8px; font-size: 13px; display: inline-block; color: var(--text-muted); }
        .date-badge strong { color: var(--primary-dark); display: block; font-size: 14px; margin-top: 2px; }

        .status-pill { padding: 6px 14px; border-radius: 30px; font-size: 13px; font-weight: 700; display: inline-block; }
        .status-pending { background: rgba(255, 181, 71, 0.1); color: #D48A1A; }
        .status-confirmed { background: rgba(5, 205, 153, 0.1); color: var(--accent-green); }
        .status-cancelled { background: rgba(238, 93, 80, 0.1); color: var(--accent-red); }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .empty-state i { font-size: 60px; margin-bottom: 20px; color: var(--border-light); }
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
            <a href="my_bookings.php" class="nav-item active">
                <i class="ph ph-calendar-check"></i> My Bookings
            </a>
            <a href="profile.php" class="nav-item">
                <i class="ph ph-user-circle"></i> Profile Setting
            </a>
        </div>
        <a href="logout.php" class="nav-item logout-btn">
            <i class="ph ph-sign-out"></i> Logout
        </a>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1>My Bookings</h1>
            <p>Manage and view your upcoming stays at Grand Premier.</p>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Accommodation</th>
                        <th>Dates of Stay</th>
                        <th>Total ($)</th>
                        <th>Paid ($)</th>
                        <th>Due ($)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Notice the removed _fk to match your actual database
                    $sql = "SELECT b.*, r.room_no, r.room_type 
                            FROM Booking b
                            JOIN Booking_Room br ON b.b_id = br.b_id
                            JOIN Room r ON br.room_id = r.room_id
                            WHERE b.g_id = '$g_id'
                            ORDER BY b.b_date DESC";
                    
                    $bookings = $conn->query($sql);

                    if($bookings && $bookings->num_rows > 0):
                        while($row = $bookings->fetch_assoc()): 
                            
                            $status_class = 'status-pending';
                            if ($row['b_status'] == 'Confirmed') $status_class = 'status-confirmed';
                            if ($row['b_status'] == 'Cancelled') $status_class = 'status-cancelled';
                    ?>
                        <tr>
                            <td>
                                <div class="room-details">
                                    <div class="room-icon"><i class="ph ph-bed"></i></div>
                                    <div class="room-info">
                                        <h4>Room #<?php echo htmlspecialchars($row['room_no']); ?></h4>
                                        <p><?php echo htmlspecialchars($row['room_type']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <div class="date-badge">In: <strong><?php echo date('M d', strtotime($row['check_in'])); ?></strong></div>
                                    <i class="ph ph-arrow-right" style="color: var(--text-muted);"></i>
                                    <div class="date-badge">Out: <strong><?php echo date('M d', strtotime($row['check_out'])); ?></strong></div>
                                </div>
                            </td>
                            <td><strong style="color: var(--primary-dark); font-size: 16px;">$<?php echo number_format($row['total_amount'], 2); ?></strong></td>
                            <td><span style="color: #05CD99; font-weight: 800;">$<?php echo number_format($row['paid_amount'], 2); ?></span></td>
                            <td><span style="color: #EE5D50; font-weight: 800;">$<?php echo number_format($row['due_amount'], 2); ?></span></td>
                            <td>
                                <span class="status-pill <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($row['b_status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="ph ph-suitcase-rolling"></i>
                                    <h3>No bookings found</h3>
                                    <p>You haven't made any reservations yet. <a href="guest_dashboard.php" style="color: var(--primary-light);">Explore our rooms</a> to book your first stay!</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>