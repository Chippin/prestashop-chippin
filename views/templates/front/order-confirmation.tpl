{*
* Simpleweb
*}

{if $chippin_order.valid == 1}
<div class="conf confirmation">
	{l s='Congratulations! Your payment is done, and your order has been saved under' mod='chippin'}
	{if isset($chippin_order.reference)}
		{l s='the reference' mod='chippin'} <b>{$chippin_order.reference|escape:'html':'UTF-8'}</b>
	{else}
		{l s='the ID' mod='chippin'} <b>{$chippin_order.id|escape:'html':'UTF-8'}</b>
	{/if}.
	<br /><br />
	{l s='The total amount of this order is' mod='chippin'} <span class="price">{$chippin_order.total_to_pay|escape:'htmlall':'UTF-8'}</span>
</div>
{else}
<div class="error">
	{l s='Unfortunately, an error occurred during the transaction.' mod='chippin'}<br /><br />
	{if isset($chippin_order.reference)}
		({l s='Your Order\'s Reference:' mod='chippin'} <b>{$chippin_order.reference|escape:'html':'UTF-8'}</b>)
	{else}
		({l s='Your Order\'s ID:' mod='chippin'} <b>{$chippin_order.id|escape:'html':'UTF-8'}</b>)
	{/if}
</div>
{/if}
