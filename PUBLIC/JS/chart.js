// Dark Mode Toggle
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    const icon = themeToggle.querySelector('i');
    const text = themeToggle.querySelector('span');

    // Check for saved theme preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        body.classList.add('dark-mode');
        icon.classList.replace('fa-moon', 'fa-sun');
        text.textContent = 'Light Mode';
    }

    // Toggle theme
    themeToggle.addEventListener('click', function() {
        body.classList.toggle('dark-mode');
        if (body.classList.contains('dark-mode')) {
            localStorage.setItem('theme', 'dark');
            icon.classList.replace('fa-moon', 'fa-sun');
            text.textContent = 'Light Mode';
        } else {
            localStorage.setItem('theme', 'light');
            icon.classList.replace('fa-sun', 'fa-moon');
            text.textContent = 'Dark Mode';
        }
    });

    // Initialize Grade Chart
    const ctx = document.getElementById('gradeChart').getContext('2d');
    const gradeChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Grade',
                data: [6.5, 7.2, 6.8, 7.5, 7.8, 7.3],
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    min: 5,
                    max: 10,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Period Selector Change Handler
    const periodSelect = document.getElementById('period-select');
    periodSelect.addEventListener('change', function() {
        // Here you would typically fetch new data based on the selected period
        // For now, we'll just update the chart with some random data
        const periods = {
            yearly: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            monthly: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            weekly: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
        };

        const data = {
            yearly: [6.5, 7.2, 6.8, 7.5, 7.8, 7.3, 7.1, 7.4, 7.6, 7.2, 7.5, 7.8],
            monthly: [7.2, 7.5, 7.1, 7.8],
            weekly: [7.3, 7.1, 7.5, 7.2, 7.8, 7.4, 7.6]
        };

        gradeChart.data.labels = periods[this.value];
        gradeChart.data.datasets[0].data = data[this.value];
        gradeChart.update();
    });

    // Search Functionality
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const courseCards = document.querySelectorAll('.course-card');

        courseCards.forEach(card => {
            const title = card.querySelector('h3').textContent.toLowerCase();
            if (title.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
}); 