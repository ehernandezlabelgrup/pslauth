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

 {extends file='page.tpl'}

 {block name='page_title'}
   {l s='Log in to your account' mod='pslauth'}
 {/block}
 
 {block name='page_content'}
   <div class="login-form">
     <section>
       <div id="pslauth-messages"></div>
       
       <form id="pslauth-login-form" action="{$link->getModuleLink('pslauth', 'loginapi', [], true)}" method="post">
         <section>
           <div class="form-group row">
             <label class="col-md-3 form-control-label required" for="pslauth-email">
               {l s='Email' mod='pslauth'}
             </label>
             <div class="col-md-9">
               <input type="email" class="form-control" name="email" id="pslauth-email" required>
             </div>
           </div>
 
           <div class="form-group row">
             <label class="col-md-3 form-control-label required" for="pslauth-password">
               {l s='Password' mod='pslauth'}
             </label>
             <div class="col-md-9">
               <div class="input-group js-parent-focus">
                 <input type="password" class="form-control js-child-focus js-visible-password" name="password" id="pslauth-password" required>
                 <span class="input-group-btn">
                   <button class="btn" type="button" data-action="show-password" data-text-show="{l s='Show' mod='pslauth'}" data-text-hide="{l s='Hide' mod='pslauth'}">
                     {l s='Show' mod='pslauth'}
                   </button>
                 </span>
               </div>
             </div>
           </div>
 
           <div class="form-group row">
             <label class="col-md-3 form-control-label" for="pslauth-stay-logged-in">
             </label>
             <div class="col-md-9">
               <span class="custom-checkbox">
                 <label>
                   <input type="checkbox" name="stay_logged_in" id="pslauth-stay-logged-in" value="1">
                   <span><i class="material-icons rtl-no-flip checkbox-checked">&#xE834;</i></span>
                   {l s='Remember me' mod='pslauth'}
                 </label>
               </span>
             </div>
           </div>
 
           {if isset($back) && $back}
             <input type="hidden" name="back" value="{$back}">
           {/if}
 
           <div class="form-group row">
             <div class="col-md-9 offset-md-3">
               <button id="pslauth-submit" class="btn btn-primary" type="submit">
                 {l s='Sign in' mod='pslauth'}
               </button>
             </div>
           </div>
         </section>
       </form>
       {include file='module:pslauth/views/templates/front/social_buttons.tpl'}
     </section>
 
     <hr>
 
     <div class="no-account pslauth-no-account text-center mt-3">
     <a href="{$urls.base_url}auth/register" class="btn btn-outline-primary">
     {l s='No account? Create one here' mod='pslauth'}
 </a>
     </div>
 
     <div class="forgot-password pslauth-forgot-password text-center mt-3">
       <a href="{$link->getPageLink('password')}" rel="nofollow">
         {l s='Forgot your password?' mod='pslauth'}
       </a>
     </div>
   </div>
 {/block}