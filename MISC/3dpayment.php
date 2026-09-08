<?php
session_start();
require_once '../config.php';

// Get amount from URL parameter if available
$default_amount = isset($_GET['amount']) ? $_GET['amount'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_code'])) {
        $entered_code = $_POST['verification_code'];
        $correct_code = '12345'; // Changed verification code to 12345
        
        if ($entered_code === $correct_code) {
            // Generate transaction ID
            $transaction_id = 'TXN' . time() . rand(1000, 9999);
            
            // Save transaction details
            $transaction_data = [
                'transaction_id' => $transaction_id,
                'amount' => $_SESSION['payment_amount'],
                'card_holder_name' => $_SESSION['card_holder_name'],
                'email' => $_SESSION['email'],
                'mobile_number' => $_SESSION['mobile_number'],
                'date' => date('Y-m-d H:i:s'),
                'status' => 'Completed'
            ];
            
            // Convert transaction data to JSON for JavaScript
            $transaction_json = json_encode($transaction_data);
            
            // Create HTML with JavaScript to store transaction and redirect
            $redirect_html = "
            <!DOCTYPE html>
            <html>
            <head>
                <title>Processing Payment</title>
                <script>
                    // Store transaction in localStorage
                    let transactions = JSON.parse(localStorage.getItem('transactions') || '[]');
                    transactions.push(" . $transaction_json . ");
                    localStorage.setItem('transactions', JSON.stringify(transactions));
                    
                    // Redirect to transaction history
                    window.location.href = 'transaction-history.html';
                </script>
            </head>
            <body>
                <p>Processing your payment...</p>
            </body>
            </html>";
            
            // Clear session data
            session_unset();
            session_destroy();
            
            // Output the redirect HTML
            echo $redirect_html;
            exit();
        } else {
            $error = "Invalid verification code";
        }
    } else {
        // Store payment details in session
        $_SESSION['payment_amount'] = $_POST['amount'];
        $_SESSION['card_holder_name'] = $_POST['card_holder_name'];
        $_SESSION['email'] = $_POST['email'];
        $_SESSION['mobile_number'] = $_POST['mobile_number'];
        
        // Redirect to verification page
        header("Location: " . $_SERVER['PHP_SELF'] . "?verify=1");
        exit();
    }
}

// Check if we should show verification page
$show_verification = isset($_GET['verify']) && $_GET['verify'] == 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Secure Payment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .payment-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            border-color: #4CAF50;
            outline: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .card-details {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 10px;
        }

        .verification-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }

        .verification-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .verification-code {
            display: flex;
            gap: 10px;
        }

        .verification-code input {
            flex: 1;
            text-align: center;
            letter-spacing: 5px;
            font-size: 20px;
        }

        .error-message {
            color: #f44336;
            margin-top: 5px;
            font-size: 14px;
        }

        .submit-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            width: 100%;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .submit-btn:hover {
            background: #45a049;
        }

        .security-info {
            margin-top: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        .security-info i {
            color: #4CAF50;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="header">
            <h1>3D Secure Payment</h1>
            <p>Please enter your payment details</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!$show_verification): ?>
            <form method="POST" action="" id="paymentForm">
                <div class="form-group">
                    <label for="amount">Amount</label>
                    <input type="number" id="amount" name="amount" required min="1" step="0.01" value="<?php echo htmlspecialchars($default_amount); ?>" <?php echo $default_amount ? 'readonly' : ''; ?>>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="card_holder_name">Card Holder Name</label>
                        <input type="text" id="card_holder_name" name="card_holder_name" required placeholder="Enter card holder name">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required placeholder="Enter your email">
                    </div>
                </div>

                <div class="form-group">
                    <label for="card_number">Card Number</label>
                    <input type="text" id="card_number" name="card_number" required maxlength="19" placeholder="1234 5678 9012 3456">
                </div>

                <div class="card-details">
                    <div class="form-group">
                        <label for="expiry">Expiry Date</label>
                        <input type="text" id="expiry" name="expiry" required pattern="(0[1-9]|1[0-2])\/([0-9]{2})" placeholder="MM/YY">
                    </div>
                    <div class="form-group">
                        <label for="cvv">CVV</label>
                        <input type="text" id="cvv" name="cvv" required pattern="[0-9]{3}" maxlength="3">
                    </div>
                </div>

                <div class="form-group">
                    <label for="mobile_number">Mobile Number</label>
                    <input type="tel" id="mobile_number" name="mobile_number" required pattern="[0-9]{11}" maxlength="11">
                </div>

                <button type="submit" class="submit-btn">Proceed to Verification</button>
            </form>
        <?php else: ?>
            <form method="POST" action="" id="verificationForm">
                <div class="verification-section">
                    <h3>Enter Verification Code</h3>
                    <p>A verification code has been sent to your mobile number</p>
                    <div class="verification-code">
                        <input type="text" name="verification_code" required pattern="[0-9]{5}" maxlength="5" placeholder="12345">
                    </div>
                </div>
                <input type="hidden" name="verify_code" value="1">
                <button type="submit" class="submit-btn">Verify & Pay</button>
            </form>
        <?php endif; ?>

        <div class="security-info">
            <i class="fas fa-lock"></i> Your payment information is secure
        </div>
    </div>

    <script>
        // Format card number with spaces
        document.getElementById('card_number')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = '';
            for(let i = 0; i < value.length; i++) {
                if(i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            e.target.value = formattedValue;
        });

        // Format expiry date
        document.getElementById('expiry')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            if (value.length >= 2) {
                value = value.substring(0,2) + '/' + value.substring(2);
            }
            e.target.value = value;
        });

        // Validate form before submission
        document.getElementById('paymentForm')?.addEventListener('submit', function(e) {
            const cardNumber = document.getElementById('card_number').value.replace(/\s+/g, '');
            const expiry = document.getElementById('expiry').value;
            const cvv = document.getElementById('cvv').value;
            const mobile = document.getElementById('mobile_number').value;

            if (cardNumber.length !== 16) {
                e.preventDefault();
                alert('Please enter a valid 16-digit card number');
                return;
            }

            if (!expiry.match(/^(0[1-9]|1[0-2])\/([0-9]{2})$/)) {
                e.preventDefault();
                alert('Please enter a valid expiry date (MM/YY)');
                return;
            }

            if (cvv.length !== 3) {
                e.preventDefault();
                alert('Please enter a valid 3-digit CVV');
                return;
            }

            if (mobile.length !== 11) {
                e.preventDefault();
                alert('Please enter a valid 11-digit mobile number');
                return;
            }
        });
    </script>
</body>
</html> 