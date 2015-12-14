<?php

class AdminWalletTransactionController extends ModuleAdminController{
	
	
	
	public function __construct()
	{
		
		$this->bootstrap = true;
		$this->table = 'lemonwaymkt_wallet_transaction';
		$this->identifier = 'id_transaction';
		$this->className = 'WalletTransaction';
		$this->lang = false;
		//$this->addRowAction('view');
		$this->list_no_link = true;
		$this->allow_export = true;
		$this->deleted = false;
		$this->context = Context::getContext();
		$this->multiple_fieldsets = true;
		$this->show_toolbar = false;
		
		$this->explicitSelect = true;
		$this->_select = '
				a.*,
				CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`';//,
				//FROM `'._DB_PREFIX_.'lemonway_moneyout` mo';
		
		$this->_join = '
				LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = a.`id_customer`)
				';
		
		
		$this->_orderBy = 'id_transaction';
		$this->_orderWay = 'DESC';
		$this->_use_found_rows = true;
		
		$this->fields_list = array(
				'id_transaction' => array(
						'title' => $this->l('ID'),
						'align' => 'text-center',
						'class' => 'fixed-width-xs'
				),
				'id_order' => array(
						'title' => $this->l('Order ID'),
						'align' => 'text-center',
						'class' => 'fixed-width-xs'
				),
				'customer' => array(
						'title' => $this->l('Customer'),
						'havingFilter' => true,
				),
				'shop_name' => array(
						'title' => $this->l('Vendor')
				),
				'amount_total' => array(
						'title' => $this->l('Amount total'),
						'align' => 'text-right',
						'type' => 'price',
						'currency' => true,
				),
				'amount_to_pay' => array(
						'title' => $this->l('To pay'),
		                'align' => 'text-right',
		                'type' => 'price',
		                'currency' => true,
				),
				'admin_commission' => array(
						'title' => $this->l('Commission'),
						'align' => 'text-right',
						'type' => 'price',
						'currency' => true,
				),
				/*'debit_wallet' => array(
						'title' => $this->l('Wallet debtor')
				),
				'credit_wallet' => array(
						'title' => $this->l('Wallet Creditor')
				),*/
				'status' => array(
						'title' => $this->l('Status')
				),
				'date_add' => array(
						'title' => $this->l('Created'),
		                'align' => 'text-right',
		                'type' => 'datetime',
		                'filter_key' => 'a!date_add'
				),
				'date_upd' => array(
						'title' => $this->l('Updated'),
						'align' => 'text-right',
						'type' => 'datetime',
						'filter_key' => 'a!date_upd'
				),
				
		);
		
		parent::__construct();

	}
	
	public function initToolbar()
	{
		parent::initToolbar();
		
	}
	
	public function initPageHeaderToolbar()
	{
		parent::initPageHeaderToolbar();
	
		if ($this->display == 'add') {
			unset($this->page_header_toolbar_btn['save']);
		}

	}
	
	/**
	 * Set default toolbar_title to admin breadcrumb
	 *
	 * @return void
	 */
	public function initToolbarTitle()
	{
		parent::initToolbarTitle();

	}
	
	
	/**
	 * Object creation
	 *
	 * @return ObjectModel|false
	 * @throws PrestaShopException
	 */
	public function processAdd()
	{	
		return parent::processAdd();
	}


	
	public function setMedia(){
	
		parent::setMedia();
		$this->addJS(_PS_MODULE_DIR_.$this->module->name."/views/js/back.js");
		//$this->addCSS($this->path.'/css/mymodule.css', 'all');
	
	}
	
	
}