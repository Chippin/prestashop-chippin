{*
* Simpleweb
*}

{capture name=path}{l s='Place Order' mod='chippin'}{/capture}

<h1 class="page-heading">{l s='Place Order' mod='chippin'}</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<form action="{$link->getModuleLink('chippin', 'checkout', [], true)|escape:'htmlall':'UTF-8'}" method="post" class="chippin_purchase_form">
    <div class="box cheque-box">
        <h3 class="page-subheading">{l s='chippin payment' mod='chippin'}</h3>
        <input type="hidden" name="confirm" value="1" />
        <div class="chippin_container">
            <div class="chippin_logo_left">
                <img src="{$this_path|escape:'htmlall':'UTF-8'}views/img/logos.png" alt="{l s='chippin payment' mod='chippin'}" />
            </div>
            <div>
                <p>{l s='You have chosen to pay with chippin’s local payment options.' mod='chippin'}</p>
                <p>{l s='The total amount of your order is' mod='chippin'}
                    <span id="amount_{$currencies.0.id_currency|intval}" class="price chippin_price">{convertPrice price=$total}</span>.
					{if $usd_total}{l s='You\'ll be charged' mod='chippin'} <span class="price chippin_price">{$usd_total|escape:'htmlall':'UTF-8'}</span>{/if}
					{if $use_taxes == 1}
						{l s='(tax incl.)' mod='chippin'}
					{/if}
				</p>
            </div>
            <div class="clear"></div>
        </div>
        <p>
            <b>{l s='To complete your purchase, you’ll be directed to a secure order page with local payment options displayed based on your country' mod='chippin'}.</b>
        </p>
    </div>

    <p class="cart_navigation clearfix" id="cart_navigation">
        <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" class="button-exclusive btn btn-default">
            <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='chippin'}
        </a>
        <button type="submit" class="button btn btn-default button-medium">
            <span>{l s='Continue to secure order form' mod='chippin'}<i class="icon-chevron-right right"></i></span>
        </button>
    </p>
    {*<p class="cart_navigation">
        <a href="{$link->getPageLink('order', true)|escape:'htmlall':'UTF-8'}?step=3" class="button_large">{l s='Other payment methods' mod='chippin'}</a>
        <input type="submit" name="submit" value="{l s='Continue to secure order form' mod='chippin'}" class="exclusive_large" />
    </p>*}
</form>
