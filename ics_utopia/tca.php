<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_icsutopia_site'] = array (
	'ctrl' => $TCA['tx_icsutopia_site']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'url,be_group,base_model,title,main_storage'
	),
	'feInterface' => $TCA['tx_icsutopia_site']['feInterface'],
	'columns' => array (
		'url' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:ics_utopia/locallang_db.xml:tx_icsutopia_site.url',		
			'config' => array (
				'readonly' => '1',
				'type'     => 'input',
				'size'     => '15',
				'max'      => '255',
				'eval'     => 'required,trim',
				/*'wizards'  => array(
					'_PADDING' => 2,
					'link'     => array(
						'type'         => 'popup',
						'title'        => 'Link',
						'icon'         => 'link_popup.gif',
						'script'       => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					)
				)*/
			)
		),
		'be_group' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:ics_utopia/locallang_db.xml:tx_icsutopia_site.be_group',		
			'config' => array (
				'readonly' => '1',
				'type' => 'select',	
				'foreign_table' => 'be_groups',	
				'foreign_table_where' => 'ORDER BY be_groups.uid',	
				'size' => 1,	
				'minitems' => 1,
				'maxitems' => 1,
			)
		),
		'base_model' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:ics_utopia/locallang_db.xml:tx_icsutopia_site.base_model',		
			'config' => array (
				'readonly' => '1',
				'type' => 'input',	
				'size' => '30',	
				'max' => '100',	
				'eval' => 'required,trim',
			)
		),
		'title' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ics_utopia/locallang_db.xml:tx_icsutopia_site.title',		
			'config' => array (
				'readonly' => '1',
				'type' => 'input',	
				'size' => '30',	
				'max' => '100',	
				'eval' => 'required,trim',
			)
		),
		'main_storage' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ics_utopia/locallang_db.xml:tx_icsutopia_site.main_storage',		
			'config' => array (
				'readonly' => '1',
				'type' => 'group',	
				'internal_type' => 'db',	
				'allowed' => 'pages',	
				'size' => 1,	
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'title;;1;;1-1-1, be_group;;;;2-2-2, base_model, main_storage;;;;3-3-3')
	),
	'palettes' => array (
		'1' => array('showitem' => 'url')
	)
);
?>