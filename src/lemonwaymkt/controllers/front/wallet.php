<?php
require_once _PS_MODULE_DIR_ . 'marketplace/classes/MarketplaceCustomer.php';
require_once _PS_MODULE_DIR_ . 'lemonway/classes/Wallet.php';
require_once _PS_MODULE_DIR_ . 'lemonway/classes/Iban.php';
require_once _PS_MODULE_DIR_ . 'lemonway/classes/MoneyOut.php';
require_once _PS_MODULE_DIR_ . 'lemonway/lemonway.php';

class LemonwaymktWalletModuleFrontController extends ModuleFrontController
{
	public $auth = true;
	//public $php_self = 'lemonwaymkt_wallet';
	public $ssl = true;
	
	protected $wallet;
	
	/**
	 * Do whatever you have to before redirecting the customer on the website of your payment processor.
	 */
	public function postProcess()
	{
		/* @var $this->module Lemonwaymkt */
		
		$has_wallet = false;
		
		$wallet = new WalletCore();
		if ($walletExist = $wallet->getByCustomerId($this->context->customer->id)) {
			$wallet = $walletExist;
			$this->context->smarty->assign('wallet',$wallet);
			$has_wallet = true;
		}
		
		if (Tools::isSubmit('createWalletSubmit') && !$has_wallet) {
			
			$customer = $this->context->customer;
			$mkt_customer_obj = new MarketplaceCustomer();
			$mkt_customer = $mkt_customer_obj->findMarketPlaceCustomer($customer->id);
			if (!$mkt_customer ||( is_array($mkt_customer) && !$mkt_customer['is_seller'])) {
				$this->errors[] = $this->module->l('You are not a vendor or your account is not active!');
			} else {
				try {	
					$wallet = $this->module->registerWallet($customer,$mkt_customer['marketplace_seller_id']);
					if ($wallet) {
						$this->context->smarty->assign('wallet',$wallet);
						$has_wallet = true;
					}
				} catch (Exception $e) {
					$this->errors[] = $e->getMessage();
				}
			}
		} elseif (Tools::isSubmit('uploadDocSubmit') && $has_wallet) {
			$file = Tools::fileAttachment();
			if(!$file) {
				$m = $this->module;
				$uploadErrors = array(
						0 => $m->l('There is no error, the file uploaded with success'),
						1 => $m->l('The uploaded file exceeds the upload_max_filesize directive in php.ini'),
						2 => $m->l('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'),
						3 => $m->l('The uploaded file was only partially uploaded'),
						4 => $m->l('No file was uploaded'),
						6 => $m->l('Missing a temporary folder'),
						7 => $m->l('Failed to write file to disk.'),
						8 => $m->l('A PHP extension stopped the file upload.'),
				);
				$errorId = (int)$_FILES['fileUpload']['error'];
				$this->errors[] = $uploadErrors[$errorId];
			} else {
				if (!Tools::isSubmit('file_type')) {
					$this->errors[] = $this->module->l('You must to choose a file type!');
				} else {
					$params = array(
							'wallet'=>$wallet->id_lw_wallet,
							'fileName'=>$file['name'],
							'type'=>Tools::getValue('file_type'),
							'buffer'=>base64_encode($file['content']),
					);
					
					$kit = new LemonWayKit();
					try {
						
						$res = $kit->UploadFile($params);
						
						if(isset($res->lwError)){
							$this->errors[] = $res->lwError->MSG;
						}
						else{
							
							$success = $this->module->l('File Upload success');
							$this->context->smarty->assign('success',$success);
							
						}

					} catch (Exception $e) {
						$this->errors[] = $e->getMessage();
					}

				}
			
			}
			
			
		}
		 else {
		 	
			$_POST = array_map('stripslashes', $wallet->getFieldsWithoutValidation());
		}
		
		if($has_wallet)
		{
			//get variables datas
			try {
				$lemonway = Module::getInstanceByName('lemonway');
				$res = $lemonway->getwalletDetails($wallet->id_lw_wallet);
				
				if(isset($res->lwError))
				{
					$this->errors[] = Tools::displayError($this->module->l('Get last update failed'));
					$this->errors[] = Tools::displayError($res->lwError->MSG);
				}
				else{
					$this->context->smarty->assign('bal', (float)$res->wallet->BAL);
					$statues = Lemonway::$statuesLabel;
					$statusLbl = isset($statues[trim($res->wallet->STATUS)]) ?  $statues[trim($res->wallet->STATUS)] : "N/A";
					$this->context->smarty->assign('status', $statusLbl);
					$badge_status = '-success';
					switch ((int)$res->wallet->STATUS){
						case 1:
						case 4:
							$badge_status = '-warning';
							break;
						case 3:
						case 5:
							$badge_status = '-danger';
							break;
					}
					$this->context->smarty->assign('badge_status', $badge_status);
				}
				
				
			} catch (Exception $e) {
				$this->errors[] = $e->getMessage();
			}
		}
		$this->context->smarty->assign(array(
				'max_upload_size' 	=> (int)Tools::getMaxUploadSize(4096*1000),
				'has_wallet'		=> $has_wallet,
				'ibans'				=> IbanCore::getCustomerIbans($this->context->customer->id),
				'moneyouts'			=> MoneyOut::getCustomerMoneyout($this->context->customer->id,false,10),
		));
		
	}
	
	
	
	public function initContent()
	{
		$link = new Link();
		if (isset($this->context->customer->id))
		{
			$id_customer = $this->context->customer->id;
			parent::initContent();
	
			$mp_customer = new MarketplaceCustomer();
			$mp_customer_info = $mp_customer->findMarketPlaceCustomer($id_customer);
	
			if ($mp_customer_info)
			{
				$is_seller = $mp_customer_info['is_seller'];
				if ($is_seller == 1)
				{
					$this->context->smarty->assign('logic', 'lemonwaymkt_wallet');
					$this->context->smarty->assign("title_bg_color", Configuration::get('MP_TITLE_BG_COLOR'));
					$this->context->smarty->assign("title_text_color", Configuration::get('MP_TITLE_TEXT_COLOR'));
	
					
					$this->setTemplate('wallet.tpl');
				}
				else
					Tools::redirect($link->getPageLink('my-account'));
			}
			else
				Tools::redirect($link->getPageLink('my-account'));
		}
		else
			Tools::redirect($link->getPageLink('my-account'));
	}
	
	public function setMedia()
	{
		parent::setMedia();
		
		$this->addCSS(_MODULE_DIR_.'marketplace/views/css/marketplace_account.css');
	}
	
}