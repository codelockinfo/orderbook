// Reset Password - Step 3: Reset Password

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
    setupPasswordToggle('toggleNewPassword', 'newPassword');
    setupPasswordToggle('toggleConfirmNewPassword', 'confirmNewPassword');
});

const resetPasswordForm = document.getElementById('resetPasswordForm');
const resetEmailFinalInput = document.getElementById('resetEmailFinal');
const resetCodeFinalInput = document.getElementById('resetCodeFinal');
const newPasswordInput = document.getElementById('newPassword');
const confirmNewPasswordInput = document.getElementById('confirmNewPassword');
const errorMessage = document.getElementById('error-message');
const successMessage = document.getElementById('success-message');

function clearMessages() {
    if (errorMessage) {
        errorMessage.textContent = '';
        errorMessage.classList.remove('show');
    }
    if (successMessage) {
        successMessage.textContent = '';
        successMessage.classList.remove('show');
    }
}

function showError(message) {
    if (!errorMessage) return;
    errorMessage.textContent = message;
    errorMessage.classList.add('show');
    if (successMessage) {
        successMessage.classList.remove('show');
    }
}

function showSuccess(message) {
    if (!successMessage) return;
    successMessage.textContent = message;
    successMessage.classList.add('show');
    if (errorMessage) {
        errorMessage.classList.remove('show');
    }
}

if (resetPasswordForm) {
    resetPasswordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearMessages();

        const email = resetEmailFinalInput ? resetEmailFinalInput.value.trim() : '';
        const code = resetCodeFinalInput ? resetCodeFinalInput.value.trim() : '';
        const newPassword = newPasswordInput ? newPasswordInput.value : '';
        const confirmPassword = confirmNewPasswordInput ? confirmNewPasswordInput.value : '';

        if (!email || !code || !newPassword || !confirmPassword) {
            showError('All fields are required.');
            return;
        }

        if (newPassword.length < 6) {
            showError('Password must be at least 6 characters long.');
            return;
        }

        if (newPassword !== confirmPassword) {
            showError('Passwords do not match.');
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
                showSuccess(data.message || 'Password reset successfully! Redirecting to login...');
                // Redirect to login page after successful reset
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                showError(data.message || 'Unable to reset password.');
            }
        } catch (error) {
            showError('An error occurred. Please try again.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Reset password';
            }
        }
    });
}

// Auto-focus on password input when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (newPasswordInput) {
        setTimeout(() => newPasswordInput.focus(), 100);
    }
});

