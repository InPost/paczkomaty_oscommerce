
# Installation Instructions Module Paczkomaty InPost for osCommerce 2.3


## Make a Backup
You should make a backup of your entire site. This includes your code and the database.


## Installation of the shipping module

### Upload Files

#### New files
The following files need to be uploaded to your site.

    /includes/functions/inpost_functions.php
    /includes/languages/english/modules/shipping/paczkomaty.php
    /includes/languages/polish/modules/shipping/paczkomaty.php
    /includes/modules/shipping/paczkomaty.php

### Edit existing files
Make the changes outlined below for each file. The numbers of lines are given in approximate.

#### You need to manually edit these files

    /checkout_shipping.php
    /checkout_confirmation.php
    /checkout_process.php
    /includes/database_tables.php

#### OPEN: /checkout_shipping.php

`FIND on line 122:`
```php
$shipping = array('id' => $shipping,
    'title' => (($free_shipping == true) ?  $quote[0]['methods'][0]['title'] : $quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')'),
    'cost' => $quote[0]['methods'][0]['cost']);
```

`ADD this code after:`
```php
// start paczkomaty
    if (preg_match('/paczkomaty_/', $shipping['id'])) {
        $pa = new Paczkomaty();
        $shipping = $pa->oscomm_prepare_shipping($quote, $shipping, $free_shipping);
    }
// end paczkomaty
```

`FIND on line 274:`
```php
    } else {
          for ($j=0, $n2=sizeof($quotes[$i]['methods']); $j<$n2; $j++) {
    // set the radio button to be checked if it is the method chosen
            $checked = (($quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'] == $shipping['id']) ? true : false);
```
`ADD this code before:`
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

#### OPEN: /checkout_confirmation.php

`FIND on line 65:`
```php
    require(DIR_WS_CLASSES . 'shipping.php');
  	$shipping_modules = new shipping($shipping);
```

`ADD this code after:`
```php
// start paczkomaty
	if (preg_match('/paczkomaty_/', $shipping['id'])) {
		if ($shipping['payment_cod'] == 'f' && $payment == 'cod')
			tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, 'error_message='.MODULE_SHIPPING_PACZKOMATY_ERROR_UNAVAILABLE_COD, 'SSL'));
	}
// end paczkomaty
```

#### OPEN: /checkout_process.php

`FIND on line 286:`
```php
        $payment_modules->after_process();

    	$cart->reset(true);
```

`ADD this code after:`
```php
 // start paczkomaty
	if (preg_match('/paczkomaty_/', $shipping['id'])) {
		$pa = new Paczkomaty();
		$pa->create_packs($shipping, $payment, $order->info['total'], $insert_id);
	}
// end paczkomaty
```

#### OPEN: /includes/database_tables.php

`ADD:`
```php
// start paczkomaty
    define('TABLE_EXTERNAL_PACZKOMATY_INPOST', 'external_paczkomaty_inpost');
// end paczkomaty
```

### Install the Paczkomaty InPost shipping module

Log into your admin section and go to `Modules` -> `Shipping`. Next, click `+ Install Module` button, select `Paczkomaty InPost` module from list and click `Install Module` button in the box on the right.
The installation should be now complete.

#### Configuration options
*   `URL Address for API` - default: https://api.paczkomaty.pl
*   `Email for Paczkomaty InPost account` - default value is only for testing, you must set your data for Paczkomaty InPost account
*   `Password for Paczkomaty Inpost account` - default value is only for testing, you must set your data for Paczkomaty InPost account
*   `Sender machine` - default value is empty, to activate option you must enter the code for the sending machine, [find machine](http://www.paczkomaty.pl/znajdz_paczkomat,33.html), example: AND039
*   `Pack type` - pack size, default: A, available sizes: A, B, C
*   `Price for shipping` - default: 6.99


## Installation of the management options for packs in admin panel

### Upload Files

#### New files
The following files need to be uploaded to your site.

    /admin/external_paczkomaty.php
    /admin/includes/classes/paczkomaty_ext.php
    /admin/includes/functions/inpost_functions.php
    /admin/includes/languages/english/external_paczkomaty.php
    /admin/includes/languages/polish/external_paczkomaty.php

### Edit existing files
Make the changes outlined below for each file. The numbers of lines are given in approximate.

#### You need to manually edit these files

    /admin/boxes/customers.php
    /admin/includes/database_tables.php
    /admin/includes/filenames.php
    /admin/includes/languages/english.php
    or /admin/includes/languages/polish.php

#### OPEN: /admin/boxes/customers.php

`FIND on line 21:`
```php
    array(
        'code' => FILENAME_ORDERS,
        'title' => BOX_CUSTOMERS_ORDERS,
        'link' => tep_href_link(FILENAME_ORDERS)
    )
```

`REPLACE with this code:`
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

#### OPEN: /admin/includes/database_tables.php

`ADD:`
```php
// start paczkomaty
    define('TABLE_EXTERNAL_PACZKOMATY_INPOST', 'external_paczkomaty_inpost');
// end paczkomaty
```

#### OPEN: /admin/includes/filenames.php

`ADD:`
```php
// start paczkomaty
    define('FILENAME_EXTERNAL_PACZKOMATY', 'external_paczkomaty.php');
// end paczkomaty
```
#### OPEN: /admin/includes/languages/english.php or ../polish.php

`FIND on line 80:`
```php
// customers box text in includes/boxes/customers.php
define('BOX_HEADING_CUSTOMERS', 'Customers');
define('BOX_CUSTOMERS_CUSTOMERS', 'Customers');
define('BOX_CUSTOMERS_ORDERS', 'Orders');
```

`ADD this code after:`
```php
// start paczkomaty
define('BOX_CUSTOMERS_EXTERNAL_PACZKOMATY', 'Paczkomaty InPost');
// end paczkomaty
```

### New option in admin panel
After all, in the admin panel you will find a new option. `Customers` -> `Paczkomaty InPost`