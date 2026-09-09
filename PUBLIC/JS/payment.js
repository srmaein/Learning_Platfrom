/**
 * Payment Form Handler
 * Manages payment form submission and transaction storage
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get URL parameters to display the selected course
    const urlParams = new URLSearchParams(window.location.search);
    const courseId = urlParams.get('courseId');
    const courseName = urlParams.get('courseName');
    const courseImg = urlParams.get('courseImg');
    const coursePrice = urlParams.get('price');
    
    console.log('Course Price from URL:', coursePrice); // Debug log
    
    // Display course name if available
    const selectedCourseElement = document.getElementById('selected-course');
    const courseNameInput = document.getElementById('course_name');
    
    if (courseName) {
        const decodedCourseName = decodeURIComponent(courseName);
        selectedCourseElement.textContent = decodedCourseName;
        courseNameInput.value = decodedCourseName;
        document.title = `Payment for ${decodedCourseName}`;
    } else {
        selectedCourseElement.textContent = 'Course';
    }
    
    // Handle amount changes
    const amountInput = document.getElementById('amount');
    const coursePriceElement = document.getElementById('course-price');
    const totalAmountElement = document.getElementById('total-amount');
    
    console.log('Amount Input Element:', amountInput); // Debug log
    
    // Set initial amount if price is available from URL
    if (coursePrice) {
        // Set with slight delay to ensure DOM is fully loaded
        setTimeout(() => {
            amountInput.value = coursePrice;
            updatePaymentSummary(coursePrice);
            console.log('Price set to:', coursePrice); // Debug log
            
            // Trigger a change event to ensure any listeners are notified
            const event = new Event('input', { bubbles: true });
            amountInput.dispatchEvent(event);
        }, 100);
    }
    
    amountInput.addEventListener('input', function() {
        const amount = this.value || 0;
        updatePaymentSummary(amount);
    });
    
    function updatePaymentSummary(amount) {
        coursePriceElement.textContent = `BDT ${amount}`;
        totalAmountElement.textContent = `BDT ${amount}`;
    }
    
    // Fix: Make amount field readonly if price is provided
    if (coursePrice) {
        amountInput.readOnly = true;
        amountInput.style.backgroundColor = "#f8f8f8";
    }
    
    // Handle form submission
    const paymentForm = document.getElementById('payment-form');
    const successModal = document.getElementById('success-modal');
    const closeModal = document.querySelector('.close-modal');
    const modalTransactionId = document.getElementById('modal-transaction-id');
    
    paymentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        const customCourseName = document.getElementById('course_name').value;
        const email = document.getElementById('email').value;
        const amount = document.getElementById('amount').value;
        const transactionId = document.getElementById('transaction_id').value;
        
        // Get selected payment method
        let paymentMethod = '';
        const paymentMethodInputs = document.querySelectorAll('input[name="payment_method"]');
        paymentMethodInputs.forEach(input => {
            if (input.checked) {
                paymentMethod = input.value;
            }
        });
        
        if (!paymentMethod) {
            alert('Please select a payment method');
            return;
        }
        
        // Create transaction object
        const transaction = {
            id: transactionId,
            email: email,
            paymentMethod: paymentMethod,
            courseId: courseId || 'unknown',
            courseName: customCourseName || (courseName ? decodeURIComponent(courseName) : 'Unknown Course'),
            courseImg: courseImg ? decodeURIComponent(courseImg) : '',
            amount: `BDT ${amount}`,
            date: new Date().toISOString(),
            status: 'Completed'
        };
        
        // Save transaction to localStorage
        saveTransaction(transaction);
        
        // Save to database
        saveTransactionToDatabase(transaction)
            .then(response => {
                if (!response.success) {
                    console.error('Database save error:', response.message);
                }
            })
            .catch(error => {
                console.error('Error saving to database:', error);
            });
        
        // Show success modal
        modalTransactionId.textContent = transactionId;
        successModal.style.display = 'block';
    });
    
    // Close modal when clicking X
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            successModal.style.display = 'none';
        });
    }
    
    // Close modal when clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target === successModal) {
            successModal.style.display = 'none';
        }
    });
});

// Alternative direct script at the end of the file to set the amount
// This helps ensure the script runs even if there are timing issues
window.addEventListener('load', function() {
    setTimeout(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const coursePrice = urlParams.get('price');
        const amountInput = document.getElementById('amount');
        
        if (coursePrice && amountInput) {
            amountInput.value = coursePrice;
            
            // Update payment summary if elements exist
            const coursePriceElement = document.getElementById('course-price');
            const totalAmountElement = document.getElementById('total-amount');
            
            if (coursePriceElement) coursePriceElement.textContent = `BDT ${coursePrice}`;
            if (totalAmountElement) totalAmountElement.textContent = `BDT ${coursePrice}`;
            
            console.log('Price set by backup method:', coursePrice);
        }
    }, 300);
});

/**
 * Save a transaction to localStorage
 * @param {Object} transaction - The transaction details
 */
function saveTransaction(transaction) {
    // Get existing transactions or initialize empty array
    let transactions = JSON.parse(localStorage.getItem('courseTransactions')) || [];
    
    // Add new transaction
    transactions.push(transaction);
    
    // Save back to localStorage
    localStorage.setItem('courseTransactions', JSON.stringify(transactions));
}

/**
 * Save transaction to database via AJAX
 * @param {Object} transaction - The transaction to save
 * @returns {Promise} A promise that resolves with the server response
 */
async function saveTransactionToDatabase(transaction) {
    try {
        const response = await fetch('save_transaction.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(transaction)
        });
        
        return await response.json();
    } catch (error) {
        console.error('Error saving transaction:', error);
        return { success: false, message: error.message };
    }
} 