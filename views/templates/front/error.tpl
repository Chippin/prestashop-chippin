
<div>
	<h3>{l s='An error occurred' mod='chippin'}:</h3>
	<ul class="alert alert-danger">
		{foreach from=$errors item='error'}
			<li>{l s=$error|escape:'htmlall':'UTF-8' mod='chippin'}.</li>
		{/foreach}
	</ul>
</div>
