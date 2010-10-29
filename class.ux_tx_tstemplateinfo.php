<?php
/**
 * This class displays the Info/Modify screen of the Web > Template module
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 *
 * $Id: class.tx_tstemplateinfo.php 8538 2010-08-09 10:20:28Z lolli $
 */
class ux_tx_tstemplateinfo extends tx_tstemplateinfo {

	/**
	 * The main processing method if this class
	 *
	 * @return	string		Information of the template status or the taken actions as HTML string
	 */
	function main()	{
		global $SOBE,$BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		global $tmpl,$tplRow,$theConstants;

		$edit = $this->pObj->edit;
		$e = $this->pObj->e;

		t3lib_div::loadTCA('sys_template');




		// **************************
		// Checking for more than one template an if, set a menu...
		// **************************
		$manyTemplatesMenu = $this->pObj->templateMenu();
		$template_uid = 0;
		if ($manyTemplatesMenu)	{
			$template_uid = $this->pObj->MOD_SETTINGS['templatesOnPage'];
		}


		// **************************
		// Initialize
		// **************************
		$existTemplate = $this->initialize_editor($this->pObj->id, $template_uid);		// initialize

		if ($existTemplate)	{
			$saveId = ($tplRow['_ORIG_uid'] ? $tplRow['_ORIG_uid'] : $tplRow['uid']);
		}
		// **************************
		// Create extension template
		// **************************
		$newId = $this->pObj->createTemplate($this->pObj->id, $saveId);
		if($newId) {
			// switch to new template
			t3lib_utility_Http::redirect('index.php?id=' . $this->pObj->id. '&SET[templatesOnPage]=' . $newId);
		}

		if ($existTemplate)	{
			
			$this->onTemplateLoad();
			
				// Update template ?
			$POST = t3lib_div::_POST();
			if ($POST['submit'] || (t3lib_div::testInt($POST['submit_x']) && t3lib_div::testInt($POST['submit_y']))
				|| $POST['saveclose'] || (t3lib_div::testInt($POST['saveclose_x']) && t3lib_div::testInt($POST['saveclose_y']))) {
					// Set the data to be saved
				$recData = array();
				$alternativeFileName = array();
				$resList = $tplRow['resources'];

				$tmp_upload_name = '';
				$tmp_newresource_name = '';	// Set this to blank

				if (is_array($POST['data']))	{
					foreach ($POST['data'] as $field => $val) {
						switch ($field)	{
							case 'constants':
							case 'config':
							case 'title':
							case 'sitetitle':
							case 'description':
								$recData['sys_template'][$saveId][$field] = $val;
								break;
							case 'resources':
								$tmp_upload_name = t3lib_div::upload_to_tempfile($_FILES['resources']['tmp_name']);	// If there is an uploaded file, move it for the sake of safe_mode.
								if ($tmp_upload_name)	{
									if ($tmp_upload_name!='none' && $_FILES['resources']['name'])	{
										$alternativeFileName[$tmp_upload_name] = trim($_FILES['resources']['name']);
										$resList = $tmp_upload_name.','.$resList;
									}
								}
								break;
							case 'new_resource':
								$newName = trim(t3lib_div::_GP('new_resource'));
								if ($newName)	{
									$newName.= '.'.t3lib_div::_GP('new_resource_ext');
									$tmp_newresource_name = t3lib_div::tempnam('new_resource_');
									$alternativeFileName[$tmp_newresource_name] = $newName;
									$resList = $tmp_newresource_name.','.$resList;
								}
								break;
							case 'makecopy_resource':
								if (is_array($val))	{
									$resList = ','.$resList.',';
									foreach ($val as $k => $file) {
										$tmp_name = PATH_site.$TCA['sys_template']['columns']['resources']['config']['uploadfolder'].'/'.$file;
										$resList = $tmp_name.','.$resList;
									}
								}
								break;
							case 'remove_resource':
								if (is_array($val))	{
									$resList = ','.$resList.',';
									foreach ($val as $k => $file) {
										$resList = str_replace(','.$file.',', ',', $resList);
									}
								}
								break;
							case 'totop_resource':
								if (is_array($val))	{
									$resList = ','.$resList.',';
									foreach ($val as $k => $file) {
										$resList = str_replace(','.$file.',', ',', $resList);
										$resList = ','.$file.$resList;
									}
								}
								break;
						}
					}
				}
				$resList=implode(',', t3lib_div::trimExplode(',', $resList, 1));
				if (strcmp($resList, $tplRow['resources']))	{
					$recData['sys_template'][$saveId]['resources'] = $resList;
				}
				
				// Added by Fabrizio
				$recData['sys_template'][$saveId] = $this->onTemplateSave($recData['sys_template'][$saveId]);
				
				if (count($recData))	{
						// Create new  tce-object
					$tce = t3lib_div::makeInstance('t3lib_TCEmain');
					$tce->stripslashes_values=0;
					$tce->alternativeFileName = $alternativeFileName;
						// Initialize
					$tce->start($recData, array());
						// Saved the stuff
					$tce->process_datamap();
						// Clear the cache (note: currently only admin-users can clear the cache in tce_main.php)
					$tce->clear_cacheCmd('all');

						// tce were processed successfully
					$this->tce_processed = true;

						// re-read the template ...
					$this->initialize_editor($this->pObj->id, $template_uid);
				}

					// Unlink any uploaded/new temp files there was:
				t3lib_div::unlink_tempfile($tmp_upload_name);
				t3lib_div::unlink_tempfile($tmp_newresource_name);

					// If files has been edited:
				if (is_array($edit))		{
					if ($edit['filename'] && $tplRow['resources'] && t3lib_div::inList($tplRow['resources'], $edit['filename']))	{		// Check if there are resources, and that the file is in the resourcelist.
						$path = PATH_site.$TCA['sys_template']['columns']['resources']['config']['uploadfolder'].'/'.$edit['filename'];
						$fI = t3lib_div::split_fileref($edit['filename']);
						if (@is_file($path) && t3lib_div::getFileAbsFileName($path) && t3lib_div::inList($this->pObj->textExtensions, $fI['fileext']))	{		// checks that have already been done.. Just to make sure
								// @TODO: Check if the hardcorded value already has a config member, otherwise create one
							if (filesize($path) < 30720)	{	// checks that have already been done.. Just to make sure
								t3lib_div::writeFile($path, $edit['file']);

								$theOutput.= $this->pObj->doc->spacer(10);
								$theOutput.= $this->pObj->doc->section(
									'<font color=red>' . $GLOBALS['LANG']->getLL('fileChanged') . '</font>',
									sprintf($GLOBALS['LANG']->getLL('resourceUpdated'), $edit['filename']),
									0, 0, 0, 1
								);

									// Clear cache - the file has probably affected the template setup
									// @TODO: Check if the edited file really had something to do with cached data and prevent this clearing if possible!
								$tce = t3lib_div::makeInstance('t3lib_TCEmain');
								$tce->stripslashes_values = 0;
								$tce->start(array(), array());
								$tce->clear_cacheCmd('all');
							}
						}
					}
				}
			}

				// hook	Post updating template/TCE processing
			if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postTCEProcessingHook']))	{
				$postTCEProcessingHook =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postTCEProcessingHook'];
				if (is_array($postTCEProcessingHook)) {
					$hookParameters = array(
						'POST' 	=> $POST,
						'tce'	=> $tce,
					);
					foreach ($postTCEProcessingHook as $hookFunction)	{
						t3lib_div::callUserFunction($hookFunction, $hookParameters, $this);
					}
				}
			}

			$theOutput.= $this->pObj->doc->spacer(5);
			$theOutput.= $this->pObj->doc->section($GLOBALS['LANG']->getLL('templateInformation'), t3lib_iconWorks::getSpriteIconForRecord('sys_template', $tplRow).'<strong>'.htmlspecialchars($tplRow['title']).'</strong>'.htmlspecialchars(trim($tplRow['sitetitle'])?' - ('.$tplRow['sitetitle'].')':''), 0, 1);
			if ($manyTemplatesMenu)	{
				$theOutput.= $this->pObj->doc->section('', $manyTemplatesMenu);
				$theOutput.= $this->pObj->doc->divider(5);
			}

			#$numberOfRows= t3lib_div::intInRange($this->pObj->MOD_SETTINGS["ts_template_editor_TArows"],0,150);
			#if (!$numberOfRows)
			$numberOfRows = 35;

				// If abort pressed, nothing should be edited:
			if ($POST['abort'] || (t3lib_div::testInt($POST['abort_x']) && t3lib_div::testInt($POST['abort_y']))
				|| $POST['saveclose'] || (t3lib_div::testInt($POST['saveclose_x']) && t3lib_div::testInt($POST['saveclose_y']))) {
				unset($e);
			}

			if ($e['title'])	{
				$outCode = '<input type="Text" name="data[title]" value="'.htmlspecialchars($tplRow['title']).'"'.$this->pObj->doc->formWidth().'>';
				$outCode.= '<input type="Hidden" name="e[title]" value="1">';
				$theOutput.= $this->pObj->doc->spacer(15);
				$theOutput.= $this->pObj->doc->section($GLOBALS['LANG']->getLL('title'), $outCode);
			}
			if ($e['sitetitle'])	{
				$outCode = '<input type="Text" name="data[sitetitle]" value="'.htmlspecialchars($tplRow['sitetitle']).'"'.$this->pObj->doc->formWidth().'>';
				$outCode.= '<input type="Hidden" name="e[sitetitle]" value="1">';
				$theOutput.= $this->pObj->doc->spacer(15);
				$theOutput.= $this->pObj->doc->section($GLOBALS['LANG']->getLL('sitetitle'), $outCode);
			}
			if ($e['description'])	{
				$outCode = '<textarea name="data[description]" rows="5" class="fixed-font enable-tab"'.$this->pObj->doc->formWidthText(48, '', '').'>'.t3lib_div::formatForTextarea($tplRow['description']).'</textarea>';
				$outCode.= '<input type="Hidden" name="e[description]" value="1">';
				$theOutput.= $this->pObj->doc->spacer(15);
				$theOutput.= $this->pObj->doc->section($GLOBALS['LANG']->getLL('description'), $outCode);
			}
			if ($e['resources'])	{
					// Upload
				$outCode = '<input type="File" name="resources"'.$this->pObj->doc->formWidth().' size="50">';
				$outCode.= '<input type="Hidden" name="data[resources]" value="1">';
				$outCode.= '<input type="Hidden" name="e[resources]" value="1">';
				$outCode.= '<BR>' . $GLOBALS['LANG']->getLL('allowedExtensions') . ' <strong>' . $TCA['sys_template']['columns']['resources']['config']['allowed'] . '</strong>';
				$outCode.= '<BR>' . $GLOBALS['LANG']->getLL('maxFilesize') . ' <strong>' . t3lib_div::formatSize($TCA['sys_template']['columns']['resources']['config']['max_size']*1024) . '</strong>';
				$theOutput.= $this->pObj->doc->spacer(15);
				$theOutput.= $this->pObj->doc->section($GLOBALS['LANG']->getLL('uploadResource'), $outCode);

					// New
				$opt = explode(',', $this->pObj->textExtensions);
				$optTags = '';
				foreach ($opt as $extVal) {
					$optTags.= '<option value="'.$extVal.'">.'.$extVal.'</option>';
				}
				$outCode = '<input type="text" name="new_resource"'.$this->pObj->doc->formWidth(20).'>
					<select name="new_resource_ext">'.$optTags.'</select>';
				$outCode.= '<input type="Hidden" name="data[new_resource]" value="1">';
				$theOutput.= $this->pObj->doc->spacer(15);
				$theOutput.= $this->pObj->doc->section($GLOBALS['LANG']->getLL('newTextResource'), $outCode);

					// Make copy
				$rL = $this->resourceListForCopy($this->pObj->id, $template_uid);
				if ($rL)	{
					$theOutput.= $this->pObj->doc->spacer(20);
					$theOutput.= $this->pObj->doc->section($GLOBALS['LANG']->getLL('copyResource'), $rL);
				}

					// Update resource list
				$rL = $this->procesResources($tplRow['resources'], 1);
				if ($rL)	{
					$theOutput.= $this->pObj->doc->spacer(20);
					$theOutput.= $this->pObj->doc->section($GLOBALS['LANG']->getLL('updateResourceList'), $rL);
				}
			}
			if ($e['constants'])	{
				$outCode = '<textarea name="data[constants]" rows="'.$numberOfRows.'" wrap="off" class="fixed-font enable-tab"'.$this->pObj->doc->formWidthText(48, 'width:98%;height:70%', 'off').' class="fixed-font">'.t3lib_div::formatForTextarea($tplRow['constants']).'</textarea>';
				$outCode.= '<input type="Hidden" name="e[constants]" value="1">';
				$theOutput.= $this->pObj->doc->spacer(15);
				$theOutput.= $this->pObj->doc->section($GLOBALS['LANG']->getLL('constants'), '');
				$theOutput.= $this->pObj->doc->sectionEnd().$outCode;
			}
			if ($e['file'])	{
				$path = PATH_site.$TCA['sys_template']['columns']['resources']['config']['uploadfolder'].'/'.$e[file];

				$fI = t3lib_div::split_fileref($e[file]);
				if (@is_file($path) && t3lib_div::inList($this->pObj->textExtensions, $fI['fileext']))	{
					if (filesize($path) < $TCA['sys_template']['columns']['resources']['config']['max_size']*1024)	{
						$fileContent = t3lib_div::getUrl($path);
						$outCode = $GLOBALS['LANG']->getLL('file'). ' <strong>' . $e[file] . '</strong><BR>';
						$outCode.= '<textarea name="edit[file]" rows="'.$numberOfRows.'" wrap="off" class="fixed-font enable-tab"'.$this->pObj->doc->formWidthText(48, 'width:98%;height:70%', 'off').' class="fixed-font">'.t3lib_div::formatForTextarea($fileContent).'</textarea>';
						$outCode.= '<input type="Hidden" name="edit[filename]" value="'.$e[file].'">';
						$outCode.= '<input type="Hidden" name="e[file]" value="'.htmlspecialchars($e[file]).'">';
						$theOutput.= $this->pObj->doc->spacer(15);
						$theOutput.= $this->pObj->doc->section($GLOBALS['LANG']->getLL('editResource'), '');
						$theOutput.= $this->pObj->doc->sectionEnd().$outCode;
					} else {
						$theOutput.= $this->pObj->doc->spacer(15);
						$fileToBig = sprintf($GLOBALS['LANG']->getLL('filesizeExceeded'), $TCA['sys_template']['columns']['resources']['config']['max_size']);
						$filesizeNotAllowed = sprintf($GLOBALS['LANG']->getLL('notAllowed'), $TCA['sys_template']['columns']['resources']['config']['max_size']);
						$theOutput.= $this->pObj->doc->section(
							'<font color=red>' . $fileToBig . '</font>',
							$filesizeNotAllowed,
							0, 0, 0, 1
						);
					}
				}
			}
			if ($e['config'])	{
				$outCode='<textarea name="data[config]" rows="'.$numberOfRows.'" wrap="off" class="fixed-font enable-tab"'.$this->pObj->doc->formWidthText(48,"width:98%;height:70%","off").' class="fixed-font">'.t3lib_div::formatForTextarea($tplRow["config"]).'</textarea>';

				if (t3lib_extMgm::isLoaded('tsconfig_help'))	{
					$url = $BACK_PATH.'wizard_tsconfig.php?mode=tsref';
					$params = array(
						'formName' => 'editForm',
						'itemName' => 'data[config]',
					);
					$outCode.= '<a href="#" onClick="vHWin=window.open(\''.$url.t3lib_div::implodeArrayForUrl('', array('P' => $params)).'\',\'popUp'.$md5ID.'\',\'height=500,width=780,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;">'.t3lib_iconWorks::getSpriteIcon('actions-system-typoscript-documentation-open', array('title'=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:tsRef', true))) . '</a>';
				}

				$outCode.= '<input type="Hidden" name="e[config]" value="1">';
				$theOutput.= $this->pObj->doc->spacer(15);
				$theOutput.= $this->pObj->doc->section($GLOBALS['LANG']->getLL('setup'), '');
				$theOutput.= $this->pObj->doc->sectionEnd().$outCode;
			}

				// Processing:
			$outCode = '';
			$outCode.= $this->tableRow(
				$GLOBALS['LANG']->getLL('title'),
				htmlspecialchars($tplRow['title']),
				'title'
			);
			$outCode.= $this->tableRow(
				$GLOBALS['LANG']->getLL('sitetitle'),
				htmlspecialchars($tplRow['sitetitle']),
				'sitetitle'
			);
			$outCode.= $this->tableRow(
				$GLOBALS['LANG']->getLL('description'),
				nl2br(htmlspecialchars($tplRow['description'])),
				'description'
			);
			$outCode.= $this->tableRow(
				$GLOBALS['LANG']->getLL('resources'),
				$this->procesResources($tplRow['resources']),
				'resources'
			);
			$outCode.= $this->tableRow(
				$GLOBALS['LANG']->getLL('constants'),
				sprintf($GLOBALS['LANG']->getLL('editToView'), (trim($tplRow[constants]) ? count(explode(LF, $tplRow[constants])) : 0)),
				'constants'
			);
			$outCode.= $this->tableRow(
				$GLOBALS['LANG']->getLL('setup'),
				sprintf($GLOBALS['LANG']->getLL('editToView'), (trim($tplRow[config]) ? count(explode(LF, $tplRow[config])) : 0)),
				'config'
			);
			$outCode = '<br /><br /><table class="t3-table-info">' . $outCode . '</table>';

				// Edit all icon:
			$outCode.= '<br /><a href="#" onClick="' . t3lib_BEfunc::editOnClick(rawurlencode('&createExtension=0') .
				'&amp;edit[sys_template][' . $tplRow['uid'] . ']=edit', $BACK_PATH, '') . '"><strong>' .
				t3lib_iconWorks::getSpriteIcon('actions-document-open', array('title'=> 
				$GLOBALS['LANG']->getLL('editTemplateRecord') ))  . $GLOBALS['LANG']->getLL('editTemplateRecord') . '</strong></a>';
			$theOutput.= $this->pObj->doc->section('', $outCode);


				// hook	after compiling the output
			if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postOutputProcessingHook']))	{
				$postOutputProcessingHook =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postOutputProcessingHook'];
				if (is_array($postOutputProcessingHook)) {
					$hookParameters = array(
						'theOutput' => &$theOutput,
						'POST'		=> $POST,
						'e'			=> $e,
						'tplRow'		=> $tplRow,
						'numberOfRows'		=> $numberOfRows
					);
					foreach ($postOutputProcessingHook as $hookFunction)	{
						t3lib_div::callUserFunction($hookFunction, $hookParameters, $this);
					}
				}
			}

		} else {
			$theOutput.= $this->pObj->noTemplate(1);
		}


		return $theOutput;
	}
	
	protected function onTemplateLoad() {
		global $tplRow;
		$tplRow['config'] = $this->checkIncludeLines($tplRow['config']);
		$tplRow['constants'] = $this->checkIncludeLines($tplRow['constants']);
	}
	
	protected function onTemplateSave($row) {
		$row['config'] = $this->extractIncludeLines($row['config']);
		$row['constants'] = $this->extractIncludeLines($row['constants']);
		return $row;
	}
	
	protected function writeTsFile($fileName, $fileContentString, $fileChecksum=NULL) {
		$fileContentString = $this->extractIncludeLines($fileContentString);
		$realFileName = t3lib_div::getFileAbsFileName($fileName);
		if ($fileChecksum) {
			// check if file has changed in the meantime
			if ($fileChecksum != md5_file($realFileName)) {
				throw new Exception(sprintf('File "%s" has changed (expected checksum: "%s")', $realFileName, $fileChecksum));
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
	
	protected function extractIncludeLines($content) {
		
		### <INCLUDE_TYPOSCRIPT: source="FILE: EXT:tsf/test1.ts"> BEGIN:
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
					$this->writeTsFile($fileName, $fileContentString, $fileChecksum);
					
					// reset variable
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
	 * @param	boolean		When set an array containing the resulting typoscript and all included files will get returned
	 * @return	string		Complete TypoScript with includes added.
	 * @static
	 */
	protected function checkIncludeLines($string, $cycle_counter=1) {
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

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tsf/class.ux_tx_tstemplateinfo.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tsf/class.ux_tx_tstemplateinfo.php']);
}

?>