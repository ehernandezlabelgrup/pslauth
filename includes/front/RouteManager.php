<?php
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

if (!defined('_PS_VERSION_')) {
    exit;
}

class PSLAuthRouteManager
{
    public function getModuleRoutes()
    {
        return [
            'module-pslauth-login' => [
                'controller' => 'login',
                'rule' => 'auth/login',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => 'pslauth',
                ]
            ],
            'module-pslauth-register' => [
                'controller' => 'register',
                'rule' => 'auth/register',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => 'pslauth',
                ]
            ],
            'module-pslauth-social' => [
                'controller' => 'social',
                'rule' => 'auth/social/{provider}',
                'keywords' => [
                    'provider' => ['regexp' => '[a-zA-Z0-9_-]+', 'param' => 'provider']
                ],
                'params' => [
                    'fc' => 'module',
                    'module' => 'pslauth',
                ]
            ],
            'module-pslauth-callback' => [
                'controller' => 'callback',
                'rule' => 'auth/callback/{provider}',
                'keywords' => [
                    'provider' => ['regexp' => '[a-zA-Z0-9_-]+', 'param' => 'provider']
                ],
                'params' => [
                    'fc' => 'module',
                    'module' => 'pslauth',
                ]
            ],
            'module-pslauth-deleteaccount' => [
                'controller' => 'deleteaccount',
                'rule' => 'auth/delete-account',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => 'pslauth',
                ]
            ],
            'module-pslauth-loginapi' => [
                'controller' => 'loginapi',
                'rule' => 'auth/api/login',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => 'pslauth',
                ]
            ],
            'module-pslauth-registerapi' => [
                'controller' => 'registerapi',
                'rule' => 'auth/api/register',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => 'pslauth',
                ]
            ],
            'module-pslauth-meapi' => [
                'controller' => 'meapi',
                'rule' => 'auth/api/me',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => 'pslauth',
                ]
            ],
            'module-pslauth-deleteaccountapi' => [
                'controller' => 'deleteaccountapi',
                'rule' => 'auth/api/delete-account',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => 'pslauth',
                ]
            ]
        ];
    }
}