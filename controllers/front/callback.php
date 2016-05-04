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

    public function __construct()
    {
        parent::__construct();

        // load chippin classes
        require_once(dirname(__FILE__).'/../../classes/loader.php');
    }

    public function postProcess()
    {
        $chippin = new Chippin();

        $payment_response = new PaymentResponse();
        $payment_response->getPostData();

        // if a valid response from chippin
        if(ChippinValidator::isValidHmac($payment_response)) {

            if($payment_response->getAction() === "invited") {

                $cart = new Cart($payment_response->getMerchantOrderId());

                $customer = new Customer($cart->id_customer);

                if (!Validate::isLoadedObject($customer)) {
                    Chippin::log('Error - Not a valid Customer');
                    die();
                }

                $currency = $this->context->currency;

                $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

                // create the order in the orders table
                $this->module->validateOrder(
                    (int) $cart->id,
                    Configuration::get('CP_OS_PAYMENT_INITIATED'),
                    $total,
                    $this->module->displayName,
                    NULL,
                    NULL,
                    (int)$currency->id,
                    false,
                    $customer->secure_key
                );

            } elseif ($payment_response->getAction() === "timed_out") {

                $order_id = Order::getOrderByCartId((int) ($payment_response->getMerchantOrderId()));
                $order = new Order($order_id);
                $timed_out_status_id = Configuration::get('CP_OS_PAYMENT_TIMED_OUT');

                // update order in orders table and orders history
                $order->setCurrentState($timed_out_status_id);

            } elseif ($payment_response->getAction() === "cancelled") {

                $order_id = Order::getOrderByCartId((int) ($payment_response->getMerchantOrderId()));
                $order = new Order($order_id);

                // update order in orders table and orders history
                $order->setCurrentState(Configuration::get('CP_OS_PAYMENT_CANCELLED'));

                $this->errors[] = $chippin->l('Your Chippin payment has been cancelled. Please contact the store owner for more information.');
                return $this->setTemplate('error.tpl');

            } elseif ($payment_response->getAction() === "completed") {

                $order_id = Order::getOrderByCartId((int) ($payment_response->getMerchantOrderId()));
                $order = new Order($order_id);
                $order->setCurrentState(Configuration::get('CP_OS_PAYMENT_COMPLETED'));

                $this->context->smarty->assign(
                    array(
                        'is_guest' => (($this->context->customer->is_guest) || $this->context->customer->id == false),
                        'order' => $order->reference,
                        // 'price' => Tools::displayPrice($price, $this->context->currency->id),
                        // 'HOOK_ORDER_CONFIRMATION' => $this->displayOrderConfirmation(),
                        // 'HOOK_PAYMENT_RETURN' => $this->displayPaymentReturn(),
                    )
                );

                $this->setTemplate('confirmation.tpl');

            } elseif ($payment_response->getAction() === "contributed") {

                $order_id = Order::getOrderByCartId((int) ($payment_response->getMerchantOrderId()));
                $order = new Order($order_id);

                $this->context->smarty->assign(
                    array(
                        'is_guest' => (($this->context->customer->is_guest) || $this->context->customer->id == false),
                        'order' => $order->reference,
                        // 'price' => Tools::displayPrice($price, $this->context->currency->id),
                        // 'HOOK_ORDER_CONFIRMATION' => $this->displayOrderConfirmation(),
                        // 'HOOK_PAYMENT_RETURN' => $this->displayPaymentReturn(),
                    )
                );

                $this->setTemplate('contributed.tpl');

            } elseif ($payment_response->getAction() === "failed") {

                $order_id = Order::getOrderByCartId((int) ($payment_response->getMerchantOrderId()));
                $order = new Order($order_id);
                $order->setCurrentState(Configuration::get('CP_OS_PAYMENT_FAILED'));

                $this->errors[] = $chippin->l('Your Chippin payment has failed. Please contact the store owner for more information.');
                return $this->setTemplate('error.tpl');

            } elseif ($payment_response->getAction() === "rejected") {

                $order_id = Order::getOrderByCartId((int) ($payment_response->getMerchantOrderId()));
                $order = new Order($order_id);
                $order->setCurrentState(Configuration::get('CP_OS_PAYMENT_REJECTED'));

                $this->errors[] = $chippin->l('Your Chippin payment was reject. Please contact the store owner for more information.');
                return $this->setTemplate('error.tpl');
            }

        } else {

            $this->errors[] = $chippin->l('An error occured. Please contact the store owner for more information.');
            return $this->setTemplate('error.tpl');
        }
    }

    private function displayHook()
    {
        $payment_response = new PaymentResponse();
        $payment_response->getPostData();

        if (Validate::isUnsignedId($payment_response->getMerchantOrderId()) && Validate::isUnsignedId(72)) {
            $order = new Order((int) $payment_response->getMerchantOrderId());
            $currency = new Currency((int) $order->id_currency);

            if (Validate::isLoadedObject($order)) {
                $params = array();
                $params['objOrder'] = $order;
                $params['currencyObj'] = $currency;
                $params['currency'] = $currency->sign;
                $params['total_to_pay'] = $order->getOrdersTotalPaid();

                return $params;
            }
        }

        return false;
    }

    /**
     * Execute the hook displayPaymentReturn
     */
    public function displayPaymentReturn()
    {

        $params = $this->displayHook();

        if ($params && is_array($params)) {
            return Hook::exec('displayPaymentReturn', $params, (int) $this->module->id);
        }

        return false;
    }

    /**
     * Execute the hook displayOrderConfirmation
     */
    public function displayOrderConfirmation()
    {
        $params = $this->displayHook();

        if ($params && is_array($params)) {
            return Hook::exec('displayOrderConfirmation', $params);
        }

        return false;
    }
}
