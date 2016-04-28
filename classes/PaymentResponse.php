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
 * Class PaymentResponse
 * Extends Response
 * @see Response
 */
class PaymentResponse
{
    private $merchant_order_id;

    private $hmac;

    private $action;

    private $param_list;

    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * This method compares the Sign string sent by Alipay's response with a Sign string generated with local values
     * @return bool
     */
    // public function compareSign()
    // {
    //     require_once(dirname(__FILE__).'/alipay.api.php');
    //     $default_config = array(
    //         'partner_id' => false,
    //         'service' => false
    //     );
    //     $credentials = AlipayTools::getCredentials(false, $default_config);
    //     $alipayapi = new AlipayApi($credentials);
    //     $generated_string = $alipayapi->getResponseSign($this);
    //     if ($this->sign != $generated_string) {
    //         return false;
    //     }
    //     return true;
    // }

    /**
     * This method checks data integrity
     * @param bool $response
     * @param bool $params
     * @return bool
     */
    // public function processResponse($response = true, $params = true)
    // {
    //     $alipay = new Alipay();
    //     if (!$response || !$params) {
    //         $this->errors[] = $alipay->l('An error occured. Please contact the merchant to have more informations');
    //         return false;
    //     }
    //     $cart = new Cart((int)$this->id_cart);
    //     if (!Validate::isLoadedObject($cart)) {
    //         $this->errors[] = $alipay->l('Cannot load Cart object.');
    //         return false;
    //     }
    //     $customer = new Customer((int)$cart->id_customer);
    //     if (!Validate::isLoadedObject($customer)) {
    //         $this->errors[] = $alipay->l('Cannot load Customer object.');
    //         return false;
    //     }
    //     if ($customer->secure_key != $this->secure_key || !$this->compareSign()) {
    //         $this->errors[] = $alipay->l('An error occured. Please contact the merchant to have more informations');
    //         return false;
    //     }
    //     $this->tplVars = array(
    //         'id_cart'       => $this->id_cart,
    //         'id_module'     => $this->id_module,
    //         'secure_key'    => $this->secure_key
    //     );
    //     return true;
    // }

    /**
     * @return mixed
     */
    public function getMerchantOrderId()
    {
        return $this->merchant_order_id;
    }

    /**
     * @param mixed $merchant_order_id
     */
    public function setMerchantOrderId($merchant_order_id)
    {
        $this->merchant_order_id = $merchant_order_id;
    }

    /**
     * @return mixed
     */
    public function getHmac()
    {
        return $this->hmac;
    }

    /**
     * @param mixed $hmac
     */
    public function setHmac($hmac)
    {
        $this->hmac = $hmac;
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param mixed $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @return mixed
     */
    public function getParamList()
    {
        return $this->param_list;
    }

    /**
     * This method sets the PaymentResponse object parameters with the Post values
     */
    public function getPostData()
    {
        $this->setMerchantOrderId(Tools::getValue('merchant_order_id'));
        $this->setHmac(Tools::getValue('hmac'));
        $this->setAction(Tools::getValue('action'));
    }
}
