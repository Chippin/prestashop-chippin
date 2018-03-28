<div class="row">
  <div class="col-xs-12">
    <div class="payment_module chippin_checkout_button">

      <form action="{$chippin_url|escape:'htmlall':'UTF-8'}" method="POST">
        <button type="submit" class="chippin-payment">
          {l s='Split the cost' mod='chippin'}
          <span>({l s='Pay with Chippin to split the cost with your friends and family' mod='chippin'})</span>
        </button>

        <input type="hidden" name="merchant_id" value="{$chippin_merchant_id}">
        <input type="hidden" name="merchant_order_id" value="{$cart_id}">
        <input type="hidden" name="duration" value="{$chippin_duration}">
        <input type="hidden" name="grace_period" value="{$chippin_grace_period}">
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
      </form>

    </div>
  </div>
</div>
