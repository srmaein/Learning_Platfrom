// DOM Elements
const themeToggle = document.querySelector('.theme-toggle');
const body = document.body;
const navLinks = document.querySelectorAll('.nav-links li');

// Check for saved theme preference
if (localStorage.getItem('theme') === 'dark') {
    body.classList.remove('light-mode');
    body.classList.add('dark-mode');
    themeToggle.querySelector('i').classList.remove('fa-moon');
    themeToggle.querySelector('i').classList.add('fa-sun');
    themeToggle.querySelector('span').textContent = 'Light Mode';
}

// Theme Toggle Functionality
themeToggle.addEventListener('click', () => {
    if (body.classList.contains('light-mode')) {
        // Switch to Dark Mode
        body.classList.remove('light-mode');
        body.classList.add('dark-mode');
        themeToggle.querySelector('i').classList.remove('fa-moon');
        themeToggle.querySelector('i').classList.add('fa-sun');
        themeToggle.querySelector('span').textContent = 'Light Mode';
        localStorage.setItem('theme', 'dark');
    } else {
        // Switch to Light Mode
        body.classList.remove('dark-mode');
        body.classList.add('light-mode');
        themeToggle.querySelector('i').classList.remove('fa-sun');
        themeToggle.querySelector('i').classList.add('fa-moon');
        themeToggle.querySelector('span').textContent = 'Dark Mode';
        localStorage.setItem('theme', 'light');
    }
    updateChartColors();
});

// Navigation Active State
navLinks.forEach(link => {
    link.addEventListener('click', () => {
        navLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');
    });
});

// Search Functionality
const searchInput = document.querySelector('.search-bar input');
searchInput.addEventListener('input', (e) => {
    const searchTerm = e.target.value.toLowerCase();
    // Add search functionality here when needed
});

// Progress Bar Animation
const progressBars = document.querySelectorAll('.progress-bar');
progressBars.forEach(bar => {
    const width = bar.style.width;
    bar.style.width = '0';
    setTimeout(() => {
        bar.style.transition = 'width 1s ease-in-out';
        bar.style.width = width;
    }, 100);
});

// Add smooth transitions for all theme changes
document.documentElement.style.setProperty('--transition-speed', '0.3s');

// Initialize the grade chart
const ctx = document.getElementById('gradeChart').getContext('2d');
const gradeData = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    datasets: [{
        label: 'Grade',
        data: [6.5, 7.8, 6.2, 5.8, 6.5, 5.9, 6.7, 6.1, 7.2, 6.8, 7.1, 6.9],
        fill: true,
        borderColor: '#7c3aed',
        backgroundColor: 'rgba(124, 58, 237, 0.1)',
        tension: 0.4,
        pointRadius: 4,
        pointBackgroundColor: '#7c3aed'
    }]
};

const gradeChart = new Chart(ctx, {
    type: 'line',
    data: gradeData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 10,
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                },
                ticks: {
                    stepSize: 2
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: '#7c3aed',
                titleColor: '#fff',
                bodyColor: '#fff',
                padding: 10,
                displayColors: false
            }
        }
    }
});

// Handle period selector changes
const periodSelect = document.getElementById('period-select');
periodSelect.addEventListener('change', (e) => {
    // You can implement different data for different periods here
    console.log('Period changed to:', e.target.value);
});

// Update chart colors based on theme
function updateChartColors() {
    const isDark = document.body.classList.contains('dark-mode');
    gradeChart.options.scales.y.grid.color = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
    gradeChart.options.scales.y.ticks.color = isDark ? '#a0a0a0' : '#636e72';
    gradeChart.options.scales.x.ticks.color = isDark ? '#a0a0a0' : '#636e72';
    gradeChart.update();
} 