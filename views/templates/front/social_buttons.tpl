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

 {if isset($pslauth_social_enabled) && $pslauth_social_enabled}
  <div class="pslauth-social-buttons mt-3 mb-3">
    <div class="pslauth-social-separator">
      <span>{l s='Or sign in with' mod='pslauth'}</span>
    </div>
    
    <div class="pslauth-social-providers text-center mt-3">
      {if isset($pslauth_google_enabled) && $pslauth_google_enabled}
        <a href="{$link->getModuleLink('pslauth', 'social', ['provider' => 'google', 'back' => $back|default:''], true)}" class="btn pslauth-btn-google">
          <i class="icon-google"></i> {l s='Google' mod='pslauth'}
        </a>
      {/if}
      
      {if isset($pslauth_apple_enabled) && $pslauth_apple_enabled}
        <a href="{$link->getModuleLink('pslauth', 'social', ['provider' => 'apple', 'back' => $back|default:''], true)}" class="btn pslauth-btn-apple">
          <i class="icon-apple"></i> {l s='Apple' mod='pslauth'}
        </a>
      {/if}
      
      {* Add more social providers here in the future *}
    </div>
  </div>
{/if}