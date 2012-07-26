# Instalacja Modułu Paczkomaty InPost dla osCommerce 2.3

## Upload plików

Nowe pliki, które należy umieścić na serwerze w odpowiednich katalogach, zainstalowanego systemu osCommerce.

    /includes/functions/inpost_functions.php
    /includes/languages/polish/modules/shipping/paczkomaty.php
    /includes/modules/shipping/paczkomaty.php


## Edycja istniejących plików

Wprowadź zmiany opisane poniżej dla każdego z pliku. Numery lini są szacunkowe dla nieedytowanej wcześniej kopi pliku.

### Otwórz plik '/checkout_shipping.php'

Znajdź kod (linia 122):
```php
$shipping = array('id' => $shipping,
	'title' => (($free_shipping == true) ?  $quote[0]['methods'][0]['title'] : $quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')'),
	'cost' => $quote[0]['methods'][0]['cost']);
```
Dodaj po:
```php
// start paczkomaty
if (preg_match('/paczkomaty_/', $shipping['id'])) {
	$pa = new Paczkomaty();
	$shipping = $pa->oscomm_prepare_shipping($quote, $shipping, $free_shipping);
}
// end paczkomaty
```

Znadź kod (linia 274):
```php
} else {
          for ($j=0, $n2=sizeof($quotes[$i]['methods']); $j<$n2; $j++) {
// set the radio button to be checked if it is the method chosen
            $checked = (($quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'] == $shipping['id']) ? true : false);
```

Dodaj przed:
```php
// start paczkomaty
	} elseif ($quotes[$i]['module'] == MODULE_SHIPPING_PACZKOMATY_TEXT_TITLE) {
		$pa = new paczkomaty();
       	$checked = preg_match('/paczkomaty/', $shipping['id']);
		if ( ($checked) || ($n == 1 && $n2 == 1) ) {
			echo '<tr id="defaultSelected" class="moduleRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, '.$radio_buttons.')">';
   		} else {
   			echo '<tr class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, '.$radio_buttons.')">';
   		}
   		echo '<td width="75%" style="padding-left: 15px;">'.$pa->oscomm_paczkomaty_dropbox($quotes[$i], $shipping).'</td>';
   		echo '<td>'.$currencies->format(tep_add_tax($quotes[$i]['methods'][0]['cost'], (isset($quotes[$i]['tax']) ? $quotes[$i]['tax'] : 0))).'</td>';
		echo '<td align="right">'.tep_draw_radio_field('shipping', $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][0]['id'], $checked, 'id="shipping_paczkomaty"').
				'<script type="text/javascript">
					function changeCheckedValue(obj) {
						document.getElementById("shipping_paczkomaty").value = obj.options[obj.selectedIndex].value;
					}
					changeCheckedValue(document.getElementById("dropbox_paczkomaty"));
				</script>
	        	</td>';
		echo '</tr>';
// end paczkomaty */
```

### Otwórz plik '/checkout_confirmation.php'

Znajdź kod (linia 65):
```php
    require(DIR_WS_CLASSES . 'shipping.php');
  	$shipping_modules = new shipping($shipping);
```

Dodaj po:
```php
// start paczkomaty
	if (preg_match('/paczkomaty_/', $shipping['id'])) {
		if ($shipping['payment_cod'] == 'f' && $payment == 'cod')
			tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, 'error_message='.MODULE_SHIPPING_PACZKOMATY_ERROR_UNAVAILABLE_COD, 'SSL'));
	}
// end paczkomaty
```

### Otwórz plik '/checkout_process.php'

Znajdź kod (linia 286):
```php
        $payment_modules->after_process();

    	$cart->reset(true);
```

Dodaj po:
```php
// start paczkomaty
	if (preg_match('/paczkomaty_/', $shipping['id'])) {
		$pa = new Paczkomaty();
		$pa->create_packs($shipping, $payment, $order->info['total']);
	}
// end paczkomaty
```

### Otwórz plik '/includes/database_tables.php'

Dodaj wpis:
```php
// start paczkomaty
	define('TABLE_EXTERNAL_PACZKOMATY_INPOST', 'external_paczkomaty_inpost');
// end paczkomaty
```


## Instalacja modułu
Instalacja modułu odbywa sie w panelu administracyjnym

1.  Korzystając z menu głównego, rozwiń zakładkę Moduły (Modules), a nasępie wybierz opcję Dostawy (Shipping)
2.  Zainstaluj Moduł (Install Module) i wybierz z listy Paczkomaty InPost)
3.  Opcje konfiguracyjne dla modułu to:
    *   Adres URL do API - domyślnie https://api.paczkomaty.pl
    *   Paczkomat nadawczy (Nadawanie paczek bezpośrednio w Paczkomacie) - domyślnie wyłączony (wartość pusta), aby włączyć opcję należy podać odpowiedni kod Paczkomatu (znajdź paczkomat)
    *   Email konta sklepu zarajestrowanego w usłudze Pakomaty InPost - domyślnie ustawiony email konta testowego
    *   Hasło do konta sklepu zarajestrowanego w usłudze Pakomaty InPost - domyślnie ustawione hasło do konto testowego
    *   Typ paczki generowanej w Managerze Paczek Paczkomaty InPost - domyślnie typ A (dostępne: A, B, C)
    *   Cena wysyłki za pomoca usługi Pakoczmaty InPost - domyślnie 6.99