{*
* Simpleweb
*}

<div id="chippin_header" class="grid_9 alpha omega">
    <a id="chippin_logo" href="{$base_dir}" title="{$shop_name|escape:'htmlall':'UTF-8'}">
        <img class="logo" src="{$logo_url}" alt="{$shop_name|escape:'htmlall':'UTF-8'}" {if $logo_image_width}width="{$logo_image_width}"{/if} {if $logo_image_height}height="{$logo_image_height}" {/if}/>
    </a>
    <div id="chippin_header_right" class="grid_9 omega"></div>
</div>

{include file="./shopping-cart.tpl"}

<form action="http://staging.chippin.co.uk/sandbox/new" method="POST">

    <input type="hidden" name="merchant_id" value="1">
    <input type="hidden" name="merchant_order_id" value="123">
    <input type="hidden" name="duration" value="1">
    <input type="hidden" name="currency_code" value="gbp">
    <input type="hidden" name="hmac" value="4fda2f7a4feec9bcedfe0d13a25b6dbb17f7033b4402c977958a913763b1209b">
    <input type="hidden" name="total_amount" value="500000">

    {foreach $products as $product}

        <input type="hidden" name="products[][label]" value="{$product.name|escape:'htmlall':'UTF-8'}">
        <input type="hidden" name="products[][image]" value="{$link->getImageLink($product.link_rewrite, $product.id_image, 'small_default')|escape:'html':'UTF-8'}">
        <input type="hidden" name="products[][amount]" value="1000">

    {/foreach}

    <input type="text" name="first_name" id="first_name" value="">
    <input type="text" name="last_name" id="last_name" value="">
    <input type="text" name="email" id="email" value="">

    <p><input type="submit" value="Created Chippin"></p>

</form>
