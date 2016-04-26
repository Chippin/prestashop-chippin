{*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
*         DISCLAIMER   *
* ***************************************
* Do not edit or add to this file if you wish to upgrade Prestashop to newer
* versions in the future.
* ****************************************************
* Simpleweb
*}
<div class="col-lg-12">
    <div class="panel">
        <fieldset>
            <legend><img src="../img/admin/money.gif">{l s='Full refund via chippin' mod='chippin'}</legend>
			{if $chippin_error}
				<div class="alert alert-danger">{$chippin_error|escape:'htmlall':'UTF-8'}</div>
			{else}
				{if !$chippin_refunded}
					<form method="post" action="" name="refund">
						<div>{l s='chippin order reference number:' mod='chippin'} <b>{$chippin_reference_number|escape:'htmlall':'UTF-8'}</b></div>
						<p></p>
						<input type="hidden" name="chippin_reference_number" value="{$chippin_reference_number|escape:'htmlall':'UTF-8'}" />
						<input type="hidden" name="id_chippin_order" value="{$id_chippin_order|escape:'htmlall':'UTF-8'}" />
						<input type="submit" name="process_chippin_refund" value ="{l s='Process Full Refund' mod='chippin'}" class="btn btn-default" />
					</form>
				{else}
					<div class="alert alert-warning">{l s='Refunded' mod='chippin'}</div>
				{/if}
			{/if}
        </fieldset>

        {literal}
        <script type="text/javascript">
            $("input[name=process_chippin_refund]").click(function(){
                if (confirm('{/literal}{l s='Are you sure you want to refund this order? This action cannot be undone' mod='chippin'}{literal}')) {
                    return true;
                } else {
                    event.stopPropagation();
                    event.preventDefault();
                };
            });
        </script>
        {/literal}
    </div>
</div>
