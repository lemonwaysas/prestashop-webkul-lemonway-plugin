<?php

require_once _PS_MODULE_DIR_.'lemonway/classes/Iban.php';
require_once _PS_MODULE_DIR_.'lemonway/classes/Wallet.php';
class LemonwaymktIbanModuleFrontController extends ModuleFrontController
{
	public $auth = true;
	//public $php_self = 'iban';
	//public $authRedirection = 'wallet';
	public $ssl = true;
	
	protected $iban;
	protected $display = 'add';
	
	/**
	 * Do whatever you have to before redirecting the customer on the website of your payment processor.
	 */
	public function postProcess()
	{
		/* @var $this->module Lemonwaymkt */
		
		$this->iban = new IbanCore();
		

		if(Tools::isSubmit('addIbanSubmit')){
			
			$customer = $this->context->customer;
			$wallet = new WalletCore();
			
			if($wallet->getByCustomerId($customer->id)){
				
					$data = Tools::getValue('iban_data');
					$data['id_customer'] = $customer->id;
					$data['id_wallet'] = $wallet->id_lw_wallet;
					
					$this->iban->hydrate($data);
					
					$this->errors = $this->iban->validateController();
					
					if($this->errors)
						return;
				
					try {
							
						$params = array(
								'wallet'	=>	$wallet->id_lw_wallet,
								'holder'	=>  $this->iban->holder,
								'iban'		=>	$this->iban->iban,
								'bic'		=>	$this->iban->bic,
								'dom1'		=>  $this->iban->dom1,
								'dom2'		=>	$this->iban->dom2,
						);
						
						$kit = new LemonWayKit();
						
						$res = $kit->RegisterIBAN($params);
						
						if(isset($res->lwError)){
							$this->errors[] =  Tools::displayError($res->lwError->MSG);
							return ;
						}
						
						$this->iban->id_lw_iban =  $res->iban->ID;
						$this->iban->id_status = $res->iban->S;
						
						$this->iban->save();
						
						$this->context->smarty->assign('success',1);
						
					} catch (Exception $e) {
						
						$this->errors[] = $e->getMessage();
						
					}
			}
			else{
				$this->errors[] = $this->module->l('You must to create a wallet before!');
			}

			
			
		}
		/* else {
		 	
			$_POST = array_map('stripslashes', $this->iban->getFieldsWithoutValidation());
		}*/
		
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
					$this->context->smarty->assign('iban', $this->iban);
					$this->setTemplate('iban/form.tpl');
					
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