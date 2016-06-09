<?php

/**
 * Class ChippinConfirmationModuleFrontController
 * Extends ModuleFrontController
 * @see ModuleFrontController
 */
class ChippinValidator
{
    public static function isValidHmac(PaymentResponseChippin $paymentResponse)
    {
        $hash = "";

        if($paymentResponse->getAction() !== "contributed") {

            $hash = hash_hmac('sha256', $paymentResponse->getAction() . Chippin::getConfig('MERCHANT_ID') . $paymentResponse->getMerchantOrderId(), Chippin::getConfig('MERCHANT_SECRET'));

        } elseif ($paymentResponse->getAction() === "contributed") {

            $hash = hash_hmac('sha256', $paymentResponse->getAction() . Chippin::getConfig('MERCHANT_ID') . $paymentResponse->getMerchantOrderId() . $paymentResponse->getFirstName() .
            $paymentResponse->getLastName() . $paymentResponse->getEmail(), Chippin::getConfig('MERCHANT_SECRET'));
        }

        if($hash === $paymentResponse->getHmac()) {
            return true;
        }

        return false;
    }

    public static function generateHash($price_in_pence, $orderCurrency, $cartId)
    {
        return hash_hmac('sha256', Chippin::getConfig('MERCHANT_ID') . $cartId . $price_in_pence . Chippin::getConfig('DURATION') . Chippin::getConfig('GRACE_PERIOD') . $orderCurrency, Chippin::getConfig('MERCHANT_SECRET'));
    }
}
