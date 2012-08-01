<?php
/*

Paczkomaty InPost osCommerce Module
Revision 2.0.0

Copyright (c) 2012 InPost Sp. z o.o.

*/
class Paczkomaty_ext {

	protected $inpost_api_url;
	protected $password;
	protected $email;
	
	public function __construct() {
		$this->inpost_api_url = str_replace('http://', 'https://', constant('MODULE_SHIPPING_PACZKOMATY_API_URL'));
		$this->password = constant('MODULE_SHIPPING_PACZKOMATY_PASSWORD');
		$this->email = constant('MODULE_SHIPPING_PACZKOMATY_EMAIL');
	}
	
	public function create_label($pack_id) {
    	$packs_query = tep_db_query("select packcode from ".TABLE_EXTERNAL_PACZKOMATY_INPOST." where id = ".$pack_id.";");
    	$pack = tep_db_fetch_array($packs_query);
		
    	if ($pack) {
			$pdf = $this->inpost_get_sticker($pack['packcode']);
			if (is_array($pdf) && array_key_exists('error', $pdf))
				return $pdf;
			
			if (!$pack['label_printed'])
				tep_db_query("update ".TABLE_EXTERNAL_PACZKOMATY_INPOST." set label_printed = now() where id = ".$pack_id.";");
			
			$this->download_pdf($pdf, $pack['packcode'], 'sticker');
			exit;
    	}
	}
	
	public function download_pdf($pdf, $packcode, $prefix) {
		header('Content-type', 'application/pdf');
		header('Content-Disposition: attachment; filename='.$prefix.'_'.$packcode.'.pdf');
		print_r($pdf);
	}
	
	public function get_status($packcode) {
		$pack_status = $this->inpost_get_pack_status($packcode);
		tep_db_query("update ".TABLE_EXTERNAL_PACZKOMATY_INPOST." set pack_status = '".$pack_status."' where packcode = '".$packcode."';");
		return $pack_status;
	}
	
	public function remove_pack($pack_id, $cancel_pack) {
		$pack = $this->get_pack($pack_id);
		if ($pack) {			
			tep_db_query("delete from " . TABLE_EXTERNAL_PACZKOMATY_INPOST . " where id = '".(int)$pack_id."'");
			if ($cancel_pack)
				$this->inpost_cancel_pack($pack['packcode']);
		}
	}
	
	public function confirm_pack($pack_id, $print_test) {
		$pack = $this->get_pack($pack_id);
		($print_test)? $print_test = true : $print_test = false;
		
		if ($pack) {
			$pdf = $this->inpost_get_confirm_printout(array($pack['packcode']), $print_test);
			if (!$pdf || is_array($pdf) && array_key_exists('error', $pdf))
				return $pdf;
			
			$this->download_pdf($pdf, $pack['packcode'], 'confirm');
		}
	}
	
	private function get_pack($pack_id) {
		$pack_query = tep_db_query("select packcode from ".TABLE_EXTERNAL_PACZKOMATY_INPOST." where id =".$pack_id.";");
		$pack = tep_db_fetch_array($pack_query);
		return $pack;
	}
	
	private function inpost_get_confirm_printout($packCodes, $testPrintout=0) {
		
		$digest = inpost_digest($this->password);
		
		if (is_array($packCodes)) {
			$_lastArgSeparatorOutput = ini_get('arg_separator.output');
			ini_set('arg_separator.output', '&');
	
			$packsXML = "<paczkomaty>\n";
			$packsXML .= "<testprintout>$testPrintout</testprintout>\n";
			foreach ($packCodes as $packCode) {
				$packsXML .= "<pack>\n";
				$packsXML .= "<packcode>" . $packCode . "</packcode>\n";
				$packsXML .= "</pack>\n";
			}
			$packsXML .= "</paczkomaty>\n";
	
			$packsData = array('email' => $this->email, 'digest' => $digest, 'content' => $packsXML);
			$postData = http_build_query($packsData);
	
			if ($customerResponse = inpost_post_request("$this->inpost_api_url/?do=getconfirmprintout", $postData)) {
				if (strpos($customerResponse, 'PDF'))
					return $customerResponse;
				$parsedXML = inpost_xml2array($customerResponse);
				if (isset($parsedXML['paczkomaty']['error'])) {
					ini_set('arg_separator.output', $_lastArgSeparatorOutput);
					return array('error' => array('key' => $parsedXML['paczkomaty']['error']['attr']['key'],
							'message' => $parsedXML['paczkomaty']['error']['value']));
				}
			}
	
			ini_set('arg_separator.output', $_lastArgSeparatorOutput);
		}
		return 0;
	}
	
	private function inpost_cancel_pack($packCode) {
	
		$digest = inpost_digest($this->password);
	
		if (isset($packCode)) {
			$_lastArgSeparatorOutput = ini_get('arg_separator.output');
			ini_set('arg_separator.output', '&');
	
			$customerData = array('email' => $this->email, 'digest' => $digest, 'packcode' => $packCode);
			$postData = http_build_query($customerData);
	
	
			if ($customerResponse = inpost_post_request("$this->inpost_api_url/?do=cancelpack", $postData)) {
	
				$parsedXML = inpost_xml2array($customerResponse);
				if (isset($parsedXML['paczkomaty']['error'])) {
					return array('error' => array('key' => $parsedXML['paczkomaty']['error']['attr']['key'],
							'message' => $parsedXML['paczkomaty']['error']['value']));
				}
				else
					return $customerResponse;
			}
	
			ini_set('arg_separator.output', $_lastArgSeparatorOutput);
		}
		return 0;
	}
	
	private function inpost_get_pack_status($packcode) {
	
		if ($statusContents = @file_get_contents("$this->inpost_api_url/?do=getpackstatus&packcode=$packcode")) {
			$parsedXML = inpost_xml2array($statusContents);
			if (isset($parsedXML['paczkomaty']['error'])) {
				return array('error' => array('key' => $parsedXML['paczkomaty']['error']['attr']['key'],
						'message' => $parsedXML['paczkomaty']['error']['value']));
			}
			$parsedXML = $parsedXML['paczkomaty'];
			$packStatus = $parsedXML['status']['value'];
			return $packStatus;
		}
		return 0;
	}
	
	private function inpost_get_sticker($packCode, $labelType='') {

		$digest = inpost_digest($this->password);
	
		if (isset($packCode)) {
			$_lastArgSeparatorOutput = ini_get('arg_separator.output');
			ini_set('arg_separator.output', '&');
	
			$customerData = array('email' => $this->email, 'digest' => $digest, 'packcode' => $packCode, 'labeltype' => $labelType);
			$postData = http_build_query($customerData);
			if ($customerResponse = inpost_post_request("$this->inpost_api_url/?do=getsticker", $postData)) {
				if (strpos($customerResponse, 'PDF'))
					return $customerResponse;
				$parsedXML = inpost_xml2array($customerResponse);
				if (isset($parsedXML['paczkomaty']['error'])) {
					return array('error' => array('key' => $parsedXML['paczkomaty']['error']['attr']['key'],
							'message' => $parsedXML['paczkomaty']['error']['value']));
				}
			}
	
			ini_set('arg_separator.output', $_lastArgSeparatorOutput);
		}
		return 0;
	}
	
}