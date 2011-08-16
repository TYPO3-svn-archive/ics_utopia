<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_icsutopia_site"] = Array (
	"ctrl" => $TCA["tx_icsutopia_site"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "url,be_group,base_model,title,main_storage"
	),
	"feInterface" => $TCA["tx_icsutopia_site"]["feInterface"],
	"columns" => Array (
		"url" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:ics_utopia/locallang_db.xml:tx_icsutopia_site.url",		
			"config" => Array (
				"readonly" => "1",
				"type" => "input",
				"size" => "15",
				"max" => "255",
				"eval" => "required,trim,lower",
/*				"wizards" => Array(
					"_PADDING" => 2,
					"link" => Array(
						"type" => "popup",
						"title" => "Link",
						"icon" => "link_popup.gif",
						"script" => "browse_links.php?mode=wizard",
						"JSopenParams" => "height=300,width=500,status=0,menubar=0,scrollbars=1"
					)
				)*/
			)
		),
		"be_group" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:ics_utopia/locallang_db.xml:tx_icsutopia_site.be_group",		
			"config" => Array (
				"readonly" => "1",
				"type" => "select",	
				"foreign_table" => "be_groups",	
				"foreign_table_where" => "AND be_groups.pid=###SITEROOT### ORDER BY be_groups.uid",	
				"size" => 1,	
				"minitems" => 1,
				"maxitems" => 1,
			)
		),
		"base_model" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:ics_utopia/locallang_db.xml:tx_icsutopia_site.base_model",		
			"config" => Array (
				"readonly" => "1",
				"type" => "input",	
				"size" => "30",	
				"max" => "100",	
				"eval" => "required,trim",
			)
		),
		"title" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:ics_utopia/locallang_db.xml:tx_icsutopia_site.title",		
			"config" => Array (
				"readonly" => "1",
				"type" => "input",	
				"size" => "30",	
				"max" => "100",	
				"eval" => "required,trim",
			)
		),
		"main_storage" => Array (        
			"exclude" => 1,        
			"label" => "LLL:EXT:ics_utopia/locallang_db.xml:tx_icsutopia_site.main_storage",        
			"config" => Array (
				"readonly" => "1",
				"type" => "group",    
				"internal_type" => "db",    
				"allowed" => "pages",    
				"size" => 1,    
				"minitems" => 0,
				"maxitems" => 1,
            )
        ),

	),
	"types" => Array (
		"0" => Array("showitem" => "title;;1;;1-1-1, be_group;;;;2-2-2, base_model, main_storage;;;;3-3-3")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "url")
	)
);
?>
