<?php

if (!defined('_PS_VERSION_')) {
	exit;
}

require_once _PS_MODULE_DIR_.'chippin/includer.php';

/**
 * Class chippin
 *
 *
 * Module class
 */
class Chippin extends PaymentModule {

	const PREFIX = 'CHIPPIN_';
	const SANDBOX_CHECKOUT_URL = 'http://staging.chippin.co.uk/sandbox/new';
	const CHECKOUT_URL = 'http://staging.chippin.co.uk/new';
	const SANDBOX_CHIPPIN_ADMIN_URL = 'http://staging.chippin.co.uk/admin';
	const CHIPPIN_ADMIN_URL = 'http://staging.chippin.co.uk/admin';
	const LOG_FILE = 'log/chippin.log';

	protected $_postErrors = array();
	protected $_html = '';

	private $chippinMerchantId;
	private $chippinMerchantSecret;
	private $chippinDuration;
	private $orderCurrency;

	private static $locally_supported = array(
		'USD',
		'EUR',
		'GBP'
	);

	/**
	 * hooks uses by module
	 *
	 * @var array
	 */
	protected $hooks = array(
		'displayHeader',
		'payment',
		'paymentReturn',
		'adminOrder',
		'BackOfficeHeader',
		'displayOrderConfirmation',
		'actionObjectCurrencyUpdateBefore',
	);

	/**
	 * Chippin waiting status
	 *
	 * @var array
	 */
	private $os_statuses = array(
		'CP_OS_PAYMENT_INITIATED' => 'Chippin initiated',
	);

	/**
	 * Status for orders with accepted payment
	 *
	 * @var array
	 */
	private $os_payment_green_statuses = array(
		'CP_OS_PAYMENT_COMPLETED' => 'Chippin completed',
	);

	/**
	 * Chippin waiting status
	 *
	 * @var array
	 */
	private $os_payment_paid_status = array(
		'CP_OS_PAYMENT_PAID' => 'Chippin successfully paid',
	);


	/**
	 * Chippin error status
	 *
	 * @var array
	 */
	private $os_payment_red_statuses = array(
		'CP_OS_PAYMENT_FAILED' => 'Chippin failed',
		'CP_OS_PAYMENT_TIMED_OUT' => 'Chippin timed-out',
		'CP_OS_PAYMENT_CANCELLED' => 'Chippin cancelled',
		'CP_OS_PAYMENT_REJECTED' => 'Chippin rejected',
	);

	/**
	 * module settings
	 *
	 * @var array
	 */
	protected $module_params = array(
		'SANDBOX' => 0,
		'MERCHANT_ID' => '',
		'MERCHANT_SECRET' => '',
		'DURATION' => 24,
	);

	/**
	 * create module object
	 */
	public function __construct()
	{
		$this->name = 'chippin';
		$this->tab = 'payments_gateways';
		$this->version = '1.0.0';
		$this->author = 'Simpleweb';
		$this->need_instance = 1;
		$this->is_configurable = 1;
		$this->bootstrap = true;
		$this->module_key = '';

		parent::__construct();

		//$this->ps_versions_compliancy = array('min' => '1.6.0', 'max' => _PS_VERSION_);

		$this->displayName = $this->l('Chippin');
		$this->description = $this->l('Shared payments made easy.');

		$this->chippinMerchantId = $this->getConfig('MERCHANT_ID');
		$this->chippinMerchantSecret = $this->getConfig('MERCHANT_SECRET');
		$this->chippinDuration = Configuration::get('DURATION');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
	}

	/**
	 * install module, register hooks, set default config values
	 *
	 * @return bool
	 */
	public function install()
	{
		if (Shop::isFeatureActive()) {
			Shop::setContext(Shop::CONTEXT_ALL);
		}

		// set up configuration params in configuration table
		foreach ($this->module_params as $param => $value) {
			if (!self::setConfig($param, $value)) {
				return false;
			}
		}

		if (parent::install()) {
			foreach ($this->hooks as $hook) {
				if (!$this->registerHook($hook)) {
					return false;
				}
			}

			$this->createChippinPaymentStatus($this->os_statuses, '#3333FF', '', false, false, '', false);
			$this->createChippinPaymentStatus($this->os_payment_green_statuses, '#32cd32', 'payment', true, true, true, true);
			$this->createChippinPaymentStatus($this->os_payment_red_statuses, '#ec2e15', 'payment_error', false, true, false, true);
			$this->createChippinPaymentStatus($this->os_payment_paid_status, '#32cd32', 'payment', true,   true, true, true);

			return true;
		}

		return false;
	}

	/**
	 * uninstall module
	 *
	 * @return bool
	 */
	public function uninstall()
	{
		if (parent::uninstall()) {
			foreach ($this->hooks as $hook) {
				if (!$this->unregisterHook($hook)) {
					return false;
				}
			}

			$array = array_merge($this->os_statuses, $this->os_payment_green_statuses, $this->os_payment_red_statuses);

			foreach (array_keys($array) as $key => $value) {
				Configuration::deleteByName($value);  
			}

			foreach ($this->module_params as $param => $value) {
				Configuration::deleteByName(self::PREFIX.$param);
			}

			Configuration::deleteByName('CHIPPIN_SUBMITUPDATE');
			Configuration::deleteByName('CHIPPIN_TAB');
			Configuration::deleteByName('CONF_CHIPPIN_FIXED');
			Configuration::deleteByName('CONF_CHIPPIN_VAR');
			Configuration::deleteByName('CONF_CHIPPIN_FIXED_FOREIGN');
			Configuration::deleteByName('CONF_CHIPPIN_VAR_FOREIGN');
		}

		return true;
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
				'logo_1.png'),
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
					'type' => 'text',
					'label' => $this->l('Secret'),
					'name' => 'merchant_secret',
					'size' => 32,
					'prefix' => '<i class="icon icon-tag"></i>',
					'desc' => $this->l('This string should be kept secret and is used for signing requests and validating responses.')
				),
				array(
					'type' => 'text',
					'label' => $this->l('Duration (in hours)'),
					'name' => 'duration',
					'prefix' => '<i class="icon icon-tag"></i>'
				),
			),
		);

		return $helper;
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
	 * include css file in frontend
	 *
	 * @param $params
	 */
	public function hookHeader()
	{
		if (!$this->active) {
			return;
		}
	}

	private function price_in_pence($price) {
		return (int) ($price * 100);
	}

	public function hookDisplayHeader()
	{
		$this->context->controller->addCSS($this->_path.'/views/css/front.css');
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

		$price_in_pence = $this->price_in_pence(
			$this->context->cart->getOrderTotal(true, Cart::BOTH)
		);

		$products = $this->context->cart->getProducts();

		foreach ($products as $key => $value) {
			$products[$key]['price_in_pence'] = $this->price_in_pence($value['total_wt']);
		}

		$this->setOrderCurrency();

		$this->smarty->assign(array(
			'chippin_hmac' => ChippinValidator::generateHash($price_in_pence, $this->getOrderCurrency(), $this->context->cart->id),
			'chippin_url' => $this->getCheckoutUrl(),
			'chippin_path' => $this->_path,
			'price_in_pence' => $price_in_pence,
			'products' => $products,
			'chippin_merchant_id' => $this->chippinMerchantId,
			'chippin_duration' => $this->getConfig('DURATION'),
			'cart_id' => $this->context->cart->id,
			'currency' => $this->getOrderCurrency(),
		));

		$this->context->controller->addCSS(($this->_path).'views/css/front.css', 'all');

		return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
	}

	public function hookDisplayOrderConfirmation()
	{
		if (!$this->active) {
	        return null;
	    }
	}

	public function hookOrderConfirmation()
	{
		if (!$this->active) {
	        return null;
	    }
	}

	public function hookPaymentReturn($params)
	{
	    if (!$this->active) {
	        return;
	    }

		$state = $params['objOrder']->getCurrentState();

		if (in_array($state, array(Configuration::get('CP_OS_PAYMENT_COMPLETED')))) {
			$this->smarty->assign(array(
				'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
				'status' => 'completed',
				'id_order' => $params['objOrder']->id
			));
			if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference)) {
				$this->smarty->assign('reference', $params['objOrder']->reference);
			}
		} else {
			$this->smarty->assign('status', 'failed');
		}

		return $this->display(__FILE__, 'payment_return.tpl');
	}

	/**
	 * Show view/do action on an individual Orders page.
	 * This hook will change the page basically.
	 */
	public function hookAdminOrder()
	{
		if (!$this->active) {
	        return null;
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
		if ($this->getConfig('SANDBOX') === "1") {
			return self::SANDBOX_CHECKOUT_URL;
		}

		return self::CHECKOUT_URL;
	}

	public function getChippinAdminUrl()
	{
		if ($this->getConfig('SANDBOX') === "1") {
			return self::SANDBOX_CHIPPIN_ADMIN_URL;
		}

		return self::CHIPPIN_ADMIN_URL;
	}

	/**
	 * getContent method - needed to display "Configure" option in back-office
	 * @return [type] [description]
	 */
	public function getContent()
	{
		$this->postProcess();

		$helper = $this->initForm();

		foreach ($this->fields_form as $field_form) {
			foreach ($field_form['form']['input'] as $input) {
				$helper->fields_value[$input['name']] = $this->getConfig(Tools::strtoupper($input['name']));
			}
		}

		$this->_html .= $this->generateCallbackGuide();

		$this->_html .= $helper->generateForm($this->fields_form);

		return $this->_html;
	}

	private function generateCallbackGuide()
	{
		$this->smarty->assign(array(
			'chippin_base_return_url' => _PS_BASE_URL_.'/index.php?fc=module&module=chippin&controller=callback&action=',
			'chippin_admin_url' => $this->getChippinAdminUrl(),
		));

		return $this->display(__FILE__, $this->getTemplate('admin', 'callbacks.tpl'));
	}

	protected function _postValidation()
	{
		if (Tools::isSubmit('submitUpdate'))
		{
			if ((int) Tools::getValue('duration') > 72) {
				$this->_postErrors[] = $this->l('Duration maximum is 72 hours.');
			}
		}
	}

	/**
	 * save configuration values
	 */
	protected function postProcess()
	{
		if (Shop::isFeatureActive()) {
			Shop::setContext(Shop::CONTEXT_ALL);
		}

		if (Tools::isSubmit('submitUpdate')) {
			$this->_postValidation();

			if (!count($this->_postErrors)) {
				$data = $_POST;
				if (is_array($data)) {
					foreach ($data as $key => $value) {
						self::setConfig($key, $value);
					}
				}

				Tools::redirectAdmin('index.php?tab=AdminModules&conf=4&configure='.$this->name.'&token='.Tools::getAdminToken('AdminModules'.(int)Tab::getIdFromClassName('AdminModules').(int)$this->context->employee->id));

			} else {
				foreach ($this->_postErrors as $err) {
					$this->_html .= $this->displayError($err);
				}
			}
		}
	}

	public function hookBackOfficeTop()
	{
		if (!$this->active) {
	        return null;
	    }
	}

	public function hookBackOfficeHeader()
	{
		if (!$this->active) {
	        return null;
	    }

	    $this->context->controller->addCSS($this->_path.'/views/css/admin.css');
	}

	public function getChippinMerchantSecret()
	{
		return $this->chippinMerchantSecret;
	}

	/**
	 * save log file
	 *
	 * @param $string
	 * @param null $file
	 */
	public static function log($string, $file = null)
	{
		if (empty($file)) {
			$file = self::LOG_FILE;
		}

		$file = dirname(__FILE__).DS.$file;
		file_put_contents($file, $string.' - '.date('Y-m-d H:i:s')."\n", FILE_APPEND | LOCK_EX);
	}

	private function setOrderCurrency()
	{
		$currencies = Currency::getCurrencies();

		foreach ($currencies as $key => $currency) {
			if ($currency['id_currency'] == $this->context->cart->id_currency) {
				$this->orderCurrency = $currency['iso_code'];
			}
		}
	}

	private function getOrderCurrency()
	{
		return $this->orderCurrency;
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
	private function createChippinPaymentStatus($array, $color, $template, $invoice, $send_email, $paid, $logable)
	{
		foreach ($array as $key => $value)
		{
			$ow_status = Configuration::get($key);
			if ($ow_status === false) {
				$order_state = new OrderState();
			} else {
				$order_state = new OrderState((int)$ow_status);
			}

			$langs = Language::getLanguages();

			foreach ($langs as $lang) {
				$order_state->name[$lang['id_lang']] = utf8_encode(html_entity_decode($value));
			}

			$order_state->invoice = $invoice;
			$order_state->send_email = $send_email;

			if ($template != '') {
				$order_state->template = $template;
			}

			if ($paid != '') {
				$order_state->paid = $paid;
			}

			$order_state->logable = $logable;
			$order_state->color = $color;
			$order_state->save();

			Configuration::updateValue($key, (int)$order_state->id);

			Tools::copy(dirname(__FILE__).'/views/img/statuses/'.$key.'.gif', _PS_ROOT_DIR_.'/img/os/'.(int)$order_state->id.'.gif');
		}
	}
}
