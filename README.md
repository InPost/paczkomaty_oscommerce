# Instalacja Modułu Paczkomaty InPost dla osCommerce 2.3

## Upload plików

Nowe pliki, które należy umieścić na serwerze w odpowiednich katalogach, zainstalowanego systemu osCommerce.

    /includes/functions/inpost_functions.php
    /includes/languages/polish/modules/shipping/paczkomaty.php
    /includes/modules/shipping/paczkomaty.php

## Edycja istniejących plików

Wprowadź zmiany opisane poniżej dla każdego z pliku. Numery lini są szacunkowe dla nieedytowanej wcześniej kopi pliku.

### Otwórz plik 'checkout_shipping.php'

Znajdź kod (linia 122):

```php
$shipping = array('id' => $shipping,
	'title' => (($free_shipping == true) ?  $quote[0]['methods'][0]['title'] : $quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')'),
	'cost' => $quote[0]['methods'][0]['cost']);
```