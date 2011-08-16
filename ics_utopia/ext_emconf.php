<?php

########################################################################
# Extension Manager/Repository config file for ext "ics_utopia".
#
# Auto generated 16-08-2011 16:01
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
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
	'module' => '',
	'state' => 'stable',
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
	'_md5_values_when_last_written' => 'a:90:{s:9:"ChangeLog";s:4:"ffb7";s:10:"README.txt";s:4:"ee2d";s:21:"ext_conf_template.txt";s:4:"a1c9";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"ca1d";s:14:"ext_tables.php";s:4:"bdba";s:14:"ext_tables.sql";s:4:"1121";s:26:"icon_tx_icsutopia_site.gif";s:4:"475a";s:22:"locallang_csh_site.xml";s:4:"2ce1";s:16:"locallang_db.xml";s:4:"546b";s:25:"locallang_db_be_users.xml";s:4:"e254";s:7:"tca.php";s:4:"09eb";s:10:"utopia.gif";s:4:"d449";s:19:"doc/wizard_form.dat";s:4:"3c64";s:20:"doc/wizard_form.html";s:4:"1d62";s:17:"lib/class.PDF.php";s:4:"29e4";s:21:"lib/class.pdf_gen.php";s:4:"34b7";s:27:"lib/class.utopia_config.php";s:4:"1e07";s:36:"lib/class.utopia_first_form_base.php";s:4:"1233";s:30:"lib/class.utopia_form_base.php";s:4:"9c6a";s:33:"lib/class.utopia_form_manager.php";s:4:"6853";s:30:"lib/class.utopia_form_path.php";s:4:"342e";s:26:"lib/class.utopia_forms.php";s:4:"64df";s:27:"lib/class.utopia_impexp.php";s:4:"3fb3";s:32:"lib/class.utopia_mail_notify.php";s:4:"c09b";s:40:"lib/class.utopia_model_selector_base.php";s:4:"4ed5";s:29:"lib/class.utopia_pdf_base.php";s:4:"2677";s:34:"lib/class.utopia_pdf_generator.php";s:4:"4014";s:36:"lib/class.utopia_postAction_base.php";s:4:"04b1";s:28:"lib/class.utopia_session.php";s:4:"de13";s:32:"lib/class.utopia_softrefproc.php";s:4:"6f73";s:31:"lib/class.utopia_t3d_editor.php";s:4:"8e2e";s:22:"lib/pdf_styles.inc.php";s:4:"8362";s:31:"lib/csh/locallang_csh_form1.xml";s:4:"f564";s:31:"lib/csh/locallang_csh_form2.xml";s:4:"fcf0";s:31:"lib/csh/locallang_csh_form3.xml";s:4:"faa3";s:31:"lib/csh/locallang_csh_form4.xml";s:4:"9065";s:32:"lib/forms/class.utopia_form1.php";s:4:"b292";s:32:"lib/forms/class.utopia_form2.php";s:4:"b7b4";s:32:"lib/forms/class.utopia_form3.php";s:4:"e5af";s:32:"lib/forms/class.utopia_form4.php";s:4:"bfa0";s:36:"lib/forms/class.utopia_form_last.php";s:4:"298d";s:34:"lib/pdf/class.utopia_pdf_form1.php";s:4:"af7f";s:34:"lib/pdf/class.utopia_pdf_form2.php";s:4:"01df";s:34:"lib/pdf/class.utopia_pdf_form3.php";s:4:"0d88";s:34:"lib/pdf/class.utopia_pdf_form4.php";s:4:"402b";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"404e";s:14:"mod1/index.php";s:4:"2dc8";s:18:"mod1/locallang.xml";s:4:"36a1";s:22:"mod1/locallang_mod.xml";s:4:"68a2";s:22:"mod1/mod_template.html";s:4:"7c59";s:19:"mod1/moduleicon.gif";s:4:"8074";s:14:"mod2/clear.gif";s:4:"cc11";s:13:"mod2/conf.php";s:4:"c7de";s:14:"mod2/index.php";s:4:"9135";s:18:"mod2/locallang.xml";s:4:"dc35";s:34:"mod2/locallang_csh_mod2_fakedb.xml";s:4:"8546";s:22:"mod2/locallang_mod.xml";s:4:"c85f";s:22:"mod2/mod_template.html";s:4:"7c59";s:19:"mod2/moduleicon.gif";s:4:"8074";s:18:"mod2/tca_equiv.php";s:4:"9b2a";s:14:"mod3/clear.gif";s:4:"cc11";s:13:"mod3/conf.php";s:4:"71bc";s:14:"mod3/index.php";s:4:"c402";s:18:"mod3/locallang.xml";s:4:"765f";s:22:"mod3/locallang_mod.xml";s:4:"2288";s:22:"mod3/mod_template.html";s:4:"7c59";s:19:"mod3/moduleicon.gif";s:4:"8074";s:14:"mod4/clear.gif";s:4:"cc11";s:13:"mod4/conf.php";s:4:"6fca";s:14:"mod4/index.php";s:4:"e7ef";s:18:"mod4/locallang.xml";s:4:"8a36";s:22:"mod4/locallang_mod.xml";s:4:"9cf6";s:22:"mod4/mod_template.html";s:4:"7c59";s:19:"mod4/moduleicon.gif";s:4:"8074";s:14:"mod5/clear.gif";s:4:"cc11";s:13:"mod5/conf.php";s:4:"ae2f";s:14:"mod5/index.php";s:4:"ec23";s:18:"mod5/locallang.xml";s:4:"1b2b";s:22:"mod5/locallang_mod.xml";s:4:"f9a5";s:22:"mod5/mod_template.html";s:4:"7c59";s:19:"mod5/moduleicon.gif";s:4:"8074";s:30:"pi1/class.tx_icsutopia_pi1.php";s:4:"0616";s:28:"res/acceptNotification.phtml";s:4:"2fcc";s:31:"res/acceptNotification.phtml.fr";s:4:"6aa4";s:19:"res/mailAdmin.phtml";s:4:"6df9";s:22:"res/mailAdmin.phtml.fr";s:4:"6e9d";s:28:"res/rejectNotification.phtml";s:4:"0fb1";s:31:"res/rejectNotification.phtml.fr";s:4:"0fb1";}',
);

?>