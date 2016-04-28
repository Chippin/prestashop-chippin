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
class ChippinValidator
{
    const PREFIX = 'CHIPPIN_';

    private static $chippinMerchantId;
    private static $chippinMerchantSecret;

    public static function getConfig($name)
    {
        return Configuration::get(Tools::strtoupper(self::PREFIX.$name));
    }

    public static function isValidHmac(PaymentResponse $paymentResponse)
    {
        self::$chippinMerchantId = self::getConfig('MERCHANT_ID');
        self::$chippinMerchantSecret = self::getConfig('MERCHANT_SECRET');
        $hash = "";

        if($paymentResponse->getAction() !== "contributed") {

            $hash = hash_hmac('sha256', $paymentResponse->getAction() . self::$chippinMerchantId . $paymentResponse->getMerchantOrderId(), self::$chippinMerchantSecret);

        } elseif ($paymentResponse->getAction() === "contributed") {

            $hash = hash_hmac('sha256', $paymentResponse->getAction() . self::$chippinMerchantId . $paymentResponse->getMerchantOrderId() . $paymentResponse->getFirstName() .
            $paymentResponse->getLastName() . $paymentResponse->getEmail(), self::$chippinMerchantSecret);

        }

        if($hash === $paymentResponse->getHmac()) {
            return true;
        }

        return false;
    }
}
