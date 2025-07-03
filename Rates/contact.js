// Contact Form Validation and Functionality
class ContactForm {
    constructor() {
        this.form = document.getElementById('contactForm');
        this.submitBtn = document.getElementById('submitBtn');
        this.successMessage = document.getElementById('successMessage');
        this.messageTextarea = document.getElementById('message');
        this.charCount = document.getElementById('charCount');
        
        this.validators = {
            firstName: this.validateName,
            lastName: this.validateName,
            email: this.validateEmail,
            phone: this.validatePhone,
            subject: this.validateRequired,
            message: this.validateMessage,
            terms: this.validateTerms
        };
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.setupCharacterCounter();
        this.setupPhoneFormatter();
    }
    
    setupEventListeners() {
        // Form submission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Real-time validation
        Object.keys(this.validators).forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
                field.addEventListener('blur', () => this.validateField(fieldName));
                field.addEventListener('input', () => this.clearError(fieldName));
            }
        });
        
        // Special handling for checkboxes
        document.getElementById('terms').addEventListener('change', () => {
            this.validateField('terms');
        });
    }
    
    setupCharacterCounter() {
        this.messageTextarea.addEventListener('input', () => {
            const length = this.messageTextarea.value.length;
            const maxLength = 1000;
            
            this.charCount.textContent = length;
            
            // Update character count styling
            const counterElement = this.charCount.parentElement;
            counterElement.classList.remove('warning', 'error');
            
            if (length > maxLength * 0.9) {
                counterElement.classList.add('warning');
            }
            if (length > maxLength) {
                counterElement.classList.add('error');
                this.messageTextarea.value = this.messageTextarea.value.substring(0, maxLength);
                this.charCount.textContent = maxLength;
            }
        });
    }
    
    setupPhoneFormatter() {
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{3})(\d{0,3})/, '($1) $2');
            }
            e.target.value = value;
        });
    }
    
    validateField(fieldName) {
        const field = document.getElementById(fieldName);
        const validator = this.validators[fieldName];
        
        if (validator) {
            const result = validator.call(this, field.value, field);
            this.displayValidation(fieldName, result);
            return result.isValid;
        }
        return true;
    }
    
    validateName(value) {
        if (!value.trim()) {
            return { isValid: false, message: 'This field is required.' };
        }
        if (value.trim().length < 2) {
            return { isValid: false, message: 'Name must be at least 2 characters long.' };
        }
        if (!/^[a-zA-Z\s'-]+$/.test(value)) {
            return { isValid: false, message: 'Name can only contain letters, spaces, hyphens, and apostrophes.' };
        }
        return { isValid: true, message: '' };
    }
    
    validateEmail(value) {
        if (!value.trim()) {
            return { isValid: false, message: 'Email address is required.' };
        }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            return { isValid: false, message: 'Please enter a valid email address.' };
        }
        return { isValid: true, message: '' };
    }
    
    validatePhone(value) {
        if (!value.trim()) {
            return { isValid: true, message: '' }; // Phone is optional
        }
        const phoneRegex = /^$$\d{3}$$\s\d{3}-\d{4}$/;
        if (!phoneRegex.test(value)) {
            return { isValid: false, message: 'Please enter a valid phone number: (555) 123-4567' };
        }
        return { isValid: true, message: '' };
    }
    
    validateRequired(value) {
        if (!value.trim()) {
            return { isValid: false, message: 'This field is required.' };
        }
        return { isValid: true, message: '' };
    }
    
    validateMessage(value) {
        if (!value.trim()) {
            return { isValid: false, message: 'Message is required.' };
        }
        if (value.trim().length < 10) {
            return { isValid: false, message: 'Message must be at least 10 characters long.' };
        }
        if (value.length > 1000) {
            return { isValid: false, message: 'Message cannot exceed 1000 characters.' };
        }
        return { isValid: true, message: '' };
    }
    
    validateTerms(value, field) {
        if (!field.checked) {
            return { isValid: false, message: 'You must agree to the terms and conditions.' };
        }
        return { isValid: true, message: '' };
    }
    
    displayValidation(fieldName, result) {
        const field = document.getElementById(fieldName);
        const errorElement = document.getElementById(fieldName + 'Error');
        
        // Remove existing classes
        field.classList.remove('error', 'success');
        
        if (result.isValid) {
            field.classList.add('success');
            if (errorElement) errorElement.textContent = '';
        } else {
            field.classList.add('error');
            if (errorElement) errorElement.textContent = result.message;
        }
    }
    
    clearError(fieldName) {
        const field = document.getElementById(fieldName);
        const errorElement = document.getElementById(fieldName + 'Error');
        
        field.classList.remove('error');
        if (errorElement) errorElement.textContent = '';
    }
    
    validateForm() {
        let isValid = true;
        
        Object.keys(this.validators).forEach(fieldName => {
            if (!this.validateField(fieldName)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        // Validate form
        if (!this.validateForm()) {
            this.scrollToFirstError();
            return;
        }
        
        // Show loading state
        this.setLoadingState(true);
        
        try {
            // Simulate API call
            await this.submitForm();
            this.showSuccess();
            this.resetForm();
        } catch (error) {
            this.showError('Failed to send message. Please try again.');
        } finally {
            this.setLoadingState(false);
        }
    }
    
    async submitForm() {
        // Simulate API call delay
        return new Promise((resolve) => {
            setTimeout(() => {
                // Here you would normally send the data to your server
                console.log('Form data:', new FormData(this.form));
                resolve();
            }, 2000);
        });
    }
    
    setLoadingState(isLoading) {
        const btnText = this.submitBtn.querySelector('.btn-text');
        const btnLoading = this.submitBtn.querySelector('.btn-loading');
        
        if (isLoading) {
            btnText.style.display = 'none';
            btnLoading.style.display = 'flex';
            this.submitBtn.disabled = true;
        } else {
            btnText.style.display = 'block';
            btnLoading.style.display = 'none';
            this.submitBtn.disabled = false;
        }
    }
    
    showSuccess() {
        this.successMessage.style.display = 'flex';
        this.form.style.display = 'none';
        
        // Scroll to success message
        this.successMessage.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' 
        });
        
        // Auto-hide success message and show form again after 5 seconds
        setTimeout(() => {
            this.hideSuccess();
        }, 5000);
    }
    
    hideSuccess() {
        this.successMessage.style.display = 'none';
        this.form.style.display = 'flex';
    }
    
    showError(message) {
        // You could implement a toast notification or error banner here
        alert(message);
    }
    
    resetForm() {
        this.form.reset();
        this.charCount.textContent = '0';
        
        // Clear all validation states
        Object.keys(this.validators).forEach(fieldName => {
            const field = document.getElementById(fieldName);
            const errorElement = document.getElementById(fieldName + 'Error');
            
            if (field) {
                field.classList.remove('error', 'success');
            }
            if (errorElement) {
                errorElement.textContent = '';
            }
        });
    }
    
    scrollToFirstError() {
        const firstError = this.form.querySelector('.error');
        if (firstError) {
            firstError.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
            firstError.focus();
        }
    }
}

// Initialize the contact form when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ContactForm();
});

// Additional utility functions
function sanitizeInput(input) {
    const div = document.createElement('div');
    div.textContent = input;
    return div.innerHTML;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Export for potential use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ContactForm;
}