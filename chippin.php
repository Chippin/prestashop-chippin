<?php

if (!defined('_PS_VERSION_'))
	exit;

require_once _PS_MODULE_DIR_.'chippin/includer.php';

/**
 * Class chippin
 *
 *
 * Module class
 */
class Chippin extends PaymentModule {

	const PREFIX = 'CHIPPIN_';
	const SANDBOX_CHECKOUT_URL = 'https://chippin.co.uk/sandbox/new';
	const CHECKOUT_URL = 'https://chippin.co.uk/new';
	const LOG_FILE = 'log/chippin.log';

	private static $locally_supported = array('USD', 'EUR', 'GBP');

	/**
	 * hooks uses by module
	 *
	 * @var array
	 */
	protected $hooks = array(
		'displayHeader',
		'payment',
		'adminOrder',
		'BackOfficeHeader',
		'displayOrderConfirmation',
		'actionObjectCurrencyUpdateBefore',
	);

	protected $html = '';

	/**
	 * module settings
	 *
	 * @var array
	 */
	protected $module_params = array(
		'USER' => '',
		'PSWD' => '',
		'SANDBOX_USER' => '',
		'STORE' => '',
		'SANDBOX' => 0,
		'CONTRACT' => '',
		'PROTECTION_KEY' => '',
		'BUYNOW_DEBUG_MODE' => '',
		'API_DEBUG_MODE' => '',
		'USE_BS_EXCHANGE' => 0,
	);

	/**
	 * Chippin waiting status
	 *
	 * @var array
	 */
	private $os_statuses = array(
		'BS_OS_WAITING' => 'Awaiting Chippin payment',
	);

	/**
	 * Status for orders with accepted payment
	 *
	 * @var array
	 */
	private $os_payment_green_statuses = array(
		'BS_OS_PAYMENT_VALID' => 'Accepted Chippin payment',
	);

	/**
	 * Chippin error status
	 *
	 * @var array
	 */
	private $os_payment_red_statuses = array(
		'BS_OS_PAYMENT_ERROR' => 'Error Chippin payment',
	);

	/**
	 * create module object
	 */
	public function __construct()
	{
		// exit;
		$this->name = 'chippin';
		$this->tab = 'payments_gateways';
		$this->version = '1.0.0';
		$this->author = 'Simpleweb';
		$this->need_instance = 1; // maybe 0
		$this->is_configurable = 1;
		$this->bootstrap = true;
		$this->module_key = '';

		parent::__construct();

		//$this->ps_versions_compliancy = array('min' => '1.6.0', 'max' => _PS_VERSION_);
		$this->displayName = $this->l('Chippin');
		$this->description = $this->l('Shared payments made easy.');
		if ($this->getConfig('SANDBOX')) {
			$this->api = new ChippinApi($this->getConfig('SANDBOX_USER'), $this->getConfig('SANDBOX_PSWD'));
		} else {
			$this->api = new ChippinApi($this->getConfig('USER'), $this->getConfig('PSWD'));
		}

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

		if (!Configuration::get('MYMODULE_NAME'))
    		$this->warning = $this->l('No name provided.');

		/* Backward compatibility
		if (version_compare(_PS_VERSION_, '1.5', '<')) {
			require(_PS_MODULE_DIR_.$this->name.'/backward_compatibility/backward.php');
		} */
	}

	/**
	 * install module, register hooks, set default config values
	 *
	 * @return bool
	 */
	public function install()
	{
		if (parent::install())
		{
			foreach ($this->hooks as $hook)
			{
				if (!$this->registerHook($hook))
					return false;
			}

			if (!$this->installConfiguration())
				return false;

			if (!function_exists('curl_version'))
			{
				$this->_errors[] = $this->l('Unable to install the module (CURL isn\'t installed).');
				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * set default config values
	 *
	 * @return bool
	 */
	public function installConfiguration()
	{
		foreach ($this->module_params as $param => $value)
		{
			if (!self::setConfig($param, $value))
				return false;
		}

		if (!Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'chippin_order` (
                `id_chippin_order` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `id_cart` int(11) unsigned NOT NULL,
                `chippin_reference` int(11) NOT NULL,
                `refunded` tinyint(1) NOT NULL,
                PRIMARY KEY (`id_chippin_order`)
            ) ENGINE= '._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8'))
			return false;

		//waiting payment status creation
		$this->createChippinPaymentStatus($this->os_statuses, '#3333FF', '', false, false, '', false);

		//validate green payment status creation
		$this->createChippinPaymentStatus($this->os_payment_green_statuses, '#32cd32', 'payment', true, true, true, true);

		//validate red payment status creation
		$this->createChippinPaymentStatus($this->os_payment_red_statuses, '#ec2e15', 'payment_error', false, true, false, true);

		return true;
	}

	/**
	 * uninstall module
	 *
	 * @return bool
	 */
	public function uninstall()
	{
		if (parent::uninstall())
		{
			foreach ($this->hooks as $hook)
			{
				if (!$this->unregisterHook($hook))
					return false;
			}
		}

		return true;
	}

	/**
	 * create new order statuses
	 *
	 * @param $array
	 * @param $color
	 * @param $template
	 * @param $invoice
	 * @param $send_email
	 * @param $paid
	 * @param $logable
	 */
	public function createChippinPaymentStatus($array, $color, $template, $invoice, $send_email, $paid, $logable)
	{
		foreach ($array as $key => $value)
		{
			$ow_status = Configuration::get($key);
			if ($ow_status === false)
			{
				$order_state = new OrderState();
				//$order_state->id_order_state = (int)$key;
			}
			else
				$order_state = new OrderState((int)$ow_status);

			$langs = Language::getLanguages();

			foreach ($langs as $lang)
				$order_state->name[$lang['id_lang']] = utf8_encode(html_entity_decode($value));

			$order_state->invoice = $invoice;
			$order_state->send_email = $send_email;

			if ($template != '')
				$order_state->template = $template;

			if ($paid != '')
				$order_state->paid = $paid;

			$order_state->logable = $logable;
			$order_state->color = $color;
			$order_state->save();

			Configuration::updateValue($key, (int)$order_state->id);

			Tools::copy(dirname(__FILE__).'/views/img/statuses/'.$key.'.gif', _PS_ROOT_DIR_.'/img/os/'.(int)$order_state->id.'.gif');
		}
	}

	/**
	 * Return server path for file
	 *
	 * @param string $file
	 * @return string
	 */
	public function getDir($file = '')
	{
		return _PS_MODULE_DIR_.$this->name.DIRECTORY_SEPARATOR.$file;
	}

	/**
	 * return correct path for .tpl file
	 *
	 * @param $area
	 * @param $file
	 * @return string
	 */
	public function getTemplate($area, $file)
	{
		return 'views/templates/'.$area.'/'.$file;
	}

	/**
	 * alias for Configuration::get()
	 *
	 * @param $name
	 * @return mixed
	 */
	public static function getConfig($name)
	{
		//exit;
		return Configuration::get(Tools::strtoupper(self::PREFIX.$name));
	}

	/**
	 * alias for Configuration::updateValue()
	 *
	 * @param $name
	 * @param $value
	 * @return mixed
	 */
	public static function setConfig($name, $value)
	{
		return Configuration::updateValue(Tools::strtoupper(self::PREFIX.$name), $value);
	}

	/**
	 * return html with configuration
	 *
	 * @return string
	 */
	public function getContent()
	{

		$this->postProcess();
		$helper = $this->initForm();
		foreach ($this->fields_form as $field_form)
		{
			foreach ($field_form['form']['input'] as $input)
				$helper->fields_value[$input['name']] = $this->getConfig(Tools::strtoupper($input['name']));
		}

		$this->html .= $helper->generateForm($this->fields_form);

		return $this->html;
	}


	/**
	 * helper with configuration
	 *
	 * @return HelperForm
	 */
	private function initForm()
	{
		$helper = new HelperForm();
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->identifier = $this->identifier;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		$helper->toolbar_scroll = true;
		$helper->toolbar_btn = $this->initToolbar();
		$helper->title = $this->displayName;
		$helper->submit_action = 'submitUpdate';

		$this->fields_form[0]['form'] = array(
			'tinymce' => true,
			'legend' => array('title' => $this->l('Chippin Credentials'), 'image' => $this->_path.
				'logo.gif'),
			'submit' => array(
				'name' => 'submitUpdate',
				'title' => $this->l('   Save   ')
			),
			'input' => array(
				array(
					'type' => 'switch',
					'values' => array(
						array('label' => $this->l('Yes'), 'value' => 1, 'id' => 'sandbox_on'),
						array('label' => $this->l('No'), 'value' => 0, 'id' => 'sandbox_off'),
					),
					'is_bool' => true,
					'class' => 't',
					'label' => $this->l('Sandbox mode'),
					'name' => 'sandbox',
				),
				array(
					'type' => 'text',
					'label' => $this->l('Merchant ID'),
					'name' => 'merchant_id',
					'prefix' => '<i class="icon icon-tag"></i>',
				),
				array(
					'type' => 'password',
					'label' => $this->l('Secret'),
					'name' => 'merchant_secret',
					'prefix' => '<i class="icon icon-tag"></i>',
					'desc' => $this->l('This string should be kept secret and is used for signing requests and validating responses.')
				),
			),
		);

		return $helper;
	}

	public function refreshCurrencies()
	{
		// get shop default currency
		if (!$default_currency = Currency::getDefaultCurrency())
			return Tools::displayError('No default currency');

		$default_iso_code = $default_currency->iso_code;
		$currencies = Currency::getCurrencies(true, false, true);

		/* @var $currency Currency */
		foreach ($currencies as $currency)
		{
			if ($currency->id != $default_currency->id)
			{
				if ($conversion_rate = $this->api->getCurrencyRate($default_iso_code, $currency->iso_code))
				{
					$currency->conversion_rate = $conversion_rate;
					$currency->update();
				}
			}
		}

		return null;
	}

	/**
	 * PrestaShop way save button
	 *
	 * @return mixed
	 */
	private function initToolbar()
	{
		$toolbar_btn = array();
		$toolbar_btn['save'] = array('href' => '#', 'desc' => $this->l('Save'));
		return $toolbar_btn;
	}

	/**
	 * save configuration values
	 */
	protected function postProcess()
	{
		if (Tools::isSubmit('submitUpdate'))
		{
			$data = $_POST;
			if (is_array($data))
			{
				foreach ($data as $key => $value)
				{
					if (in_array($key, array('sandbox_pswd', 'pswd')) && empty($value))
						continue;

					if ($key == 'use_bs_exchange')
						if ($value && !$this->getConfig('USE_BS_EXCHANGE'))
							$this->refreshCurrencies();
						elseif (!$value && $this->getConfig('USE_BS_EXCHANGE'))
							Currency::refreshCurrencies();

						self::setConfig($key, $value);
				}
			}

			Tools::redirectAdmin('index.php?tab=AdminModules&conf=4&configure='.$this->name.
			'&token='.Tools::getAdminToken('AdminModules'.
			(int)Tab::getIdFromClassName('AdminModules').(int)$this->context->employee->id));
		}
	}

	/**
	 * include css file in frontend
	 *
	 * @param $params
	 */
	public function hookHeader()
	{
		//exit;
		$this->context->controller->addCSS(($this->_path).'views/css/front.css', 'all');
	}

	/**
	 * show module on payment step
	 *
	 * @param $params
	 * @return mixed
	 */
	public function hookPayment()
	{
		if (!$this->active) {
			return;
		}

		$chippin_url = $this->context->link->getModuleLink('chippin', 'checkout', array(), true);

		$this->smarty->assign(array(
			'chippin_url' => $chippin_url,
			'chippin_path' => $this->_path,
		));

		return $this->display(__FILE__, $this->getTemplate('front', 'payment.tpl'));
	}

	public function hookAdminOrder()
	{
		if (Tools::isSubmit('id_order'))
		{
			$order_obj = new Order(Tools::getValue('id_order'));
			$chippin_info = ChippinOrder::getByPsCartId($order_obj->id_cart);
			if (isset($chippin_info['chippin_reference']) && !empty($chippin_info['chippin_reference']))
			{
				$this->context->smarty->assign(array(
					'chippin_reference_number' => $chippin_info['chippin_reference'],
					'chippin_refunded' => (int)$chippin_info['refunded'],
					'id_chippin_order' => (int)$chippin_info['id_chippin_order']
				));

				return $this->display(__FILE__, $this->getTemplate('admin', 'order.tpl'));
			}
		}
	}

	public function hookBackOfficeHeader()
	{
		$reference_number = Tools::getValue('chippin_reference_number');
		$id_order = Tools::getValue('id_order');
		$id_chippin_order = Tools::getValue('id_chippin_order');
		$chippin_error = false;
		if ($reference_number && $id_order && $id_chippin_order)
		{
			$id_order_state = Configuration::get('PS_OS_REFUND');
			$template_vars = array();

			if ($this->api->refund($reference_number))
			{
				$order_obj = new Order($id_order);
				$orders_collection = Order::getByReference($order_obj->reference);
				foreach ($orders_collection->getResults() as $order)
				{
					// Set new order state
					$new_history = new OrderHistory();
					$new_history->id_order = (int)$order->id;
					$new_history->changeIdOrderState((int)$id_order_state, $order, true);
					// Save all changes
					if ($new_history->addWithemail(true, $template_vars))
					{
						$chippin_order = new ChippinOrder($id_chippin_order);
						$chippin_order->refunded = 1;
						$chippin_order->update();
					}
				}
			}
			else
				$chippin_error = $this->l('An error has occurred. Please contact Chippin support for further assistance');
		}
		$this->context->smarty->assign('chippin_error', $chippin_error);
	}

	public function hookActionObjectCurrencyUpdateBefore($params)
	{
		// do not apply changes if currency is updated mannualy or Chippin EX Rates API is disabled
		if (!Tools::getIsset('submitUpdate') && !Tools::getIsset('submitAddcurrency') && $this->getConfig('USE_BS_EXCHANGE'))
		{
			$currency = $params['object'];
			/* @var $currency Currency */

			$default_currency = Currency::getDefaultCurrency();
			$conversion_rate = $this->api->getCurrencyRate($default_currency->iso_code, $currency->iso_code);

			if ($conversion_rate != null && $conversion_rate != $currency->conversion_rate)
				$currency->conversion_rate = $conversion_rate;
		}
	}

	/**
	 * prepare url for chippin hidden form
	 *
	 * @param $id_order
	 * @return string
	 */
	public function getCheckoutUrl()
	{

		if ($this->getConfig('SANDBOX'))
			$chippin_url = self::SANDBOX_CHECKOUT_URL;
		else
			$chippin_url = self::CHECKOUT_URL;

		$total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
		$currency_code = $current_currency_code = $this->context->currency->iso_code;
		$usd_currency_id = (int)Currency::getIdByIsoCode('USD');
		if (!$this->isLocallySupported($current_currency_code) && $usd_currency_id)
		{
			$currency = Currency::getCurrencyInstance($usd_currency_id);
			$conversion_rate = $this->context->currency->conversion_rate?$this->context->currency->conversion_rate:1;
			$base = $total / $conversion_rate;
			$usd_total = $currency->conversion_rate * $base;

			$c_decimals = (int)$currency->decimals * _PS_PRICE_DISPLAY_PRECISION_;
			$total = round(Tools::convertPrice($usd_total, $usd_currency_id), $c_decimals);
			$currency_code = $currency->iso_code;
		}

		$chippin_url .= '?';
		$currency = Currency::getCurrency($this->context->cart->id_currency);
		$chippin_params = array(
			'storeId' => (int)$this->getConfig('STORE'),
			'currency' => $currency['iso_code'],
			'email' => Context::getContext()->cookie->email,
			//'language' => Context::getContext()->language->name,
			'sku'.$this->getConfig('CONTRACT') => 1,
			'custom1' => $this->context->cart->id,
		);
		if ($api_lang = ChippinApi::getLangByIso(Context::getContext()->language->iso_code))
			$chippin_params['language'] = $api_lang;

		$this->billingAddressParams($this->context->cart, $chippin_params);
		//$this->shippingAddressParams($this->context->cart, $chippin_params);

		$chippin_url .= http_build_query($chippin_params, '', '&');
		$enc = $this->api->paramEncryption(
				array(
					"sku{$this->getConfig('CONTRACT')}priceamount" => $total,
					"sku{$this->getConfig('CONTRACT')}name" => $this->getCartItemOverrideName($this->context->cart),
					"sku{$this->getConfig('CONTRACT')}pricecurrency" => $currency_code,
					'expirationInMinutes' => 90,
		));
		if (!$enc)
			return null;

		$chippin_url .= '&enc='.$enc;

		return $chippin_url;
	}

	public function billingAddressParams($cart_obj, &$chippin_params)
	{
		$invoice_address = new Address($cart_obj->id_address_invoice);
		$country = new Country($invoice_address->id_country);
		$state = new State($invoice_address->id_state);

		$chippin_params['firstName'] = $invoice_address->firstname;
		$chippin_params['lastName'] = $invoice_address->lastname;
		//$chippin_params['address1'] = $invoice_address->address1;
		$chippin_params['country'] = $country->iso_code;
		$chippin_params['state'] = $state->iso_code;
		//$chippin_params['city'] = $invoice_address->city;
		//$chippin_params['zipCode'] = $invoice_address->postcode;
		//$chippin_params['phone'] = isset($invoice_address->phone_mobile) ? $invoice_address->phone_mobile : $invoice_address->phone;
	}

	public function shippingAddressParams($cart_obj, &$chippin_params)
	{
		$delivery_address = new Address($cart_obj->id_address_delivery);
		$country = new Country($delivery_address->id_country);
		$state = new State($delivery_address->id_state);

		$chippin_params['shippingFirstName'] = $delivery_address->firstname;
		$chippin_params['shippingLastName'] = $delivery_address->lastname;
		$chippin_params['shippingAddress1'] = $delivery_address->address1;
		$chippin_params['shippingCountry'] = $country->iso_code;
		$chippin_params['shippingState'] = $state->iso_code;
		$chippin_params['shippingCity'] = $delivery_address->city;
		$chippin_params['shippingZipCode'] = $delivery_address->postcode;
		$chippin_params['shippingPhone'] = isset($delivery_address->phone_mobile) ? $delivery_address->phone_mobile : $delivery_address->phone;
	}

	/**
	 * return string for custom1 param (prestashop_order_id)
	 *
	 * @param Order $order
	 * @return string
	 */
	/*private function getOrderItemOverrideName(Order $order)
	{
		return $this->l('Order reference #').$order->reference;
	}*/

	/**
	 * return string for custom1 param (prestashop_order_id)
	 *
	 * @param Cart $order
	 * @return string
	 */
	private function getCartItemOverrideName(Cart $cart)
	{
		return $this->l('Cart #').$cart->id;
	}

	/**
	 * return orders amount
	 *
	 * @param Order $order
	 * @return string
	 */
	/*private function getAmountByReference($reference)
	{
		$orders_collection = Order::getByReference($reference);
		$amount = 0;
		foreach ($orders_collection->getResults() as $order)
			$amount += $order->total_paid;

		return $amount;
	}*/

	/**
	 * save log file
	 *
	 * @param $string
	 * @param null $file
	 */
	public static function log($string, $file = null)
	{
		if (empty($file))
			$file = self::LOG_FILE;

		$file = dirname(__FILE__).DS.$file;
		file_put_contents($file, $string.' - '.date('Y-m-d H:i:s')."\n", FILE_APPEND | LOCK_EX);
	}

	public function hookDisplayOrderConfirmation($params)
	{
		if (!isset($params['objOrder']) || ($params['objOrder']->module != $this->name))
			return false;
		if (isset($params['objOrder']) && Validate::isLoadedObject($params['objOrder']) && isset($params['objOrder']->valid) &&
				version_compare(_PS_VERSION_, '1.5', '>=') && isset($params['objOrder']->reference))
		{
			$this->smarty->assign('chippin_order', array(
				'id' => $params['objOrder']->id,
				'reference' => $params['objOrder']->reference,
				'valid' => $params['objOrder']->valid,
				'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false)
				)
			);
			return $this->display(__FILE__, $this->getTemplate('front', 'order-confirmation.tpl'));
		}
	}

	/**
	 * Check if currency is locally supported
	 * @param string $currency_code
	 * @return bool
	 */
	public static function isLocallySupported($currency_code)
	{
		return in_array($currency_code, self::$locally_supported);
	}
}
