// Forgot Password - Step 1: Request Code

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

const requestResetForm = document.getElementById('requestResetForm');
const resetEmailRequestInput = document.getElementById('resetEmailRequest');
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

if (requestResetForm) {
    requestResetForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearMessages();

        if (!resetEmailRequestInput) {
            return;
        }

        const email = resetEmailRequestInput.value.trim();

        if (!email) {
            showError('Please enter your email address.');
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
                showSuccess(`${data.message} Redirecting to verification page...`);
                // Redirect to verify code page with email parameter
                setTimeout(() => {
                    window.location.href = `verify-code.php?email=${encodeURIComponent(email)}`;
                }, 1500);
            } else {
                showError(data.message || 'Unable to send code.');
            }
        } catch (error) {
            showError('An error occurred. Please try again.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send verification code';
            }
        }
    });
}

