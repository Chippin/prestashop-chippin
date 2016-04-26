<?php
/**
* Simpleweb
 */

require_once _PS_MODULE_DIR_.'chippin/includer.php';

/**
 * Class chippincheckoutModuleFrontController
 *
 * process action with module on payment method page
 */
class chippincheckoutModuleFrontController extends ModuleFrontController
{

	/**
	 * flag allow use ssl for this controller
	 *
	 * @var bool
	 */
	public $ssl = true;
	private static $usd_amount = null;

	/**
	 * check access for using this module
	 */
	public function postProcess()
	{
		if ($this->context->cart->id_customer == 0 ||
			$this->context->cart->id_address_delivery == 0 ||
			$this->context->cart->id_address_invoice == 0 || !$this->module->active)
			Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');

		/* Check that this payment option is still available in case the customer changed
		 * his address just before the end of the checkout process */
		$authorized = false;
		foreach (Module::getPaymentModules() as $module)
		{
			if ($module['name'] == 'chippin')
			{
				$authorized = true;
				break;
			}
		}

		if (!$authorized)
			die(Tools::displayError('This payment method is not available.'));

		$customer = new Customer($this->context->cart->id_customer);
		if (!Validate::isLoadedObject($customer))
			Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');
	}

	/**
	 * @param float $total
	 */
	public function assignDefaultTotals($total)
	{
		$total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
		$current_currency_code = $this->context->currency->iso_code;
		$usd_currency_id = (int)Currency::getIdByIsoCode('USD');
		$usd_total = false;
		if (!chippin::isLocallySupported($current_currency_code) && $usd_currency_id)
		{
			$conversion_rate = $this->context->currency->conversion_rate?$this->context->currency->conversion_rate:1;
			$base = $total / $conversion_rate;
			$usd_total = Currency::getCurrencyInstance($usd_currency_id)->conversion_rate * $base;
		}
		$this->context->smarty->assign('usd_total', Tools::displayPrice($usd_total, $usd_currency_id));
	}

	public function generateHash()
	{
		return hash('sha256', '5ba3e1caf655f11b65c2bcef3ec55299a174072a' + 'completed' + '100000' + '123' + '50000' + '72' + 'gbp');
	}

	/**
	 * display form for placing order
	 * after confirm order, create new order and redirect to hidden form with payment gateway
	 */
	public function initContent()
	{
		$this->display_column_left = false;
		$this->display_column_right = false;
		parent::initContent();

		if (Tools::getValue('confirm'))
		{
			var_dump($this->generateHash());

			var_dump("placing order");

			$this->context->smarty->assign(array(
				'chippin_hidden_form_url' => $this->module->getCheckoutUrl(),
			));
			$this->assignSummaryInformations();

			var_dump($this->assignSummaryInformations());

			$this->setTemplate('checkout-form.tpl');
		}
		else
		{
			var_dump("just before placing order");

			$total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
			$this->assignDefaultTotals($total);

			$this->context->smarty->assign(array(
				'total' => $total,
				'this_path' => $this->module->getPathUri(),
				'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
			));

			$this->setTemplate('validation.tpl');
		}
	}

	protected function assignSummaryInformations()
	{
		$this->islogged = $this->context->customer->id && Customer::customerIdExistsStatic((int)$this->context->cookie->id_customer);
		$summary = $this->context->cart->getSummaryDetails();
		$customized_datas = Product::getAllCustomizedDatas($this->context->cart->id);

		// override customization tax rate with real tax (tax rules)
		if ($customized_datas)
		{
			foreach ($summary['products'] as &$product_update)
			{
				$product_id = (int)isset($product_update['id_product']) ?
						$product_update['id_product'] : $product_update['product_id'];
				$product_attribute_id = (int)isset($product_update['id_product_attribute']) ?
						$product_update['id_product_attribute'] : $product_update['product_attribute_id'];

				if (isset($customized_datas[$product_id][$product_attribute_id]))
					$product_update['tax_rate'] = Tax::getProductTaxRate($product_id, $this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
			}

			Product::addCustomizationPrice($summary['products'], $customized_datas);
		}

		$cart_product_context = Context::getContext()->cloneContext();
		foreach ($summary['products'] as &$product)
		{
			$product['quantity'] = $product['cart_quantity']; // for compatibility with 1.2 themes

			if ($cart_product_context->shop->id != $product['id_shop'])
				$cart_product_context->shop = new Shop((int)$product['id_shop']);
				$null = null;
				$product['price_without_specific_price'] = Product::getPriceStatic(
					$product['id_product'], !Product::getTaxCalculationMethod(),
					$product['id_product_attribute'], 2, null, false, false, 1, false, null, null, null, $null, true,
					true, $cart_product_context
				);

			if (Product::getTaxCalculationMethod())
				$product['is_discounted'] = $product['price_without_specific_price'] != $product['price'];
			else
				$product['is_discounted'] = $product['price_without_specific_price'] != $product['price_wt'];
		}

		// Get available cart rules and unset the cart rules already in the cart
		$available_cart_rules = CartRule::getCustomerCartRules($this->context->language->id,
				(isset($this->context->customer->id) ? $this->context->customer->id : 0), true, true, true,
				$this->context->cart);
		$cart_cart_rules = $this->context->cart->getCartRules();
		foreach ($available_cart_rules as $key => $available_cart_rule)
		{
			if (!$available_cart_rule['highlight'] || strpos($available_cart_rule['code'], 'BO_ORDER_') === 0)
			{
				unset($available_cart_rules[$key]);
				continue;
			}
			foreach ($cart_cart_rules as $cart_cart_rule)
				if ($available_cart_rule['id_cart_rule'] == $cart_cart_rule['id_cart_rule'])
				{
					unset($available_cart_rules[$key]);
					continue 2;
				}
		}

		$show_option_allow_separate_package = (!$this->context->cart->isAllProductsInStock(true) && Configuration::get('PS_SHIP_WHEN_AVAILABLE'));

		$this->context->smarty->assign($summary);
		$this->context->smarty->assign(array(
			'token_cart' => Tools::getToken(false),
			'isLogged' => $this->islogged,
			'isVirtualCart' => $this->context->cart->isVirtualCart(),
			'productNumber' => $this->context->cart->nbProducts(),
			'voucherAllowed' => CartRule::isFeatureActive(),
			'shippingCost' => $this->context->cart->getOrderTotal(true, Cart::ONLY_SHIPPING),
			'shippingCostTaxExc' => $this->context->cart->getOrderTotal(false, Cart::ONLY_SHIPPING),
			'customizedDatas' => $customized_datas,
			'CUSTOMIZE_FILE' => Product::CUSTOMIZE_FILE,
			'CUSTOMIZE_TEXTFIELD' => Product::CUSTOMIZE_TEXTFIELD,
			'lastProductAdded' => $this->context->cart->getLastProduct(),
			'displayVouchers' => $available_cart_rules,
			'currencySign' => $this->context->currency->sign,
			'currencyRate' => $this->context->currency->conversion_rate,
			'currencyFormat' => $this->context->currency->format,
			'currencyBlank' => $this->context->currency->blank,
			'show_option_allow_separate_package' => $show_option_allow_separate_package,
			'smallSize' => Image::getSize(ImageType::getFormatedName('small')),
		));

		$this->assignDefaultTotals($this->context->cart->getOrderTotal(true, Cart::BOTH));

		$this->context->smarty->assign(array(
			'HOOK_SHOPPING_CART' => Hook::exec('displayShoppingCartFooter', $summary),
			'HOOK_SHOPPING_CART_EXTRA' => Hook::exec('displayShoppingCart', $summary)
		));
	}

}
