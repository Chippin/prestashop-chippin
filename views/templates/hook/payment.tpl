<div class="row">
    <div class="col-xs-12 col-md-12">
        <p class="payment_module" id="chippin_payment_button">

            <form action="{$chippin_url|escape:'htmlall':'UTF-8'}" method="POST">

                <input type="hidden" name="merchant_id" value="{$chippin_merchant_id}">
                <input type="hidden" name="merchant_order_id" value="{$secure_key}">
                <input type="hidden" name="duration" value="{$chippin_duration}">
                <input type="hidden" name="currency_code" value="{$currency}">
                <input type="hidden" name="hmac" value="{$chippin_hmac}">
                <input type="hidden" name="total_amount" value="{$price_in_pence}">

                {foreach $products as $product}
                    <input type="hidden" name="products[][label]" value="{$product.name|escape:'htmlall':'UTF-8'} x {$product.cart_quantity|intval}">
                    <input type="hidden" name="products[][image]" value="{$link->getImageLink($product.link_rewrite, $product.id_image, 'small_default')|escape:'html':'UTF-8'}">
                    <input type="hidden" name="products[][amount]" value="{$product.price_in_pence}">
                {/foreach}

                <input type="hidden" name="first_name" id="first_name" value="{$cookie->customer_firstname}">
                <input type="hidden" name="last_name" id="last_name" value="{$cookie->customer_lastname}">
                <input type="hidden" name="email" id="email" value="{$cookie->email}">

                <p><input type="submit" value="Pay with Chippin"></p>

            </form>

        </p>
    </div>
</div>
