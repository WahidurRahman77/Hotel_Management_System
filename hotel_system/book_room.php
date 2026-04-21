<?php
session_start();
require 'db.php';

if (!isset($_SESSION['guest_id']) || !isset($_GET['id'])) {
    header("Location: guest_dashboard.php");
    exit();
}

$room_id = $conn->real_escape_string($_GET['id']);
$g_id = $_SESSION['guest_id'];

// Fetch Room Details
$room_query = $conn->query("SELECT * FROM Room WHERE room_id = '$room_id' AND r_status = 'Available'");
if ($room_query->num_rows == 0) {
    header("Location: guest_dashboard.php?error=unavailable");
    exit();
}
$room = $room_query->fetch_assoc();

$msg = '';

// Handle Combined Booking & Payment
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $pay_amount = $_POST['pay_amount'];
    $method = $_POST['method'];
    $bank_acc_no = $conn->real_escape_string($_POST['bank_acc_no']);

    $date1 = new DateTime($check_in);
    $date2 = new DateTime($check_out);
    $interval = $date1->diff($date2);
    $nights = $interval->days;

    if ($nights <= 0) {
        $msg = "<div class='alert'><i class='ph ph-warning-circle'></i> Check-out date must be after check-in date.</div>";
    } else {
        $total_amount = $nights * $room['price'];
        $min_advance = $total_amount / 2; // 50% Advance Required

        if ($pay_amount < $min_advance) {
            $msg = "<div class='alert'><i class='ph ph-warning-circle'></i> You must pay at least 50% ($" . number_format($min_advance, 2) . ") to confirm booking.</div>";
        } elseif ($pay_amount > $total_amount) {
            $msg = "<div class='alert'><i class='ph ph-warning-circle'></i> You cannot pay more than the total amount.</div>";
        } else {
            $due_amount = $total_amount - $pay_amount;
            // Since they are paying, status becomes Confirmed immediately
            $b_status = 'Confirmed'; 

            // 1. Insert into Booking table
            $sql_booking = "INSERT INTO Booking (g_id, check_in, check_out, total_amount, paid_amount, due_amount, b_status) 
                            VALUES ('$g_id', '$check_in', '$check_out', '$total_amount', '$pay_amount', '$due_amount', '$b_status')";
            
            if ($conn->query($sql_booking) === TRUE) {
                $booking_id = $conn->insert_id;

                // 2. Insert into Booking_Room relationship table
                $conn->query("INSERT INTO Booking_Room (b_id, room_id) VALUES ('$booking_id', '$room_id')");

                // 3. Update Room status
                $conn->query("UPDATE Room SET r_status = 'Booked' WHERE room_id = '$room_id'");

                // 4. Insert into Payment table
                $conn->query("INSERT INTO Payment (b_id, method, bank_acc_no, p_amount, p_status) VALUES ('$booking_id', '$method', '$bank_acc_no', '$pay_amount', 'Completed')");

                // Redirect to success
                header("Location: my_bookings.php?status=success");
                exit();
            } else {
                $msg = "<div class='alert'><i class='ph ph-warning-circle'></i> Database Error: " . $conn->error . "</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Booking & Payment | Grand Premier</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        :root { --bg-body: #F4F7FE; --bg-card: #FFFFFF; --primary-dark: #0B1437; --primary-light: #4318FF; --text-main: #2B3674; --text-muted: #A3AED0; --border-light: #E0E5F2; --shadow-soft: 0px 18px 40px rgba(112, 144, 176, 0.12); --transition: all 0.3s ease; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 40px; }

        .booking-container { display: flex; background: var(--bg-card); border-radius: 24px; box-shadow: var(--shadow-soft); max-width: 1000px; width: 100%; overflow: hidden; }
        .room-summary { background: linear-gradient(135deg, var(--primary-dark) 0%, #1a237e 100%); color: white; padding: 40px; width: 35%; display: flex; flex-direction: column; justify-content: space-between; }
        .room-summary h2 { font-size: 28px; margin-bottom: 10px; font-weight: 700; }
        .room-badge { background: rgba(255,255,255,0.2); padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-block; margin-bottom: 30px; }
        .price-display { font-size: 36px; font-weight: 800; color: #FFB547; margin: 20px 0; }
        .feature-list { list-style: none; margin-top: 30px; }
        .feature-list li { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; font-size: 15px; color: rgba(255,255,255,0.9); }

        .booking-form-area { padding: 40px; width: 65%; overflow-y: auto; max-height: 90vh; }
        .booking-form-area h3 { font-size: 24px; color: var(--primary-dark); margin-bottom: 24px; border-bottom: 2px solid var(--border-light); padding-bottom: 10px;}
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; background: rgba(238, 93, 80, 0.1); color: #EE5D50; display: flex; align-items: center; gap: 10px; font-weight: 600; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: var(--text-main); }
        .form-group input, .form-group select { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border-light); font-size: 14px; outline: none; transition: var(--transition); background: #FAFCFE; }
        .form-group input:focus, .form-group select:focus { border-color: var(--primary-light); box-shadow: 0 0 0 4px rgba(67, 24, 255, 0.1); }

        .bill-box { background: rgba(67, 24, 255, 0.05); padding: 20px; border-radius: 12px; margin-bottom: 24px; border: 1px dashed var(--primary-light); }
        .bill-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 15px; font-weight: 600; }
        
        .btn-submit { background: var(--primary-light); color: white; width: 100%; padding: 16px; border-radius: 12px; border: none; font-size: 16px; font-weight: 700; cursor: pointer; transition: var(--transition); }
        .btn-submit:hover { box-shadow: 0px 8px 20px rgba(67, 24, 255, 0.25); transform: translateY(-2px); }
    </style>
</head>
<body>

    <div class="booking-container">
        <div class="room-summary">
            <div>
                <span class="room-badge"><?php echo htmlspecialchars($room['room_type']); ?></span>
                <h2>Room #<?php echo htmlspecialchars($room['room_no']); ?></h2>
                <div class="price-display">$<?php echo htmlspecialchars($room['price']); ?> <span style="font-size:14px; color:#ddd;">/ night</span></div>
                <ul class="feature-list">
                    <li><i class="ph ph-check-circle" style="color: #05CD99;"></i> 50% Advance to Confirm</li>
                    <li><i class="ph ph-check-circle" style="color: #05CD99;"></i> Secure Payment</li>
                </ul>
            </div>
        </div>

        <div class="booking-form-area">
            <?php echo $msg; ?>
            <form method="POST" action="">
                
                <h3>1. Select Dates</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Check-in Date</label>
                        <input type="date" id="check_in" name="check_in" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Check-out Date</label>
                        <input type="date" id="check_out" name="check_out" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                </div>

                <div class="bill-box">
                    <div class="bill-row"><span>Total Amount:</span> <span id="display_total">$0.00</span></div>
                    <div class="bill-row" style="color: #EE5D50; font-size: 13px;"><span>Min Advance Required (50%):</span> <span id="display_advance">$0.00</span></div>
                </div>

                <h3>2. Payment Details</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="method" required>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Mobile Banking">Mobile Banking</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Account/Card No</label>
                        <input type="text" name="bank_acc_no" placeholder="Last 4 digits" required>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Amount to Pay Now ($)</label>
                        <input type="number" step="0.01" id="pay_amount" name="pay_amount" placeholder="Enter amount to pay" required>
                    </div>
                </div>

                <button type="submit" class="btn-submit"><i class="ph ph-lock-key"></i> Confirm Booking & Pay</button>
                <a href="guest_dashboard.php" style="display:block; text-align:center; margin-top:15px; color:var(--text-muted); text-decoration:none;">Cancel</a>
            </form>
        </div>
    </div>

    <script>
        const checkIn = document.getElementById('check_in');
        const checkOut = document.getElementById('check_out');
        const totalDisplay = document.getElementById('display_total');
        const advanceDisplay = document.getElementById('display_advance');
        const payAmountInput = document.getElementById('pay_amount');
        const nightlyRate = <?php echo $room['price']; ?>;

        function calculateBill() {
            if (checkIn.value && checkOut.value) {
                const date1 = new Date(checkIn.value);
                const date2 = new Date(checkOut.value);
                const diffTime = Math.abs(date2 - date1);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                
                if (diffDays > 0 && date2 > date1) {
                    let total = diffDays * nightlyRate;
                    let advance = total / 2;
                    totalDisplay.innerText = '$' + total.toFixed(2);
                    advanceDisplay.innerText = '$' + advance.toFixed(2);
                    
                    // Auto-fill payment with minimum advance
                    payAmountInput.min = advance.toFixed(2);
                    payAmountInput.max = total.toFixed(2);
                    payAmountInput.value = advance.toFixed(2);
                }
            }
        }

        checkIn.addEventListener('change', calculateBill);
        checkOut.addEventListener('change', calculateBill);
    </script>
</body>
</html>