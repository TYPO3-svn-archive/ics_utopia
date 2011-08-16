<?php

$SOBE->tca = array(
	//# cat=basic/template/1; type=int+; label=Site template pages: The page where the site templates are created.
	'template.root' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.template.root',
		'config' => array(
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => 'pages',
			'minitems' => 1,
			'maxitems' => 1,
			'size' => 1,
		),
	),
	//# cat=basic/template/2; type=string; label=BE template users: Comma-separated list of the back-end user templates. A user is defined by its id and its role joigned by an hyphen.
	'template.beusers' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.template.beusers',
		'config' => array(
			'type' => 'input',
			'size' => '48',
		),
	),
	//# cat=basic/template/3; type=int+; label=BE template group: The group id used as a template for the created users main group.
	'template.begroup' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.template.begroup',
		'config' => array(
			'type' => 'select',
			'foreing_table' => 'be_groups',
			'size' => 1,
			'minitems' => 1,
			'maxitems' => 1,
		),
	),
	//# cat=basic/template/4; type=string; label=Storage folder name: Name of the main storage folder which exists in each template.
	'template.storage' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.template.storage',
		'config' => array(
			'type' => 'input',
			'size' => '48',
			'default' => 'Storage',
		),
	),
	//# cat=basic/template/5; type=boolean; label=Backend users as frontend users: Create frontend user counterpart for backend users.
	'template.createfe' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.template.createfe',
		'config' => array(
			'type' => 'check',
		),
	),
	//# cat=basic/template/6; type=string; label=Frontend users group name: Name of the main frontend group where to put users into. This group have to exists in each template.
	'template.fegroup' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.template.fegroup',
		'config' => array(
			'type' => 'input',
			'size' => '48',
			'default' => 'Private access',
		),
	),
	//# cat=basic/extension/1; type=string; label=Forms order: The order of the extension forms in the creation course.
	'forms.order' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.forms.order',
		'config' => array(
			'type' => 'input',
			'size' => '48',
		),
	),
	//# cat=basic/extension/2; type=string; label=Disabled extensions: The comma-separated list of the disabled extensions.
	'disabledexts' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.disabledexts',
		'config' => array(
			'type' => 'input',
			'size' => '48',
		),
	),
	//# cat=basic/mail/1; type=file[phtml]; label=Admin notification mail template: The template file used to notify the administrator about a new site. Translation can be guessed using language extension (ie. .fr or .en).
	'mail.adminfile' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.mail.adminfile',
		'config' => array(
			'type' => 'select',
			'fileFolder' => 'EXT:ics_utopia/res/',
			'fileFolder_extList' => 'phtml',
			'maxitems' => 1,
			'minitems' => 1,
			'size' => 1,
		),
	),
	//# cat=basic/mail/2; type=string; label=Admin notification mail subject: The subject template for the notification mail sent to the administrator. Usual translation can be used.
	'mail.adminsubject' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.mail.adminsubject',
		'config' => array(
			'type' => 'input',
			'size' => '48',
			'default' => 'New site',
		),
	),
	//# cat=basic/mail/3; type=file[phtml]; label=Accept notification mail template: The template file used to notify the requester about the acceptance of his site. Translation can be guessed using language extension (ie. .fr or .en).
	'mail.acceptfile' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.mail.acceptfile',
		'config' => array(
			'type' => 'select',
			'fileFolder' => 'EXT:ics_utopia/res/',
			'fileFolder_extList' => 'phtml',
			'maxitems' => 1,
			'minitems' => 1,
			'size' => 1,
		),
	),
	//# cat=basic/mail/4; type=string; label=Accept notification mail subject: The subject template for the acceptance notification mail sent to the requester. Usual translation can be used.
	'mail.acceptsubject' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.mail.acceptsubject',
		'config' => array(
			'type' => 'input',
			'size' => '48',
			'default' => 'Site accepted',
		),
	),
	//# cat=basic/mail/5; type=file[phtml]; label=Reject notification mail template: The template file used to notify the requester about the rejection of his site. Translation can be guessed using language extension (ie. .fr or .en).
	'mail.rejectfile' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.mail.rejectfile',
		'config' => array(
			'type' => 'select',
			'fileFolder' => 'EXT:ics_utopia/res/',
			'fileFolder_extList' => 'phtml',
			'maxitems' => 1,
			'minitems' => 1,
			'size' => 1,
		),
	),
	//# cat=basic/mail/6; type=string; label=Reject notification mail subject: The subject template for the rejection notification mail sent to the requester. Usual translation can be used.
	'mail.rejectsubject' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.mail.rejectsubject',
		'config' => array(
			'type' => 'input',
			'size' => '48',
			'default' => 'Site rejected',
		),
	),
	//# cat=basic/mail/7; type=string; label=Administrators: The comma-separated list of UTOPIA's administrators uids.
	'mail.adminusers' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.mail.adminusers',
		'config' => array(
			'type' => 'select',
			'foreign_table' => 'be_users',
			'size' => 5,
			'maxitems' => 20,
			'minitems' => 1,
		),
	),
	//# cat=basic/storage/1; type=string; label=Request folder: fileadmin/ relative path where to save the requests.
	'storage.requests' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.storage.requests',
		'config' => array(
			'type' => 'input',
			'size' => '48',
		),
	),
	//# cat=basic/storage/2; type=string; label=Archive folder: fileadmin/ relative path where to save accepted requests.
	'storage.archives' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.storage.archives',
		'config' => array(
			'type' => 'input',
			'size' => '48',
		),
	),
	//# cat=basic/storage/3; type=string; label=Old fileadmin: fileadmin/ relative path where the root of the site templates are. The match is done by path segment. An empty path segment (nothing between two slashes) means the name is not relevant. A not empty one means must match. When this root match, it is replaced by the new root.
	'storage.oldroot' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.storage.oldroot',
		'config' => array(
			'type' => 'input',
			'size' => '48',
		),
	),
	//# cat=basic/storage/4; type=string; label=New fileadmin: fileadmin/ relative path where to save the site specific files. If no ###TITLE### marker is found, the title is appended to the path.
	'storage.newroot' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.storage.newroot',
		'config' => array(
			'type' => 'input',
			'size' => '48',
			'default' => '###TITLE###/',
		),
	),
	//# cat=basic/storage/5; type=int+; label=Site cointainer folder; The sysfolder page where to import sites.
	'storage.siteroot' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.storage.siteroot',
		'config' => array(
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => 'pages',
			'size' => '1',
			'minitems' => 1,
			'maxitems' => 1,
		),
	),
	//# cat=basic/import/1; type=string; label=Static tables: Comma-separated list of the static tables set to define when exporting site templates.
	'import.statics' => array(
		'label' => 'LLL:EXT:ics_utopia/mod2/locallang.xml:conf.import.statics',
		'config' => array(
			'type' => 'input',
			'size' => '48',
			'default' => 'sys_languages',
		),
	),
);
