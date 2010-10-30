<?php

require_once t3lib_extMgm::extPath('tsf').'class.tx_tsf.php';

class tx_tsf_hooks extends ux_tx_tstemplateinfo {

	/**
	 * Post processing hook
	 * 
	 * @param array $parameters
	 * @param tx_tstemplateinfo $pObj
	 * @return void
	 */
	public function postTCEProcessingHook(array $parameters, tx_tstemplateinfo $pObj) {
		global $tplRow;
		
		$POST = $parameters['POST'];
		
		if ($POST['submit'] || (t3lib_div::testInt($POST['submit_x']) && t3lib_div::testInt($POST['submit_y']))
				|| $POST['saveclose'] || (t3lib_div::testInt($POST['saveclose_x']) && t3lib_div::testInt($POST['saveclose_y']))) {
				
			// get template uid
			$manyTemplatesMenu = $pObj->pObj->templateMenu();
			$template_uid = 0;
			if ($manyTemplatesMenu)	{
				$template_uid = $pObj->pObj->MOD_SETTINGS['templatesOnPage'];
			}
			
			$existTemplate = $pObj->initialize_editor($pObj->pObj->id, $template_uid);		// initialize
			if ($existTemplate)	{
				$saveId = ($tplRow['_ORIG_uid'] ? $tplRow['_ORIG_uid'] : $tplRow['uid']);
			}
			
			tx_tsf::extractFromRecord($saveId);
	
				// tce were processed successfully
			$pObj->tce_processed = true;
	
				// re-read the template ...
			$pObj->initialize_editor($pObj->pObj->id, $template_uid);
		}
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tsf/class.tx_tsf_hooks.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tsf/class.tx_tsf_hooks.php']);
}


?>