<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/

  $cl_box_groups[] = array(
    'heading' => BOX_HEADING_CUSTOMERS,
    'apps' => array(
      array(
        'code' => FILENAME_CUSTOMERS,
        'title' => BOX_CUSTOMERS_CUSTOMERS,
        'link' => tep_href_link(FILENAME_CUSTOMERS)
      ),
      array(
        'code' => FILENAME_ORDERS,
        'title' => BOX_CUSTOMERS_ORDERS,
        'link' => tep_href_link(FILENAME_ORDERS)
      ),
	/* start paczkomaty */
      array(
        'code' => FILENAME_EXTERNAL_PACZKOMATY,
        'title' => BOX_CUSTOMERS_EXTERNAL_PACZKOMATY,
        'link' => tep_href_link(FILENAME_EXTERNAL_PACZKOMATY)
      )
    /* end paczkomaty */
    )
  );
?>
