// Session check interval (check every 30 seconds)
const SESSION_CHECK_INTERVAL = 30000;

// Function to check session status
function checkSessionStatus() {
    fetch('check_session.php', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'error') {
            // Session is invalid or expired
            alert(data.message);
            window.location.href = data.redirect;
        }
    })
    .catch(error => {
        console.error('Session check failed:', error);
        // If we can't reach the server, assume session is invalid
        window.location.href = 'login.php';
    });
}

// Start periodic session checking
function startSessionChecking() {
    // Check immediately when page loads
    checkSessionStatus();
    
    // Then check periodically
    setInterval(checkSessionStatus, SESSION_CHECK_INTERVAL);
    
    // Also check when the page becomes visible again
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            checkSessionStatus();
        }
    });
}

// Start session checking when the page loads
document.addEventListener('DOMContentLoaded', startSessionChecking); 