<?php
session_start();
require 'db.php';

// Check if Guest is logged in and Booking ID is provided
if (!isset($_SESSION['guest_id']) || !isset($_GET['id'])) {
    header("Location: guest_dashboard.php");
    exit();
}

$g_id = $_SESSION['guest_id'];
$b_id = $conn->real_escape_string($_GET['id']);

// Fetch Booking details
$booking_query = $conn->query("SELECT * FROM Booking WHERE b_id = '$b_id' AND g_id = '$g_id'");
if ($booking_query->num_rows == 0) {
    header("Location: my_bookings.php");
    exit();
}
$booking = $booking_query->fetch_assoc();

$msg = '';

// Handle Payment Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_payment'])) {
    $pay_amount = $conn->real_escape_string($_POST['pay_amount']);
    $method = $conn->real_escape_string($_POST['method']);
    $bank_acc_no = $conn->real_escape_string($_POST['bank_acc_no']);

    if ($pay_amount > $booking['due_amount']) {
        $msg = "<div class='alert alert-error'><i class='ph ph-warning-circle'></i> You cannot pay more than the due amount!</div>";
    } elseif ($pay_amount <= 0) {
        $msg = "<div class='alert alert-error'><i class='ph ph-warning-circle'></i> Please enter a valid amount.</div>";
    } else {
        // 1. Insert into Payment table
        $sql_payment = "INSERT INTO Payment (b_id, method, bank_acc_no, p_amount, p_status) 
                        VALUES ('$b_id', '$method', '$bank_acc_no', '$pay_amount', 'Completed')";
        
        if ($conn->query($sql_payment) === TRUE) {
            // 2. Update Booking table (Add to paid, subtract from due)
            $new_paid = $booking['paid_amount'] + $pay_amount;
            $new_due = $booking['due_amount'] - $pay_amount;
            
            // If fully paid, optionally auto-confirm the booking
            $status_update = ($new_due == 0 && $booking['b_status'] == 'Pending') ? ", b_status = 'Confirmed'" : "";

            $conn->query("UPDATE Booking SET paid_amount = '$new_paid', due_amount = '$new_due' $status_update WHERE b_id = '$b_id'");
            
            $msg = "<div class='alert alert-success'><i class='ph ph-check-circle'></i> Payment of $$pay_amount successful!</div>";
            // Refresh booking data
            $booking = $conn->query("SELECT * FROM Booking WHERE b_id = '$b_id'")->fetch_assoc();
        } else {
            $msg = "<div class='alert alert-error'>Database Error: " . $conn->error . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout | Grand Premier</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        :root {
            --bg-body: #F4F7FE; --bg-card: #FFFFFF; --primary-dark: #0B1437;
            --primary-light: #4318FF; --text-main: #2B3674; --text-muted: #A3AED0;
            --border-light: #E0E5F2; --shadow-soft: 0px 18px 40px rgba(112, 144, 176, 0.12);
            --transition: all 0.3s ease; --accent-green: #05CD99; --accent-red: #EE5D50;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg-body); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 40px; }

        .checkout-wrapper { display: flex; background: var(--bg-card); border-radius: 24px; box-shadow: var(--shadow-soft); max-width: 900px; width: 100%; overflow: hidden; }
        
        .bill-summary { background: var(--primary-dark); color: white; padding: 40px; width: 40%; }
        .bill-summary h2 { font-size: 24px; margin-bottom: 30px; color: white; }
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 15px; color: rgba(255,255,255,0.8); }
        .summary-total { display: flex; justify-content: space-between; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2); font-size: 20px; font-weight: 700; color: white; }
        .due-highlight { font-size: 32px; color: #FFCE20; font-weight: 800; margin-top: 10px; }

        .payment-form { padding: 40px; width: 60%; }
        .payment-form h3 { font-size: 24px; color: var(--primary-dark); margin-bottom: 24px; }
        
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(5, 205, 153, 0.1); color: var(--accent-green); border: 1px solid rgba(5, 205, 153, 0.2); }
        .alert-error { background: rgba(238, 93, 80, 0.1); color: var(--accent-red); border: 1px solid rgba(238, 93, 80, 0.2); }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: var(--text-main); }
        .form-group input, .form-group select { width: 100%; padding: 14px; border-radius: 12px; border: 1px solid var(--border-light); font-size: 15px; outline: none; transition: var(--transition); }
        .form-group input:focus, .form-group select:focus { border-color: var(--primary-light); box-shadow: 0 0 0 4px rgba(67, 24, 255, 0.1); }

        .btn-pay { background: var(--primary-light); color: white; width: 100%; padding: 16px; border-radius: 12px; border: none; font-size: 16px; font-weight: 700; cursor: pointer; transition: var(--transition); display: flex; justify-content: center; gap: 10px; margin-top: 10px; }
        .btn-pay:hover { box-shadow: 0px 8px 20px rgba(67, 24, 255, 0.25); transform: translateY(-2px); }
        .btn-pay:disabled { background: var(--text-muted); cursor: not-allowed; box-shadow: none; transform: none; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: var(--text-muted); text-decoration: none; font-weight: 600; }
        .back-link:hover { color: var(--primary-dark); }
    </style>
</head>
<body>

    <div class="checkout-wrapper">
        <div class="bill-summary">
            <h2><i class="ph ph-receipt"></i> Booking #<?php echo htmlspecialchars($booking['b_id']); ?></h2>
            
            <div class="summary-item">
                <span>Total Amount:</span>
                <span>$<?php echo number_format($booking['total_amount'], 2); ?></span>
            </div>
            <div class="summary-item">
                <span>Amount Paid:</span>
                <span style="color: #05CD99;">$<?php echo number_format($booking['paid_amount'], 2); ?></span>
            </div>
            
            <div class="summary-total">
                <span>Remaining Due</span>
            </div>
            <div class="due-highlight">
                $<?php echo number_format($booking['due_amount'], 2); ?>
            </div>
        </div>

        <div class="payment-form">
            <h3>Payment Details</h3>
            
            <?php echo $msg; ?>

            <?php if ($booking['due_amount'] > 0): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="method" required>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Mobile Banking">Mobile Banking</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cash">Cash</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Account/Card Number (Optional)</label>
                    <input type="text" name="bank_acc_no" placeholder="Enter last 4 digits or phone number">
                </div>

                <div class="form-group">
                    <label>Amount to Pay ($)</label>
                    <input type="number" step="0.01" name="pay_amount" max="<?php echo htmlspecialchars($booking['due_amount']); ?>" value="<?php echo htmlspecialchars($booking['due_amount']); ?>" required>
                </div>

                <button type="submit" name="submit_payment" class="btn-pay">
                    <i class="ph ph-lock-key"></i> Process Secure Payment
                </button>
            </form>
            <?php else: ?>
                <div style="text-align: center; padding: 40px 0;">
                    <i class="ph ph-check-circle" style="font-size: 60px; color: var(--accent-green); margin-bottom: 15px;"></i>
                    <h4 style="font-size: 20px; color: var(--primary-dark);">Fully Paid</h4>
                    <p style="color: var(--text-muted);">Thank you! Your balance is zero.</p>
                </div>
            <?php endif; ?>

            <a href="my_bookings.php" class="back-link">Return to My Bookings</a>
        </div>
    </div>

</body>
</html>