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
   {l s='Create an account' mod='pslauth'}
 {/block}
 
 {block name='page_content'}
   <div class="register-form">
     <section>
       <div id="pslauth-messages"></div>
       
       <form id="pslauth-register-form" action="{$link->getModuleLink('pslauth', 'registerapi', [], true)}" method="post">
         <section>
           <div class="form-group row">
             <label class="col-md-3 form-control-label required" for="pslauth-firstname">
               {l s='First name' mod='pslauth'}
             </label>
             <div class="col-md-9">
               <input type="text" class="form-control" name="firstname" id="pslauth-firstname" required>
             </div>
           </div>
 
           <div class="form-group row">
             <label class="col-md-3 form-control-label required" for="pslauth-lastname">
               {l s='Last name' mod='pslauth'}
             </label>
             <div class="col-md-9">
               <input type="text" class="form-control" name="lastname" id="pslauth-lastname" required>
             </div>
           </div>
            
           <div class="form-group row">
             <label class="col-md-3 form-control-label" for="pslauth-gender">
               {l s='Social title' mod='pslauth'}
             </label>
             <div class="col-md-9">
               <div class="form-control-valign">
                 <div class="radio-inline">
                   {if isset($genders) && $genders}
                     {foreach from=$genders item=gender}
                       <label class="radio-inline mr-2">
                         <input name="id_gender" type="radio" value="{$gender->id}">
                         {$gender->name}
                       </label>
                     {/foreach}
                   {else}
                     <label class="radio-inline mr-2">
                       <input name="id_gender" type="radio" value="1">
                       {l s='Mr.' mod='pslauth'}
                     </label>
                     <label class="radio-inline">
                       <input name="id_gender" type="radio" value="2">
                       {l s='Mrs.' mod='pslauth'}
                     </label>
                   {/if}
                 </div>
               </div>
             </div>
           </div>
           
           <div class="form-group row">
             <label class="col-md-3 form-control-label" for="pslauth-birthday">
               {l s='Birth date' mod='pslauth'}
             </label>
             <div class="col-md-9">
               <input type="date" class="form-control" name="birthday" id="pslauth-birthday" 
                      placeholder="YYYY-MM-DD">
               <small class="form-text text-muted">
                 {l s='Format: YYYY-MM-DD (e.g., 1980-01-31)' mod='pslauth'}
               </small>
             </div>
           </div>
 
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
               <small class="form-text text-muted">
                 {l s='Your password must be at least 5 characters long.' mod='pslauth'}
               </small>
             </div>
           </div>
 
           <div class="form-group row">
             <label class="col-md-3 form-control-label" for="pslauth-newsletter">
             </label>
             <div class="col-md-9">
               <span class="custom-checkbox">
                 <label>
                   <input name="newsletter" id="pslauth-newsletter" type="checkbox" value="1">
                   <span><i class="material-icons rtl-no-flip checkbox-checked">&#xE834;</i></span>
                   {l s='Sign up for our newsletter' mod='pslauth'}
                   <br><em>{l s='You may unsubscribe at any moment. For that purpose, please find the unsubscribe link in the newsletter.' mod='pslauth'}</em>
                 </label>
               </span>
             </div>
           </div>
 
           <div class="form-group row">
             <label class="col-md-3 form-control-label" for="pslauth-psgdpr">
             </label>
             <div class="col-md-9">
               <span class="custom-checkbox">
                 <label>
                   <input name="psgdpr" id="pslauth-psgdpr" type="checkbox" value="1" required>
                   <span><i class="material-icons rtl-no-flip checkbox-checked">&#xE834;</i></span>
                   {l s='I agree to the terms and conditions and the privacy policy' mod='pslauth'}
                 </label>
               </span>
             </div>
           </div>
 
           {if isset($back) && $back}
             <input type="hidden" name="back" value="{$back}">
           {/if}
 
           <div class="form-group row">
             <div class="col-md-9 offset-md-3">
               <button id="pslauth-submit-register" class="btn btn-primary" type="submit">
                 {l s='Create account' mod='pslauth'}
               </button>
             </div>
           </div>
         </section>
       </form>
     </section>
 
     <hr>
 
     <div class="login-instead pslauth-login-instead text-center mt-3">
       <a href="{$link->getModuleLink('pslauth', 'login', [], true)}" class="btn btn-outline-primary">
         {l s='Already have an account? Sign in instead' mod='pslauth'}
       </a>
     </div>
   </div>
 {/block}