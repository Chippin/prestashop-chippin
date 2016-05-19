<?php
/**
 * 2007-2015 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2015 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

/**
 * Class ChippinConfirmationModuleFrontController
 * Extends ModuleFrontController
 * @see ModuleFrontController
 */
class ChippinCallbackModuleFrontController extends ModuleFrontController
{
    /**
     * flag allow use ssl for this controller
     *
     * @var bool
     */
    public $ssl = true;

    protected $totalAmount = '';
    protected $paymentResponse = '';
    protected $chippin = '';

    public function __construct()
    {
        $response = new PaymentResponseChippin();
        $response->setPostData();
        $this->paymentResponse = $response;

        parent::__construct();

        // load chippin classes
        require_once(dirname(__FILE__).'/../../classes/loader.php');
    }

    public function postProcess()
    {
        $this->chippin = new Chippin();

        // if a valid response from chippin
        // if(ChippinValidator::isValidHmac($this->paymentResponse)) {
        if(true === true) {
            $method = "chippin" . ucfirst($this->paymentResponse->getAction());
            call_user_func(array($this, $method));
        } else {
            $this->errors[] = $this->chippin->l('An error occured. Please contact the store owner for more information');
            $this->setTemplate('error.tpl');
        }
    }

    protected function chippinInvited()
    {
        $cart = new Cart($this->paymentResponse->getMerchantOrderId());
        $customer = new Customer($cart->id_customer);
        $this->validateOrderObject($customer);
        
        $this->module->validateOrder(
            (int) $cart->id,
            Configuration::get('CP_OS_PAYMENT_INITIATED'),
            (float) $cart->getOrderTotal(true, Cart::BOTH),
            $this->module->displayName,
            NULL,
            NULL,
            (int) $this->context->currency->id,
            false,
            $customer->secure_key
        );
    }

    protected function chippinCompleted()
    {
        $cart = new Cart($this->paymentResponse->getMerchantOrderId());
        $customer = new Customer($cart->id_customer);
        $this->validateOrderObject($customer);

        $order = $this->getOrder();
        $order->setCurrentState(Configuration::get('CP_OS_PAYMENT_COMPLETED'));

        Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$order->id.'&key='.$customer->secure_key);
    }

    protected function chippinContributed()
    {
        $cart = new Cart($this->paymentResponse->getMerchantOrderId());
        $customer = new Customer($cart->id_customer);
        $this->validateOrderObject($customer);

        $order = $this->getOrder();

        $this->context->smarty->assign(array(
            'order' => $order,
            'products' => $cart->getProducts(),
        ));

        $this->addCSS(_THEME_CSS_DIR_.'product_list.css');
        $this->setTemplate('contributed.tpl');
    }

    protected function chippinFailed()
    {
        $order = $this->getOrder();
        $order->setCurrentState(Configuration::get('CP_OS_PAYMENT_FAILED'));

        $this->errors[] = $this->chippin->l('Chippin payment status: ' . $this->paymentResponse->getAction() . '. Please contact the store owner for more information');
        $this->setTemplate('error.tpl');
    }

    protected function chippinRejected()
    {
        $order = $this->getOrder();
        $order->setCurrentState(Configuration::get('CP_OS_PAYMENT_REJECTED'));

        $this->errors[] = $this->chippin->l('Chippin payment status: ' . $payment_response->getAction() . '. Please contact the store owner for more information');
        $this->setTemplate('error.tpl');
    }

    protected function chippinTimedOut()
    {
        $order = $this->getOrder();
        $this->order->setCurrentState(Configuration::get('CP_OS_PAYMENT_TIMED_OUT'));

        $this->errors[] = $this->chippin->l('Chippin payment status: ' . $this->paymentResponse->getAction() . '. Please contact the store owner for more information');
        $this->setTemplate('error.tpl');
    }

    protected function chippinCancelled()
    {
        // only if there is an order with that merchant id - then update the status
        if($this->orderExists()) {
            $order = $this->getOrder();
            $order->setCurrentState(Configuration::get('CP_OS_PAYMENT_CANCELLED'));
        }

        // redirect to order page
        Tools::redirectLink(_PS_BASE_URL_.'/order.php?step=1');
    }

    private function validateOrderObject($customer)
    {
        if (!Validate::isLoadedObject($customer)) {
            $this->errors[] = $this->chippin->l('An error occured. Please contact the store owner for more information');
            return $this->setTemplate('error.tpl');
            die();
        }

        return true;
    }

    private function getOrder()
    {
        $id = Order::getOrderByCartId((int) ($this->paymentResponse->getMerchantOrderId()));
        return new Order($id);
    }

    private function orderExists()
    {
        return (bool) Order::getOrderByCartId((int) ($this->paymentResponse->getMerchantOrderId()));
    }
}
