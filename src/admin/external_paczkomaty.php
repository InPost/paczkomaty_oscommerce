<?php
/*

Paczkomaty InPost osCommerce Module
Revision 2.0.0

Copyright (c) 2012 InPost Sp. z o.o.

*/

require('includes/application_top.php');
require(DIR_WS_FUNCTIONS.'inpost_functions.php');
require(DIR_WS_CLASSES.'paczkomaty_ext.php');

$action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');
$error = (isset($HTTP_GET_VARS['error']) ? $HTTP_GET_VARS['error'] : '');
$message = (isset($HTTP_GET_VARS['message']) ? $HTTP_GET_VARS['message'] : '');
$pack_id = (isset($HTTP_GET_VARS['pID']) ? $HTTP_GET_VARS['pID'] : '');
  
if (tep_not_null($error)) {
	$messageStack->add( $error, 'error' );
}
if (tep_not_null($message)) {
	$messageStack->add( $message, 'success' );
}

$paczkomaty = new Paczkomaty_ext();

if (tep_not_null($action)) {
	switch ($action) {
    	case 'create_label':    		
    		$response = $paczkomaty->create_label($pack_id);
			if (array_key_exists('error',$response))
				$messageStack->add(TEXT_LABEL_GENERATE_FAIL .' ('.$response['error']['message'].')', 'error' );
			break;
		case 'deleteconfirm':
	        $pID = tep_db_prepare_input($HTTP_GET_VARS['pID']);			
	        $paczkomaty->remove_pack($pID, $HTTP_POST_VARS['cancel_pack']);
	        tep_redirect(tep_href_link(FILENAME_EXTERNAL_PACZKOMATY, tep_get_all_get_params(array('pID', 'action'))));
	        break;
		case 'confirmpackconfirm':
			$pID = tep_db_prepare_input($HTTP_GET_VARS['pID']);
			$paczkomaty->confirm_pack($pID, $HTTP_POST_VARS['test_confirm']);
			tep_redirect(tep_href_link(FILENAME_EXTERNAL_PACZKOMATY, tep_get_all_get_params(array('pID', 'action'))));
			break;
	}
}
		
require(DIR_WS_INCLUDES . 'template_top.php');

?>
<table border="0" width="100%" cellspacing="0" cellpadding="2">
	<tr>
		<td width="100%">
			<table border="0" width="100%" cellspacing="0" cellpadding="0">
				<tr>
					<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
					<td class="pageHeading" align="right"><img src="images/pixel_trans.gif" border="0" alt="" width="1" height="40"></td>
				</tr>
			</table>
		</td>
	</tr>
	
	<tr>
		<td>
			<table border="0" width="100%" cellspacing="0" cellpadding="0">
				<tr>
            		<td valign="top">
            			<table border="0" width="100%" cellspacing="0" cellpadding="2">
              				<tr class="dataTableHeadingRow">
                				<td class="dataTableHeadingContent"><?php echo TABLE_HEADING_ORDER_ID ?></td>
                				<td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PACKCODE ?></td>
                				<td class="dataTableHeadingContent"><?php echo TABLE_HEADING_STATUS ?></td>
                				<td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_DATE_LABEL_CREATED ?></td>
                				<td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_DATE_CREATED ?></td>
                				<td class="dataTableHeadingContent" align="right">Action&nbsp;</td>
              				</tr>
<?php
$packs_query_raw = "select id, order_id, packcode, pack_status, label_printed, date_added from ".TABLE_EXTERNAL_PACZKOMATY_INPOST." order by date_added desc"; 
$packs_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $packs_query_raw, $packs_query_numrows);
    $packs_query = tep_db_query($packs_query_raw);
    while ($pack = tep_db_fetch_array($packs_query)) {
    	
    	$pack_status = $pack['pack_status'];
    	
    	if (tep_not_null($action)) {
    		switch ($action) {
	    		case 'refresh':    		
		    		$pack_status = $paczkomaty->get_status($pack['packcode']);
					break;
    		}
    	}
    	
		if ((!isset($HTTP_GET_VARS['pID']) || (isset($HTTP_GET_VARS['pID']) && ($HTTP_GET_VARS['pID'] == $pack['id']))) && !isset($pInfo)) {
			$pack_status = $paczkomaty->get_status($pack['packcode']);
			$pInfo = new objectInfo($pack);
		}

		if (isset($pInfo) && is_object($pInfo) && ($pack['id'] == $pInfo->id)) {
			echo '			<tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">' . "\n";
		} else {
			echo '			<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_EXTERNAL_PACZKOMATY, tep_get_all_get_params(array('pID', 'action')) . 'pID=' . $pack['id']) . '\'">' . "\n";
		}
?>              				
								<td class="dataTableContent"><?php echo $pack['order_id'] ?></td>
								<td class="dataTableContent"><?php echo $pack['packcode'] ?></td>
								<td class="dataTableContent"><?php echo $pack_status; ?></td>
								<td class="dataTableContent" align="right"><?php echo $pack['label_printed'] ?></td>
								<td class="dataTableContent" align="right"><?php echo $pack['date_added'] ?></td>
								<td class="dataTableContent" align="right"><?php echo '<a href="' . tep_href_link(FILENAME_EXTERNAL_PACZKOMATY, tep_get_all_get_params(array('pID')) . 'pID=' . $pack['id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; ?>&nbsp;</td>
              				</tr>
<?php  } ?>
							<tr>
								<td colspan="4">
									<table border="0" width="100%" cellspacing="0" cellpadding="2">
                  						<tr>
											<td class="smallText" valign="top"><?php echo $packs_split->display_count($packs_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_PACKS); ?></td>
											<td class="smallText" align="right"><?php echo $packs_split->display_links($packs_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page'], tep_get_all_get_params(array('page', 'pID', 'action'))); ?></td>
										</tr>
									</table>
								</td>
              				</tr>
            			</table>
            		</td>
            		
<?php
	$heading = array();
	$contents = array();

	switch ($action) {
		case 'delete':
			$heading[] = array('text' => '<strong>['.TEXT_ORDER_NUMBER.$pInfo->order_id.' - '.$pInfo->packcode.']&nbsp;&nbsp;</strong>');
		
		    $contents = array('form' => tep_draw_form('packs', FILENAME_EXTERNAL_PACZKOMATY, tep_get_all_get_params(array('pID', 'action')) . 'pID=' . $pInfo->id . '&action=deleteconfirm'));
		    $contents[] = array('text' => TEXT_INFO_DELETE_PACK . '<br />');
		    if ($pInfo->pack_status == 'Created')
		    	$contents[] = array('text' => '<br />' . tep_draw_checkbox_field('cancel_pack') . ' ' . TEXT_INFO_CANCEL_PACK);
		    else
		      	$contents[] = array('text' => '<br />' . TEXT_INFO_CANCEL_PACK_UNAVAILABLE);
		    $contents[] = array('align' => 'center', 'text' => '<br />' . tep_draw_button(IMAGE_DELETE, 'trash', null, 'primary') . tep_draw_button(IMAGE_CANCEL, 'close', tep_href_link(FILENAME_EXTERNAL_PACZKOMATY, tep_get_all_get_params(array('pID', 'action')) . 'pID=' . $pInfo->id)));
		    break;
		case 'confirmpack':
			$heading[] = array('text' => '<strong>['.TEXT_ORDER_NUMBER.$pInfo->order_id.' - '.$pInfo->packcode.']&nbsp;&nbsp;</strong>');
		
		    $contents = array('form' => tep_draw_form('packs', FILENAME_EXTERNAL_PACZKOMATY, tep_get_all_get_params(array('pID', 'action')) . 'pID=' . $pInfo->id . '&action=confirmpackconfirm'));
		    $contents[] = array('text' => TEXT_INFO_GET_CONFIRM_PRINTOUT . '<br />');
		    $contents[] = array('text' => TEXT_INFO_GET_CONFIRM_PRINTOUT_DESC . '<br />');
		    $contents[] = array('text' => '<br />' . tep_draw_checkbox_field('test_confirm') . ' ' . TEXT_INFO_GET_TEST_CONFIRM_PRINTOUT);
		    $contents[] = array('align' => 'center', 'text' => '<br />' . tep_draw_button(IMAGE_DOWNLOAD_CONFIRM, 'document', null, 'primary') . tep_draw_button(IMAGE_CANCEL, 'close', tep_href_link(FILENAME_EXTERNAL_PACZKOMATY, tep_get_all_get_params(array('pID', 'action')) . 'pID=' . $pInfo->id)));
		    break;
		default:
			if (isset($pInfo) && is_object($pInfo)) {
				$heading[] = array('text' => '<strong>['.TEXT_ORDER_NUMBER.$pInfo->order_id.' - '.$pInfo->packcode.']&nbsp;&nbsp;</strong>');

				$button_generate = tep_draw_button(($pInfo->label_printed)? IMAGE_DOWNLOAD_LABEL : IMAGE_GENERATE_LABEL, 'document', tep_href_link(FILENAME_EXTERNAL_PACZKOMATY, tep_get_all_get_params(array('pID', 'action')) . 'pID=' . $pInfo->id . '&action=create_label'));
				$button_delete = tep_draw_button(IMAGE_DELETE, 'trash', tep_href_link(FILENAME_EXTERNAL_PACZKOMATY, tep_get_all_get_params(array('pID', 'action')) . 'pID=' . $pInfo->id . '&action=delete'));
				$button_confirm = tep_draw_button(IMAGE_CONFIRM_PRINTOUT, 'document', tep_href_link(FILENAME_EXTERNAL_PACZKOMATY, tep_get_all_get_params(array('pID, action')) . 'pID=' . $pInfo->id . '&action=confirmpack'));
				$button_refresh = tep_draw_button(IMAGE_REFRESH_PACKS_STATUS, 'document', tep_href_link(FILENAME_EXTERNAL_PACZKOMATY, tep_get_all_get_params(array('action')) . 'action=refresh'));
				if (!$pInfo->pack_status || $pInfo->pack_status == 'Created')
					$button_confirm = null;
				
				$contents[] = array('align' => 'center', 'text' => $button_generate . $button_delete . $button_confirm);
        		$contents[] = array('align' => 'center', 'text' => $button_refresh);
				$contents[] = array('text' => '<br />'.TEXT_DATE_PACK_CREATED.': '.tep_date_short($pInfo->date_added));
				$contents[] = array('text' => TEXT_PACK_STATUS.': '.$pInfo->pack_status);
			}
      		break;
	}
	
	if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
    	echo '            <td width="25%" valign="top">' . "\n";
	
    	$box = new box;
		echo $box->infoBox($heading, $contents);
		echo '            </td>' . "\n";
	}
?>
          		</tr>
        	</table>
		</td>
    </tr>
</table>
    
<?php 
require(DIR_WS_INCLUDES . 'template_bottom.php');
require(DIR_WS_INCLUDES . 'application_bottom.php'); 
?>