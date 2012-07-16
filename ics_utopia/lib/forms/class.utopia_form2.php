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
class utopia_form2 extends utopia_form_base
{
	function renderForm($mode, $formData)
	{
		parent::renderForm($mode, $formData);
		//var_dump('Render');
		//var_dump($formData);
		if (empty($formData))
			$formData = array();
		if (!isset($formData['be_users']) || empty($formData['be_users']))
			$formData['be_users'] = array();
		
		$uidMap = array();
		$maxUid = 1;
		foreach($formData['be_users'] as $key => $BeUser)
		{
			if (!isset($uidMap[$BeUser['origUid'][0]]))
				$uidMap[$BeUser['origUid'][0]] = array();
			$uidMap[$BeUser['origUid'][0]][$BeUser['origUid'][1]] = $key;
			$formData['be_users'][$key]['uid'] = $key;
			$maxUid = max($maxUid, $key + 1);
		}

		// Get global data
		$user = & utopia_session::getUser();
		$config = t3lib_div::makeInstance('utopia_config');
		$confBeU = $config->getConfig('template.beusers');
		if(!empty($confBeU)){
			$tab = explode(',', $confBeU);
			$confBeU = array();
			foreach($tab as $beu){
				$tabEx = explode('-', $beu, 2);
				if (!isset($mapUid[$tabEx[0]]) || !isset($mapUid[$tabEx[0]][$tabEx[1]]))
				{
					$formData['be_users'][$maxUid]['uid'] = $maxUid;
					$formData['be_users'][$maxUid]['origUid'] = $tabEx;
					$confBeU[$maxUid] = $tabEx[1];
					$maxUid++;
				}
				else
					$confBeU[$mapUid[$tabEx[0]][$tabEx[1]]] = $tabEx[1];
			}
		}
		// Champs be_users necessaires
		$fieldList = array(
			'realName;LLL:EXT:ics_utopia/locallang_db_be_users.xml:be_users.realName',
			'email;LLL:EXT:ics_utopia/locallang_db_be_users.xml:be_users.email',
			'--linebreak--',
			'username;LLL:EXT:ics_utopia/locallang_db_be_users.xml:be_users.username',
			'password;LLL:EXT:ics_utopia/locallang_db_be_users.xml:be_users.password',
		);
			
		$panel = "";
		t3lib_div::loadTCA('be_users');
		$GLOBALS['TCA']['be_users']['columns']['realName']['config']['eval'] = 'required';
		$GLOBALS['TCA']['be_users']['columns']['email']['config']['eval'] 	 = 'required';
		$GLOBALS['TCA']['be_users']['columns']['password']['config']['eval'] = str_replace('md5', '', $GLOBALS['TCA']['be_users']['columns']['password']['config']['eval']);
		$GLOBALS['TCA']['be_users']['columns']['password']['config']['eval'] = str_replace(',,', ',', $GLOBALS['TCA']['be_users']['columns']['password']['config']['eval']);
		
		$GLOBALS['TCA']['be_users']['columns']['realName']['config']['size'] = 20;
		$GLOBALS['TCA']['be_users']['columns']['username']['config']['size'] = 20;
		$GLOBALS['TCA']['be_users']['columns']['email']['config']['size'] 	 = 20;
		$GLOBALS['TCA']['be_users']['columns']['password']['config']['size'] = 20;
		if (!empty($confBeU))
		{
			$uid = 1;
			foreach($confBeU as $id => $role)
			{
				// ajout de la palette au formulaire
				$panel .= $this->_forms->getPaletteFields('be_users', $formData['be_users'][$uid], $fieldList, $role);
				// ajout des input = hidden
				$panel .= '<input type="hidden" name="data[be_users][' . $uid . '][origUid]" value="' . base64_encode(serialize($formData['be_users'][$uid]['origUid'])) . '" />';
				// retrait du required sur les champs suivants
				foreach($fieldList as $field)
				{
					if ($field == '--linebreak--')
						continue;
					if (strpos($field, ';'))
						$field = substr($field, 0, strpos($field, ';'));
					$GLOBALS['TCA']['be_users']['columns'][$field]['config']['eval'] = str_replace('required', '', $GLOBALS['TCA']['be_users']['columns'][$field]['config']['eval']);
				}
				$uid++;
			}
		}
		
		if(TYPO3_MODE == 'BE')
		{
			$panel .= '<tr class="bgColor2"><td></td><td>';
			$panel .= $this->_forms->getSubmitButton($GLOBALS['LANG']->getLL('action.update'), 'update', 'form2');
			$panel .= '</td></tr>';
		}else
		{
			$panel .= '<li class="utopia-form-buttons">' . $this->_forms->getSubmitButton($GLOBALS['LANG']->getLL('action.update'), 'update', 'form2') . '</li>';
		}

		$content = $this->_forms->wrapTotal($panel, $GLOBALS['LANG']->getLL('form2.title'), '');

		return $content;
	}
	
	function getFormData()
	{
		$data = t3lib_div::_POST('data');
		if (isset($data['be_users']))
			foreach ($data['be_users'] as $uid => $beuser)
			{
				$data['be_users'][$uid]['origUid'] = unserialize(base64_decode($data['be_users'][$uid]['origUid']));
				$data['be_users'][$uid]['username'] = strtolower($data['be_users'][$uid]['username']);
				$data['be_users'][$uid]['email'] = strtolower($data['be_users'][$uid]['email']);
			}
		return $data;
	}
	
	function updateData(& $t3d, $formData)
	{
		if (!isset($t3d['header']['records'])) // Workaround FE
			return;
		
		// Get global data
		$user = utopia_session::getUser();
		$config = t3lib_div::makeInstance('utopia_config');
		$home = array_keys($t3d['header']['pagetree']);
		$home = $home[0];

		$group = $config->getConfig('template.begroup');

		// fe_group si cochee
		if ($config->getConfig('template.createfe'))
		{
			$grouptitle = $config->getConfig('template.fegroup');
			foreach($t3d['header']['records']['fe_groups'] as $fegroup){
				if(strcmp($grouptitle, $fegroup['title']) == 0){
					$groupFe = $fegroup['uid'];
					break;
				}
			}
			$storagePid = $t3d['records']['tx_icsutopia_site:1']['data']['main_storage'];
		}

		$data = $formData['be_users'];
		
		foreach($data as $key => $beuser){
			if (empty($beuser['username']) || empty($beuser['realName']) || empty($beuser['password']) || empty($beuser['password']))
			{
				if(isset($t3d['records']['be_users:'.$key]))
					unset($t3d['records']['be_users:'.$key]);
				if(isset($t3d['records']['fe_users:'.$key]))
					unset($t3d['records']['fe_users:'.$key]);
				continue;
			}
			// Set the records to integrate.
			$templateUser = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'be_users', 'uid = ' . $beuser['origUid'][0]);
			if(!isset($t3d['records']['be_users:'.$key]))
				$t3d['records']['be_users:'.$key] = array(
					'data' => $templateUser[0],
					'rels' => array(
						'usergroup' => array(
							'type' => 'db',
							'itemArray' => array(),
						)
					)
				);
			$t3d['records']['be_users:'.$key]['data']['tstamp'] = time();
			$t3d['records']['be_users:'.$key]['data']['usergroup'] = implode(',', array_unique(array_merge(t3lib_div::trimExplode(',', $t3d['records']['be_users:'.$key]['data']['usergroup'], true), array($group))));
			$t3d['records']['be_users:'.$key]['data']['username'] = $beuser['username'];
			$t3d['records']['be_users:'.$key]['data']['realName'] = $beuser['realName'];
			$t3d['records']['be_users:'.$key]['data']['password'] = md5($beuser['password']);
			$t3d['records']['be_users:'.$key]['data']['email'] = $beuser['email'];
			foreach (t3lib_div::trimExplode(',', $t3d['records']['be_users:'.$key]['data']['usergroup'], true) as $usergroup) {
				$t3d['records']['be_users:'.$key]['rels']['usergroup']['itemArray'][] = array(
					'id' => $usergroup,
					'table' => 'be_groups'
				);
			}
			
			if ($config->getConfig('template.createfe'))
			{
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
								'type' => 'db',
								'itemArray' => array(
									array(
										'id' => $groupFe,
										'table' => 'fe_groups'
									)
								)
							)
						)
					);
				$t3d['records']['fe_users:'.$key]['data']['tstamp'] = time();
				$t3d['records']['fe_users:'.$key]['data']['usergroup'] = $groupFe;
				$t3d['records']['fe_users:'.$key]['data']['username'] = $beuser['username'];
				$t3d['records']['fe_users:'.$key]['data']['name'] = $beuser['realName'];
				$t3d['records']['fe_users:'.$key]['data']['password'] = $beuser['password'];
				$t3d['records']['fe_users:'.$key]['data']['email'] = $beuser['email'];
			}
			// Set the header data about these records and their references.
			if (!isset($t3d['header']['records']['be_users']))
				$t3d['header']['records']['be_users'] = array();
			if (!isset($t3d['header']['records']['be_users'][$key])) {
				$t3d['header']['records']['be_users'][$key] = array(
					'uid' => $key,
					'pid' => 0,
					'rels' => array(),
					'softrefs' => array()
				);
				foreach (t3lib_div::trimExplode(',', $t3d['records']['be_users:'.$key]['data']['usergroup'], true) as $usergroup) {
					$t3d['header']['records']['be_users'][$key]['rels']['be_groups:' . $usergroup] = array(
						'table' => 'be_groups',
						'id' => $usergroup,
					);
				}
			}
			foreach (t3lib_div::trimExplode(',', $t3d['records']['be_users:'.$key]['data']['usergroup'], true) as $usergroup) {
				if ($usergroup != $group)
					$t3d['header']['excludeMap']['be_groups:' . $usergroup] = 1;
			}
			$admin = & $t3d['header']['records']['be_users'][$key];
			$admin['title'] = $beuser['username'];
			$admin['size'] = strlen(serialize($t3d['records']['be_users:'.$key]['data']));
			
			if ($config->getConfig('template.createfe'))
			{
				if (!isset($t3d['header']['records']['fe_users']))
					$t3d['header']['records']['fe_users'] = array();
				if (!isset($t3d['header']['records']['fe_users'][$key]))
					$t3d['header']['records']['fe_users'][$key] = array(
						'uid' => $key,
						'pid' => $storagePid,
						'rels' => array(
							'fe_groups:' . $groupFe => array(
								'table' => 'fe_groups',
								'id' => $groupFe
							)
						),
						'softrefs' => array()
					);
				$admin = & $t3d['header']['records']['fe_users'][$key];
				$admin['title'] = $beuser['username'];
				$admin['size'] = strlen(serialize($t3d['records']['fe_users:'.$key]['data']));
				// Set the header pid_lookup about the new records
				if (!isset($t3d['header']['pid_lookup']['0']))
					$t3d['header']['pid_lookup']['0'] = array();
				if (!isset($t3d['header']['pid_lookup']['0']['be_users']))
					$t3d['header']['pid_lookup']['0']['be_users'] = array();
				if (!in_array($key, array_keys($t3d['header']['pid_lookup']['0']['be_users'])))
					$t3d['header']['pid_lookup']['0']['be_users'][$key] = 1;
				if (!isset($t3d['header']['pid_lookup'][$storagePid]))
					$t3d['header']['pid_lookup'][$storagePid] = array();
				if (!isset($t3d['header']['pid_lookup'][$storagePid]['fe_users']))
					$t3d['header']['pid_lookup'][$storagePid]['fe_users'] = array();
				if (!in_array($key, array_keys($t3d['header']['pid_lookup'][$storagePid]['fe_users'])))
					$t3d['header']['pid_lookup'][$storagePid]['fe_users'][$key] = 1;
			}
		}
	}

	function validateInput()
	{
		$data = t3lib_div::_POST('data');
		if (isset($data['be_users']))
			foreach($data['be_users'] as $id => $BeUser)
			{
				if (!empty($BeUser['email']) || !empty($BeUser['password']) || !empty($BeUser['realName']) || !empty($BeUser['username']))
				{
					if (!empty($BeUser['email']) && !empty($BeUser['password']) && !empty($BeUser['realName']) && !empty($BeUser['username']))
					{
						if(!preg_match('/^[a-z0-9.+_-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$/i', $BeUser['email']))
						{
							$origUid = unserialize(base64_decode($BeUser['origUid']));
							$this->errors[] = sprintf($GLOBALS['LANG']->getLL('form2.errors.email.invalid'), $origUid[1]);
							return false;
						}
					}
					else
					{
						$origUid = unserialize(base64_decode($BeUser['origUid']));
						if ($id == 1)
							$this->errors[] = $GLOBALS['LANG']->getLL('form2.errors.firstuser.empty');
						else
							$this->errors[] = sprintf($GLOBALS['LANG']->getLL('form2.errors.user.incomplete'), $origUid[1]);
						return false;				
					}
				}
				elseif ($id == 1)
				{
					$this->errors[] = $GLOBALS['LANG']->getLL('form2.errors.firstuser.empty');
					return false;
				}
			}
		return true;
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/forms/class.utopia_form2.php"]){
include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/forms/class.utopia_form2.php"]);
}
