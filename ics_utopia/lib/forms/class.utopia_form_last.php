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
 * First form for the site create of the 'ics_utopia' extension.
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */

require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_form_base.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_forms.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_session.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_config.php'));

/**
 * Form implementation for the generic information form in the site creation course.
 *
 * USE:
 * Not designed to be called by user code.
 * 
 * @author In Cité Solution <technique@incitesolution.fr>
 * @package UTOPIA
 */
class utopia_form_last
{
	var $session;
	
	function utopia_form_last()
	{
	}
	
	function init(& $session)
	{
		$this->session = & $session;
	}
	
	function renderForm($mode)
	{
		$forms = t3lib_div::makeInstance('utopia_forms');
		$forms->init($mode);
		$form1 = $this->session->getFormData(1);
		$form2 = $this->session->getFormData(2);
		$form3 = $this->session->getFormData(3);
		$form4 = $this->session->getFormData(4);
		$content = '
		<dl>
			<dt>Nom du site</dt><dd>' . $form1['tx_icsutopia_site'][1]['title'] . '</dd>
			<dt>Url du site</dt><dd>' . $form1['tx_icsutopia_site'][1]['url'] . '</dd>';
		if ($form2)
		{
			$content .= '
			<dt>Administrateurs Back-end</dt>
			<dd>
				<dl>';
			foreach($form2['be_users'] as $id => $beuser){
				if(strlen($beuser['realName'])>0)
					$content .= '
					<dt><!--' . $beuser['origUid'][1] . '-->
						' . $beuser['realName'] . '
					</dt>
					<dd>
						<dl>
							<dt>Nom d\'utilisateur</dt><dd>' . $beuser['username'] . '</dd>
							<dt>Courriel</dt><dd>' .$beuser['email'] . '</dd>
						</dl>
					</dd>';
			}
			$content .= '
				</dl>
			</dd>';
		}
		if ($form3)
		{
			$content .= '
			<dt>Utilisateurs Front-end</dt>
			<dd>
				<dl>';
			foreach($form3['fe_users'] as $id => $feuser){
				if(strlen($feuser['name'])>0)
					$content .= '
					<dt>' . $feuser['name'] . '</dt>
					<dd>
						<dl>
							<dt>Nom d\'utilisateur</dt><dd>' . $feuser['username'] . '</dd>
							<dt>Courriel</dt><dd>' .$feuser['email'] . '</dd>
						</dl>
					</dd>';
			}
			$content .= '
				</dl>
			</dd>';
		}
		$pages = explode(',', $form4['tx_icsutopia_site'][1]['base_model']);
		foreach($pages as $index => $page)
			$pages[$index] = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('title', 'pages', 'uid = ' . $page);
		
		//var_dump($this->session);
		
		if(is_array(t3lib_div::_POST('action')))
		{
			foreach(t3lib_div::_POST('action') as $cle => $val)
			{
				//var_dump(t3lib_div::_POST('action'));
				//var_dump('cle'.$cle);
				//var_dump('val'.$val);
			}
		}
		
		$content .= '
			<dt>Architecture choisie</dt><dd>' . implode(', ', array_map(create_function('$val', 'return $val[0][\'title\'];'), $pages)) . '</dd>
		</dl>
		<ul class="utopia-last">
			' . $forms->getActionButtons(true, false) . '
			<li class="utopia-last-buttons">' . $forms->getSubmitButton($GLOBALS['LANG']->getLL('action.validate'), 'valid', 'formlast') . '
			</li>
			<li class="utopia-last-buttons">' . $forms->getSubmitButton($GLOBALS['LANG']->getLL('action.pdf'), 'pdf', 'formlast') . '
			</li>
		</ul>
		';
		//<a target="_blank" href="'.t3lib_div::getIndpEnv('TYPO3_REQUEST_URL').'?action[pdf]=1">'.$GLOBALS['LANG']->getLL('action.pdf').'</a>
		//$GLOBALS['TSFE']->cObj->getTypoLink_URL($GLOBALS["TSFE"]->id, array('pdf' => 1))
		if(TYPO3_MODE == 'BE')
		{
			$import = t3lib_div::makeInstance('tx_impexp');
			$import->loadFile($this->session->getT3DFile(),1);
			$overviewContent = $import->displayContentOverview();
//var_dump(md5(serialize($import->dat['header']['files'])));
			$content .= $overviewContent;
		}
		
		return $content;
		
	}
	
	function getFormData()
	{
		return null;
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/forms/class.utopia_form_last.php"]){
include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/forms/class.utopia_form_last.php"]);
}
