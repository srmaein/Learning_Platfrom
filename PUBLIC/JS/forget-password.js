// Default admin email
const ADMIN_EMAIL = 'admin@gmail.com';

// DOM Elements
const form = document.getElementById('resetForm');
const step1 = document.getElementById('step1');
const step2 = document.getElementById('step2');
const emailPhoneInput = document.getElementById('emailPhone');
const captchaInput = document.getElementById('captchaInput');
const verifyButton = document.getElementById('verifyButton');
const newPasswordInput = document.getElementById('newPassword');
const confirmPasswordInput = document.getElementById('confirmPassword');
const togglePasswordButtons = document.querySelectorAll('.toggle-password');
const notification = document.getElementById('notification');

// Password requirement elements
const requirements = {
    length: document.getElementById('length'),
    uppercase: document.getElementById('uppercase'),
    lowercase: document.getElementById('lowercase'),
    number: document.getElementById('number'),
    special: document.getElementById('special')
};

// Captcha generation
let captchaText = '';

function generateCaptcha() {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    canvas.width = 150;
    canvas.height = 50;

    // Fill background
    ctx.fillStyle = '#f3f4f6';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Generate random text
    captchaText = Math.random().toString(36).substring(2, 8).toUpperCase();

    // Add text
    ctx.font = 'bold 30px Arial';
    ctx.fillStyle = '#4F46E5';
    ctx.textBaseline = 'middle';
    ctx.textAlign = 'center';
    
    // Add noise
    for (let i = 0; i < 50; i++) {
        ctx.strokeStyle = '#6366F1';
        ctx.beginPath();
        ctx.moveTo(Math.random() * canvas.width, Math.random() * canvas.height);
        ctx.lineTo(Math.random() * canvas.width, Math.random() * canvas.height);
        ctx.stroke();
    }

    // Add text with slight rotation for each character
    [...captchaText].forEach((char, i) => {
        ctx.save();
        ctx.translate(
            canvas.width/2 - (captchaText.length * 15)/2 + i * 15 + 15,
            canvas.height/2
        );
        ctx.rotate((Math.random() - 0.5) * 0.4);
        ctx.fillText(char, 0, 0);
        ctx.restore();
    });

    document.getElementById('captchaImage').src = canvas.toDataURL();
}

// Refresh captcha
document.getElementById('refreshCaptcha').addEventListener('click', generateCaptcha);

// Toggle password visibility
togglePasswordButtons.forEach(button => {
    button.addEventListener('click', () => {
        const input = button.previousElementSibling;
        const type = input.type === 'password' ? 'text' : 'password';
        input.type = type;
        button.textContent = type === 'password' ? 'visibility_off' : 'visibility';
    });
});

// Password validation
function validatePassword(password) {
    const validations = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[!@#$%^&*]/.test(password)
    };

    Object.entries(validations).forEach(([key, valid]) => {
        requirements[key].classList.toggle('valid', valid);
    });

    return Object.values(validations).every(Boolean);
}

// Show notification
function showNotification(message, type = 'error') {
    notification.textContent = message;
    notification.className = `notification show ${type}`;
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

// Verify email/phone and captcha
verifyButton.addEventListener('click', () => {
    const emailPhone = emailPhoneInput.value.trim();
    const captcha = captchaInput.value.trim();

    if (!emailPhone) {
        showNotification('Please enter your email or phone number');
        return;
    }

    if (!captcha) {
        showNotification('Please enter the verification code');
        return;
    }

    if (captcha.toUpperCase() !== captchaText) {
        showNotification('Invalid verification code');
        generateCaptcha();
        captchaInput.value = '';
        return;
    }

    if (emailPhone !== ADMIN_EMAIL) {
        showNotification('Email not found in our records');
        return;
    }

    // Show password reset form
    step1.style.display = 'none';
    step2.style.display = 'block';
});

// Handle form submission
form.addEventListener('submit', (e) => {
    e.preventDefault();

    const newPassword = newPasswordInput.value;
    const confirmPassword = confirmPasswordInput.value;

    if (!validatePassword(newPassword)) {
        showNotification('Please meet all password requirements');
        return;
    }

    if (newPassword !== confirmPassword) {
        showNotification('Passwords do not match');
        return;
    }

    showNotification('Password reset successfully!', 'success');
    setTimeout(() => {
        window.location.href = 'teadas.html';
    }, 2000);
});

// Initialize
generateCaptcha();

// Password validation on input
newPasswordInput.addEventListener('input', () => {
    validatePassword(newPasswordInput.value);
}); 