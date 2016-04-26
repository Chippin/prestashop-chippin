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
 *         DISCLAIMER   *
 * ***************************************
 * Do not edit or add to this file if you wish to upgrade Prestashop to newer
 * versions in the future.
 * ****************************************************
 *
* Simpleweb
 */

require_once _PS_MODULE_DIR_.'chippin/includer.php';

/**
 * Chippin Order Object Model
 *
 *
 */
class ChippinOrder extends ObjectModel {

	public $id;
	public $id_chippin_order;
	public $id_cart;
	public $chippin_reference;
	public $refunded = 0;
	public static $definition = array(
		'table' => 'chippin_order',
		'primary' => 'id_chippin_order',
		'multilang' => false,
		'fields' => array(
			'id_cart' => array('type' => self::TYPE_INT, 'required' => true),
			'chippin_reference' => array('type' => self::TYPE_INT, 'required' => true),
			'refunded' => array('type' => self::TYPE_BOOL),
		),
	);

	public static function getByPsCartId($id_cart, $refunded = false)
	{
		return Db::getInstance()->getRow(
			'SELECT * FROM `'._DB_PREFIX_.'chippin_order`
			WHERE 1 '.
			($refunded ? 'AND refunded != 1 ' : '').'
			AND id_cart = "'.pSQL($id_cart).'"'
		);
	}

	/**
	 * Get all available order states
	 *
	 * @param integer $id_lang Language id for state name
	 * @return array Order states
	 */
	public static function getOrderStates($id_order, $id_lang)
	{
		$states = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT *
            FROM `'._DB_PREFIX_.'order_state` os
            LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state`
				AND osl.`id_lang` = '.(int)$id_lang.')
            WHERE deleted = 0
            ORDER BY `name` ASC
        ');

		$order_obj = new Order($id_order);
		$chippin_info = ChippinOrder::getByPsCartId($order_obj->id_cart);
		if (isset($chippin_info['chippin_reference']) && !empty($chippin_info['chippin_reference']))
			return $states;
		else
		{
			foreach ($states as $key => $state)
			{
				if ($state['id_order_state'] == Configuration::get('BS_OS_PAYMENT_VALID'))
					unset($states[$key]);
			}

			return $states;
		}
	}

}
