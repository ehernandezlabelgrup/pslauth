<?php
require_once _PS_MODULE_DIR_ . 'pslauth/classes/PSLAuthUser.php';
require_once _PS_MODULE_DIR_ . 'pslauth/classes/PSLAuthAPI.php';
require_once _PS_MODULE_DIR_ . 'pslauth/classes/PSLAuthSocialProvider.php';
require_once _PS_MODULE_DIR_ . 'pslauth/classes/PSLAuthGoogleProvider.php';
require_once _PS_MODULE_DIR_ . 'pslauth/classes/PSLAuthAppleProvider.php';

// // Cargar servicios
// require_once _PS_MODULE_DIR_ . 'pslauth/includes/services/AuthenticationService.php';
// require_once _PS_MODULE_DIR_ . 'pslauth/includes/services/SocialLoginService.php';

// Cargar admin
require_once _PS_MODULE_DIR_ . 'pslauth/includes/admin/AdminConfig.php';
require_once _PS_MODULE_DIR_ . 'pslauth/includes/admin/TabManager.php';

// // Cargar front
require_once _PS_MODULE_DIR_ . 'pslauth/includes/front/HookHandler.php';
require_once _PS_MODULE_DIR_ . 'pslauth/includes/front/RouteManager.php';