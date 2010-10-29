<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'BE')	{
	$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tstemplate_info/class.tx_tstemplateinfo.php'] = t3lib_extMgm::extPath('tsf').'class.ux_tx_tstemplateinfo.php';
}