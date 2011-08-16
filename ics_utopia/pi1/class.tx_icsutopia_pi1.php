<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Pierrick Caillon <pierrick@in-cite.net>
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_session.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_config.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_form_manager.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_mail_notify.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_pdf_generator.php'));
require_once(t3lib_extMgm::extPath('lang', 'lang.php'));
require_once(PATH_t3lib.'class.t3lib_befunc.php');
require_once(PATH_t3lib.'class.t3lib_userauth.php');
require_once(PATH_t3lib.'class.t3lib_userauthgroup.php');
require_once(PATH_t3lib.'class.t3lib_beuserauth.php');
require_once(PATH_t3lib.'class.t3lib_tsfebeuserauth.php');


/**
 * Plugin 'Site creation' for the 'ics_utopia' extension.
 *
 * @author	Pierrick Caillon <pierrick@in-cite.net>
 * @package	TYPO3
 * @subpackage	tx_icsutopia
 */
class tx_icsutopia_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_icsutopia_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_icsutopia_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'ics_utopia';	// The extension key.
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
		$language = & $GLOBALS['LANG'];
		$language->init($this->LLkey);
		$language->includeLLFile(t3lib_extMgm::extPath('ics_utopia', 'mod5/locallang.xml'));
		
		/*return 'Hello World!<HR>
			Here is the TypoScript passed to the method:'.
					t3lib_div::view_array($conf);*/
		return $this->pi_wrapInBaseClass('
		<form action="" method="POST" name="newsite" enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '">
			'.$this->moduleContent().'
		</form>');
	}
	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent() 
	{
		utopia_form_manager::initPath();
		$this->_session = t3lib_div::makeInstance('utopia_session');
		$session = & $this->_session;
		$formId = $session->get(array('form'));
		if ($formId == null)
			$formId = utopia_form_manager::getStartFormId();
		$action = t3lib_div::_POST('action');
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
			$formData = $session->getFormData($formId);
			//var_dump('Dump formdata');
			//var_dump($formId);
			//var_dump($formData);
			if ($formId != 'last')
			{
				$content = $form->renderForm('fe', $formData);
				$forms = & $form->getForms();
				$errors = $form->getErrors();
				if (!empty($errors))
					$errors = implode('', array_map(create_function('$val', 'return \'
				<p class="utopia_error">\' . $val . \'</p>\';'), $errors));
				else
					$errors = '';
				$content = $forms->getTopJS() . $errors . str_replace('<!--###FOOTER###-->', $forms->getActionButtons($formId != 1, $formId != 'last'), $content) . $forms->getBottomJS();
			}
			else
			{
				$content .= $form->renderForm('fe');
			}
			$session->set(array('form'), $formId);
			$session->saveSession();
		}
		else
		{
			$session->set(array('form'), null);
			$session->saveSession();
		}
		if ($this->success)
			$content = $this->success . $content;
		return $content;
	}
	
	function & validAction(& $formId){
		$config = t3lib_div::makeInstance('utopia_config');
		$name = $this->_session->getT3DFile();
		$cheminArr = PATH_site . 'fileadmin/' . $config->getConfig('storage.requests') . 'utopia_' . $this->_session->get(array('forms', 1, 'tx_icsutopia_site', '1', 'title')) . '.t3d_fe';
		$cheminDep = $name;
		$admins = $config->getConfig('mail.adminusers');
		$admins = ($admins) ? (explode(',', $admins)) : (array());
		$mailer = t3lib_div::makeInstance('utopia_mail_notify');
		$user = & $this->_session->getUser();
		$markers = array('###SITE_NAME###' => $this->_session->get(array('forms', 1, 'tx_icsutopia_site', '1', 'title')));
		$userid = strtolower(TYPO3_MODE) . '_' . $user->user['uid'];
		foreach ($admins as $admin)
		{
			$mailer->notify($userid, 'be_' . $admin, $markers, 'mail.admin');
		}
		$this->_session->reset();
		rename($cheminDep, $cheminArr);
		$formId = utopia_form_manager::getStartFormId();
		$form = utopia_form_manager::getForm($formId);
		$this->success .= '<p>' . $GLOBALS['LANG']->getLL('action.validate.success') . '<p>';
		return $form;
	}
	
	function saveFormData($formId, & $form)
	{
		//var_dump('Save formdata');
		$formData = $form->getFormData();
		$this->_session->setFormData($formId, $formData);
	}
	
	function simulateAdminBE()
	{
		$GLOBALS['BE_USER'] = t3lib_div::makeInstance("t3lib_tsfeBeUserAuth");
		$be_user_obj = & $GLOBALS['BE_USER'];

		//let's get the record for the backend admin we want to simulate in the frontend
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $be_user_obj->user_table, 'pid = 0 AND uid = 1 ' . $be_user_obj->user_where_clause());

		//if no be_user found, return
		if ($tempuser = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))
			$beuser = $tempuser;
		else 
			return null;

		//faking a be-session for our frontend user
		//The be-session get's the same id and hashlock as the current fe-session.
		$insertFields = array(
			'ses_id' => $GLOBALS["TSFE"]->fe_user->user["ses_id"],
			'ses_name' => $be_user_obj->name,
			'ses_iplock' => $beuser['disableIPlock'] ? '[DISABLED]' : $be_user_obj->ipLockClause_remoteIPNumber($be_user_obj->lockIP),
			'ses_hashlock' => $GLOBALS["TSFE"]->fe_user->user["ses_hashlock"],
			'ses_userid' => $beuser[$be_user_obj->userid_column],
			'ses_tstamp' => $GLOBALS['EXEC_TIME'] );

		$GLOBALS['TYPO3_DB']->exec_INSERTquery($be_user_obj->session_table, $insertFields);
	
		$be_user_obj->dontSetCookie = true;
		$be_user_obj->writeDevLog = false;
		$_COOKIE[$be_user_obj->name] = addslashes($GLOBALS["TSFE"]->fe_user->user["ses_id"]);
		$be_user_obj->OS = TYPO3_OS;
		$be_user_obj->lockIP = $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP'];
		$be_user_obj->start();
		$be_user_obj->unpack_uc('');
		if ($be_user_obj->user['uid'])	{
			$be_user_obj->fetchGroupData();
			$GLOBALS['TSFE']->beUserLogin = 1;
		}
	}
	
	function updateT3D(& $form, $formId)
	{
		if ($formId == 4) // Simulate a backend user if we are exporting the page tree and related records.
			$this->simulateAdminBE();
		utopia_form_manager::updateT3D($form, $formId);
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_utopia/pi1/class.tx_icsutopia_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_utopia/pi1/class.tx_icsutopia_pi1.php']);
}

?>