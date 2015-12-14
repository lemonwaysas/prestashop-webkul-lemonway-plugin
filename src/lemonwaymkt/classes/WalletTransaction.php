<?php
class WalletTransaction extends ObjectModel{
	
	public static $statuesTransaction = array(
			0=> 'To pay',
			1=> 'Paid',
			2=> 'pending schedule'
	);
	
	const STATUS_TO_PAY = 0;
	const STATUS_PAID = 1;
	const STATUS_PENDING_SCHEDULE = 2;
	
	public $id_order;
	public $id_customer;
	public $seller_id;
	public $shop_name;
	public $amount_total;
	public $amount_to_pay;
	public $admin_commission;
	public $lw_commission;
	public $status;
	public $lw_id_send_payment;
	public $debit_wallet;
	public $credit_wallet;
	public $date_add;
	public $date_upd;
	
	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = array(
			'table' => 'lemonwaymkt_wallet_transaction',
			'primary' => 'id_transaction',
			'multilang' => false,
			'multilang_shop' => false,
			'fields' => array(
					'id_order' 			=>    array('type' => self::TYPE_INT,'required'=>true),
					'id_customer' 		=>    array('type' => self::TYPE_INT,'required'=>true),
					'seller_id' 		=>    array('type' => self::TYPE_INT,'required'=>true),
					'shop_name' 		=>    array('type' => self::TYPE_STRING,'validate' => 'isGenericName','required'=>true),
					'amount_total' 		=>    array('type' => self::TYPE_FLOAT,'validate' => 'isPrice','required'=>true),
					'amount_to_pay' 	=>    array('type' => self::TYPE_FLOAT,'validate' => 'isPrice','required'=>true),
					'admin_commission' 	=>    array('type' => self::TYPE_FLOAT,'validate' => 'isPrice','required'=>true),
					'lw_commission' 	=>    array('type' => self::TYPE_FLOAT,'validate' => 'isPrice','required'=>false),
					'status' 			=>    array('type' => self::TYPE_INT,'required'=>true),
					'lw_id_send_payment'=>    array('type' => self::TYPE_STRING,'required'=>false),
					'debit_wallet'		=>    array('type' => self::TYPE_STRING, 'validate' => 'isGenericName','required'=>true),
					'credit_wallet' 	=>    array('type' => self::TYPE_STRING, 'validate' => 'isGenericName','required'=>true),

					'date_add' 			=>    array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
					'date_upd' 			=>	  array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
	
			),
	);
	
	public function getByOrderForCustomer($id_order,$id_customer){
	
		$query = 'SELECT * FROM `'._DB_PREFIX_.'lemonwaymkt_wallet_transaction` lw WHERE lw.`id_order` = '.(int)$id_order.' AND lw.`id_customer` = '.(int)$id_customer;
		$result = Db::getInstance()->getRow($query);
	
		if (!$result) {
			return false;
		}
	
		$this->id = $result['id_transaction'];
		foreach ($result as $key => $value) {
			if (property_exists($this, $key)) {
				$this->{$key} = $value;
			}
		}
		return $this;
	
	}
	
	public static function getStatusLabel($status){
		return self::$statuesTransaction[$status];
		
	}
	
	
}