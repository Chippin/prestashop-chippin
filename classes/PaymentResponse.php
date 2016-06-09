<?php

/**
 * Class PaymentResponseChippin
 * Extends Response
 * @see Response
 */
class PaymentResponseChippin
{
    private $merchant_order_id;
    private $hmac;
    private $action;
    private $param_list;
    private $email;
    private $first_name;
    private $last_name;

    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }

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
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $action
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * @param mixed $action
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @param mixed $action
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
    }

    /**
     * @return mixed
     */
    public function getParamList()
    {
        return $this->param_list;
    }

    /**
     * This method sets the PaymentResponseChippin object parameters with the Post values
     */
    public function getPostData()
    {
        $this->setMerchantOrderId(Tools::getValue('merchant_order_id'));
        $this->setHmac(Tools::getValue('hmac'));
        $this->setAction(Tools::getValue('action'));
        $this->setEmail(Tools::getValue('email'));
        $this->setFirstName(Tools::getValue('first_name'));
        $this->setLastName(Tools::getValue('last_name'));
    }
}
