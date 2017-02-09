<?php

if (!defined('_PS_VERSION_')) {
	exit;
}

require_once _PS_MODULE_DIR_.'lemonway/services/LemonWayKit.php';
require_once _PS_MODULE_DIR_.'lemonway/classes/Wallet.php';
require_once _PS_MODULE_DIR_.'lemonwaymkt/classes/WalletTransaction.php';
require_once _PS_MODULE_DIR_.'marketplace/classes/MarketplaceClassInclude.php';
class Lemonwaymkt extends Module{
	
	protected $_current_wallet = null;
	protected $_lw_module;
	
	public function __construct()
	{
		$this->name = 'lemonwaymkt';
		$this->tab = 'payments_gateways';
		$this->version = '1.1.2';
		$this->author = 'SIRATECK';
		$this->need_instance = 0;
		$this->dependencies = array('lemonway','marketplace');
		
		$this->_lw_module = Module::getInstanceByName('lemonway');
	
		/**
		 * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
		 */
		$this->bootstrap = true;
	
		parent::__construct();
	
		$this->displayName = $this->l('Lemonway Marketplace');
		$this->description = $this->l('It\'s adaptive Lemonway payment for marketplace module from Webkul');
	
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall my module? You will be loose your datas !');

	}
	
	public function getBaseModule(){
		return $this->_lw_module;
	}
	
	/**
	 * Don't forget to create update methods if needed:
	 * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
	 */
	public function install()
	{
		if (extension_loaded('curl') == false)
		{
			$this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
			return false;
		}
	
		//Mkt CONFIGURATION
		Configuration::updateValue('LEMONWAYMKT_WALLET_AUTO_CREATE', 1);
		Configuration::updateValue('LEMONWAYMKT_AUTO_DISPATCH', 1);
		Configuration::updateValue('LEMONWAY_COMMISSION_AMOUNT', 0.00);
		Configuration::updateValue('LEMONWAY_IS_AUTO_COMMISSION', 1);
		
		include(dirname(__FILE__).'/sql/install.php');
		
		$translationsAdminWalletTransactions = array('en'=>'Wallet transaction','fr'=>'Transactions wallets vendeurs');
		
		$adminLemonwayId = Db::getInstance()->getValue("SELECT `id_tab` FROM "._DB_PREFIX_."tab WHERE `class_name`='AdminLemonway'");
		
		return parent::install() &&
			$this->registerHook('displayMpMyAccountMenuActiveSeller') &&
			$this->registerHook('displayMpmenuhookext') &&
			$this->registerHook('actionObjectSellerInfoDetailAddAfter') &&
			$this->registerHook('actionSellerProfileStatus') &&
			$this->registerHook('actionValidateOrder') &&
			$this->_lw_module->installModuleTab('AdminWalletTransaction', $translationsAdminWalletTransactions, $adminLemonwayId,$this->name);
		
	}
	
	public function uninstall()
	{
		//API CONFIGURATION
		Configuration::deleteByName('LEMONWAYMKT_WALLET_AUTO_CREATE');
		Configuration::deleteByName('LEMONWAYMKT_AUTO_DISPATCH');
		Configuration::deleteByName('LEMONWAY_COMMISSION_AMOUNT');
		Configuration::deleteByName('LEMONWAY_IS_AUTO_COMMISSION');
		
		$this->_lw_module->uninstallModuleTab('AdminWalletTransaction');
		
		return parent::uninstall();
	}
	
	/**
	 * Load the configuration form
	 */
	public function getContent()
	{
		/**
		 * If values have been submitted in the form, process.
		 */
		if (((bool)Tools::isSubmit('submitLemonwaymktModule')) == true) {
			$this->postProcess();
		}
	
		return $this->renderForm();
	}
	
	/**
	 * Create the form that will be displayed in the configuration of your module.
	 */
	protected function renderForm()
	{
		$helper = new HelperForm();
	
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$helper->module = $this;
		$helper->default_form_language = $this->context->language->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
	
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitLemonwaymktModule';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
		.'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
	
		$helper->tpl_vars = array(
				'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
				'languages' => $this->context->controller->getLanguages(),
				'id_language' => $this->context->language->id,
		);
	
		return $helper->generateForm(array($this->getConfigForm()));
	}
	
	/**
	 * Create the structure of marketplace infos form.
	 */
	protected function getConfigForm()
	{
		$container = array(
				'form' => array(
						'legend'=>array(
								'title' => $this->l('MARKETPLACE CONFIGURATION'),
								'icon' => 'icon-xart',
						),
						'input' => array(),
						'submit' => array(
								'title' => $this->l('Save'),
						)
				),
		);
		
		$container['form']['input'][] = array(
				'type' => 'switch',
				'label' => $this->l('Auto commission'),
				'name' => 'LEMONWAY_IS_AUTO_COMMISSION',
				'is_bool' => true,
				'desc' => $this->l('If No you must fill field Commission amount below.'),
				'values' => array(
						array(
								'id' => 'active_on',
								'value' => true,
								'label' => $this->l('Enabled')
						),
						array(
								'id' => 'active_off',
								'value' => false,
								'label' => $this->l('Disabled')
						)
				)
		);
		 
		$container['form']['input'][] = array(
				'col' => 3,
				'label' => $this->l('Comission amount'),
				'name' => 'LEMONWAY_COMMISSION_AMOUNT',
				'type' => 'text',
				'prefix' => '<i class="icon icon-eur"></i>',
				'is_number' => true,
				'desc' => '',
		);
		 
		 
		$container['form']['input'][] = array(
				'type' => 'switch',
				'label' => $this->l('Wallet auto creation'),
				'name' => 'LEMONWAYMKT_WALLET_AUTO_CREATE',
				'is_bool' => true,
				'desc' => $this->l('If you choose No your vendors must create it manually.'),
				'values' => array(
						array(
								'id' => 'active_on',
								'value' => true,
								'label' => $this->l('Enabled')
						),
						array(
								'id' => 'active_off',
								'value' => false,
								'label' => $this->l('Disabled')
						)
				)
		);
		
		/*$container['form']['input'][] = array(
				'type' => 'switch',
				'label' => $this->l('Auto money dispatch'),
				'name' => 'LEMONWAYMKT_AUTO_DISPATCH',
				'is_bool' => true,
				'desc' => $this->l('If you choose No you will be do that manually.'),
				'values' => array(
						array(
								'id' => 'active_on',
								'value' => true,
								'label' => $this->l('Enabled')
						),
						array(
								'id' => 'active_off',
								'value' => false,
								'label' => $this->l('Disabled')
						)
				)
		);
		 
		$container['form']['input'][] = array(
				'col' => 3,
				'label' => $this->l('Comission amount'),
				'name' => 'LEMONWAY_COMMISSION_AMOUNT',
				'type' => 'text',
				'prefix' => '<i class="icon icon-eur"></i>',
				'is_number' => true,
				'desc' => '',
		);*/
		 
		
		 
		 
		 
		return $container;
	}
	
	/**
	 * Set values for the inputs.
	 */
	protected function getConfigFormValues()
	{
		return array(
				'LEMONWAY_IS_AUTO_COMMISSION' => Configuration::get('LEMONWAY_IS_AUTO_COMMISSION', null),
				'LEMONWAY_COMMISSION_AMOUNT' => Configuration::get('LEMONWAY_COMMISSION_AMOUNT', null),
				'LEMONWAYMKT_WALLET_AUTO_CREATE' => Configuration::get('LEMONWAYMKT_WALLET_AUTO_CREATE', null),
				'LEMONWAYMKT_AUTO_DISPATCH' => Configuration::get('LEMONWAYMKT_AUTO_DISPATCH', null),
		);
	}
	
	/**
	 * Save form data.
	 */
	protected function postProcess()
	{
		$form_values = $this->getConfigFormValues();
	
		foreach (array_keys($form_values) as $key) {
			if ($key == 'LEMONWAYMKT_AUTO_DISPATCH') {
				continue;
			}
			Configuration::updateValue($key, Tools::getValue($key));
		}
	}
	
	public function hookDisplayMpMyAccountMenuActiveSeller()
	{
		$this->context->smarty->assign('mpmenu', '0');
		return $this->display(__FILE__, 'wallet_link.tpl');
		
		$id_customer = $this->context->cookie->id_customer;
		$mp_customer = new MarketplaceCustomer();
		$link = new Link();
		$mp_customer_info = $mp_customer->findMarketPlaceCustomer($id_customer);
		if ($mp_customer_info) {
			$is_seller = $mp_customer_info['is_seller'];
			if ($is_seller == 1) {
				$this->context->smarty->assign('mpmenu', '0');
				return $this->display(__FILE__, 'wallet_link.tpl');
			}
		}
	}
	
	public function hookDisplayMpmenuhookext()
	{
		$this->context->smarty->assign('mpmenu', '1');
		return $this->display(__FILE__, 'wallet_link.tpl');
		
		$id_customer = $this->context->cookie->id_customer;
		$mp_customer = new MarketplaceCustomer();
		$link = new Link();
		$mp_customer_info = $mp_customer->findMarketPlaceCustomer($id_customer);
		if ($mp_customer_info) {
			$is_seller = $mp_customer_info['is_seller'];
			if ($is_seller == 1) {
				$this->context->smarty->assign('mpmenu', '1');
				return $this->display(__FILE__, 'wallet_link.tpl');
			}
		}
	}
	
	/**
	 * 
	 * @param Customer $customer
	 * @param int $seller_id
	 * @return boolean
	 */
	public function registerWallet($customer,$seller_id,$id_lang=null)
    {
		$sellerInfoObj = new SellerInfoDetail();
		$sellerInfo = $sellerInfoObj->sellerDetail($seller_id);
		if(!$sellerInfo)
			throw new Exception($this->l("Seller infos not found!"));
		
		$wallet = new WalletCore();
		$wallet->is_admin = false;
		$wallet->is_default = true;
		$wallet->id_customer = $customer->id;
		
		$kit = new LemonWayKit();
		$params = array();
		
		$params['wallet'] = "wallet-" .$seller_id. "-" . $customer->id;
		$wallet->id_lw_wallet = $params['wallet'];
		
		$params['clientMail'] = $sellerInfo['business_email'];
		$wallet->customer_email = $params['clientMail'];
		
		$gender = new Gender($customer->id_gender, Context::getContext()->language->id);
		if ($gender) {
			//$params['clientTitle'] = $gender->name;
			$wallet->customer_prefix = $gender->name;
		}

		$params['clientFirstName'] = $customer->firstname;
		$wallet->customer_firstname = $params['clientFirstName'];
		
		$params['clientLastName'] = $customer->lastname;
		$wallet->customer_lastname = $params['clientLastName'];
		
		$params['street'] = ''; //@TODO maybe get customer address because shop address is all(street,postcode,city...) in one field
		$params['postCode'] = '';
		$params['city'] = '';
		$params['ctry'] = '';
		
		$birthday = '';
		if ($customer->birthday) {
			$wallet->customer_dob = $customer->birthday;
			$birthday = explode('-', $customer->birthday);
		}
		
		$params['birthdate'] = is_array($birthday) ? $birthday[2]."/".$birthday[1]."/".$birthday[0] : "";
		
		$params['phoneNumber'] = $sellerInfo['phone'];
		$wallet->billing_address_phone = $params['phoneNumber'];
		
		$params['mobileNumber'] = $sellerInfo['phone'];
		$wallet->billing_address_mobile = $params['mobileNumber'];
		
		$params['isCompany'] = 1;
		$wallet->is_company = 1;
		
		$params['companyName'] = $sellerInfo['shop_name'];
		$wallet->company_name = $params['companyName'] ;
		
		$params['companyWebsite'] = '';
		$wallet->company_website = $params['companyWebsite'];
		
		//$params['companyDescription'] = substr($sellerInfo['about_shop'], 0,150);
		//$wallet->company_description = $params['companyDescription'] ;
		
		if($customer->siret) {		
			$params['companyIdentificationNumber'] = $customer->siret;
			$wallet->company_id_number = $params['companyIdentificationNumber'];
		}
		
		$params['isDebtor'] = 0;
		$wallet->is_debtor = $params['isDebtor'] ;
		
		$params['nationality'] = '';
		$params['birthcity'] = '';
		$params['birthcountry'] = '';
		$params['payerOrBeneficiary'] = 2; //1 for payer, 2 for beneficiary
		$wallet->payer_or_beneficiary = $params['payerOrBeneficiary'] ;
		
		$params['isOneTimeCustomer'] = 0;
		$wallet->is_onetime_customer = $params['isOneTimeCustomer'] ;
		Logger::AddLog('<pre>'.print_r($params,true));
		try {
			$res = $kit->RegisterWallet($params);
			
			if (isset($res->lwError) && (int)$res->lwError->CODE != 152) {
				throw new Exception($res->lwError->MSG, (int)$res->lwError->CODE,null);
			}
			elseif (!isset($res->lwError)) {
				$wallet->status = $res->wallet->STATUS;
			}
			
			$wallet->save();	
		} catch (Exception $e) {
			throw $e;	
		}

		return $wallet;
	}
	
	protected function registerWalletAfterVendorCreation($params)
    {
		$obj = $params['object'];
		$mk_customer_obj = new MarketplaceCustomer();
		$customer_id = $mk_customer_obj->getCustomerId($obj->id);
		$wallet_obj = new WalletCore();
		$walletExist = $wallet_obj->getByCustomerId($customer_id);
		
		if($obj->is_seller && !$walletExist && (bool)Configuration::get('LEMONWAYMKT_WALLET_AUTO_CREATE') === true ) {
			try {
		
				$this->registerWallet(new Customer($customer_id), $obj->id);
			} catch (Exception $e) {
				Logger::AddLog($e->getMessage(),4);
			}	
		}
	}
	
	/**
	 * When admn approve vendor the first time
	 * @param array $params
	 */
	public function hookActionSellerProfileStatus($params){
		
		$wallet_obj = new WalletCore();
		$mk_customer_obj = new MarketplaceCustomer();
		$mk_customer = $mk_customer_obj->getMpCustomer($params['mp_id_seller']);
		
		$walletExist = $wallet_obj->getByCustomerId($obj->id_customer);
		
		if($mk_customer['is_seller'] && !$walletExist && (bool)Configuration::get('LEMONWAYMKT_WALLET_AUTO_CREATE') === true ) {
			try {
				$this->registerWallet(new Customer($mk_customer['id_customer']), $params['mp_id_seller']);
			} catch (Exception $e) {
				Logger::AddLog($e->getMessage(),4);
			}
		}
	}
	
	/**
	 * When seller info detail created
	 * @param array $params
	 */
	public function hookActionObjectSellerInfoDetailAddAfter($params){
		$this->registerWalletAfterVendorCreation($params);
	}
	
	public function hookActionValidateOrder($params)
	{
		/* @var $order OrderCore  */ 
		$order = $params['order'];
		$id_order = $order->id;
		$order_status = $params['orderStatus'];
		
		if(Tools::strtolower($order->module) != 'lemonway')
			return ;
		
		if($order_status->id != Configuration::get('PS_OS_PAYMENT') && $order_status->id != Configuration::get('LEMONWAY_PENDING_OS') ){
			return ;
		}

		$obj_mpsellerorderdetails = new MarketplaceSellerOrderDetails();
		// get cart order products, customer, seller details
        $seller_cart_products = $obj_mpsellerorderdetails->getSellerOrderedProductDetails($id_order);
        if ($seller_cart_products) {
        	foreach ($seller_cart_products as $product) {
        			
        		$id_customer = $product['id_customer'];
        		
        		$obj_mpcommission = new MarketplaceCommision();
        		$obj_mpcommission->customer_id = $id_customer;
        		$commission_by_seller = $obj_mpcommission->getCommissionRateBySeller();
        		//apply global commission, if commission by particular seller not defined and if commission set to 0.00 no commission applied for this seller
        		if (!is_numeric($commission_by_seller)) {
        			if ($global_commission = Configuration::get('MP_GLOBAL_COMMISSION')) {
        				$commission_rate = $global_commission;
        			} else {
        				$commission_rate = 0;
        			}
        		} else {
        			$commission_rate = $commission_by_seller;
        		}

        		// create seller order commission details
        		$admin_commission = (($product['total_price_tax_excl']) * $commission_rate) / 100;
        	
        		//create seller amount, the rest amount from 100 after seller commission
        		$seller_amt = (($product['total_price_tax_excl']) * (100 - $commission_rate)) / 100;
        	
        		//Distribution of product tax
        		$total_tax = $product['total_price_tax_incl'] - $product['total_price_tax_excl'];
        	
        		if (Configuration::get('MP_PRODUCT_TAX_DISTRIBUTION') == 'admin') {
        			$admin_commission = $admin_commission + $total_tax;
        		} elseif (Configuration::get('MP_PRODUCT_TAX_DISTRIBUTION') == 'seller') {
        			$seller_amt = $seller_amt + $total_tax;
        		} elseif (Configuration::get('MP_PRODUCT_TAX_DISTRIBUTION') == 'distribute_both') {
        			$tax_to_admin = ($total_tax * $commission_rate) / 100; //for ex: 10% to admin
        			$tax_to_seller = $total_tax - $tax_to_admin; //the rest 90% to seller
        	
        			$admin_commission = $admin_commission + $tax_to_admin;
        			$seller_amt = $seller_amt + $tax_to_seller;
        		}
        		//Distribution of product tax close

        		if(!isset($transactions[$id_customer])) {
        			try {
        				$wallet_obj = new WalletCore();
        				$wallet_transac_obj = new WalletTransaction();
        				$wallet_transac = $wallet_transac_obj->getByOrderForCustomer($id_order, $id_customer);
        					
        				if(!$wallet_transac) {
        					$wallet_transac = $wallet_transac_obj;
                        }
        					
        				$wallet = $wallet_obj->getByCustomerId($id_customer);
        	
        				$wallet_transac->id_order = $id_order;
        				$wallet_transac->id_customer = $id_customer;
        				$wallet_transac->status = WalletTransaction::STATUS_TO_PAY;
        				$wallet_transac->seller_id = $product['marketplace_seller_id'];
        				$wallet_transac->shop_name = $product['shop_name'];
        				$wallet_transac->credit_wallet = $wallet ? $wallet->id_lw_wallet : "none";
        				$wallet_transac->debit_wallet = LemonWayConfig::getWalletMerchantId();
        				$wallet_transac->admin_commission = $admin_commission;
        				$wallet_transac->amount_to_pay = $seller_amt;
        				$wallet_transac->amount_total = $admin_commission + $seller_amt;

        				$transactions[$id_customer] = $wallet_transac;
        					
        			} catch (Exception $e) {
        				Logger::AddLog($e->getMessage(),4);
        			}
        		}
        		else {	
        			$wallet_transac = $transactions[$id_customer];
        	
        			$wallet_transac->admin_commission += $admin_commission;
        			$wallet_transac->amount_to_pay += $seller_amt;
        			$wallet_transac->amount_total += $admin_commission +$seller_amt;
        	
        		}
        			
        			
        			
        	}//end foreach $seller_cart_products
			
        	
        	//$total_admin_commission = 0;
        	$total_seller_amt = 0;
        	$kit = new LemonWayKit();
        	
        	foreach ($transactions as $customer_id=>$w_transac) {
        		//Save with default status "to pay" before call LW service
        		try {
        			$res =$w_transac->save();
        		} catch (Exception $e) {
        			Logger::AddLog($e->getMessage(),4,null,null,true);
        		}
        		if($res && $w_transac->credit_wallet != 'none')
        		{
        	
        			//Call send Payment method if config is auto dispatch
        			//Check conf auto dispatch and Check status order and if it's valid order we do send payment
        			if(Configuration::get('LEMONWAYMKT_AUTO_DISPATCH')) {
        						
	        			
	        			
	        			$params = array(
	        					"debitWallet"	=> $w_transac->debit_wallet,
	        					"creditWallet"	=> $w_transac->credit_wallet,
	        					"amount"		=> number_format((float)$w_transac->amount_to_pay, 2, '.', ''),
	        					"message"		=> Configuration::get('PS_SHOP_NAME') . " - " . sprintf($this->l('Send payment for order %s'), $id_order)
	        			);
	        				
	        				
	        			try {
		        				$res = $kit->SendPayment($params);
		        	
		        				if(isset($res->lwError))
		        				{
		        					$msg = sprintf(Tools::displayError("Error: %s. Code: %s"),$res->lwError->MSG, $res->lwError->CODE);
		        					Logger::AddLog($msg,4);
		        				}
		        	
		        				if(count($res->operations) && ($op = current($res->operations)))
		        				{
			        				$w_transac->status = WalletTransaction::STATUS_PAID;
			        				$w_transac->lw_commission = $op->COM;
			        					
			        				$w_transac->save();
		        				}
	        	
	        				} catch (Exception $e) {
	        	
	        					Logger::AddLog($e->getMessage(),4);
	        	
	        				}
        					
        			}
        			
        			//$total_admin_commission += $w_transac->admin_commission;
        			$total_seller_amt += $w_transac->amount_to_pay;
        			
        		}
        		elseif($w_transac->credit_wallet == 'none'){
        			Logger::AddLog(sprintf($this->l("Transaction not sended to Lemonway because shop: %s no has wallet !"), $w_transac->shop_name), 1, null, null, true);
        		}
        		else{
        			Logger::AddLog($this->l("Transaction not saved!"), 1, null, null, true);
        		}
        	}//endforeach transactions
        	
        	//rest of amount order
        	/*$rest_order_amount = $order->total_paid - $total_seller_amt;
        	
        	if($rest_order_amount > 0){
        		
	        	//Send commissions amount to wallet SC
	        	$params = array(
	        			"debitWallet"	=> $w_transac->debit_wallet,
	        			"creditWallet"	=> "SC",
	        			"amount"		=> number_format((float)$rest_order_amount, 2, '.', ''),
	        			"message"		=> sprintf($this->l('Send the rest of the order %s (commssions,shipping, your products ...)'),$id_order)
	        	);
	        	
	        	
	        	try {
	        		$res = $kit->SendPayment($params);
	        		 
	        		if(isset($res->lwError))
	        		{
	        			$msg = sprintf(Tools::displayError("Error: %s. Code: %s"),$res->lwError->MSG, $res->lwError->CODE);
	        			Logger::AddLog($msg,4);
	        		}
	        		 
	        		//@TODO maybe send an email to marketplace
	        	
	        	} catch (Exception $e) {
	        	
	        		Logger::AddLog($e->getMessage(),4);
	        	
	        	}
        	}*/
        	
        }
	}
	
	
	
	
}