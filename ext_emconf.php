<?php

########################################################################
# Extension Manager/Repository config file for ext "tsf".
#
# Auto generated 30-10-2010 03:06
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'TypoScript Files',
	'description' => 'This extension allows you to edit TypoScript content from files within the t3editor',
	'category' => 'be',
	'author' => 'Fabrizio Branca',
	'author_email' => 'mail@fabrizio-branca.de',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.0.3',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.2.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			't3editor' => '',
		),
	),
	'_md5_values_when_last_written' => 'a:9:{s:16:"class.tx_tsf.php";s:4:"4f1a";s:22:"class.tx_tsf_hooks.php";s:4:"7c93";s:45:"class.ux_tx_t3editor_hooks_tstemplateinfo.php";s:4:"dbce";s:30:"class.ux_tx_tstemplateinfo.php";s:4:"3826";s:12:"ext_icon.gif";s:4:"89b0";s:17:"ext_localconf.php";s:4:"28f8";s:8:"test1.ts";s:4:"b1af";s:8:"test2.ts";s:4:"a787";s:8:"test3.ts";s:4:"74ba";}',
	'suggests' => array(
	),
);

?>