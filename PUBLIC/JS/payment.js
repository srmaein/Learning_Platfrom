/**
 * Real Payment System JS Handler
 * Enforces PostgreSQL Backend Course Price & Real Payment Verification
 */
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const courseId = urlParams.get('course_id') || urlParams.get('courseId');

    const selectedCourseElement = document.getElementById('selected-course');
    const courseNameInput = document.getElementById('course_name');
    const amountInput = document.getElementById('amount');
    const coursePriceElement = document.getElementById('course-price');
    const totalAmountElement = document.getElementById('total-amount');

    let currentTransactionId = null;

    // Check user session first
    fetch('../VIEWS/auth/check_session.php?check_session=1')
        .then(res => res.json())
        .then(sessionData => {
            if (!sessionData.valid) {
                alert("Please log in to enroll and make payments.");
                window.location.href = '../login.php';
                return;
            }
            
            // Prefill user email if available
            const emailInput = document.getElementById('email');
            if (emailInput && sessionData.data && sessionData.data.username) {
                if (sessionData.data.username.includes('@')) {
                    emailInput.value = sessionData.data.username;
                }
            }
        });

    if (courseId) {
        // Fetch authoritative course details & price from PostgreSQL
        fetch(`../CONTROLLAR/process/process_course.php?action=get_course&id=${courseId}`)
            .then(res => res.json())
            .then(resData => {
                if (resData.status === 'success' && resData.data) {
                    const course = resData.data;
                    const price = parseFloat(course.price);

                    if (selectedCourseElement) selectedCourseElement.textContent = course.title;
                    if (courseNameInput) {
                        courseNameInput.value = course.title;
                        courseNameInput.readOnly = true;
                    }

                    if (amountInput) {
                        amountInput.value = price.toFixed(2);
                        amountInput.readOnly = true;
                        amountInput.style.backgroundColor = "#f8f8f8";
                    }

                    if (coursePriceElement) coursePriceElement.textContent = `BDT ${price.toFixed(2)}`;
                    if (totalAmountElement) totalAmountElement.textContent = `BDT ${price.toFixed(2)}`;

                    // Initiate payment session in backend
                    initiatePaymentSession(course.id);
                }
            })
            .catch(err => console.error("Error fetching course details:", err));
    }

    function initiatePaymentSession(cId) {
        const formData = new FormData();
        formData.append('course_id', cId);

        fetch('../CONTROLLAR/process/process_payment.php?action=initiate', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(resData => {
            if (resData.status === 'success') {
                if (resData.already_enrolled) {
                    alert(resData.message);
                    window.location.href = '../MODELS/index.html';
                    return;
                }
                currentTransactionId = resData.data.transaction_id;
                const txInput = document.getElementById('transaction_id');
                if (txInput && !txInput.value) {
                    txInput.value = currentTransactionId;
                }
            }
        })
        .catch(err => console.error("Error initiating payment session:", err));
    }

    // Payment form submission
    const paymentForm = document.getElementById('payment-form');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const confirmBtn = document.querySelector('.confirm-btn');
            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying Payment...';
            }

            let selectedMethod = 'bKash';
            const methodInputs = document.querySelectorAll('input[name="payment_method"]');
            methodInputs.forEach(input => {
                if (input.checked) selectedMethod = input.value;
            });

            const userProvidedTxId = document.getElementById('transaction_id').value.trim();

            const formData = new FormData();
            formData.append('transaction_id', currentTransactionId || userProvidedTxId);
            formData.append('user_provided_txid', userProvidedTxId);
            formData.append('payment_method', selectedMethod);

            fetch('../CONTROLLAR/process/process_payment.php?action=confirm', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(resData => {
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Payment';
                }

                if (resData.status === 'success') {
                    document.getElementById('modal-transaction-id').textContent = resData.transaction_id || userProvidedTxId;
                    document.getElementById('success-modal').style.display = 'block';
                } else {
                    alert(resData.message || 'Payment verification failed.');
                }
            })
            .catch(err => {
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Payment';
                }
                alert("Payment error: Server verification failed.");
            });
        });
    }
});