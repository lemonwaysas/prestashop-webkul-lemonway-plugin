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

$sql = array();

$sql[] = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."lemonwaymkt_wallet_transaction` (
  `id_transaction` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Transaction ID',
  `id_order` int(11) NOT NULL COMMENT 'Real Order ID',
  `id_customer` int(11) NOT NULL COMMENT 'Customer ID',
  `seller_id` int(11) NOT NULL COMMENT 'Seller ID',
  `shop_name` varchar(255) NOT NULL COMMENT 'Shop name',
  `amount_total` decimal(20,6) NOT NULL COMMENT 'Total amount to pay',
  `amount_to_pay` decimal(20,6) NOT NULL COMMENT 'Total amount to pay',
  `admin_commission` decimal(20,6) NOT NULL COMMENT 'Total amount to pay',
  `lw_commission` decimal(20,6) DEFAULT 0 COMMENT 'LW commission returned after send payment',
  `status` smallint(2) NOT NULL DEFAULT 0 COMMENT 'Transaction Status',
  `lw_id_send_payment` varchar(255) COMMENT 'Send payment Lemonway ID',
  `debit_wallet` varchar(255) DEFAULT NULL COMMENT 'Wallet debtor',
  `credit_wallet` varchar(255) DEFAULT NULL COMMENT 'Wallet creditor',
  `date_add` datetime NOT NULL COMMENT 'Wallet Creation Time',
  `date_upd` datetime NOT NULL COMMENT 'Wallet Modification Time',
  PRIMARY KEY (`id_transaction`),
  UNIQUE KEY (`id_order`,`id_customer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Wallet transactions Table' ;";

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
