<?php

require_once t3lib_extMgm::extPath('tsf').'class.tx_tsf.php';

class ux_tx_t3editor_hooks_tstemplateinfo extends tx_t3editor_hooks_tstemplateinfo {
	
	/**
	 * Overwriting save method 
	 * 
	 * @param $parameters
	 * @param $pObj
	 * @return boolean true if successful
	 */
	public function save($parameters, $pObj) {
		$result = parent::save($parameters, $pObj);
		
		$set = t3lib_div::_GP('SET');
		$template_uid = $set['templatesOnPage'] ? $set['templatesOnPage'] : 0;
		
		// as the editor content will not be refreshed after saving the checksum will not be updatet. So disable checks...
		tx_tsf::$checkSumCheck = false; 
		tx_tsf::extractFromRecord($template_uid);
		tx_tsf::$checkSumCheck = true;;
		
		return $result;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tsf/class.ux_tx_t3editor_hooks_tstemplateinfo.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tsf/class.ux_tx_t3editor_hooks_tstemplateinfo.php']);
}

?>