/**
 * Student Session Management
 * 
 * This script handles:
 * 1. Verification that the user is logged in as a student
 * 2. Displaying the student's name in the welcome section
 * 3. Session expiration handling
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get welcome elements
    const studentName = document.querySelector('.welcome-text h2');
    
    // Check if student is logged in
    function checkStudentSession() {
        fetch('get_student_info.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success' && data.loggedIn) {
                    // Update student name in the welcome section
                    if (studentName) {
                        studentName.textContent = data.name;
                    }
                    
                    // Store session info in sessionStorage for quick access
                    sessionStorage.setItem('studentName', data.name);
                    sessionStorage.setItem('studentRole', data.user_type);
                } else {
                    // Redirect to login page if not logged in
                    window.location.href = 'login.php';
                }
            })
            .catch(error => {
                console.error('Error checking student session:', error);
                // Handle connection error gracefully
                if (studentName) {
                    studentName.textContent = 'Student';
                }
            });
    }
    
    // Initialize - check student session
    checkStudentSession();
    
    // Periodically check session status (every 5 minutes)
    setInterval(checkStudentSession, 5 * 60 * 1000);
    
    // Handle logout if logout link exists
    const logoutLink = document.querySelector('.logout a');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(event) {
            // Clear sessionStorage on logout
            sessionStorage.clear();
        });
    }
}); 