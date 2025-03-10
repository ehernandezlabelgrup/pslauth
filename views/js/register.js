/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * @author    Emilio Hernandez <ehernandez@okoiagency.com>
 * @copyright OKOI AGENCY S.L.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('pslauth-register-form');
    const messagesContainer = document.getElementById('pslauth-messages');
    const passwordField = document.getElementById('pslauth-password');
    const passwordStrengthContainer = document.createElement('div');
    passwordStrengthContainer.className = 'password-strength-meter';
    
    // Add password strength meter container after password field
    if (passwordField) {
        passwordField.parentNode.insertBefore(passwordStrengthContainer, passwordField.nextSibling);
        
        // Add event listener for password input
        passwordField.addEventListener('input', function() {
            checkPasswordStrength(passwordField.value);
        });
    }
    
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous messages
            messagesContainer.innerHTML = '';
            
            // Disable submit button
            const submitButton = document.getElementById('pslauth-submit-register');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="material-icons">hourglass_empty</i> Processing...';
            
            // Get form data
            const formData = new FormData(registerForm);
            const jsonData = {};
            
            // Convert FormData to JSON
            for (const [key, value] of formData.entries()) {
                jsonData[key] = value;
            }
            
            // Basic client-side validation
            if (!validateForm(jsonData, messagesContainer)) {
                // Re-enable submit button if validation fails
                submitButton.disabled = false;
                submitButton.innerHTML = 'Create account';
                return;
            }
            
            // Send AJAX request
            fetch(registerForm.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(jsonData)
            })
            .then(response => response.json())
            .then(data => {
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.innerHTML = 'Create account';
                
                if (data.success) {
                    // Show success message
                    showMessage('success', data.message);
                    
                    // Clear form
                    registerForm.reset();
                    
                    // Redirect after successful registration
                    if (data.data && data.data.redirect_url) {
                        setTimeout(function() {
                            window.location.href = data.data.redirect_url;
                        }, 1000);
                    }
                } else {
                    // Show error message
                    showMessage('danger', data.message);
                }
            })
            .catch(error => {
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.innerHTML = 'Create account';
                
                // Show error message
                showMessage('danger', 'An error occurred. Please try again.');
                console.error('Error:', error);
            });
        });
    }
    
    /**
     * Check password strength and show visual feedback
     * 
     * @param {string} password The password to check
     */
    function checkPasswordStrength(password) {
        // Initialize requirements
        const requirements = [
            { regex: /.{6,}/, description: 'At least 6 characters' },
            { regex: /[a-z]/, description: 'At least one lowercase letter' },
            { regex: /[A-Z]/, description: 'At least one uppercase letter' },
            { regex: /[0-9]/, description: 'At least one number' },
            { regex: /[^a-zA-Z0-9]/, description: 'At least one special character' }
        ];
        
        // Clear previous content
        passwordStrengthContainer.innerHTML = '';
        
        // Check which requirements are met
        let strengthScore = 0;
        const requirementsList = document.createElement('ul');
        requirementsList.className = 'password-requirements';
        
        requirements.forEach(requirement => {
            const isValid = requirement.regex.test(password);
            if (isValid) strengthScore++;
            
            const listItem = document.createElement('li');
            listItem.className = isValid ? 'requirement-met' : 'requirement-not-met';
            listItem.innerHTML = `<i class="material-icons">${isValid ? 'check_circle' : 'cancel'}</i> ${requirement.description}`;
            requirementsList.appendChild(listItem);
        });
        
        // Calculate strength class
        let strengthClass = 'strength-weak';
        let strengthText = 'Weak';
        
        if (strengthScore >= 3) {
            strengthClass = 'strength-medium';
            strengthText = 'Medium';
        }
        if (strengthScore >= 5) {
            strengthClass = 'strength-strong';
            strengthText = 'Strong';
        }
        
        // Create strength meter
        const strengthBar = document.createElement('div');
        strengthBar.className = `strength-meter ${strengthClass}`;
        strengthBar.style.width = `${(strengthScore / 5) * 100}%`;
        
        // Create strength label
        const strengthLabel = document.createElement('div');
        strengthLabel.className = 'strength-text';
        strengthLabel.textContent = `Password strength: ${strengthText}`;
        
        // Add all elements to container
        const meterContainer = document.createElement('div');
        meterContainer.className = 'meter-container';
        meterContainer.appendChild(strengthBar);
        
        passwordStrengthContainer.appendChild(meterContainer);
        passwordStrengthContainer.appendChild(strengthLabel);
        passwordStrengthContainer.appendChild(requirementsList);
    }
    
    /**
     * Basic client-side form validation
     * 
     * @param {Object} data Form data
     * @param {HTMLElement} messagesContainer Container for error messages
     * @return {boolean} True if validation passes
     */
    function validateForm(data, messagesContainer) {
        let isValid = true;
        let errors = [];
        
        // Check required fields
        ['firstname', 'lastname', 'email', 'password'].forEach(field => {
            if (!data[field] || data[field].trim() === '') {
                isValid = false;
                errors.push(`The ${field} field is required.`);
            }
        });
        
        // Email validation
        if (data.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
            isValid = false;
            errors.push('Please enter a valid email address.');
        }
        
        // Password validation
        if (data.password) {
            const password = data.password;
            
            // Check minimum length
            if (password.length < 6) {
                isValid = false;
                errors.push('Password must be at least 6 characters long.');
            }
            
            // Check for lowercase letter
            if (!/[a-z]/.test(password)) {
                isValid = false;
                errors.push('Password must contain at least one lowercase letter.');
            }
            
            // Check for uppercase letter
            if (!/[A-Z]/.test(password)) {
                isValid = false;
                errors.push('Password must contain at least one uppercase letter.');
            }
            
            // Check for number
            if (!/[0-9]/.test(password)) {
                isValid = false;
                errors.push('Password must contain at least one number.');
            }
            
            // Check for special character
            if (!/[^a-zA-Z0-9]/.test(password)) {
                isValid = false;
                errors.push('Password must contain at least one special character.');
            }
        }
        
        // Birthday validation if provided
        if (data.birthday && data.birthday.trim() !== '') {
            // Check if the format is valid (YYYY-MM-DD)
            const birthdayRegex = /^\d{4}-\d{2}-\d{2}$/;
            if (!birthdayRegex.test(data.birthday)) {
                isValid = false;
                errors.push('Birth date must be in the format YYYY-MM-DD.');
            } else {
                // Check if it's a valid date
                const birthdayDate = new Date(data.birthday);
                if (isNaN(birthdayDate.getTime())) {
                    isValid = false;
                    errors.push('Please enter a valid birth date.');
                }
            }
        }
        
        // Terms agreement validation
        if (!data.psgdpr) {
            isValid = false;
            errors.push('You must agree to the terms and conditions.');
        }
        
        // Display errors if any
        if (!isValid && errors.length > 0) {
            let errorHtml = '<ul class="list-unstyled">';
            errors.forEach(error => {
                errorHtml += `<li>${error}</li>`;
            });
            errorHtml += '</ul>';
            
            showMessage('danger', 'Please correct the following errors:', errorHtml);
        }
        
        return isValid;
    }
    
    /**
     * Display message in the messages container
     * 
     * @param {string} type Message type (success, danger, warning, info)
     * @param {string} message Message text
     * @param {string} additionalHtml Optional additional HTML to include
     */
    function showMessage(type, message, additionalHtml = '') {
        const alertElement = document.createElement('div');
        alertElement.className = `alert alert-${type}`;
        alertElement.innerHTML = `<strong>${message}</strong>`;
        
        if (additionalHtml) {
            alertElement.innerHTML += additionalHtml;
        }
        
        messagesContainer.appendChild(alertElement);
        
        // Scroll to message
        messagesContainer.scrollIntoView({ behavior: 'smooth' });
    }
});