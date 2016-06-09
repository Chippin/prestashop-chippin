
{if $status == 'completed'}
	<img src="/modules/chippin/views/img/logo.png" alt="Chippin" width="150">

	<hr>

	<h4>{l s='Your Chippin with %s has been completed.' sprintf=$shop_name mod='chippin'}</h4>
	<p class="payment-return-padding-top">{l s='Thank you for completing your chippin, we will email your order completion details shortly.' mod='chippin'}</p>
	<p>{l s='If you have questions, comments or concerns, please contact our' mod='chippin'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='chippin'}</a>.</p>

	<hr>

	<h4>Order details</h4>
	<table class="table-data-sheet">
		<tbody>
			<tr class="odd">
				<td>{l s='Amount' mod='chippin'}</td>
				<td>{$total_to_pay}</td>
			</tr>
			<tr class="even">
				<td>{l s='Order Reference' mod='chippin'}</td>
				<td>{$reference}</td>
			</tr>
		</tbody>
	</table>
{elseif $status == 'paid'}
	<img src="/modules/chippin/views/img/logo.png" alt="Chippin" width="150">

	<hr>

	<h4>{l s='Your Chippin with %s is now paid.' sprintf=$shop_name mod='chippin'}</h4>
	<p class="payment-return-padding-top">{l s='Thank you for using Chippin.' mod='chippin'}</p>
	<p>{l s='If you have questions, comments or concerns, please contact our' mod='chippin'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='chippin'}</a>.</p>

	<hr>

	<h4>Order details</h4>
	<table class="table-data-sheet">
		<tbody>
			<tr class="odd">
				<td>{l s='Amount' mod='chippin'}</td>
				<td>{$total_paid}</td>
			</tr>
			<tr class="even">
				<td>{l s='Order Reference' mod='chippin'}</td>
				<td>{$reference}</td>
			</tr>
		</tbody>
	</table>
{else}
	<p class="warning">
		{l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='chippin'}
		<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='chippin'}</a>.
	</p>
{/if}
