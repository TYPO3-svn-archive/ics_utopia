<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 In Cité Solution <technique@incitesolution.fr>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Module 'New site' for the 'ics_utopia' extension.
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */



	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require ("conf.php");
require ($BACK_PATH."init.php");
require ($BACK_PATH."template.php");
$LANG->includeLLFile('EXT:impexp/app/locallang.php');
$LANG->includeLLFile("EXT:ics_utopia/mod5/locallang.xml");
require_once (PATH_t3lib."class.t3lib_scbase.php");
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]

require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_session.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_config.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_form_manager.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_mail_notify.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_pdf_generator.php'));

class tx_icsutopia_module5 extends t3lib_SCbase {
	var $pageinfo;

	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();

		/*
		if (t3lib_div::_GP("clear_all_cache"))	{
			$this->include_once[]=PATH_t3lib."class.t3lib_tcemain.php";
		}
		*/
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $LANG;
		$this->MOD_MENU = Array (
			"function" => Array (
			)
		);
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$this->access = is_array($this->pageinfo) ? 1 : 0;
	
			// initialize doc
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->setModuleTemplate(t3lib_extMgm::extPath('ics_utopia') . 'mod5/mod_template.html');
		$this->doc->backPath = $BACK_PATH;

		if (($this->id && $this->access) || ($BE_USER->user["admin"] && !$this->id))	{

				// Draw the form
			$this->doc->form='<form action="" method="POST" name="newsite" onsubmit="return TBE_EDITOR_checkSubmit(1);" enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '">';

			$this->doc->loadJavascriptLib($BACK_PATH . 'contrib/prototype/prototype.js');
				// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';

			$this->content.=$this->doc->header($LANG->getLL("title"));
			$this->content.=$this->doc->spacer(5);
				// Render content:
			$this->moduleContent();
		} else {
				// If no access or if ID == zero
		}
		$docHeaderButtons = $this->getButtons();

			// compile document
		//$markers['FUNC_MENU'] = t3lib_BEfunc::getFuncMenu(0, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
		$markers['CONTENT'] = $this->content;

				// Build the <body> for the module
		$this->content = $this->doc->startPage($LANG->getLL("title"));
		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()	{

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent()	{
		utopia_form_manager::initPath();
		$this->_session = t3lib_div::makeInstance('utopia_session');
		$session = & $this->_session;
		$formId = $session->get(array('form'));
		if ($formId == null)
			$formId = utopia_form_manager::getStartFormId();
			
		$action = t3lib_div::_POST('action');
		
		if(!is_array(t3lib_div::_POST('action')))
		{
			$action = array();
		}
		
		if(is_array(t3lib_div::_GET('action')))
		{
			$action = array_merge($action,t3lib_div::_GET('action'));
		}
		
		if ($action)
			foreach($action as $action_key => $action_text)
				if (!empty($action_text))
				{
					$action = $action_key;
					break;
				}
		if ($action != null)
		{
			switch ($action)
			{
			case 'prev':
				$form = & utopia_form_manager::previousAction($formId);
				break;
			case 'next':
				$form = & utopia_form_manager::nextAction($formId);
				break;
			case 'valid':
				$form = & $this->validAction($formId);
				break;
			case 'return':
				$returnurl = $this->_session->get(array('returnurl'));
				$this->_session->reset();
				if ($returnurl)
				{
					$this->_session->reset();
					$this->_session->saveSession();
					header('Location: ' . $returnurl);
					exit();
				}
				break;
			case 'pdf':
				$pdfRenderer = t3lib_div::makeInstance('utopia_pdf_generator');
				$pdf = $pdfRenderer->printPdf($this->_session);
				exit;
			default:
				$form = & utopia_form_manager::getForm($formId);
				$this->saveFormData($formId, $form);
			}
		}
		else
			$form = & utopia_form_manager::getForm($formId);
		if ($form !== null)
		{
			if ($this->_session->get(array('returnurl')))
			{
				$this->content .= '<p><a href="#" name="action[return]_btn" onclick="document.forms.item(\'newsite\').elements.namedItem(\'action[return]\').value = \'1\'; document.forms.item(\'newsite\').submit();">' . $GLOBALS['LANG']->getLL('action.return') . '</a>
						<input type="hidden" name="action[return]" value="" /></p>';
			}
			$formData = $session->getFormData($formId);
			//var_dump('Dump formdata');
			//var_dump($formId);
			//var_dump($formData);
			if ($formId != 'last')
			{
				$content = $form->renderForm('be', $formData);
				$forms = & $form->getForms();
				$errors = $form->getErrors();
				if (!empty($errors))
					$errors = implode('', array_map(create_function('$val', 'return \'
				<p style="color: red">\' . $val . \'</p>\';'), $errors));
				else
					$errors = '';
				$this->content .= $forms->getTopJS() . $errors . str_replace('<!--###FOOTER###-->', $forms->getActionButtons($formId != 1, $formId != 'last'), $content) . $forms->getBottomJS();
			}
			else
			{
				$this->content .= '<script language="javascript" type="text/javascript">
function TBE_EDITOR_checkSubmit()
{
	return true;
}
</script>';
				$this->content .= $form->renderForm('be');
			}
			$session->set(array('form'), $formId);
			$session->saveSession();
		}
		else
		{
			$session->set(array('form'), null);
			$session->saveSession();
		}
	}
	
	function & validAction(& $formId){
		$config = t3lib_div::makeInstance('utopia_config');
		$name = $this->_session->getT3DFile();
		$cheminArr = PATH_site . 'fileadmin/' . $config->getConfig('storage.requests') . 'utopia_' . $this->_session->get(array('forms', 1, 'tx_icsutopia_site', '1', 'title')) . '.t3d';
		$cheminDep = $name;
		$returnurl = $this->_session->get(array('returnurl'));
		$admins = $config->getConfig('mail.adminusers');
		$admins = ($admins) ? (explode(',', $admins)) : (array());
		$mailer = t3lib_div::makeInstance('utopia_mail_notify');
		$t3d = utopia_t3d_editor::loadFile($cheminDep);
		$user = & $this->_session->getUser();
		$markers = array('###SITE_NAME###' => $this->_session->get(array('forms', 1, 'tx_icsutopia_site', '1', 'title')));
		
		$t3d = utopia_t3d_editor::loadFile($cheminDep, 1);
		$impexp = t3lib_div::makeInstance('tx_impexp');
		$impexp->dat = $t3d;
		$impexp->compress = false;
		$res = $impexp->compileMemoryToFileContent('xml');
		t3lib_div::writeFile(PATH_site . 'fileadmin/' . basename($cheminDep) . '.xml', $res);
		
		$this->_session->reset();
		if ($returnurl)
		{
			$this->_session->saveSession();
			header('Location: ' . $returnurl);
			exit;
		}
		
		$userid = strtolower(TYPO3_MODE) . '_' . $user->user['uid'];
		foreach ($admins as $admin)
		{
			$mailer->notify($userid, 'be_' . $admin, $markers, 'mail.admin');
		}
		
		rename($cheminDep, $cheminArr);
		$formId = utopia_form_manager::getStartFormId();
		$form = utopia_form_manager::getForm($formId);
		$this->content .= '<p>' . $GLOBALS['LANG']->getLL('action.validate.success') . '<p>';
		return $form;
	}
	
	function saveFormData($formId, & $form)
	{
		//var_dump('Save formdata');
		$formData = $form->getFormData();
		$this->_session->setFormData($formId, $formData);
	}
	
	function updateT3D(& $form, $formId)
	{
		utopia_form_manager::updateT3D($form, $formId);
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons()	{
		global $TCA, $LANG, $BACK_PATH, $BE_USER;

		$buttons = array(
			'shortcut' => '',
			'save' => '',
		);

		if ($this->id && $this->access)	{
				// Shortcut
			if ($BE_USER->mayMakeShortcut())	{
				$buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
			}
		} else {
				// Shortcut
			if ($BE_USER->mayMakeShortcut())	{
				$buttons['shortcut'] = $this->doc->makeShortcutIcon('id', '', $this->MCONF['name']);
			}
		}

		return $buttons;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_utopia/mod5/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_utopia/mod5/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_icsutopia_module5');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>
