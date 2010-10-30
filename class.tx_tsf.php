<?php

class tx_tsf {
	
	/**
	 * @var bool checksum checks can be disabled (needed for ajax save in t3editor)
	 */
	public static $checkSumCheck = true;
	
	
	
	/**
	 * Extract include content from records
	 * 
	 * @param int $uid
	 * @return void
	 */
	public static function extractFromRecord($uid) {
		// load record
		$recData = t3lib_BEfunc::getRecord('sys_template', $uid, 'config,constants');
		$dataMap['sys_template'][$uid]['config'] = tx_tsf::extractIncludeLines($recData['config']);
		$dataMap['sys_template'][$uid]['constants'] = tx_tsf::extractIncludeLines($recData['constants']);
		
		$tce = t3lib_div::makeInstance('t3lib_TCEmain'); /* @var $tce t3lib_TCEmain */
		$tce->stripslashes_values=0;
			// Initialize
		$tce->start($dataMap, array());
			// Saved the stuff
		$tce->process_datamap();
		
			// Clear the cache (note: currently only admin-users can clear the cache in tce_main.php)
		$tce->clear_cacheCmd('all');
	}

	
	
	/**
	 * Write TypoScript file
	 * 
	 * @param string $fileName
	 * @param string $fileContentString
	 * @param string (optional) $fileChecksum
	 * @return void
	 */
	public static function writeTsFile($fileName, $fileContentString, $fileChecksum=NULL) {
		$fileContentString = self::extractIncludeLines($fileContentString);
		$realFileName = t3lib_div::getFileAbsFileName($fileName);
		
		// check if file has changed in the meantime
		if (self::$checkSumCheck && $fileChecksum) {
			$checkSum = md5_file($realFileName);
			if ($fileChecksum != $checkSum) {
				throw new Exception(sprintf('File "%s" has changed (expected checksum: "%s", actual checksum: "%s")', $realFileName, $fileChecksum, $checkSum));
			}
		}
		if (is_file($realFileName) && !is_writable($realFileName)) {
			throw new Exception(sprintf('File "%s" is not writeable', $realFileName));
		}
		// Check if content has actually changed
		if ($fileChecksum != md5($fileContentString)) {
			file_put_contents($realFileName, $fileContentString);
		}
	}
	
	
	
	/**
	 * Extract include lines from string and store content into file
	 * 
	 * @param string $content
	 * @return string rest without included TypoScript code
	 */
	public static function extractIncludeLines($content) {
		$fileContent = array();
		$restContent = array();
		$fileName = '';
		$lines = explode("\n", $content);
		$inIncludePart = false;
		$found = 0;
		$fileNames = array();
		foreach ($lines as $line) {
			$matches = array();
			if (!$inIncludePart && preg_match('/### <INCLUDE_TYPOSCRIPT:.*source="FILE:(\S*)"> BEGIN/', $line, $matches)) {
				$fileName = trim($matches[1]);
				if (in_array($fileName, $fileNames)) {
					throw new Exception(sprintf('File "%s" was included multiple times!', $fileName));
				}
				$fileNames[] = $fileName;
				$found++;
				$inIncludePart = true;
				$restContent[] = "<INCLUDE_TYPOSCRIPT: source=\"FILE:$fileName\">";
			} elseif ($inIncludePart) {
				// check if this is the endline
				if (strpos($line, '### <INCLUDE_TYPOSCRIPT: source="FILE:'.$fileName.'"> END') !== false) {
					$matches = array();
					if (preg_match('/\(Checksum:(.*)\)/', $line, $matches)) {
						$fileChecksum = $matches[1];	
					}
					$fileContentString = implode("\n", $fileContent);
					self::writeTsFile($fileName, $fileContentString, $fileChecksum);
					
					// reset variables
					$fileContent = array();
					$fileChecksum = NULL;
					$inIncludePart = false;
				} else {
					$fileContent[] = $line;
				}
			} else {
				$restContent[] = $line;
			}
		}
		
		$restContentString = implode("\n", $restContent);
		
		// if an include was found write the content into the file
		if ($fileName && $inIncludePart) {
			throw new Exception(sprintf('No end of include statement found for "%s"', $fileName));
		}
		
		return $restContentString;
	}
	
	
	
	/**
	 * Checks the input string (un-parsed TypoScript) for include-commands ("<INCLUDE_TYPOSCRIPT: ....")
	 * Use: t3lib_TSparser::checkIncludeLines()
	 *
	 * @param	string		Unparsed TypoScript
	 * @param	integer		Counter for detecting endless loops
	 * @return	string		Complete TypoScript with includes added.
	 * @static
	 */
	public static function checkIncludeLines($string, $cycle_counter=1) {
		if ($cycle_counter>100) {
			t3lib_div::sysLog('It appears like TypoScript code is looping over itself. Check your templates for "&lt;INCLUDE_TYPOSCRIPT: ..." tags','Core',2);
			return '';
		}
		$splitStr='<INCLUDE_TYPOSCRIPT:';
		if (strstr($string,$splitStr))	{
			$newString='';
			$allParts = explode($splitStr,LF.$string.LF);	// adds line break char before/after
			foreach ($allParts as $c => $v) {
				if (!$c)	{	 // first goes through
					$newString.=$v;
				} elseif (preg_match('/\r?\n\s*$/',$allParts[$c-1]))	{	// There must be a line-break char before.
					$subparts=explode('>',$v,2);
					if (preg_match('/^\s*\r?\n/',$subparts[1]))	{	// There must be a line-break char after
							// SO, the include was positively recognized:
						$newString.='### '.$splitStr.$subparts[0].'> BEGIN:'.LF;
						$params = t3lib_div::get_tag_attributes($subparts[0]);
						if ($params['source'])	{
							$sourceParts = explode(':',$params['source'],2);
							switch(strtolower(trim($sourceParts[0])))	{
								case 'file':
									$filename = t3lib_div::getFileAbsFileName(trim($sourceParts[1]));
									if (strcmp($filename,''))	{	// Must exist and must not contain '..' and must be relative
										if (@is_file($filename) && filesize($filename)<100000)	{	// Max. 100 KB include files!
											
											$included_text = t3lib_div::getUrl($filename);
											$checkSum = md5($included_text);
											
											// recursive call
											$included_text = self::checkIncludeLines($included_text,$cycle_counter+1, $returnFiles);
											
											$newString.= $included_text.LF;
										}
									}
								break;
							}
						}
						$newString.='### '.$splitStr.$subparts[0].'> END. (Checksum:'.$checkSum.')';
						$newString.=$subparts[1];
					} else $newString.=$splitStr.$v;
				} else $newString.=$splitStr.$v;
			}
			$string=substr($newString,1,-1);	// not the first/last linebreak char.
		}
		return $string;
	}
	
}

?>