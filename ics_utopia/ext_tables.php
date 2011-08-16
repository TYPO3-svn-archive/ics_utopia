<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::allowTableOnStandardPages('tx_icsutopia_site');

$TCA['tx_icsutopia_site'] = Array (
	'ctrl' => array (
		'title' => 'LLL:EXT:ics_utopia/locallang_db.xml:tx_icsutopia_site',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'fe_cruser_id' => 'fe_cruser_id',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY) . 'icon_tx_icsutopia_site.gif',
	),
);


if (TYPO3_MODE == 'BE')	{
	t3lib_extMgm::addModulePath('txicsutopiaM1', t3lib_extMgm::extPath($_EXTKEY) . 'mod1/');
	t3lib_extMgm::addModule('txicsutopiaM1', '', '', t3lib_extMgm::extPath($_EXTKEY) . 'mod1/');
}


if (TYPO3_MODE == 'BE')	{
	t3lib_extMgm::addModulePath('txicsutopiaM1_txicsutopiaM2', t3lib_extMgm::extPath($_EXTKEY) . 'mod2/');
	t3lib_extMgm::addModule('txicsutopiaM1', 'txicsutopiaM2', '', t3lib_extMgm::extPath($_EXTKEY) . 'mod2/');
}


if (TYPO3_MODE == 'BE')	{
	t3lib_extMgm::addModulePath('txicsutopiaM1_txicsutopiaM3', t3lib_extMgm::extPath($_EXTKEY) . 'mod3/');
	t3lib_extMgm::addModule('txicsutopiaM1', 'txicsutopiaM3', '', t3lib_extMgm::extPath($_EXTKEY) . 'mod3/');
}


if (TYPO3_MODE == 'BE')	{
	t3lib_extMgm::addModulePath('txicsutopiaM1_txicsutopiaM4', t3lib_extMgm::extPath($_EXTKEY) . 'mod4/');
	t3lib_extMgm::addModule('txicsutopiaM1', 'txicsutopiaM4', '', t3lib_extMgm::extPath($_EXTKEY) . 'mod4/');
}


if (TYPO3_MODE == 'BE')	{
	t3lib_extMgm::addModulePath('txicsutopiaM1_txicsutopiaM5', t3lib_extMgm::extPath($_EXTKEY) . 'mod5/');
	t3lib_extMgm::addModule('txicsutopiaM1', 'txicsutopiaM5', '', t3lib_extMgm::extPath($_EXTKEY) . 'mod5/');
}


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types'][$_EXTKEY.'_pi1']['showitem'] = 'CType;;4;button;1-1-1, header;;3;;2-2-2';


t3lib_extMgm::addPlugin(array(
	'LLL:EXT:ics_utopia/locallang_db.xml:tt_content.CType_pi1', 
	$_EXTKEY . '_pi1',
	t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif'
), 'CType');

t3lib_extMgm::addLLrefForTCAdescr('utopiaconf', 'EXT:ics_utopia/mod2/locallang_csh_mod2_fakedb.xml');
t3lib_extMgm::addLLrefForTCAdescr('tx_icsutopia_site', 'EXT:ics_utopia/locallang_csh_site.xml');
?>
