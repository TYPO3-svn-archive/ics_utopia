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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */


$LANG->includeLLFile('EXT:ics_utopia/mod4/locallang.xml');
require_once(PATH_t3lib . 'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_config.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_session.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_form_manager.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_mail_notify.php'));



/**
 * Module 'Current requests' for the 'ics_utopia' extension.
 *
 * @author	Pierrick Caillon <pierrick@in-cite.net>
 * @package	TYPO3
 * @subpackage	tx_icsutopia
 */
class  tx_icsutopia_module4 extends t3lib_SCbase {
	var $pageinfo;

	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();

		/*
		if (t3lib_div::_GP('clear_all_cache'))	{
			$this->include_once[] = PATH_t3lib.'class.t3lib_tcemain.php';
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
			'function' => Array (
				/*'1' => $LANG->getLL('function1'),
				'2' => $LANG->getLL('function2'),
				'3' => $LANG->getLL('function3'),*/
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
		$access = is_array($this->pageinfo) ? 1 : 0;
	
			// initialize doc
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->setModuleTemplate(t3lib_extMgm::extPath('ics_utopia') . 'mod4/mod_template.html');
		$this->doc->backPath = $BACK_PATH;
		$docHeaderButtons = $this->getButtons();

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{

				// Draw the form
			$this->doc->form = '<form action="" method="post" enctype="multipart/form-data">';

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
				// Render content:
			$this->moduleContent();
		} else {
				// If no access or if ID == zero
			$docHeaderButtons['save'] = '';
			$this->content.=$this->doc->spacer(10);
		}

			// compile document
		$markers['FUNC_MENU'] = t3lib_BEfunc::getFuncMenu(0, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
		$markers['CONTENT'] = $this->content;

				// Build the <body> for the module
		$this->content = $this->doc->startPage($LANG->getLL('title'));
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
		if (!empty($_POST))
		{
			$action = t3lib_div::_POST('action');					
			$action_keys = array_keys($action);
			$action_value = $action[$action_keys[0]];
			
			$action = $action_keys[0];
			unset($action_keys);
			switch($action)
			{
			case 'modify':
				$action_value = array_keys($action_value);
				$this->modify(base64_decode($action_value[0]));
				break;
			case 'import':
				$action_value = array_keys($action_value);
				if ($this->import(base64_decode($action_value[0])))
					return;
				break;
			case 'reject':
				$action_value = array_keys($action_value);
				$this->reject(base64_decode($action_value[0]));
				break;
			}
		}
		$this->content .= $this->makeList();
	}
	
	function makeList()
	{
		global $LANG;
		$config = t3lib_div::makeInstance('utopia_config');
		$this->updateFeRequests();
		$files = glob(PATH_site . 'fileadmin/' . $config->getConfig('storage.requests') . 'utopia*.t3d');
		if (count($files) > 0)
		{
			$data = array(
				array(
					$LANG->getLL('header.file'),
					$LANG->getLL('header.actions'),
				),
			);
			foreach ($files as $file)
			{
				$t3d = utopia_t3d_editor::loadFile($file);
				$basename = basename($file);
				$basename = base64_encode($basename);
				$data[] = array(
					$t3d['header']['meta']['title'],
					'
				<input type="submit" name="action[modify][' . $basename . ']" value="' . $LANG->getLL('action.modify') . '" />
				<input type="submit" name="action[import][' . $basename . ']" value="' . $LANG->getLL('action.import') . '" />
				<input type="submit" name="action[reject][' . $basename . ']" value="' . $LANG->getLL('action.reject') . '" />',
				);
			}
			$content = $this->doc->table($data, $this->listTemplate(array(
					'file',
					'actions',
				)));
		}
		else
		{
			$content = '<p>' . $LANG->getLL('error.norequest') . '</p>';
		}
		return $this->doc->section($LANG->getLL('title.list'),$content,0,1);
	}
	
	/**
	 * Provides the table layout template for the list. The table is generated using template::table.
	 */
	function listTemplate($headers)
	{
		$template = array(
			'table' => array(
				'
	<table>',
				'
	</table>'
			),
			'defRow' => array(
				'tr' => array(
					'
		<tr>',
					'
		</tr>'
				),
				'defCol' => array(
					'
			<td>
				',
					'
			</td>'
				)
			),
			0 => array(
				'defCol' => array(
					'
			<th>
				',
					'
			</th>'
				)
			)
		);
		foreach($headers as $index => $name)
		{
			$template[0][$index] = array(
				'
			<th id="table_' . $name . '">
				',
				'
			</th>'
			);
			$template['defRow'][$index] = array(
				'
			<td headers="table_' . $name . '">
				',
				'
			</td>'
			);
		}
		return $template;
	}
	
	function import($file)
	{
		global $LANG, $TYPO3_CONF_VARS;
		$config = t3lib_div::makeInstance('utopia_config');
		$file = PATH_site . 'fileadmin/' . $config->getConfig('storage.requests') . $file;
		if (!is_file($file))
		{
			$this->content .= $this->doc->section($LANG->getLL('title.import'),'<p class="error">' . str_replace('###ERROR###', $LANG->getLL('error.notfound'), $LANG->getLL('error')) . '</p>',0,1);
			return false;
		}
		$data = null;
		$content = utopia_t3d_editor::importData(array(
				'file' => $file,
				'import_file' => $file,
				'allowPHPScripts' => 1,
				'notShowDiff' => 0,
				'enableLogging' => 1,
				'pid' => $config->getConfig('storage.siteroot')
			), $data);
		//$hooks = array();
			// Hook for postprocessing the imported data:
		if (is_array($TYPO3_CONF_VARS['EXTCONF']['ics_utopia']['postAction']))    {
			foreach($TYPO3_CONF_VARS['EXTCONF']['ics_utopia']['postAction'] as $_classRef)    {
				$_procObj = & t3lib_div::getUserObj($_classRef, false);
				if (is_object($_procObj) && (get_parent_class($_procObj) == 'utopia_postAction_base'))
				{
					$_procObj->doAction($data);
					//$hooks[] = get_class($_procObj);
				}
			}
		}
		
		/*modifs loïc 08/07/08 */

		$t3d = utopia_t3d_editor::loadFile($file);
		$mailer = t3lib_div::makeInstance('utopia_mail_notify');
		$session->_session = & $t3d['header']['meta']['session'];	
		
		
		$user = utopia_session::getUser();
		$markers = array(
			'###SITE_NAME###' => $session->_session['forms'][1]['tx_icsutopia_site'][1]['title'] ,
		);
		$userid = 'be_' . $user->user['uid'];
		$mailer->notify($userid, $session->_session['creator']['type'].'_'.$session->_session['creator']['id'], $markers, 'mail.accept');

		rename($file, PATH_site . 'fileadmin/' . $config->getConfig('storage.archives') . basename($file));
		$this->content .= $this->doc->section($LANG->getLL('title.result'),$content/* . '<p>Hooks: ' . implode(', ', $hooks) . '</p>'*/,0,1);
		
		return true;
	}
	
	function modify($file)
	{
		global $LANG;
		$config = t3lib_div::makeInstance('utopia_config');
		$file = PATH_site . 'fileadmin/' . $config->getConfig('storage.requests') . $file;
		if (!is_file($file))
		{
			$this->content .= $this->doc->section($LANG->getLL('title.import'),'<p class="error">' . str_replace('###ERROR###', $LANG->getLL('error.notfound'), $LANG->getLL('error')) . '</p>',0,1);
			return;
		}
		$session = t3lib_div::makeInstance('utopia_session');
		$session->_setKey($session->extKey, array(
				't3d' => $file
			));
		$session = t3lib_div::makeInstance('utopia_session');
		$session->set(array('returnurl'), t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'));
		$session->set('form', null);
		$session->setT3DFile($file);
		$session->saveSession();
		header('Location: ' . t3lib_div::locationHeaderUrl($GLOBALS['BACK_PATH'] . 'mod.php?M=txicsutopiaM1_txicsutopiaM5'));
		exit;
	}
	
	function reject($file)
	{
		global $LANG;
		$config = t3lib_div::makeInstance('utopia_config');
		$file = PATH_site . 'fileadmin/' . $config->getConfig('storage.requests') . $file;
		
		/*modifs loïc 09/07/08 */
				
		$mailer = t3lib_div::makeInstance('utopia_mail_notify');
		$session = t3lib_div::makeInstance('utopia_session');
		$t3d = utopia_t3d_editor::loadFile($file);	
		
		$user = $session->getUser();

		$session->_session = & $t3d['header']['meta']['session'];	
					
				
		$markers = array(
			'###SITE_NAME###' => $session->_session['forms'][1]['tx_icsutopia_site'][1]['title'] ,
		);			
		
		$userid = 'be_' . $user->user['uid'];	
		
		$mailer->notify($userid, $session->_session['creator']['type'].'_'.$session->_session['creator']['id'], $markers, 'mail.reject');		
		
		/* Fin modifs */
		
		if (!is_file($file))
		{
			$this->content .= $this->doc->section($LANG->getLL('title.import'),'<p class="error">' . str_replace('###ERROR###', $LANG->getLL('error.notfound'), $LANG->getLL('error')) . '</p>',0,1);
			return;
		}
		// TODO: Make move.
		unlink($file);
		$this->content .= $this->doc->section($LANG->getLL('title.reject'),'<p>' . $LANG->getLL('rejected') . '</p>',0,1);		
		
	}
	
	function updateFeRequests()
	{
		utopia_form_manager::initPath();
		//xdebug_enable();
		$session = t3lib_div::makeInstance('utopia_session');
		$config = t3lib_div::makeInstance('utopia_config');
		$files = glob(PATH_site . 'fileadmin/' . $config->getConfig('storage.requests') . 'utopia*.t3d_fe');
		foreach ($files as $file)
		{
			$t3d = utopia_t3d_editor::loadFile($file);
			$session->_session = $t3d['header']['meta']['session'];
			$form = utopia_form_manager::getForm(4);
			
			$form->updateData($t3d, $session->getFormData(4), $session->get(array('forms', 1, 'tx_icsutopia_site', '1', 'title')), $session->get(array('creator')));
			for ($i = 1; $i < 4; ++$i)
			{
				$other = utopia_form_manager::getForm($i);
				if (!$other)
					continue;
				$data = $session->getFormData($i);
				$other->updateData($t3d, $data);
			}
			$t3d['header']['meta']['title'] = $t3d['records']['tx_icsutopia_site:1']['data']['title'];
			$t3d['header']['meta']['session'] = $session->_session;
			$formId = 4;
			while (($formId = utopia_form_manager::getNextFormId($formId)) != 'last')
			{
				$other = utopia_form_manager::getForm($formId);
				$data = $session->getFormData($formId);
				$other->updateData($t3d, $data);
			}
			utopia_t3d_editor::saveFile($file, $t3d);
			rename($file, substr($file, 0, -3));
		}
		//xdebug_disable();
	}	

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons()	{

		$buttons = array(
			'csh' => '',
			'shortcut' => '',
			'save' => ''
		);
			// CSH
		$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH']);

			// SAVE button
		// $buttons['save'] = '<input type="image" class="c-inputButton" name="submit" value="Update"' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/savedok.gif', '') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc', 1) . '" />';


			// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}

		return $buttons;
	}
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_utopia/mod4/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_utopia/mod4/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_icsutopia_module4');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>