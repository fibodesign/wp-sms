<?php

class forbiz extends WP_SMS {
	private $wsdl_link="http://forbiz.ir/api/wsdl";
	public $tariff="http://forbiz.ir";
	public $unitrial=true;
	public $unit;
	public $flash="disable";
	public $isflash=false;

	public function __construct() {
		parent::__construct();
		$this->validateNumber="09xxxxxxxxx";
		ini_set("soap.wsdl_cache_enabled", "0");
	}

	public function SendSMS() {
		if(is_wp_error($this->GetCredit()))
			return new WP_Error('account-credit', __('Your account does not credit for sending sms.', 'wp-sms'));


		$this->from=apply_filters('wp_sms_from', $this->from);
		$this->to=apply_filters('wp_sms_to', $this->to);
		$this->msg=apply_filters('wp_sms_msg', $this->msg);
		try {
			$client=new SoapClient($this->wsdl_link);
			$result=$client->smsSendFast([
					'keyPublic'=>$this->username,
					'keyPrivate'=>$this->password,
					'from'=>$this->from,
					'to'=>implode('-', $this->to),
					'message'=>$this->msg,
					'time'=>''
			]);
			if(!$result->error) {
				$this->InsertToDB($this->from, $this->msg, $this->to);
				do_action('wp_sms_send', $result->result);
				return $result->result;
			}
			else return new WP_Error('send-sms', $result->errorText);
		} catch(SoapFault $ex) {
			return new WP_Error('send-sms', $ex->faultstring);
		}
	}

	public function GetCredit() {
		if(!$this->username && !$this->password) {
			return new WP_Error('account-credit', __('Username/Password does not set for this gateway', 'wp-sms'));
		}
		if(!class_exists('SoapClient')) {
			return new WP_Error('required-class', __('Class SoapClient not found. please enable php_soap in your php.', 'wp-sms'));
		}
		try {
			$client=new SoapClient($this->wsdl_link);
			$res=$client->UserCredit([
					"keyPublic"=>$this->username,
					"keyPrivate"=>$this->password
			]);
			if($res->success) {
				return $res->credit;
			}
			else {
				return new WP_Error('account-credit', 'اطلاعات پنل فوربیز صحیح نمی باشد!');
			}
		} catch(SoapFault $ex) {
			return new WP_Error('account-credit', $ex->faultstring);
		}
	}
}
