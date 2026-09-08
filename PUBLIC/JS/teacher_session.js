/**
 * Teacher Session Management
 * 
 * This script handles:
 * 1. Verification that the user is logged in as a teacher
 * 2. Displaying the teacher's name and role in the welcome section
 * 3. Session expiration handling
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get elements
    const welcomeName = document.getElementById('welcome-name');
    const welcomeRole = document.getElementById('welcome-role');
    
    // Check if teacher is logged in
    function checkTeacherSession() {
        fetch('get_teacher_info.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success' && data.loggedIn) {
                    // Update welcome message with teacher's name and role
                    welcomeName.textContent = 'Welcome, ' + data.name + ' 👋';
                    welcomeRole.textContent = 'Role: ' + data.user_type.charAt(0).toUpperCase() + data.user_type.slice(1);
                    
                    // Store session info in sessionStorage for quick access
                    sessionStorage.setItem('teacherName', data.name);
                    sessionStorage.setItem('teacherRole', data.user_type);
                } else {
                    // Redirect to login page if not logged in
                    window.location.href = 'login.php';
                }
            })
            .catch(error => {
                console.error('Error checking teacher session:', error);
                // Handle connection error, possibly by showing an error message
                welcomeName.textContent = 'Welcome 👋';
                welcomeRole.textContent = 'Session error. Please refresh.';
            });
    }
    
    // Initialize - check teacher session
    checkTeacherSession();
    
    // Periodically check session status (every 5 minutes)
    setInterval(checkTeacherSession, 5 * 60 * 1000);
    
    // Handle logout
    const logoutLink = document.querySelector('.logout a');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(event) {
            // Clear sessionStorage on logout
            sessionStorage.clear();
        });
    }
}); 