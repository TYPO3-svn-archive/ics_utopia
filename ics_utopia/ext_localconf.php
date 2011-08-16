<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_icsutopia_pi1.php', '_pi1', 'CType', 0);

$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_softrefproc.php'] = t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_softrefproc.php'); // TODO: Check if a hook is now possible.
$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/impexp/class.tx_impexp.php'] = t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_impexp.php');

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ics_utopia']['CSH']['1']['tx_icsutopia_site'][] = 'EXT:ics_utopia/lib/csh/locallang_csh_form1.xml';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ics_utopia']['CSH']['2']['be_users'][] = 'EXT:ics_utopia/lib/csh/locallang_csh_form2.xml';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ics_utopia']['CSH']['3']['fe_users'][] = 'EXT:ics_utopia/lib/csh/locallang_csh_form3.xml';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ics_utopia']['CSH']['4']['tx_icsutopia_site'][] = 'EXT:ics_utopia/lib/csh/locallang_csh_form4.xml';
?>
