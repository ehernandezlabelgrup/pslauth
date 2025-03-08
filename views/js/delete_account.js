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
    const deleteForm = document.getElementById('pslauth-delete-account-form');
    const messagesContainer = document.getElementById('pslauth-messages');
    
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Final confirmation dialog
            if (!confirm('Are you absolutely sure you want to delete your account? This action CANNOT be undone.')) {
                return;
            }
            
            // Clear previous messages
            messagesContainer.innerHTML = '';
            
            // Disable submit button
            const submitButton = document.getElementById('pslauth-submit-delete');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="material-icons">hourglass_empty</i> Processing...';
            
            // Get form data
            const formData = new FormData(deleteForm);
            const jsonData = {};
            
            // Convert FormData to JSON
            for (const [key, value] of formData.entries()) {
                jsonData[key] = value;
            }
            
            // Basic validation
            if (jsonData.confirmation !== 'DELETE-MY-ACCOUNT') {
                showMessage('danger', 'You must type "DELETE-MY-ACCOUNT" to confirm deletion.');
                submitButton.disabled = false;
                submitButton.innerHTML = 'Permanently Delete My Account';
                return;
            }
            
            // Send AJAX request
            fetch(deleteForm.action, {
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
                submitButton.innerHTML = 'Permanently Delete My Account';
                
                if (data.success) {
                    // Show success message
                    showMessage('success', data.message);
                    
                    // Disable form
                    Array.from(deleteForm.elements).forEach(element => {
                        element.disabled = true;
                    });
                    
                    // Redirect after successful deletion
                    if (data.data && data.data.redirect_url) {
                        setTimeout(function() {
                            window.location.href = data.data.redirect_url;
                        }, 3000);
                    }
                } else {
                    // Show error message
                    showMessage('danger', data.message);
                }
            })
            .catch(error => {
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.innerHTML = 'Permanently Delete My Account';
                
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