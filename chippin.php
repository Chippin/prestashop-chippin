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
	const SANDBOX_CHECKOUT_URL = 'http://staging.chippin.co.uk/sandbox/new';
	const CHECKOUT_URL = 'http://staging.chippin.co.uk/new';
	const LOG_FILE = 'log/chippin.log';

	private static $locally_supported = array('USD', 'EUR', 'GBP');

	/**
	 * hooks uses by module
	 *
	 * @var array
	 */
	protected $hooks = array(
	);

	private $os_statuses = array(
		'CP_OS_WAITING' => array(
			'name' => 'Awaiting Chippin',
			'color' => '#4169E1'
		),
		'CP_OS_TIMED_OUT' => array(
			'name' => 'Chippin Timed-out',
			'color' => '#DC143C'
		),
	);
	private $chippinMerchantId;
	private $chippinMerchantSecret;
	private $chippinDuration;
	private $orderCurrency;

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

		// $this->chippinDuration = Configuration::get('DURATION');
		$this->chippinDuration = 72;

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

		if (!Configuration::get('MYMODULE_NAME')) {
    		$this->warning = $this->l('No name provided.');
		}

		$this->setOrderCurrency();
	}

	/**
	 * install module, register hooks, set default config values
	 *
	 * @return bool
	 */
	public function install()
	{
		if (parent::install()) {
			foreach ($this->hooks as $hook) {
				if (!$this->registerHook($hook)) {
					return false;
				}
			}

			return true;
		}

		return false;
	}

	/**
     * Create a new order state
     */
    public function createOrderStates()
    {
    	foreach ($this->os_statuses as $key => $values) {

	        if (!Configuration::get($key)) {
	            $order_state = new OrderState();

	            foreach (Language::getLanguages() as $language) {
	                $order_state->name[$language['id_lang']] = $values['name'];
	            }

	            $order_state->send_email = false;
	            $order_state->color = $values['color'];
	            $order_state->hidden = false;
	            $order_state->delivery = false;
	            $order_state->logable = true;
	            $order_state->invoice = true;

	            $order_state->add();
	            Configuration::updateValue($key, (int) $order_state->id);
	        }
    	}
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
		$this->context->controller->addCSS(($this->_path).'views/css/front.css', 'all');
	}

	public function price_in_pence($price) {
		return (int) ($price * 100);
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

		//var_dump($this->context->cart);

		var_dump($this->context->cart->getOrderTotal(true, Cart::BOTH));

		var_dump($price_in_pence);

		$products = $this->context->cart->getProducts();
		foreach ($products as $key => $value) {
			$products[$key]['price_in_pence'] = $this->price_in_pence($value['total_wt']);
		}

		$this->smarty->assign(array(
			'chippin_hmac' => $this->generateHash($price_in_pence),
			'chippin_url' => $this->getCheckoutUrl(),
			'chippin_path' => $this->_path,
			'price_in_pence' => $price_in_pence,
			'products' => $products,
			'chippin_merchant_id' => $this->chippinMerchantId,
			'chippin_duration' => $this->chippinDuration,
			'cart_id' => $this->context->cart->id,
			'currency' => $this->getOrderCurrency(),
		));

		return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
	}

	/**
	 * prepare url for chippin hidden form
	 *
	 * @param $id_order
	 * @return string
	 */
	public function getCheckoutUrl()
	{
		if ($this->getConfig('SANDBOX')) {
			return self::SANDBOX_CHECKOUT_URL;
		}

		return self::CHECKOUT_URL;
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

	private function generateHash($price_in_pence)
	{
		return hash_hmac('sha256', $this->chippinMerchantId . $this->context->cart->id . $price_in_pence . $this->chippinDuration . $this->getOrderCurrency(), $this->chippinMerchantSecret);
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
}
