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
    const loginForm = document.getElementById('pslauth-login-form');
    const messagesContainer = document.getElementById('pslauth-messages');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous messages
            messagesContainer.innerHTML = '';
            
            // Disable submit button
            const submitButton = document.getElementById('pslauth-submit');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="material-icons">hourglass_empty</i> Processing...';
            
            // Get form data
            const formData = new FormData(loginForm);
            const jsonData = {};
            
            // Convert FormData to JSON
            for (const [key, value] of formData.entries()) {
                jsonData[key] = value;
            }
            
            // Send AJAX request
            fetch(loginForm.action, {
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
                submitButton.innerHTML = 'Sign in';
                
                if (data.success) {
                    // Show success message
                    showMessage('success', data.message);
                    
                    // Redirect after successful login
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
                submitButton.innerHTML = 'Sign in';
                
                // Show error message
                showMessage('danger', 'An error occurred. Please try again.');
                console.error('Error:', error);
            });
        });
    }
    
    /**
     * Display message in the messages container
     * 
     * @param {string} type Message type (success, danger, warning, info)
     * @param {string} message Message text
     */
    function showMessage(type, message) {
        const alertElement = document.createElement('div');
        alertElement.className = `alert alert-${type}`;
        alertElement.innerHTML = message;
        
        messagesContainer.appendChild(alertElement);
        
        // Scroll to message
        messagesContainer.scrollIntoView({ behavior: 'smooth' });
    }
});