<?php

if (!defined('_PS_VERSION_'))
	exit;

/*
 * Process Module upgrade to 1.7.0
 * @param $module
 * @return bool
 */
function upgrade_module_1_7_0($module)
{
	return $module->registerHook('actionObjectCurrencyUpdateBefore');
}
