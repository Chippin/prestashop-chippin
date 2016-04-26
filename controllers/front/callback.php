<?php
/**
 * NOTICE OF LICENCE
 */

/**
 * Class chippincallbackModuleFrontController
 *
 * after placing order at chippin payment gateway uses redirect to this controller
 */
class chippinCallbackModuleFrontController extends ModuleFrontController
{
	/**
	 * flag allow use ssl for this controller
	 *
	 * @var bool
	 */
	public $ssl = true;

	/**
	 * hide header and footer
	 */
	public function init()
	{
		$_GET['content_only'] = 1;
		parent::init();
	}

	/**
	 * redirect to order-confirmation (success) page
	 */
	public function initContent()
	{
		parent::initContent();

		//create order
		$customer = new Customer($this->context->cart->id_customer);
		if (!Validate::isLoadedObject($customer))
			Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');

		$id_cart = $this->context->cart->id;
		$total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
		$this->module->validateOrder((int)$this->context->cart->id,
				Configuration::get('BS_OS_WAITING'), $total, $this->module->displayName, null, array(), null, false,
				$customer->secure_key);

		//change order status if needed
		$order_obj = new Order($this->module->currentOrder);
		$order_state_obj = new OrderState(Configuration::get('BS_OS_PAYMENT_VALID'));
		if (Validate::isLoadedObject($order_state_obj) && $order_obj->current_state != $order_state_obj->id)
		{
			$ipn_obj = new chippinIpn();
			$ipn_obj->changeOrderStatus($order_obj, (int)Configuration::get('BS_OS_PAYMENT_VALID'), $this->errors);
		}
		Configuration::updateValue('chippin_CONFIGURATION_OK', true);

		$this->context->smarty->assign(array(
			'chippin_order_confirmation_url' => Context::getContext()->link->getPageLink('order-confirmation', null, null, array(
				'id_order' => $this->module->currentOrder,
				'id_cart' => $id_cart,
				'id_module' => $this->module->id,
				'key' => $order_obj->secure_key,
				))
		));

		$this->setTemplate('callback.tpl');
	}

}
