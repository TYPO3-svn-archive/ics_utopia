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
 * Second form for the site create of the 'ics_utopia' extension.
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */

require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_form_base.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_session.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_config.php'));

/**
 * Form implementation for the back-end admins in the site creation course.
 *
 * USE:
 * Not designed to be called by user code.
 * 
 * @author In Cité Solution <technique@incitesolution.fr>
 * @package UTOPIA
 */
class utopia_form3 extends utopia_form_base
{
	function renderForm($mode, $formData)
	{
		parent::renderForm($mode, $formData);
		if (empty($formData))
			$formData = array();
		if (!isset($formData['fe_users']) || empty($formData['fe_users']))
			$formData['fe_users'] = array();
		if (!isset($formData['fe_users']['100']))
			$formData['fe_users']['100'] = array('uid' => 100);
		
		$fieldList = array(
			'name;LLL:EXT:ics_utopia/mod5/locallang.xml:users.name',
			'email',
			'--linebreak--',
			'username',
			'password',
		);
		$panel = "";
		t3lib_div::loadTCA('fe_users');
		$GLOBALS['TCA']['fe_users']['columns']['name']['config']['eval'] = 'required';
		$GLOBALS['TCA']['fe_users']['columns']['email']['config']['eval'] = 'required';
		
		$GLOBALS['TCA']['fe_users']['columns']['name']['config']['size'] = 20;
		$GLOBALS['TCA']['fe_users']['columns']['username']['config']['size'] = 20;
		$GLOBALS['TCA']['fe_users']['columns']['email']['config']['size'] = 20;
		$GLOBALS['TCA']['fe_users']['columns']['password']['config']['size'] = 20;
		
		foreach($formData['fe_users'] as $uid => $feuser)
		{
			// ajout de la palette au formulaire
			$feuser['uid'] = $uid;
			$panel .= $this->_forms->getPaletteFields('fe_users', $feuser, $fieldList, str_replace('###UID###', $uid - 99, $GLOBALS['LANG']->getLL('form3.userTitle')));
			// retrait du required sur les champs suivants
			foreach($fieldList as $field)
			{
				if ($field == '--linebreak--')
					continue;
				if (strpos($field, ';'))
					$field = substr($field, 0, strpos($field, ';'));
				$GLOBALS['TCA']['fe_users']['columns'][$field]['config']['eval'] = str_replace('required', '', $GLOBALS['TCA']['fe_users']['columns'][$field]['config']['eval']);
			}
			$uid++;
		}
		if(TYPO3_MODE == 'BE')
		{
			$panel .= '
			<tr class="bgColor2">
				<td></td> 
				<td>
					' . $this->_forms->getSubmitButton($GLOBALS['LANG']->getLL('action.update'), 'update', 'form3') . '
					' . $this->_forms->getSubmitButton($GLOBALS['LANG']->getLL('form3.addFe'), 'addFe', 'form3', true) . '
				</td>
			</tr>';
		}else
		{
			$panel .= '
			<li class="utopia-form-buttons">' . $this->_forms->getSubmitButton($GLOBALS['LANG']->getLL('action.update'), 'update', 'form3') . 
			$this->_forms->getSubmitButton($GLOBALS['LANG']->getLL('form3.addFe'), 'addFe', 'form3', true) . '
			</li>';
		}
		
		$content = $this->_forms->wrapTotal($panel, $GLOBALS['LANG']->getLL('form3.title'), '');

		return $content;
	}
	
	function getFormData()
	{
		$action = t3lib_div::_POST('action');
		if ($action)
			foreach($action as $action_key => $action_text)
				if (!empty($action_text))
				{
					$action = $action_key;
					break;
				}
		$data = t3lib_div::_POST('data');			
		if (isset($data['fe_users']))
			foreach ($data['fe_users'] as $uid => $feuser)
			{
				$data['fe_users'][$uid]['username'] = strtolower($data['fe_users'][$uid]['username']);
				$data['fe_users'][$uid]['email'] = strtolower($data['fe_users'][$uid]['email']);
			}
		if ($action == 'addFe')
		{
			$max = max(array_keys($data['fe_users'])) + 1;
			$data['fe_users'][$max] = array('uid' => $max);
		}
		return $data;
	}
	
	function updateData(& $t3d, $formData)
	{
		if (!isset($t3d['header']['records'])) // Workaround FE
			return;
		if (empty($formData))
			return;
		
		// Get global data
		$user = utopia_session::getUser();
		$config = t3lib_div::makeInstance('utopia_config');
		$home = array_keys($t3d['header']['pagetree']);
		$home = $home[0];

		$grouptitle = $config->getConfig('template.fegroup');
		foreach($t3d['header']['records']['fe_groups'] as $fegroup){
			if(strcmp($grouptitle, $fegroup['title']) == 0){
				$groupFe = $fegroup['uid'];
				break;
			}
		}
		$storagePid = $t3d['records']['tx_icsutopia_site:1']['data']['main_storage'];

		$data = $formData['fe_users'];
		
		foreach($data as $key => $feuser){
			if (empty($feuser['username']) || empty($feuser['name']) || empty($feuser['password']) || empty($feuser['password']))
			{
				if(isset($t3d['records']['fe_users:'.$key]))
					unset($t3d['records']['fe_users:'.$key]);
				continue;
			}
			// Set the records to integrate.
			if(!isset($t3d['records']['fe_users:'.$key]))
				$t3d['records']['fe_users:'.$key] = array(
					'data' => array(
						'uid' => $key,
						'pid' => $storagePid,
						'crdate' => time(),
						'cruser_id' => ((TYPO3_MODE == 'BE') ? ($user->user['uid']) : (0)),
						'fe_cruser_id' => ((TYPO3_MODE != 'BE') ? ($user->user['uid']) : (0)),
					),
					'rels' => array(
						'usergroup' => array(
							'type' 	=> 'db',
							'itemArray' => array(
								array(
									'id' 	=> $groupFe,
									'table' => 'fe_groups'
								)
							)
						)
					)
				);
			$t3d['records']['fe_users:'.$key]['data']['tstamp'] 	= time();
			$t3d['records']['fe_users:'.$key]['data']['usergroup'] 	= $groupFe;
			$t3d['records']['fe_users:'.$key]['data']['username'] 	= $feuser['username'];
			$t3d['records']['fe_users:'.$key]['data']['name'] 		= $feuser['name'];
			$t3d['records']['fe_users:'.$key]['data']['password'] 	= $feuser['password'];
			$t3d['records']['fe_users:'.$key]['data']['email'] 		= $feuser['email'];
			
			// Set the header data about these records and their references.
			if (!isset($t3d['header']['records']['fe_users']))
				$t3d['header']['records']['fe_users'] = array();
			if (!isset($t3d['header']['records']['fe_users'][$key]))
				$t3d['header']['records']['fe_users'][$key] = array(
					'uid' => $key,
					'pid' => $storagePid,
					'rels' => array(
						'fe_groups:' . $groupFe => array(
							'table' => 'fe_groups',
							'id' 	=> $groupFe
						)
					),
					'softrefs' => array()
				);
			$usersFe = & $t3d['header']['records']['fe_users'][$key];
			$usersFe['title'] = $feuser['username'];
			$usersFe['size'] = strlen(serialize($t3d['records']['fe_users:'.$key]['data']));
			// Set the header pid_lookup about the new records
			if (!isset($t3d['header']['pid_lookup'][$storagePid]))
				$t3d['header']['pid_lookup'][$storagePid] = array();
			if (!isset($t3d['header']['pid_lookup'][$storagePid]['fe_users']))
				$t3d['header']['pid_lookup'][$storagePid]['fe_users'] = array();
			if (!in_array($key, array_keys($t3d['header']['pid_lookup'][$storagePid]['fe_users'])))
				$t3d['header']['pid_lookup'][$storagePid]['fe_users'][$key] = 1;
		}	
	}

	function validateInput()
	{
		$data = t3lib_div::_POST('data');
		if (isset($data['fe_users']))
			foreach($data['fe_users'] as $id => $FeUser)
			{
				if (!empty($FeUser['email']) || !empty($FeUser['password']) || !empty($FeUser['name']) || !empty($FeUser['username']))
				{
					if (!empty($FeUser['email']) && !empty($FeUser['password']) && !empty($FeUser['name']) && !empty($FeUser['username']))
					{
						if(!preg_match('/^[a-z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$/i', $FeUser['email']))
						{
							$this->errors[] = sprintf($GLOBALS['LANG']->getLL('form3.errors.email.invalid'), str_replace('###UID###', $id - 99, $GLOBALS['LANG']->getLL('form3.userTitle')));
							return false;
						}
					}
					else
					{
						if ($id == 100)
							$this->errors[] = $GLOBALS['LANG']->getLL('form3.errors.firstuser.empty');
						else
							$this->errors[] = sprintf($GLOBALS['LANG']->getLL('form3.errors.user.incomplete'), str_replace('###UID###', $id - 99, $GLOBALS['LANG']->getLL('form3.userTitle')));
						return false;				
					}
				}
			}
		return true;
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/forms/class.utopia_form3.php"]){
include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/forms/class.utopia_form3.php"]);
}
