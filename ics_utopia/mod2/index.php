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


$LANG->includeLLFile('EXT:ics_utopia/mod2/locallang.xml');
require_once(PATH_t3lib . 'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]

require_once(PATH_t3lib.'class.t3lib_tsstyleconfig.php');
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_forms.php'));


/**
 * Module 'Settings' for the 'ics_utopia' extension.
 *
 * @author	Pierrick Caillon <pierrick@in-cite.net>
 * @package	TYPO3
 * @subpackage	tx_icsutopia
 */
class tx_icsutopia_module2 extends t3lib_SCbase {
	var $pageinfo;

	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$relPath = t3lib_extMgm::extRelPath('ics_utopia');
		$absPath = t3lib_extMgm::extPath('ics_utopia');
		$this->include_once[] = $absPath . 'mod2/tca_equiv.php';
		
			// Load tsStyleConfig class and parse configuration template:
		$tsStyleConfig = t3lib_div::makeInstance('t3lib_tsStyleConfig');
		$tsStyleConfig->doNotSortCategoriesBeforeMakingForm = TRUE;
		$theConstants = $tsStyleConfig->ext_initTSstyleConfig(
			t3lib_div::getUrl($absPath.'ext_conf_template.txt'),
			$relPath,
			$absPath,
			$GLOBALS['BACK_PATH']
		);
			// Load the list of resources.
		$tsStyleConfig->ext_loadResources($absPath.'res/');
			// Load current value:
		$this->extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['ics_utopia']);
		$this->extConf = is_array($this->extConf) ? $this->extConf : array();
		$this->mainMenu = array(
			'main' => $LANG->getLL('function.mainconf'),
		);
		$this->menuKeys = array();
		foreach ($theConstants as $key => $descr)
		{
			$keyparts = explode('.', $key);
			if (count($keyparts) > 1)
			{
				if (!isset($this->mainMenu[$keyparts[0]]))
					$this->mainMenu[$keyparts[0]] = $LANG->getLL('function.' . $keyparts[0]);
				if (!isset($this->menuKeys[$keyparts[0]]))
					$this->menuKeys[$keyparts[0]] = array();
				$this->menuKeys[$keyparts[0]][] = $key;
			}
			else
			{
				if (!isset($this->menuKeys['main']))
					$this->menuKeys['main'] = array();
				$this->menuKeys['main'][] = $key;
			}
		}
		if (empty($this->menuKeys['main']))
			unset($this->mainMenu['main']);

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
		$values = array_merge(array(''), array_values($this->mainMenu));
		unset($values[0]);
		$this->MOD_MENU = Array (
			"function" => $values
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
		$this->doc->setModuleTemplate(t3lib_extMgm::extPath('ics_utopia') . 'mod2/mod_template.html');
		$this->doc->backPath = $BACK_PATH;
		$docHeaderButtons = $this->getButtons();

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{

				// Draw the form
			$this->doc->form = '<form action="" method="post" enctype="multipart/form-data" name="utopiaconf">';

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
		$functions = array_keys($this->mainMenu);
		$function = $functions[intval($this->MOD_SETTINGS["function"]) - 1];
		if (isset($_POST['data']))
		{
		}
		// $forms = t3lib_div::makeInstance('utopia_forms');
		// $forms->init('BE');
		// $forms->tceforms->formName = 'utopiaconf';
		// $forms->tceforms->globalShowHelp = 1;
		// $forms->tceforms->edit_showFieldHelp = 'text';
		// $GLOBALS['LANG']->loadSingleTableDescription('utopiaconf');
		// $panel = '';
		// $row = array('uid' => '1');
		// $this->confToTableRow('', $row, $this->extConf);
		// foreach ($this->menuKeys[$function] as $key)
		// {
			// $panel .= $forms->getGenSingleField('utopiaconf', str_replace('.', '_', $key), $row, '', 0, '', 0, $this->tca[$key]);
		// }
		// $content = $forms->wrapTotal($panel, $this->mainMenu[$this->MOD_SETTINGS['function']], '');
		// $this->content .= $forms->getTopJS() . $content . $forms->getBottomJS();
		$this->content .= 'Not yet available';
	}
	
	function confToTableRow($prefix, & $output, $conf)
	{
		foreach($conf as $key => $value)
		{
			if (is_array($value))
			{
				$this->confToTableRow($prefix . str_replace('.', '_', $key), $output, $value);
			}
			else
			{
				$output[$prefix . $key] = $value;
			}
		}
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
		$buttons['save'] = '<input type="image" class="c-inputButton" name="submit" value="Update"' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/savedok.gif', '') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc', 1) . '" />';


			// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}

		return $buttons;
	}
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_utopia/mod2/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_utopia/mod2/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_icsutopia_module2');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>