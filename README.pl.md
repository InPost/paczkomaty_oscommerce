# Instrukcja Instalacji Modułu Paczkomaty InPost dla osCommerce 2.3


## Zrób kopię zapasową
Zrób kopię zapasową witryny sklepu, plików i bazy danych.


## Instalacja modułu wysyłek

### Prześlij pliki na serwer

#### Nowe pliki
Prześlij następujące pliki na serwer.

    /includes/functions/inpost_functions.php
    /includes/languages/polish/modules/shipping/paczkomaty.php
    /includes/modules/shipping/paczkomaty.php

### Edytuj istniejące pliki
Wprowadź zmiany opisane poniżej dla każdego pliku. Podane numery linii są szacunkowe dla nieedydowanej wcześniej kopi pliku.

#### Pliki, które należy przeedytować ręcznie

    /checkout_shipping.php
    /checkout_confirmation.php
    /checkout_process.php
    /includes/database_tables.php

#### OTWÓRZ: /checkout_shipping.php

`ZNAJDŹ przy linii 122:`
```php
$shipping = array('id' => $shipping,
    'title' => (($free_shipping == true) ?  $quote[0]['methods'][0]['title'] : $quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')'),
    'cost' => $quote[0]['methods'][0]['cost']);
```

`DODAJ ten kod po:`
```php
// start paczkomaty
    if (preg_match('/paczkomaty_/', $shipping['id'])) {
        $pa = new Paczkomaty();
        $shipping = $pa->oscomm_prepare_shipping($quote, $shipping, $free_shipping);
    }
// end paczkomaty
```

`ZNAJDŹ przy linii 274:`
```php
    } else {
          for ($j=0, $n2=sizeof($quotes[$i]['methods']); $j<$n2; $j++) {
    // set the radio button to be checked if it is the method chosen
            $checked = (($quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'] == $shipping['id']) ? true : false);
```
`DODAJ ten kod przed:`
```php
// start paczkomaty
	} elseif ($quotes[$i]['module'] == MODULE_SHIPPING_PACZKOMATY_TEXT_TITLE) {
		$pa = new paczkomaty();
       	$checked = preg_match('/paczkomaty/', $shipping['id']);
		if ( ($checked) || ($n == 1 && $n2 == 1) ) {
			echo '<tr id="defaultSelected" class="moduleRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, '.$radio_buttons.')">';
   		} else {
   			echo '<tr id="paczkomatyRow" class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectRowEffect(this, '.$radio_buttons.')">';
   		}
   		echo '<td width="75%" style="padding-left: 15px;">';
   		echo $pa->oscomm_paczkomaty_dropbox($quotes[$i], $shipping).'</td>';
   		echo '<td>'.$currencies->format(tep_add_tax($quotes[$i]['methods'][0]['cost'], (isset($quotes[$i]['tax']) ? $quotes[$i]['tax'] : 0))).'</td>';
		echo '<td align="right">'.tep_draw_radio_field('shipping', $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][0]['id'], $checked, 'id="shipping_paczkomaty"').
				'<script type="text/javascript">
					function changeCheckedValue(obj) {
						document.getElementById("shipping_paczkomaty").value = obj.options[obj.selectedIndex].value;
						selectRowEffect(document.getElementById("paczkomatyRow"), '.$radio_buttons.');
					}
					changeCheckedValue(document.getElementById("dropbox_paczkomaty"));
				</script>
	        	</td>';
		echo '</tr>';
// end paczkomaty
```

#### OTWÓRZ: /checkout_confirmation.php

`ZNAJDŹ przy linii 65:`
```php
    require(DIR_WS_CLASSES . 'shipping.php');
  	$shipping_modules = new shipping($shipping);
```

`DODAJ ten kod po:`
```php
// start paczkomaty
	if (preg_match('/paczkomaty_/', $shipping['id'])) {
		if ($shipping['payment_cod'] == 'f' && $payment == 'cod')
			tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, 'error_message='.MODULE_SHIPPING_PACZKOMATY_ERROR_UNAVAILABLE_COD, 'SSL'));
	}
// end paczkomaty
```

#### OTWÓRZ: /checkout_process.php

`ZNAJDŹ przy linii 286:`
```php
        $payment_modules->after_process();

    	$cart->reset(true);
```

`DODAJ ten kod po:`
```php
 // start paczkomaty
	if (preg_match('/paczkomaty_/', $shipping['id'])) {
		$pa = new Paczkomaty();
		$pa->create_packs($shipping, $payment, $order->info['total'], $insert_id);
	}
// end paczkomaty
```

#### OTWÓRZ: /includes/database_tables.php

`DODAJ:`
```php
// start paczkomaty
    define('TABLE_EXTERNAL_PACZKOMATY_INPOST', 'external_paczkomaty_inpost');
// end paczkomaty
```

### Instalacja modułu Paczkomaty InPost

Zaloguj sie do panelu administracyjnego, przejdź `Modules` -> `Shipping`. Następnie kliknij przycisk `+ Install Module`, wybierz z listy `Paczkomaty InPost` i kliknij przycisk `Install Module` znajdujący się menu po prawej stronie.
Instalacja modułu powinna sie zakończyć.

#### Dostępne opcje konfiguracyjne
*   `Adres URL do API` - domyślnie: https://api.paczkomaty.pl
*   `Email do konta w Paczkomaty InPost` - domyślna wartość to ustawienia testowe, wprowadź swoje dane z konta w Paczkomaty InPost
*   `Hasło do konta w Paczkomaty Inpost` - domyślna wartość to ustawienia testowe, wprowadź swoje dane z konta w Paczkomaty InPost
*   `Paczkomat nadawczy` - domyślna wartość jest pusta, abu aktywować możliwość nadawania bezpośrednio z paczkomatu, uzupełnij o właściwy dla niego kod, [znajdź paczkomat](http://www.paczkomaty.pl/znajdz_paczkomat,33.html), przykład: AND039
*   `Typ paczki` - rozmiar paczki, domyślnie: A, dostępne rozmiary: A, B, C
*   `Cena przesyłki` - domyślnie: 6.99


## Instalacja opcji zarządzania paczkami dla panelu administracyjnego

### Prześlij pliki na serwer

#### Nowe pliki
Prześlij następujące pliki na serwer.

    /admin/external_paczkomaty.php
    /admin/includes/classes/paczkomaty_ext.php
    /admin/includes/functions/inpost_functions.php
    /admin/includes/languages/polish/external_paczkomaty.php

### Edytuj istniejące pliki
Wprowadź zmiany opisane poniżej dla każdego pliku. Podane numery linii są szacunkowe dla nieedydowanej wcześniej kopi pliku.

#### Pliki, które należy przeedytować ręcznie

    /admin/boxes/customers.php
    /admin/includes/database_tables.php
    /admin/includes/filenames.php
    /admin/includes/languages/polish.php

#### OTWÓRZ: /admin/boxes/customers.php

`ZNAJDŹ przy linii 21:`
```php
    array(
        'code' => FILENAME_ORDERS,
        'title' => BOX_CUSTOMERS_ORDERS,
        'link' => tep_href_link(FILENAME_ORDERS)
    )
```

`ZAMIEŃ na kod:`
```php
    array(
        'code' => FILENAME_ORDERS,
        'title' => BOX_CUSTOMERS_ORDERS,
        'link' => tep_href_link(FILENAME_ORDERS)
    ),
// start paczkomaty
    array(
        'code' => FILENAME_EXTERNAL_PACZKOMATY,
        'title' => BOX_CUSTOMERS_EXTERNAL_PACZKOMATY,
        'link' => tep_href_link(FILENAME_EXTERNAL_PACZKOMATY)
    )
// end paczkomaty
```

#### OTWÓRZ: /admin/includes/database_tables.php

`DODAJ:`
```php
// start paczkomaty
    define('TABLE_EXTERNAL_PACZKOMATY_INPOST', 'external_paczkomaty_inpost');
// end paczkomaty
```

#### OTWÓRZ: /admin/includes/filenames.php

`DODAJ:`
```php
// start paczkomaty
    define('FILENAME_EXTERNAL_PACZKOMATY', 'external_paczkomaty.php');
// end paczkomaty
```
#### OTWÓRZ: /admin/includes/languages/polish.php

`ZNAJDŹ przy linii 80:`
```php
// customers box text in includes/boxes/customers.php
define('BOX_HEADING_CUSTOMERS', 'Customers');
define('BOX_CUSTOMERS_CUSTOMERS', 'Customers');
define('BOX_CUSTOMERS_ORDERS', 'Orders');
```

`DODAJ ten kod po:`
```php
// start paczkomaty
define('BOX_CUSTOMERS_EXTERNAL_PACZKOMATY', 'Paczkomaty InPost');
// end paczkomaty
```

### Nowa opcja w panelu administracyjnym
Po wszystkim, w panelu administracyjnym znajdziesz nową opcję. `Customers` -> `Paczkomaty InPost`