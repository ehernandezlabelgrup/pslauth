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
   {l s='Delete My Account' mod='pslauth'}
 {/block}
 
 {block name='page_content'}
   <div class="delete-account-form">
     <section>
       <div id="pslauth-messages"></div>
       
       <div class="alert alert-warning">
         <h4>{l s='Important Warning' mod='pslauth'}</h4>
         <p>{l s='You are about to permanently delete your account. This action cannot be undone.' mod='pslauth'}</p>
         <p>{l s='Deleting your account will permanently remove:' mod='pslauth'}</p>
         <ul>
           <li>{l s='Your personal information' mod='pslauth'}</li>
           <li>{l s='Your order history' mod='pslauth'}</li>
           <li>{l s='Your saved addresses' mod='pslauth'}</li>
           <li>{l s='Any other data associated with your account' mod='pslauth'}</li>
         </ul>
         <p>{l s='You will not be able to recover this information after your account is deleted.' mod='pslauth'}</p>
       </div>
       
       <form id="pslauth-delete-account-form" action="{$link->getModuleLink('pslauth', 'deleteaccountapi', [], true)}" method="post">
         <section>
           <div class="form-group row">
             <label class="col-md-3 form-control-label required" for="pslauth-email">
               {l s='Your Email Address' mod='pslauth'}
             </label>
             <div class="col-md-9">
               <input type="email" class="form-control" name="email" id="pslauth-email" value="{$customer_email}" required readonly>
               <small class="form-text text-muted">
                 {l s='Please confirm this is your email address.' mod='pslauth'}
               </small>
             </div>
           </div>
 
           <div class="form-group row">
             <label class="col-md-3 form-control-label required" for="pslauth-confirmation">
               {l s='Confirmation' mod='pslauth'}
             </label>
             <div class="col-md-9">
               <input type="text" class="form-control" name="confirmation" id="pslauth-confirmation" required placeholder="DELETE-MY-ACCOUNT">
               <small class="form-text text-muted">
                 {l s='To confirm deletion, please type "DELETE-MY-ACCOUNT" in the field above.' mod='pslauth'}
               </small>
             </div>
           </div>
 
           <div class="form-group row">
             <div class="col-md-9 offset-md-3">
               <button id="pslauth-submit-delete" class="btn btn-danger" type="submit">
                 {l s='Permanently Delete My Account' mod='pslauth'}
               </button>
               <a href="{$link->getPageLink('my-account')}" class="btn btn-outline-secondary ml-2">
                 {l s='Cancel' mod='pslauth'}
               </a>
             </div>
           </div>
         </section>
       </form>
     </section>
   </div>
 {/block}