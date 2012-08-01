<?php
/*

Paczkomaty InPost osCommerce Module
Revision 2.0.0

Copyright (c) 2012 InPost Sp. z o.o.

*/
class paczkomaty {

	var $code, $title, $description, $enabled, $num_zones;

	function paczkomaty() {
		global $order, $total_weight;

		$this->code = 'paczkomaty';
		$this->title = MODULE_SHIPPING_PACZKOMATY_TEXT_TITLE;
		$this->description = MODULE_SHIPPING_PACZKOMATY_TEXT_DESCRIPTION;
		$this->sort_order = MODULE_SHIPPING_PACZKOMATY_SORT_ORDER;
		$this->icon = 'http://media.paczkomaty.pl/pieczatka.gif'; 
		$this->tax_class = MODULE_SHIPPING_PACZKOMATY_TAX_CLASS;
		$this->enabled = ((MODULE_SHIPPING_PACZKOMATY_STATUS == 'True') ? true : false);
		$this->num_zones = 1;
	}

	function get_customer() {

		global $customer_id, $sendto;

		$account_query = tep_db_query("select customers_email_address, customers_telephone from " . TABLE_CUSTOMERS . " where customers_id = '" . (int)$customer_id . "'");
		$account = tep_db_fetch_array($account_query);
		$customer['email'] = $account['customers_email_address'];
		$customer['phone'] = $account['customers_telephone'];

		$account_query = tep_db_query("select entry_postcode from " . TABLE_ADDRESS_BOOK . " where address_book_id = '" . (int)$sendto . "'");
		$account = tep_db_fetch_array($account_query);
		$customer['postcode'] = $account['entry_postcode'];

		return $customer;
	}

	function get_paczkomaty_machines() {
		require_once DIR_WS_FUNCTIONS.'inpost_functions.php';
		
		$inpost_api_url = constant('MODULE_SHIPPING_PACZKOMATY_API_URL');

		if ($machinesContents = @file_get_contents("$inpost_api_url/?do=listmachines_xml")) {
			$parsedXML = inpost_xml2array($machinesContents);
			if (!isset($parsedXML['paczkomaty']['machine']))
				return 0;
			$machines = $parsedXML['paczkomaty']['machine'];
			return $machines;
		}
		return 0;
	}
	
	function create_packs($shipping, $payment, $order_total, $order_id) {
		$customer = $this->get_customer();
		
		$packsData = array();
		$packsData[0] = array(
			'adreseeEmail' => $customer['email'],
			'senderEmail' => constant('MODULE_SHIPPING_PACZKOMATY_EMAIL'),
			'phoneNum' => $customer['phone'],
			'boxMachineName' => $shipping['paczkomat'],
			'packType' => constant('MODULE_SHIPPING_PACZKOMATY_PACKTYPE'),
			'onDeliveryAmount' => ($payment == 'cod')? $order_total : '',
			'customerRef' => MODULE_SHIPPING_PACZKOMATY_TEXT_ORDER_NUMBER.$order_id,
			'senderAddress' => array(
				'name' => constant('STORE_NAME'),
				'surName' => constant('STORE_OWNER'),
				'email' => constant('STORE_OWNER_EMAIL_ADDRESS')
			),
		);
		
		if (constant('MODULE_SHIPPING_PACZKOMATY_CUSTOMER_DELIVERING'))
			$packsData[0]['senderBoxMachineName'] = constant('MODULE_SHIPPING_PACZKOMATY_CUSTOMER_DELIVERING');
				
		$packs = $this->inpost_send_packs(constant('MODULE_SHIPPING_PACZKOMATY_EMAIL'), constant('MODULE_SHIPPING_PACZKOMATY_PASSWORD'), $packsData);
		foreach ($packs as $pack)
			tep_db_query("insert into " . TABLE_EXTERNAL_PACZKOMATY_INPOST . " (order_id, packcode, customerdeliveringcode, label_printed, date_added) values ('".$order_id."', '".$pack['packcode']."', '".$pack['customerdeliveringcode']."', null, now())");
	}

	function find_paczkomaty_customer($email) {
		$inpost_api_url = constant('MODULE_SHIPPING_PACZKOMATY_API_URL');

		if ($machinesContents = @file_get_contents("$inpost_api_url/?do=findcustomer_csv&email=$email")) {
			if ($machinesContents=='Error') return 0;      
			$machine = explode(";",$machinesContents);
			return $machine;
		}
		
		return 0;
	}
	
	function inpost_send_packs($email, $password, $packsData, $autoLabels=0, $selfSend=0) {
		require_once DIR_WS_FUNCTIONS.'inpost_functions.php';
		
		$inpost_api_url = constant('MODULE_SHIPPING_PACZKOMATY_API_URL');
		$inpost_api_url = str_replace('http://', 'https://', $inpost_api_url);
	
		$digest = inpost_digest($password);

		if (count($packsData)) {
			$packsXML = "<paczkomaty>\n";
			$packsXML .= "<autoLabels>$autoLabels</autoLabels>\n";
			$packsXML .= "<selfSend>$selfSend</selfSend>\n";
			foreach ($packsData as $packId => $packData) {
				$packsXML .= "<pack>\n";
				$packsXML .= "<id>" . $packId . "</id>\n";
				$packsXML .= "<adreseeEmail>" . $packData['adreseeEmail'] . "</adreseeEmail>\n";
				$packsXML .= "<senderEmail>" . $packData['senderEmail'] . "</senderEmail>\n";
				$packsXML .= "<phoneNum>" . $packData['phoneNum'] . "</phoneNum>\n";
				$packsXML .= "<boxMachineName>" . $packData['boxMachineName'] . "</boxMachineName>\n";
				if (array_key_exists('alternativeBoxMachineName', $packData))
					$packsXML .= "<alternativeBoxMachineName>" . $packData['alternativeBoxMachineName'] . "</alternativeBoxMachineName>\n";
				$packsXML .= "<packType>" . $packData['packType'] . "</packType>\n";
				if (array_key_exists('customerDelivering', $packData))
					$packsXML .= "<customerDelivering>" . $packData['customerDelivering'] . "</customerDelivering>\n";
				else
					$packsXML .= "<customerDelivering>false</customerDelivering>\n";
				$packsXML .= "<insuranceAmount>" . $packData['insuranceAmount'] . "</insuranceAmount>\n";
				$packsXML .= "<onDeliveryAmount>" . $packData['onDeliveryAmount'] . "</onDeliveryAmount>\n";
				if (array_key_exists('customerRef', $packData))
					$packsXML .= "<customerRef>" . $packData['customerRef'] . "</customerRef>\n";
				if (array_key_exists('senderBoxMachineName', $packData))
					$packsXML .= "<senderBoxMachineName>" . $packData['senderBoxMachineName'] . "</senderBoxMachineName>\n";
				if (array_key_exists('senderAddress', $packData) and !empty($packData['senderAddress'])) {
					$packsXML .= "<senderAddress>\n";
					$tmpFieldsArray = array('name', 'surName', 'email', 'phoneNum', 'street', 'buildingNo', 'flatNo', 'town', 'zipCode', 'province');
					foreach ($tmpFieldsArray as $tmpField) {
						if (array_key_exists($tmpField, $packData['senderAddress']) && !empty($packData['senderAddress'][$tmpField])) {
							$packsXML .= "<$tmpField>" . $packData['senderAddress'][$tmpField] . "</$tmpField>\n";
						}
					}
					$packsXML .= "</senderAddress>\n";
				}
				$packsXML .= "</pack>\n";
			}
			$packsXML .= "</paczkomaty>\n";
	
			$packsData = array('email' => $email, 'digest' => $digest, 'content' => $packsXML);
	
	
	
			$_lastArgSeparatorOutput = ini_get('arg_separator.output');
			ini_set('arg_separator.output', '&');
			$postData = http_build_query($packsData);
			if ($packsResponse = inpost_post_request("$inpost_api_url/?do=createdeliverypacks", $postData)) {
				$parsedXML = inpost_xml2array($packsResponse);
				if (isset($parsedXML['paczkomaty']['error'])) {
					return array('error' => array('key' => $parsedXML['paczkomaty']['error']['attr']['key'], 'message' => $parsedXML['paczkomaty']['error']['value']));
				}
	
	
				if (isset($parsedXML['paczkomaty']['pack']))
					$packsData = $parsedXML['paczkomaty']['pack'];
				if (!isset($packsData[0])) {
					$temp = $packsData;
					$packsData = array();
					$packsData[0] = $temp;
				}
				if (count($packsData)) {
					foreach ($packsData as $packData) {
						if (isset($packData['packcode']['value']))
							$resultData[$packData['id']['value']]['packcode'] = $packData['packcode']['value'];
						if (isset($packData['customerdeliveringcode']['value']))
							$resultData[$packData['id']['value']]['customerdeliveringcode'] = $packData['customerdeliveringcode']['value'];
						if (isset($packData['error']['attr']['key']))
							$resultData[$packData['id']['value']]['error_key'] = $packData['error']['attr']['key'];
						if (isset($packData['error']['value']))
							$resultData[$packData['id']['value']]['error_message'] = $packData['error']['value'];
					}
					if (isset($resultData))
						return $resultData;
					else
						return array();
				}
			}
			ini_set('arg_separator.output', $_lastArgSeparatorOutput);
		}
		return 0;
	}

	function quote($method = '') {
		
		global $order, $total_weight, $shipping_weight, $shipping_num_boxes, $customer_id, $sendto;
		
		$customer = $this->get_customer();
		
		$dest_country = $order->delivery['country']['iso_code_2'];
		$dest_zone = 0;
		$error = false;
		
		if ($order->delivery['country']['iso_code_2'] == 'PL')  {  // tylko na terenie Polski
			for ($i=1; $i<=$this->num_zones; $i++) {
				$countries_table = constant('MODULE_SHIPPING_PACZKOMATY_COUNTRIES_' . $i);
				$country_zones = explode("[,]", $countries_table);
				if (in_array($dest_country, $country_zones)) {
					$dest_zone = $i;
					break;
				}
			}
			if ($dest_zone == 0) {
				$this->quotes['error'] = MODULE_SHIPPING_ZONES_INVALID_ZONE;
				return $this->quotes;
			}
			$shipping_method = MODULE_SHIPPING_PACZKOMATY_TEXT_WAY . ' : ' . $shipping_weight . ' ' . MODULE_SHIPPING_PACZKOMATY_TEXT_UNITS . ' ' . MODULE_SHIPPING_PACZKOMATY_DELIVERY_TIMES;
			$shipping_cost = constant('MODULE_SHIPPING_PACZKOMATY_COST_' . $dest_zone)* $shipping_num_boxes;
		}
		
		if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title);
		
		if ($dest_zone == 0) {   // poza rejonem
			$this->quotes['error'] = MODULE_SHIPPING_ZONES_INVALID_ZONE;
			return $this->quotes;
		}
		
		if ($total_weight > 25) { // nie pokazuj dla przesylek > 25kg
			$this->quotes['error'] = MODULE_SHIPPING_PACZKOMATY_UNDEFINED_RATE;
			return $this->quotes;
		}
		
		$this->quotes = array(
			'id' => $this->code,
			'module' => MODULE_SHIPPING_PACZKOMATY_TEXT_TITLE
		);
		
		$machines = $this->get_paczkomaty_machines();
		
		if (!$machines) {
			$this->quotes['error'] = MODULE_SHIPPING_PACZKOMATY_TEXT_MACHINES_NOT_FOUND;
			return $this->quotes;
		}
		
		$default_machine = $this->find_paczkomaty_customer($customer['email']);

		$i = 0;
		if ($default_machine) {
			$this->quotes['methods'][] = array(
				'id' => $i,
				'title' => MODULE_SHIPPING_PACZKOMATY_TEXT_PACZKOMAT_DEFAULT.': '.$default_machine[0].', '.$default_machine[1],
				'cost' => $shipping_cost,
				'paczkomat' => $default_machine[0]
			);
			$i++;
		}
		
		if (count($machines)) {
			foreach ($machines as $machine) {
				$this->quotes['methods'][] = array(
					'id' => $i,
					'title' => MODULE_SHIPPING_PACZKOMATY_TEXT_PACZKOMAT.' '.$machine['name']['value'].', '.$machine['street']['value'].' '.$machine['buildingnumber']['value'].', '.$machine['town']['value'],
					'cost' => $shipping_cost,
					'paczkomat' => $machine['name']['value'],
					'payment_cod' => $machine['paymentavailable']['value'],
					'payment_point' => $machine['paymentavailable']['paymentpointdescr']
				);
				$i++;
			}
		}
		
		if ($this->tax_class > 0) {
			$this->quotes['tax'] = tep_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
		}
		
		if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title);
		return $this->quotes;
	}

	function check() {
		
		if (!isset($this->_check)) {
			$check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_PACZKOMATY_STATUS'");
			$this->_check = tep_db_num_rows($check_query);
		}
		return $this->_check;
	}

	function install() {

		tep_db_query("create table if not exists ".TABLE_EXTERNAL_PACZKOMATY_INPOST." (id int auto_increment, order_id int(11) not null, packcode varchar(64), customerdeliveringcode varchar(64), pack_status varchar(64), label_printed datetime null, date_added datetime, primary key (id));");
		
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Wlacz wysylke do Paczkomaty InPost', 'MODULE_SHIPPING_PACZKOMATY_STATUS', 'True', 'Czy chcesz dodac opcje wysylki do Paczkomaty InPost?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Adres URL do API', 'MODULE_SHIPPING_PACZKOMATY_API_URL', 'https://api.paczkomaty.pl', 'Adres URL do API Paczkomaty InPost', '6', '0', now())");	  
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sortowanie', 'MODULE_SHIPPING_PACZKOMATY_SORT_ORDER', '1', 'Sortowanie', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Paczkomat nadawczy (Nadawanie paczek bezpośrednio w Paczkomacie)', 'MODULE_SHIPPING_PACZKOMATY_CUSTOMER_DELIVERING', '', 'Wprowadź kod Paczkomatu, aby włączyć opcję nadawania w Paczkomacie (np. AND039) - <a href=\"http://www.paczkomaty.pl/znajdz_paczkomat,33.html\" target=\"_blank\">znajdź paczkomat</a>', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Email konta Paczkomaty InPost', 'MODULE_SHIPPING_PACZKOMATY_EMAIL', 'test@testowy.pl', 'Adres email konta zarejestrowanego w Paczkomaty InPost', '6', '0', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Hasło do konta dla Paczkomaty InPost', 'MODULE_SHIPPING_PACZKOMATY_PASSWORD', 'WqJevQy*X7', 'Hasło do konta zarejestrowanego w Paczkomaty InPost', '6', '0', now())");
		
		for ($i = 1; $i <= $this->num_zones; $i++) {
			$default_countries = '';
			if ($i == 1) {
				$default_countries = 'PL';
			}
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Dostepne kraje', 'MODULE_SHIPPING_PACZKOMATY_COUNTRIES_" . $i ."', '" . $default_countries . "', 'Wprowadz oznaczenie kraju dla ktorego usluga jest dostepna (Default: PL)', '6', '0', now())");
			tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Cena przesylki', 'MODULE_SHIPPING_PACZKOMATY_COST_" . $i ."', '6.99', 'Domyslny koszt wysylki', '6', '0', now())");   
		}
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Typ paczki', 'MODULE_SHIPPING_PACZKOMATY_PACKTYPE', 'A', 'Rozmiar paczki (Dostępne typy: A, B, C)', '6', '0', now())"); 
	}

	function remove() {
		
		tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
	}

	function keys() {
		
		$keys = array(
			'MODULE_SHIPPING_PACZKOMATY_STATUS', 
			'MODULE_SHIPPING_PACZKOMATY_API_URL',
			'MODULE_SHIPPING_PACZKOMATY_SORT_ORDER',
			'MODULE_SHIPPING_PACZKOMATY_EMAIL',
			'MODULE_SHIPPING_PACZKOMATY_PASSWORD',
			'MODULE_SHIPPING_PACZKOMATY_PACKTYPE',
			'MODULE_SHIPPING_PACZKOMATY_CUSTOMER_DELIVERING'
		);

		for ($i=1; $i<=$this->num_zones; $i++) {
			$keys[] = 'MODULE_SHIPPING_PACZKOMATY_COUNTRIES_' . $i;
			$keys[] = 'MODULE_SHIPPING_PACZKOMATY_COST_' . $i;
		}
		return $keys;
	}
	
	function oscomm_prepare_shipping($quote, $shipping, $free_shipping) {
		$paczkomaty = explode('_', $shipping['id']);
        $shipping = array(
              			'id' => $shipping['id'],
                    	'title' => (($free_shipping == true) ?  $quote[0]['methods'][$paczkomaty[1]]['title'] : $quote[0]['module'] . ' (' . $quote[0]['methods'][$paczkomaty[1]]['title'] . ')'),
                    	'cost' => $quote[0]['methods'][$paczkomaty[1]]['cost'],
              			'paczkomat' => $quote[0]['methods'][$paczkomaty[1]]['paczkomat'],
        				'payment_cod' => $quote[0]['methods'][$paczkomaty[1]]['payment_cod'],
						'payment_point' => $quote[0]['methods'][$paczkomaty[1]]['payment_point'],
              		);
        return $shipping;
	}
	
	function oscomm_paczkomaty_dropbox($quotes_paczkomaty, $shipping) {
		$dropbox = 
			'<select id="dropbox_paczkomaty" name="shipping_paczkomaty" onchange="changeCheckedValue(this);">
				<option label="'. MODULE_SHIPPING_PACZKOMATY_SELECT_PACZKOMAT .'">';
		for ($j=0, $n2=sizeof($quotes_paczkomaty['methods']); $j<$n2; $j++) {
			$selected = (($shipping['id'] == $quotes_paczkomaty['id'] . '_' . $quotes_paczkomaty['methods'][$j]['id'])? 'selected="selected"' : '');
			$dropbox .= '<option '.$selected.' value="'.$quotes_paczkomaty['id'].'_'.$quotes_paczkomaty['methods'][$j]['id'].'">'.$quotes_paczkomaty['methods'][$j]['title'];
		}
		$dropbox .= '</select>';
		return $dropbox;
	}
	
}
?>
