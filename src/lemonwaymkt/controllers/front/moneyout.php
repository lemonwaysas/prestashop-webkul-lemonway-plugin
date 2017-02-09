<?php

require_once _PS_MODULE_DIR_.'lemonway/classes/MoneyOut.php';
require_once _PS_MODULE_DIR_.'lemonway/classes/Wallet.php';
require_once _PS_MODULE_DIR_.'lemonway/classes/Iban.php';
require_once _PS_MODULE_DIR_.'lemonway/services/LemonWayKit.php';
class LemonwaymktMoneyoutModuleFrontController extends ModuleFrontController
{
	public $auth = true;
	//public $php_self = 'iban';
	//public $authRedirection = 'wallet';
	public $ssl = true;
	
	protected $moneyout;
	protected $display = 'add';
	
	/**
	 * Do whatever you have to before redirecting the customer on the website of your payment processor.
	 */
	public function postProcess()
	{
		$link = new Link();
		
		/* @var $this->module Lemonwaymkt */
		
		$this->moneyout = new Moneyout();

		$ibans = array();
		$wallet = new WalletCore();
		$bal = 0;
		if($walletExist = $wallet->getByCustomerId($this->context->customer->id))
		{
			$wallet = $walletExist;		
			
			//check if customer have ibans
			$ibans = IbanCore::getCustomerIbans($this->context->customer->id);
			
			if(!count($ibans))
			{
				Tools::redirect($link->getPageLink('my-account'));
				return;
			}
			
			$this->context->smarty->assign('ibans', $ibans);
			
			try {
				$lemonway = $this->module->getBaseModule();
				$res = $lemonway->getwalletDetails($wallet->id_lw_wallet);
			
				if(isset($res->lwError))
				{
					$this->errors[] = Tools::displayError($this->module->l('Get last update failed'));
					$this->errors[] = Tools::displayError($res->lwError->MSG);
				}
				else{
					$bal = (float)$res->wallet->BAL;
					$this->context->smarty->assign('bal', $bal);
					$statues = Lemonway::$statuesLabel;
					$statusLbl = isset($statues[trim($res->wallet->STATUS)]) ?  $statues[trim($res->wallet->STATUS)] : "N/A";
					$this->context->smarty->assign('status', $statusLbl);
				}
			
			
			} catch (Exception $e) {
				$this->errors[] = displayError($e->getMessage());
			}
			
		}
		else{
			$this->errors[] = $this->module->l('You must to create a wallet before!');
		}
		
		if($this->errors && !Tools::isSubmit('addMoneyoutSubmit')){
			Tools::redirect($link->getPageLink('my-account'));
		}

		

		if(Tools::isSubmit('addMoneyoutSubmit')){
			
			if($this->errors){return ;}
			
			$data = Tools::getValue('moneyout_data');
			
			$id_iban = isset($data['id_iban']) ? $data['id_iban'] : 0;
			
			if(!$id_iban){
				$this->errors[] = $this->module->l('Please select an Iban');
				return;
			}
			
			//check if iban exist and if owner is the current vendor
			$iban = new IbanCore($id_iban);
			if(!Validate::isLoadedObject($iban)){
				$this->errors[] = $this->module->l('Iban not found');
				return;
			}
			elseif ($iban->id_customer != $this->context->customer->id){
			
				$this->errors[] = $this->module->l('It\'s not your Iban!');
				return;
			}
			
			$data['id_customer'] 	= $this->context->customer->id;
			$data['id_lw_wallet'] 	= $wallet->id_lw_wallet;
			$data['is_admin'] 		= false;	
			$data['id_lw_iban']	 	= $iban->id_lw_iban;
			$data['iban']	 		= $iban->iban;
			$data['prev_bal']	 	= $bal;
			$data['new_bal']	 	= number_format((float)0, 2, '.', ''); //Tmp value
			$data['amount_to_pay']	= number_format((float)str_replace(",", ".", $data['amount_to_pay']), 2, '.', '');
			
			//check if amount to pay is less than current wallet balance
			//die("to pay: ".$data['amount_to_pay']. " bal: ".$bal);
			if($data['amount_to_pay'] > $bal)
			{
				$this->error[] = $this->module->l('Your current balance is less than your amount to transfert !');
				return;
			}
			
			$this->moneyout->hydrate($data);
					
			$this->errors = $this->moneyout->validateController();
			
			if($this->errors){return ;}
			
			//Retrieve Shop nam for money out message
			$shop_name = 'N/A';
			$seller_info_obj = new SellerInfoDetail();
			$seller = $seller_info_obj->getMarketPlaceSellerIdByCustomerId($this->context->customer->id);
			if($seller){
				$seller_info = new SellerInfoDetail($seller['marketplace_seller_id']);
				if($seller_info){
					
				}$shop_name = $seller_info->shop_name;
			}
			
			try {
				$params = array(
						"wallet" => $this->moneyout->id_lw_wallet,
						"amountTot" => $this->moneyout->amount_to_pay,
						'amountCom' => number_format((float)Configuration::get('LEMONWAY_COMMISSION_AMOUNT'), 2, '.', ''),
						"message" => Configuration::get('PS_SHOP_NAME') . " - " . sprintf($this->module->l("Moneyout of %s %s initiated by %s"),
                                                                                                        $this->moneyout->amount_to_pay,
																										$this->context->currency->sign,
																										$shop_name
						),
						"ibanId" => $this->moneyout->id_lw_iban,
						"autCommission" => Configuration::get('LEMONWAY_IS_AUTO_COMMISSION'),
				);


				//Init APi kit
				$kit = new LemonWayKit();
				$apiResponse = $kit->MoneyOut($params);
			
				if($apiResponse->lwError)
				{
					$this->errors[] = $apiResponse->lwError->MSG;
					return ;
				}
			
				if(count($apiResponse->operations))
				{
					/* @var $op Operation */
					$op = current($apiResponse->operations);
					if($op->ID)
					{
						$this->moneyout->new_bal = (float)$bal - (float)$this->moneyout->amount_to_pay;
						$this->moneyout->save();
						
						$this->context->smarty->assign('success',1);
					}
					else {
						$this->errors[] = $this->module->l("An error occurred. Please contact support.");
					}
				}
			
			} catch (Exception $e) {
				 
				$this->errors[] = $e->getMessage();
			}

			
			
		}
		
		

		
	}
	
	public function process(){
		
		parent::process();
		
		if(Tools::isSubmit("add"))
			$this->display = 'add';
		elseif(Tools::isSubmit("edit"))
			$this->display = 'edit';
		elseif(Tools::isSubmit("view"))
			$this->display = 'view';
		else 
			$this->display = 'list';
	}
	
	
	public function initContent()
	{
		
		$link = new Link();
		if (isset($this->context->customer->id))
		{
			$id_customer = $this->context->customer->id;
			parent::initContent();
	
			switch ($this->display){
				case "edit":
				case "add":
					$this->context->smarty->assign('moneyout', $this->moneyout);
					$this->setTemplate('moneyout/form.tpl');
					
					break;
				case "view":
					break;
				default:
					Tools::redirect($link->getPageLink('my-account'));
					break;
			}
		}
		else
			Tools::redirect($link->getPageLink('my-account'));
		
		$this->context->smarty->assign('logic', 'lemonwaymkt_wallet');
		$this->context->smarty->assign("title_bg_color", Configuration::get('MP_TITLE_BG_COLOR'));
		$this->context->smarty->assign("title_text_color", Configuration::get('MP_TITLE_TEXT_COLOR'));
	}
	
	public function setMedia()
	{
		parent::setMedia();
		
		$this->addCSS(_MODULE_DIR_.'marketplace/views/css/marketplace_account.css');
	}
	
}