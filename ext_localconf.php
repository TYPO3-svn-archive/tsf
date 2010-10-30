<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'BE')	{
	$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tstemplate_info/class.tx_tstemplateinfo.php'] = t3lib_extMgm::extPath('tsf').'class.ux_tx_tstemplateinfo.php';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postTCEProcessingHook'][] = 'EXT:tsf/class.tx_tsf_hooks.php:tx_tsf_hooks->postTCEProcessingHook';
	$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/t3editor/classes/class.tx_t3editor_hooks_tstemplateinfo.php'] = t3lib_extMgm::extPath('tsf').'class.ux_tx_t3editor_hooks_tstemplateinfo.php';
}