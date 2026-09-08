// Dark Mode Functionality
const modeToggle = document.querySelector('.mode-toggle');
const html = document.documentElement;
const modeIcon = document.querySelector('.mode-icon');
const modeText = document.querySelector('.mode-text');

// Check for saved theme preference
const savedTheme = localStorage.getItem('theme') || 'light';
html.setAttribute('data-theme', savedTheme);
updateModeToggle(savedTheme === 'dark');

function updateModeToggle(isDark) {
    modeIcon.textContent = isDark ? 'light_mode' : 'dark_mode';
    modeText.textContent = isDark ? 'Light Mode' : 'Dark Mode';
    updateChartsTheme(isDark);
}

modeToggle.addEventListener('click', () => {
    const isDark = html.getAttribute('data-theme') === 'dark';
    const newTheme = isDark ? 'light' : 'dark';
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateModeToggle(!isDark);
});

// Activity Chart
const activityCtx = document.getElementById('activityChart').getContext('2d');
const activityData = {
    labels: ['S', 'M', 'T', 'W', 'T', 'F', 'S'],
    datasets: [{
        data: [2, 4, 3, 4, 6, 3, 3],
        backgroundColor: '#6366F1',
        borderRadius: 8,
        barThickness: 20,
        maxBarThickness: 30
    }]
};

const activityChart = new Chart(activityCtx, {
    type: 'bar',
    data: activityData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 8,
                grid: {
                    display: true,
                    color: 'rgba(0, 0, 0, 0.05)'
                },
                ticks: {
                    stepSize: 2,
                    color: '#64748B',
                    font: {
                        size: 12
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#64748B',
                    font: {
                        size: 12
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: {
                    size: 14
                },
                bodyFont: {
                    size: 13
                },
                cornerRadius: 8
            }
        }
    }
});

// Performance Chart
const performanceCtx = document.getElementById('performanceChart').getContext('2d');
const performanceData = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    datasets: [{
        data: [65, 75, 65, 80, 70, 85],
        borderColor: '#6366F1',
        backgroundColor: 'rgba(99, 102, 241, 0.1)',
        fill: true,
        tension: 0.4,
        pointRadius: 0,
        borderWidth: 2
    }]
};

const performanceChart = new Chart(performanceCtx, {
    type: 'line',
    data: performanceData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                display: false,
                min: 0,
                max: 100
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#64748B',
                    font: {
                        size: 12
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: {
                    size: 14
                },
                bodyFont: {
                    size: 13
                },
                cornerRadius: 8
            }
        }
    }
});

// Calendar Functionality
const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

let currentDate = new Date(2023, 1, 20); // February 20, 2023 (as shown in the image)

function generateCalendar(date) {
    const calendarGrid = document.getElementById('calendarGrid');
    const monthYear = document.querySelector('.calendar-header h2');
    
    // Update month and year display
    monthYear.textContent = `${months[date.getMonth()]} ${date.getFullYear()}`;
    
    // Clear existing calendar
    calendarGrid.innerHTML = '';
    
    // Get first day of month and total days
    const firstDay = new Date(date.getFullYear(), date.getMonth(), 1).getDay();
    const daysInMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
    
    // Add empty cells for days before the 1st
    for (let i = 0; i < firstDay; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'calendar-day empty';
        calendarGrid.appendChild(emptyDay);
    }
    
    // Add days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        dayElement.textContent = day;
        
        // Add active class to current selected date
        if (day === date.getDate()) {
            dayElement.classList.add('active');
        }
        
        // Add today class if it's today
        const today = new Date();
        if (day === today.getDate() && 
            date.getMonth() === today.getMonth() && 
            date.getFullYear() === today.getFullYear()) {
            dayElement.classList.add('today');
        }
        
        dayElement.addEventListener('click', () => {
            document.querySelectorAll('.calendar-day').forEach(d => d.classList.remove('active'));
            dayElement.classList.add('active');
            currentDate = new Date(date.getFullYear(), date.getMonth(), day);
            updateEvents(currentDate);
        });
        
        calendarGrid.appendChild(dayElement);
    }
}

// Navigation buttons
document.querySelector('.prev-month').addEventListener('click', () => {
    currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1);
    generateCalendar(currentDate);
});

document.querySelector('.next-month').addEventListener('click', () => {
    currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1);
    generateCalendar(currentDate);
});

// Update events based on selected date
function updateEvents(date) {
    // Here you can add logic to update the events display based on the selected date
    console.log('Selected date:', date.toDateString());
}

// Initialize calendar
generateCalendar(currentDate);

// Update charts theme
function updateChartsTheme(isDark) {
    const textColor = isDark ? '#94A3B8' : '#64748B';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)';
    const tooltipBg = isDark ? 'rgba(0, 0, 0, 0.9)' : 'rgba(0, 0, 0, 0.8)';

    // Update Activity Chart
    activityChart.options.scales.y.grid.color = gridColor;
    activityChart.options.scales.y.ticks.color = textColor;
    activityChart.options.scales.x.ticks.color = textColor;
    activityChart.options.plugins.tooltip.backgroundColor = tooltipBg;
    activityChart.update();

    // Update Performance Chart
    performanceChart.options.scales.x.ticks.color = textColor;
    performanceChart.options.plugins.tooltip.backgroundColor = tooltipBg;
    performanceChart.update();
}

// Navigation menu functionality
document.querySelectorAll('.nav-menu li').forEach(item => {
    item.addEventListener('click', () => {
        document.querySelectorAll('.nav-menu li').forEach(i => i.classList.remove('active'));
        item.classList.add('active');
    });
});

// Period selector functionality
document.querySelector('.period-select').addEventListener('change', (e) => {
    // Update charts based on selected period
    console.log('Period changed:', e.target.value);
});

// Search functionality
const searchInput = document.querySelector('.search-bar input');
searchInput.addEventListener('input', (e) => {
    const searchTerm = e.target.value.toLowerCase();
    // Implement search functionality
    console.log('Searching for:', searchTerm);
});

// Initialize tooltips for better UX
const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
tooltipTriggerList.forEach(tooltipTriggerEl => {
    new bootstrap.Tooltip(tooltipTriggerEl);
}); 