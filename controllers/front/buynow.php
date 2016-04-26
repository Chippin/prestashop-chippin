<?php
/**
 * NOTICE OF LICENCE
 */

/**
 * Class chippinbuynowModuleFrontController
 *
 * IPN request processing
 */
class chippinBuynowModuleFrontController extends
	ModuleFrontController
{
	/**
	 * flag allow use ssl for this controller
	 *
	 * @var bool
	 */
	public $ssl = true;

	/**
	 * process IPN request
	 */
	public function init()
	{
		parent::init();

		try {
			$ipn_model = new chippinIpn(Configuration::get('CHIPPIN_USER'), Configuration::get('CHIPPIN_PSWD'));
			if ($ipn_model->processTransactionRequest())
				echo $ipn_model->getOkResponseString();
		} catch (Exception $e) {
			chippin::log('IPN exception: '.$e->getMessage());
		}

		die();
	}

}
