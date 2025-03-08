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

// PSLAuth namespace
window.PSLAuth = window.PSLAuth || {};

// General configuration
window.PSLAuth.config = {
    debug: false,
    apiBaseUrl: '',
    version: '1.0.0'
};

// Initialize module
window.PSLAuth.init = function(options) {
    // Merge options with default config
    if (options) {
        Object.assign(window.PSLAuth.config, options);
    }
    
    // Set API base URL if not provided
    if (!window.PSLAuth.config.apiBaseUrl) {
        const baseUrl = window.location.origin + window.prestashop.urls.base_url;
        window.PSLAuth.config.apiBaseUrl = baseUrl + 'module/pslauth/api/';
    }
    
    // Log initialization if debug is enabled
    if (window.PSLAuth.config.debug) {
        console.log('PSLAuth initialized with config:', window.PSLAuth.config);
    }
};

// Helper function to make API requests
window.PSLAuth.apiRequest = function(endpoint, method, data) {
    const url = window.PSLAuth.config.apiBaseUrl + endpoint;
    
    const options = {
        method: method || 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-API-Request': 'true'
        }
    };
    
    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }
    
    return fetch(url, options).then(response => response.json());
};

// Document ready event
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the module
    window.PSLAuth.init({
        debug: false
    });
});