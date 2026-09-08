<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Payment</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 600px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .payment-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .payment-details p {
            margin: 10px 0;
            color: #333;
            font-size: 16px;
        }

        .payment-details .amount {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
        }

        .error-message {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>3D Payment</h1>
        
        <div class="payment-details">
            <p><strong>Course:</strong> <span id="courseName">Web Development</span></p>
            <p><strong>Amount:</strong> <span class="amount" id="amount">$99.99</span></p>
        </div>

        <form id="paymentForm" onsubmit="return validateForm(event)">
            <div class="form-group">
                <label for="cardNumber">Card Number</label>
                <input type="text" id="cardNumber" name="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19">
                <div class="error-message" id="cardNumberError"></div>
            </div>

            <div class="form-group">
                <label for="cardHolder">Card Holder Name</label>
                <input type="text" id="cardHolder" name="cardHolder" placeholder="John Doe" maxlength="50">
                <div class="error-message" id="cardHolderError"></div>
            </div>

            <div class="form-group">
                <label for="expiryDate">Expiry Date</label>
                <input type="text" id="expiryDate" name="expiryDate" placeholder="MM/YY" maxlength="5">
                <div class="error-message" id="expiryDateError"></div>
            </div>

            <div class="form-group">
                <label for="cvv">CVV</label>
                <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="4">
                <div class="error-message" id="cvvError"></div>
            </div>

            <button type="submit" class="submit-btn">Pay Now</button>
        </form>
        <a href="dashboard.html" class="back-link">Back to Dashboard</a>
    </div>

    <script>
        // Get course details from URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const courseName = urlParams.get('course') || 'Web Development';
        const amount = urlParams.get('amount') || '$99.99';

        // Update course details
        document.getElementById('courseName').textContent = courseName;
        document.getElementById('amount').textContent = amount;

        function validateForm(event) {
            event.preventDefault();
            let isValid = true;

            // Reset all error messages
            document.querySelectorAll('.error-message').forEach(error => {
                error.style.display = 'none';
            });

            // Validate Card Number
            const cardNumber = document.getElementById('cardNumber').value.trim();
            const cardNumberRegex = /^[0-9]{16}$/;
            if (cardNumber === '') {
                showError('cardNumberError', 'Card number is required');
                isValid = false;
            } else if (!cardNumberRegex.test(cardNumber.replace(/\s/g, ''))) {
                showError('cardNumberError', 'Please enter a valid 16-digit card number');
                isValid = false;
            }

            // Validate Card Holder Name
            const cardHolder = document.getElementById('cardHolder').value.trim();
            if (cardHolder === '') {
                showError('cardHolderError', 'Card holder name is required');
                isValid = false;
            } else if (cardHolder.length < 3) {
                showError('cardHolderError', 'Please enter a valid name');
                isValid = false;
            }

            // Validate Expiry Date
            const expiryDate = document.getElementById('expiryDate').value.trim();
            const expiryDateRegex = /^(0[1-9]|1[0-2])\/([0-9]{2})$/;
            if (expiryDate === '') {
                showError('expiryDateError', 'Expiry date is required');
                isValid = false;
            } else if (!expiryDateRegex.test(expiryDate)) {
                showError('expiryDateError', 'Please enter a valid expiry date (MM/YY)');
                isValid = false;
            } else {
                // Check if card is expired
                const [month, year] = expiryDate.split('/');
                const currentDate = new Date();
                const currentYear = currentDate.getFullYear() % 100;
                const currentMonth = currentDate.getMonth() + 1;
                
                if (parseInt(year) < currentYear || 
                    (parseInt(year) === currentYear && parseInt(month) < currentMonth)) {
                    showError('expiryDateError', 'Card has expired');
                    isValid = false;
                }
            }

            // Validate CVV
            const cvv = document.getElementById('cvv').value.trim();
            const cvvRegex = /^[0-9]{3,4}$/;
            if (cvv === '') {
                showError('cvvError', 'CVV is required');
                isValid = false;
            } else if (!cvvRegex.test(cvv)) {
                showError('cvvError', 'Please enter a valid CVV (3 or 4 digits)');
                isValid = false;
            }

            if (isValid) {
                submitPayment();
            }

            return false;
        }

        function showError(elementId, message) {
            const errorElement = document.getElementById(elementId);
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }

        function submitPayment() {
            const formData = {
                courseName: document.getElementById('courseName').textContent,
                amount: document.getElementById('amount').textContent,
                cardNumber: document.getElementById('cardNumber').value.trim(),
                cardHolder: document.getElementById('cardHolder').value.trim(),
                expiryDate: document.getElementById('expiryDate').value.trim(),
                cvv: document.getElementById('cvv').value.trim()
            };

            // Disable submit button
            const submitBtn = document.querySelector('.submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';

            // Send data to server
            fetch('../../CONTROLLAR/process/process_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    alert('Payment successful!');
                    window.location.href = 'dashboard.html';
                } else {
                    alert(data.message || 'Payment failed. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Pay Now';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Pay Now';
            });
        }

        // Format card number with spaces
        document.getElementById('cardNumber').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            if (value.length > 16) value = value.slice(0, 16);
            let formattedValue = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) formattedValue += ' ';
                formattedValue += value[i];
            }
            e.target.value = formattedValue;
        });

        // Format expiry date
        document.getElementById('expiryDate').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            e.target.value = value;
        });

        // Only allow numbers in CVV
        document.getElementById('cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });

        // Real-time validation
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function() {
                const errorElement = document.getElementById(this.id + 'Error');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 