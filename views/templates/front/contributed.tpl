<div class="conf confirmation">
    {l s='Thank you for contributing via Chippin' mod='chippin'}
</div>

<div>
    {assign var='odd' value=0}

    {foreach $products as $product}

        {assign var='productId' value=$product.id_product}
        {assign var='productAttributeId' value=$product.id_product_attribute}
        {assign var='quantityDisplayed' value=0}
        {assign var='odd' value=($odd+1)%2}

        {* Display the product line *}
        <p>
            <strong>
                product_{$product.id_product|intval}_{$product.id_product_attribute|intval}_{if $quantityDisplayed > 0}nocustom{else}0{/if}_{$product.id_address_delivery|intval}
            </strong>
        </p>

        {include file="./shopping-cart-product-line.tpl" productLast=$product@last productFirst=$product@first}

    {/foreach}
</div>
