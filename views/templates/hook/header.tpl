{**
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
 *}

 {if isset($pslauth_live_mode) && $pslauth_live_mode}
    <script>
      var pslauthConfig = {
        baseUrl: '{$urls.base_url}',
        moduleUrl: '{$urls.base_url}module/pslauth/',
        isLoggedIn: {if $customer.is_logged}true{else}false{/if}
      };
    </script>
  {/if}