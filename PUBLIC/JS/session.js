// Function to get user information from session
function getUserInfo() {
    fetch('get_user_info.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Update user information in the dashboard
                const userInfo = data.data;
                
                // Find elements with class 'user-name' and update their content
                document.querySelectorAll('.user-name').forEach(element => {
                    element.textContent = userInfo.name;
                });

                // Find elements with class 'user-type' and update their content
                document.querySelectorAll('.user-type').forEach(element => {
                    element.textContent = userInfo.user_type.charAt(0).toUpperCase() + userInfo.user_type.slice(1);
                });

                // Find elements with class 'username' and update their content
                document.querySelectorAll('.username').forEach(element => {
                    element.textContent = userInfo.username;
                });
            } else {
                // If not logged in, redirect to login page
                window.location.href = 'login.php';
            }
        })
        .catch(error => {
            console.error('Error fetching user info:', error);
            // Redirect to login page on error
            window.location.href = 'login.php';
        });
}

// Call getUserInfo when the page loads
document.addEventListener('DOMContentLoaded', getUserInfo); 