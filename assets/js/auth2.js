// Authentication JavaScript

const API_BASE = 'api/';

// Password Toggle Functionality
function setupPasswordToggle(toggleId, inputId) {
    const toggleBtn = document.getElementById(toggleId);
    const passwordInput = document.getElementById(inputId);
    
    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.classList.remove('fa-eye');
                toggleBtn.classList.add('fa-eye-slash');
                toggleBtn.classList.add('active');
            } else {
                passwordInput.type = 'password';
                toggleBtn.classList.remove('fa-eye-slash');
                toggleBtn.classList.add('fa-eye');
                toggleBtn.classList.remove('active');
            }
        });
    }
}

// Initialize password toggles
document.addEventListener('DOMContentLoaded', function() {
    setupPasswordToggle('togglePassword', 'password');
    setupPasswordToggle('toggleConfirmPassword', 'confirm_password');
        setupPasswordToggle('toggleNewPassword', 'newPassword');
    setupPasswordToggle('toggleConfirmNewPassword', 'confirmNewPassword');
});

// Login Form
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const errorMessage = document.getElementById('error-message');
        const submitBtn = loginForm.querySelector('button[type="submit"]');
        
        // Clear previous errors
        if (errorMessage) {
            errorMessage.textContent = '';
            errorMessage.classList.remove('show');
        }
        
        // Show loading state
        if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
            submitBtn.classList.add('loading');
            
            // Restore button state function
            const restoreButton = () => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                submitBtn.classList.remove('loading');
            };
            
            try {
                const response = await fetch(`${API_BASE}auth.php?action=login`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ username, password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Keep loading state while redirecting
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Success! Redirecting...';
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 500);
                } else {
                    restoreButton();
                    if (errorMessage) {
                        errorMessage.textContent = data.message || 'Login failed. Please try again.';
                        errorMessage.classList.add('show');
                    }
                }
            } catch (error) {
                restoreButton();
                if (errorMessage) {
                    errorMessage.textContent = 'An error occurred. Please check your connection and try again.';
                    errorMessage.classList.add('show');
                }
                console.error('Login error:', error);
            }
        }
    });
}

// Forgot Password Link - Now redirects to separate page
// The forgot password link is handled via href in login.php

// Register Form
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const username = document.getElementById('username').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const errorMessage = document.getElementById('error-message');
        const submitBtn = registerForm.querySelector('button[type="submit"]');
        
        // Clear previous errors
        if (errorMessage) {
            errorMessage.textContent = '';
            errorMessage.classList.remove('show');
        }
        
        if (password !== confirmPassword) {
            if (errorMessage) {
                errorMessage.textContent = 'Passwords do not match';
                errorMessage.classList.add('show');
            }
            return;
        }
        
        // Show loading state
        if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
            submitBtn.classList.add('loading');
            
            // Restore button state function
            const restoreButton = () => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                submitBtn.classList.remove('loading');
            };
            
            try {
                const response = await fetch(`${API_BASE}auth.php?action=register`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ username, email, password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Keep loading state while redirecting
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Success! Redirecting...';
                    setTimeout(() => {
                        alert('Registration successful! Please login.');
                        window.location.href = 'login.php';
                    }, 500);
                } else {
                    restoreButton();
                    if (errorMessage) {
                        errorMessage.textContent = data.message || 'Registration failed. Please try again.';
                        errorMessage.classList.add('show');
                    }
                }
            } catch (error) {
                restoreButton();
                if (errorMessage) {
                    errorMessage.textContent = 'An error occurred. Please check your connection and try again.';
                    errorMessage.classList.add('show');
                }
                console.error('Registration error:', error);
            }
        }
    });
}

