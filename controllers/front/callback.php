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
        $payment_response = new PaymentResponseChippin();
        $payment_response->getPostData();

        // if a valid response from chippin
        if(ChippinValidator::isValidHmac($payment_response)) {

            if ($payment_response->getAction() === "completed" || $payment_response->getAction() === "invited" || $payment_response->getAction() === "contributed") {

                $cart = new Cart($payment_response->getMerchantOrderId());
                $customer = new Customer($cart->id_customer);

                if (!Validate::isLoadedObject($customer)) {
                    die();
                }

                $currency = $this->context->currency;
                $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

                if ($payment_response->getAction() === "invited") {

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

                } elseif ($payment_response->getAction() === "completed") {
                    
                    if(Order::getOrderByCartId((int) ($payment_response->getMerchantOrderId()))) {
                        $order_id = Order::getOrderByCartId((int) ($payment_response->getMerchantOrderId()));
                        $order = new Order($order_id);
                        if ($order->getCurrentState() == Configuration::get('CP_OS_PAYMENT_INITIATED')) {
                            $order->setCurrentState(Configuration::get('CP_OS_PAYMENT_COMPLETED'));
                        }
                    } else {
                        $this->errors[] = $chippin->l('An error occured. Please contact the store owner for more information');
                        return $this->setTemplate('error.tpl');
                    }

                    Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$order->id.'&key='.$customer->secure_key);

                } elseif ($payment_response->getAction() === "contributed") {

                    $order_id = Order::getOrderByCartId((int) ($payment_response->getMerchantOrderId()));
                    $order = new Order($order_id);

                    $this->context->smarty->assign(array(
                        'order' => $order,
                        'products' => $cart->getProducts(),
                    ));

                    $this->addCSS(_THEME_CSS_DIR_.'contributed.css');
                    $this->addCSS(_THEME_CSS_DIR_.'product_list.css');

                    return $this->setTemplate('contributed.tpl');
                }

            } elseif ($payment_response->getAction() === "failed" || $payment_response->getAction() === "rejected" || $payment_response->getAction() === "timed_out") {

                if(Order::getOrderByCartId((int) ($payment_response->getMerchantOrderId()))) {
                    $order_id = Order::getOrderByCartId((int) ($payment_response->getMerchantOrderId()));
                    $order = new Order($order_id);
                    $action = strtoupper('CP_OS_PAYMENT_'.$payment_response->getAction());
                    $order->setCurrentState(Configuration::get($action));
                }

                if($payment_response->getAction() !== "timed_out") {
                    $this->errors[] = $chippin->l('Chippin payment status: ' . $payment_response->getAction() . '. Please contact the store owner for more information');
                    return $this->setTemplate('error.tpl');
                }

            } elseif ($payment_response->getAction() === "cancelled") {
                
                // only if there is an order with that merchant id - then update the status
                if(Order::getOrderByCartId((int) ($payment_response->getMerchantOrderId()))) {
                    $order_id = Order::getOrderByCartId((int) ($payment_response->getMerchantOrderId()));
                    $order = new Order($order_id);
                    $action = strtoupper('CP_OS_PAYMENT_'.$payment_response->getAction());
                    $order->setCurrentState(Configuration::get($action));
                } else {
                    $this->errors[] = $chippin->l('An error occured. Please contact the store owner for more information');
                    return $this->setTemplate('error.tpl');
                }

                // redirect to order page

                // if a one-page checkout is enabled
                if(Configuration::get("PS_ORDER_PROCESS_TYPE") === "1") {
                    Tools::redirectLink(_PS_BASE_URL_.'/quick-order?step=1');
                } else {
                    Tools::redirectLink(_PS_BASE_URL_.'/order?step=1');    
                }  

            } elseif ($payment_response->getAction() === "paid") {
                if(Order::getOrderByCartId((int) ($payment_response->getMerchantOrderId()))) {
                    $order_id = Order::getOrderByCartId((int) ($payment_response->getMerchantOrderId()));
                    $order = new Order($order_id);
                    $order->setCurrentState(Configuration::get('CP_OS_PAYMENT_PAID'));
                } else {
                    $this->errors[] = $chippin->l('An error occured. Please contact the store owner for more information');
                    return $this->setTemplate('error.tpl');
                }

            }  else {
                $this->errors[] = $chippin->l('An error occured. Please contact the store owner for more information');
                return $this->setTemplate('error.tpl');
            }

        } else {
            $this->errors[] = $chippin->l('An error occured. Please contact the store owner for more information');
            return $this->setTemplate('error.tpl');
        }
    }
}
