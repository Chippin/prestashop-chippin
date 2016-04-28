<?php
/**
 * NOTICE OF LICENCE
 */

if (!defined('_PS_VERSION_') || (is_object(Context::getContext()->customer) && !Tools::getToken(false, Context::getContext())))
	exit;

require_once _PS_MODULE_DIR_.'chippin/chippin.php';
require_once _PS_MODULE_DIR_.'chippin/classes/ChippinOrder.php';
require_once _PS_MODULE_DIR_.'chippin/classes/loader.php';
