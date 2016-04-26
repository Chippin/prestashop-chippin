<?php
/**
* Simpleweb
 */

require_once _PS_MODULE_DIR_.'chippin/includer.php';

/**
 * IPN request processing
 *
 */
class ChippinIpn extends ChippinApi {

	const PARAM_TRANSATION_TYPE = 'transactionType';
	const PARAM_REFERENCE_NUMBER = 'referenceNumber';
	const PARAM_ORDER_INCREMENT_ID = 'prestashop_order_id';
	const PARAM_CONTRACT_ID = 'contractId';
	const PARAM_AUTH_KEY = 'authKey';
	const TRANSACTION_TYPE_CHARGE = 'CHARGE';
	const TRANSACTION_TYPE_REFUND = 'REFUND';
	const TRANSACTION_TYPE_CANCELLATION_REFUND = 'CANCELLATION_REFUND';
	const REFUND_ORDER_STATE = 7;

	/**
	 * create Ipn object
	 */
	public function __construct()
	{
		$this->is_debug_mode = Configuration::get('CHIPPIN_BUYNOW_DEBUG_MODE');
		$this->debug_log_name = 'log/chippin_buynow_ipn.log';
	}

	/**
	 * Check if authKey is valid.
	 *
	 * @return bool
	 */
	protected function isAuthKeyValid()
	{
		if (!Tools::isSubmit(self::PARAM_REFERENCE_NUMBER) || !Tools::isSubmit(self::PARAM_CONTRACT_ID) || !Tools::isSubmit(self::PARAM_AUTH_KEY))
			return false;

		$valid_auth_key_str = Tools::getValue(self::PARAM_REFERENCE_NUMBER)
				.Tools::getValue(self::PARAM_CONTRACT_ID)
				.Configuration::get('CHIPPIN_PROTECTION_KEY');

		return (md5($valid_auth_key_str) == Tools::getValue(self::PARAM_AUTH_KEY));
	}

	/**
	 * Process IPN request:
	 * validate Authenticatin Key
	 * call processing method depending on transaction otype
	 *
	 * @return bool
	 */
	public function processTransactionRequest()
	{
		$this->logRequestDebug();

		// validate auth key
		if (!$this->isAuthKeyValid())
		{
			chippin::log('IPN exception: Invalid auth key.');
			//throw new PrestaShopException('IPN exception: Invalid auth key');
			return;
		}

		// process request
		$transaction_type = Tools::getValue(self::PARAM_TRANSATION_TYPE);
		switch ($transaction_type)
		{
			case self::TRANSACTION_TYPE_CHARGE:
				return $this->processChargeTransactionRequest();

			case self::TRANSACTION_TYPE_REFUND:
			case self::TRANSACTION_TYPE_CANCELLATION_REFUND:
				return $this->processRefundTransactionRequest();

			default:
				chippin::log("Unknown transaction type `{$transaction_type}`.");
				//throw new PrestaShopException("Unknown transaction type `{$transactionType}`.");
				return false;
		}
	}

	/**
	 * Process CHARGE request,
	 * change order status to valid
	 *
	 * @return bool
	 */
	public function processChargeTransactionRequest()
	{
		$this->errors = array();
		$order_increment_id = Tools::getValue(self::PARAM_ORDER_INCREMENT_ID);
		if (empty($order_increment_id))
		{
			// TODO: create order (BuyNow button) ??
			chippin::log('IPN exception: order reference is empty');
		}
		else
		{
			$order_state = new OrderState(Configuration::get('BS_OS_PAYMENT_VALID'));
			if (!Validate::isLoadedObject($order_state))
				$this->errors[] = Tools::displayError('The new order status is invalid.');
			else
			{
				//save transactionId
				$chippin_order = new ChippinOrder();
				$chippin_order->id_cart = $order_increment_id;
				$chippin_order->chippin_reference = Tools::getValue(self::PARAM_REFERENCE_NUMBER);
				$chippin_order->add();

				$obj_order = new Order(Order::getOrderByCartId($order_increment_id));
				if (!$obj_order->id)
				{
					chippin::log('IPN error: cannot load Order object with cart_id "'.$order_increment_id.'"');
					//throw new PrestaShopException('Cannot load Order object');
					return;
				}

				$current_order_state = $obj_order->getCurrentOrderState();
				if ($current_order_state->id != $order_state->id)
					$this->changeOrderStatus($obj_order, (int)Configuration::get('BS_OS_PAYMENT_VALID'), $this->errors);
				else
					$this->errors[] = Tools::displayError('The order has already been assigned this status.');
			}
		}

		if (count($this->errors))
		{
			foreach ($this->errors as $error)
				chippin::log('IPN error: '.$error);
		}

		return true;
	}

	/**
	 * Change order status
	 *
	 * @param $obj_order
	 * @param $id_status
	 * @param $errors
	 */
	public function changeOrderStatus($obj_order, $id_status, &$errors)
	{
		// Create new OrderHistory
		$history = new OrderHistory();
		$history->id_order = $obj_order->id;
		$history->changeIdOrderState($id_status, (int)$obj_order->id);

		$template_vars = array();
		// Save all changes
		if ($history->addWithemail(true, $template_vars))
		{
			// synchronizes quantities if needed..
			if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'))
			{
				foreach ($obj_order->getProducts() as $product)
				{
					if (StockAvailable::dependsOnStock($product['product_id']))
						StockAvailable::synchronize($product['product_id'], (int)$product['id_shop']);
				}
			}
		}
		else
			$errors[] = Tools::displayError('An error occurred while changing order status, or we were unable to send an email to the customer.');
	}

	/**
	 * Process REFUND request
	 * Cancel Order
	 *
	 * @return bool
	 */
	private function processRefundTransactionRequest()
	{
		$order_increment_id = Tools::getValue(self::PARAM_ORDER_INCREMENT_ID);
		$orders = Order::getByReference($order_increment_id);
		foreach ($orders as $obj_order)
		{
			if (!$obj_order->id)
			{
				chippin::log('Cannot load Order object with reference "'.$order_increment_id.'"');
				//throw new PrestaShopException('Cannot load Order object');
				return;
			}

			$order_detail = OrderDetail::getList($obj_order->id);
			$qty_detail = array();
			$item_detail = array();
			foreach ($order_detail as $item)
			{
				$qty_detail[$item['id_order_detail']] = $item['product_quantity'];
				$item_detail[$item['id_order_detail']] = $item['id_order_detail'];
			}

			if (!OrderSlip::createOrderSlip($obj_order, $item_detail, $qty_detail, true))
			{
				chippin::log("Cannot cancel order #{$obj_order->id}");
				//throw new PrestaShopException("Cannot cancel order #{$objOrder->id}");
				return false;
			}

			$this->changeOrderStatus($obj_order, (int)self::REFUND_ORDER_STATE, $this->errors);
		}

		return true;
	}

	/**
	 * Our answer for IPN request
	 *
	 * @return string
	 */
	public function getOkResponseString()
	{
		$response_text = 'OK'.Configuration::get('CHIPPIN_PROTECTION_KEY');
		return md5($response_text);
	}

	/**
	 * Save IPN request
	 */
	protected function logRequestDebug()
	{
		if ($this->is_debug_mode)
		{
			$message = "POST:\n";
			foreach ($_POST as $key => $value)
				$message .= "{$key}: {$value}\n";

			$message .= "GET:\n";
			foreach ($_GET as $key => $value)
				$message .= "{$key}: {$value}\n";

			$this->logDebug($message);
		}
	}

	/**
	 * Save log
	 *
	 * @param $message
	 */
	protected function logDebug($message)
	{
		if ($this->is_debug_mode)
			chippin::log($message, $this->debug_log_name);
	}

}
