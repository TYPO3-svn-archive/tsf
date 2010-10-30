<?php

require_once t3lib_extMgm::extPath('tsf').'class.tx_tsf.php';

/**
 * This class displays the Info/Modify screen of the Web > Template module
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 *
 * $Id: class.tx_tstemplateinfo.php 8538 2010-08-09 10:20:28Z lolli $
 */
class ux_tx_tstemplateinfo extends tx_tstemplateinfo {

	function initialize_editor($pageId, $template_uid=0) {
		global $tplRow;
		$result = parent::initialize_editor($pageId, $template_uid);
		$tplRow['config'] = tx_tsf::checkIncludeLines($tplRow['config']);
		$tplRow['constants'] = tx_tsf::checkIncludeLines($tplRow['constants']);
		return $result;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tsf/class.ux_tx_tstemplateinfo.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tsf/class.ux_tx_tstemplateinfo.php']);
}

?>