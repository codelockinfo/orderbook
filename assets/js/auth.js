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
                window.location.href = 'index.php';
            } else {
                errorMessage.textContent = data.message;
                errorMessage.classList.add('show');
            }
        } catch (error) {
            errorMessage.textContent = 'An error occurred. Please try again.';
            errorMessage.classList.add('show');
        }
    });
}

// Forgot Password Flow
const loginSection = document.getElementById('loginSection');
const forgotPasswordSection = document.getElementById('forgotPasswordSection');
const forgotPasswordLink = document.getElementById('forgotPasswordLink');
const backToLoginBtn = document.getElementById('backToLogin');
const forgotStepRequest = document.getElementById('forgotStepRequest');
const forgotStepReset = document.getElementById('forgotStepReset');
const requestResetForm = document.getElementById('requestResetForm');
const resetPasswordForm = document.getElementById('resetPasswordForm');
const forgotMessage = document.getElementById('forgotMessage');
const forgotSuccess = document.getElementById('forgotSuccess');
const resetEmailRequestInput = document.getElementById('resetEmailRequest');
const resetEmailConfirmInput = document.getElementById('resetEmailConfirm');
const resetCodeInput = document.getElementById('resetCode');
const newPasswordInput = document.getElementById('newPassword');
const confirmNewPasswordInput = document.getElementById('confirmNewPassword');

let lastResetEmail = '';

function setForgotStep(step) {
    if (!forgotStepRequest || !forgotStepReset) {
        return;
    }

    if (step === 'reset') {
        forgotStepRequest.classList.remove('active');
        forgotStepReset.classList.add('active');
    } else {
        forgotStepReset.classList.remove('active');
        forgotStepRequest.classList.add('active');
    }
}

function clearForgotAlerts() {
    if (forgotMessage) {
        forgotMessage.textContent = '';
        forgotMessage.classList.remove('show');
    }
    if (forgotSuccess) {
        forgotSuccess.textContent = '';
        forgotSuccess.classList.remove('show');
    }
}

function showForgotError(message) {
    if (!forgotMessage) return;
    forgotMessage.textContent = message;
    forgotMessage.classList.add('show');
}

function showForgotSuccess(message) {
    if (!forgotSuccess) return;
    forgotSuccess.textContent = message;
    forgotSuccess.classList.add('show');
}

function switchToForgotSection() {
    clearForgotAlerts();
    if (loginSection) {
        loginSection.classList.add('hidden');
    }
    if (forgotPasswordSection) {
        forgotPasswordSection.classList.remove('hidden');
    }
    setForgotStep('request');
    if (resetEmailRequestInput) {
        resetEmailRequestInput.focus();
    }
}

function switchToLoginSection() {
    clearForgotAlerts();
    if (loginSection) {
        loginSection.classList.remove('hidden');
    }
    if (forgotPasswordSection) {
        forgotPasswordSection.classList.add('hidden');
    }
    lastResetEmail = '';
    if (requestResetForm) {
        requestResetForm.reset();
    }
    if (resetPasswordForm) {
        resetPasswordForm.reset();
    }
    setForgotStep('request');
}

if (forgotPasswordLink && forgotPasswordSection) {
    forgotPasswordLink.addEventListener('click', switchToForgotSection);
}

if (backToLoginBtn) {
    backToLoginBtn.addEventListener('click', switchToLoginSection);
}

if (requestResetForm) {
    requestResetForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearForgotAlerts();

        if (!resetEmailRequestInput) {
            return;
        }

        const email = resetEmailRequestInput.value.trim();

        if (!email) {
            showForgotError('Please enter your email address.');
            return;
        }

        const submitBtn = requestResetForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';
        }

        try {
            const response = await fetch(`${API_BASE}auth.php?action=request_reset_code`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email })
            });

            const data = await response.json();

            if (data.success) {
                lastResetEmail = email;
                if (resetEmailConfirmInput) {
                    resetEmailConfirmInput.value = email;
                }
                setForgotStep('reset');
                showForgotSuccess(`${data.message} Enter the 6-digit code we sent to continue.`);
                if (resetCodeInput) {
                    resetCodeInput.focus();
                }
            } else {
                showForgotError(data.message || 'Unable to send code.');
            }
        } catch (error) {
            showForgotError('An error occurred. Please try again.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send verification code';
            }
        }
    });
}

if (resetPasswordForm) {
    resetPasswordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearForgotAlerts();

        const email = (resetEmailConfirmInput?.value || lastResetEmail || '').trim();
        const code = resetCodeInput ? resetCodeInput.value.trim() : '';
        const newPassword = newPasswordInput ? newPasswordInput.value : '';
        const confirmPassword = confirmNewPasswordInput ? confirmNewPasswordInput.value : '';

        if (!email || !code || !newPassword || !confirmPassword) {
            showForgotError('All fields are required.');
            return;
        }

        if (newPassword !== confirmPassword) {
            showForgotError('Passwords do not match.');
            return;
        }

        const submitBtn = resetPasswordForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Resetting...';
        }

        try {
            const response = await fetch(`${API_BASE}auth.php?action=reset_password`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, code, password: newPassword })
            });

            const data = await response.json();

            if (data.success) {
                showForgotSuccess(data.message || 'Password reset successfully. You can log in now.');
                lastResetEmail = '';
                if (requestResetForm) {
                    requestResetForm.reset();
                }
                if (resetPasswordForm) {
                    resetPasswordForm.reset();
                }
                setForgotStep('request');
            } else {
                showForgotError(data.message || 'Unable to reset password.');
            }
        } catch (error) {
            showForgotError('An error occurred. Please try again.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Reset password';
            }
        }
    });
}

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
        
        if (password !== confirmPassword) {
            errorMessage.textContent = 'Passwords do not match';
            errorMessage.classList.add('show');
            return;
        }
        
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
                alert('Registration successful! Please login.');
                window.location.href = 'login.php';
            } else {
                errorMessage.textContent = data.message;
                errorMessage.classList.add('show');
            }
        } catch (error) {
            errorMessage.textContent = 'An error occurred. Please try again.';
            errorMessage.classList.add('show');
        }
    });
}

