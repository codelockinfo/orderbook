// Verify Code - Step 2: Verify Code

const API_BASE = 'api/';

const verifyCodeForm = document.getElementById('verifyCodeForm');
const resetEmailConfirmInput = document.getElementById('resetEmailConfirm');
const resetCodeInput = document.getElementById('resetCode');
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

if (verifyCodeForm) {
    verifyCodeForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearMessages();

        const email = resetEmailConfirmInput ? resetEmailConfirmInput.value.trim() : '';
        const code = resetCodeInput ? resetCodeInput.value.trim() : '';

        if (!email || !code) {
            showError('Email and verification code are required.');
            return;
        }

        if (!/^\d{6}$/.test(code)) {
            showError('Please enter a valid 6-digit code.');
            return;
        }

        const submitBtn = verifyCodeForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Verifying...';
        }

        try {
            const response = await fetch(`${API_BASE}auth.php?action=verify_reset_code`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, code })
            });

            const data = await response.json();

            if (data.success) {
                showSuccess('Code verified successfully! Redirecting to reset password page...');
                // Redirect to reset password page with email and code parameters
                setTimeout(() => {
                    window.location.href = `reset-password.php?email=${encodeURIComponent(email)}&code=${encodeURIComponent(code)}`;
                }, 1500);
            } else {
                showError(data.message || 'Invalid verification code.');
            }
        } catch (error) {
            showError('An error occurred. Please try again.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Verify code';
            }
        }
    });
}

// Auto-focus on code input when page loads
document.addEventListener('DOMContentLoaded', function() {
    const codeInput = document.getElementById('resetCode');
    if (codeInput) {
        setTimeout(() => codeInput.focus(), 100);
    }
});

