<?php

########################################################################
# Extension Manager/Repository config file for ext: "ics_utopia"
#
# Auto generated 11-07-2008 17:48
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'UTOPIA',
	'description' => 'Usine TYPO3 Ouverte de Production Internet Automatisée. The core extension for automated creation of sites using configured models on a multi-sites platform.',
	'category' => 'module',
	'author' => 'In Cite Solution',
	'author_email' => 'technique@incitesolution.fr',
	'shy' => '',
	'dependencies' => 'fpdf',
	'conflicts' => '',
	'priority' => '',
	'module' => 'mod1,mod2,mod3,mod4,mod5',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'In Cite Solution',
	'version' => '1.0.0',
	'constraints' => array(
		'depends' => array(
			'fpdf' => '',
			'php' => '5.2.0-0.0.0',
			'typo3' => '4.3.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);

?>