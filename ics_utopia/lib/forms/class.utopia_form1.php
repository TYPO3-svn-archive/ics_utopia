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
class utopia_form1 extends utopia_form_base
{
	var $fields = array(
		'url',
		'title',
	);
	var $extensions = null;
	var $fieldExt = array();

	function initExtensions()
	{
		global $TYPO3_CONF_VARS;
		if ($this->extensions != null)
			return;
		$this->extensions = array();
			// Hook for first form extension:
		if (is_array($TYPO3_CONF_VARS['EXTCONF']['ics_utopia']['first_form']))    {
			foreach($TYPO3_CONF_VARS['EXTCONF']['ics_utopia']['first_form'] as $index => $_classRef)    {
				$this->extensions[$index] = & t3lib_div::getUserObj($_classRef, false);
				if (is_object($this->extensions[$index]) && (get_parent_class($this->extensions[$index]) == 'utopia_first_form_base'))
				{
					$this->fields = array_merge(array_diff($this->fields, $this->extensions[$index]->getHiddenFields($this->fields)), $newFields = $this->extensions[$index]->getNewFields());
					foreach ($newFields as $field)
						$this->fieldExt[$field] = $index;
					//$hooks[] = get_class($_procObj);
				}
				else
					unset($this->extensions[$index]);
			}
		}
	}

	function renderForm($mode, $formData)
	{
		parent::renderForm($mode, $formData);
		$this->initExtensions();
		//var_dump('Render');
		//var_dump($formData);
		if (empty($formData))
			$formData = array();
		if (!isset($formData['tx_icsutopia_site']) || empty($formData['tx_icsutopia_site']))
			$formData['tx_icsutopia_site'] = array();
		if (!isset($formData['tx_icsutopia_site']['1']))
			$formData['tx_icsutopia_site']['1'] = array('uid' => 1);
		else
			$formData['tx_icsutopia_site']['1']['uid'] = 1;
		foreach(array_keys($this->extensions) as $extKey)
			$formData = $this->extensions[$extKey]->computeValues($formData, 'form');
		
		$panel = '';
		foreach($this->fields as $field){
			$conf = (isset($this->fieldExt[$field])) ? ($this->extensions[$this->fieldExt[$field]]->getFieldConf($field)) : (array());
			$table = 'tx_icsutopia_site';
			/*if (isset($conf['table']))
			{
				$table = $conf['table'];
				if (!isset($formData[$table]) || empty($formData[$table]))
					$formData[$table] = array();
				if (!isset($formData[$table]['1']))
					$formData[$table]['1'] = array('uid' => 1);
				else
					$formData[$table]['1']['uid'] = 1;
				unset($conf['table']);
			}*/
			$panel .= $this->_forms->getGenSingleField($table, $field, $formData[$table]['1'], '', 0, '', 0, $conf);
		}
		if(TYPO3_MODE == 'BE')
		{
			$panel .= '<tr class="bgColor2"><td></td><td>';
			$panel .= $this->_forms->getSubmitButton($GLOBALS['LANG']->getLL('action.update'), 'update', 'form1');
			$panel .= '</td></tr>';
		}else
		{
			$panel .= '<li class="utopia-form-buttons">' . $this->_forms->getSubmitButton($GLOBALS['LANG']->getLL('action.update'), 'update', 'form1') . '</li>';
		}
		$content = $this->_forms->wrapTotal($panel, $GLOBALS['LANG']->getLL('form1.title'), '');
		return $GLOBALS['dbg'] . $content;
	}
	
	function getFormData()
	{
		$this->initExtensions();
		$data = t3lib_div::_POST('data');
		foreach(array_keys($this->extensions) as $extKey)
			$data = $this->extensions[$extKey]->computeValues($data, 'session');
		//var_dump('Get data');
		//var_dump(t3lib_div::_POST('data'));
		return $data;
	}
	
	function updateData(& $t3d, $formData)
	{
		if (!isset($t3d['header']['records'])) // Workaround FE
			return;
		
		$this->initExtensions();
		// Get global data.
		$user = utopia_session::getUser();
		$config = t3lib_div::makeInstance('utopia_config');
		$group = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'be_groups', 'uid = ' . $config->getConfig('template.begroup'));
		if (isset($group[0])) $group = $group[0];
		$data = $formData['tx_icsutopia_site']['1'];
		$home = array_keys($t3d['header']['pagetree']);
		$home = $home[0];
		$storageTitle = $config->getConfig('template.storage');
		$storage = 0;
		foreach($t3d['header']['records']['pages'] as $page){
			if(strcmp($storageTitle, $page['title']) == 0){
				$storage = $page['uid'];
				break;
			}
		}
		// Set home title.
		$t3d['records']['pages:' . $home]['data']['title'] = $data['title'];
		// Set the records to integrate.
		if (!isset($t3d['records']['tx_icsutopia_site:1']))
			$t3d['records']['tx_icsutopia_site:1'] = array(
				'data' => array(
					'uid' => 1,
					'pid' => $home,
					'crdate' => time(),
					'cruser_id' => ((TYPO3_MODE == 'BE') ? ($user->user['uid']) : (0)),
					'fe_cruser_id' => ((TYPO3_MODE != 'BE') ? ($user->user['uid']) : (0)),
					'deleted' => 0,
					'be_group' => $group['uid'],
					'main_storage' => $storage,
				),
				'rels' => array(
					'be_group' => array(
						'type' => 'db',
						'itemArray' => array(
							array(
								'id' => $group['uid'],
								'table' => 'be_groups'
							)
						)
					),
					'main_storage' => array(
						'type' => 'db',
						'itemArray' => array(
							array(
								'id' => $storage,
								'table' => 'pages'
							)
						)
					),
				)
			);
		t3lib_div::loadTCA('tx_icsutopia_site');
		foreach($data as $field => $value)
			if (isset($GLOBALS['TCA']['tx_icsutopia_site']['columns'][$field]))
				$t3d['records']['tx_icsutopia_site:1']['data'][$field] = $value;
		$t3d['records']['tx_icsutopia_site:1']['data']['tstamp'] = time();
		if (!isset($t3d['records']['sys_filemounts:1']))
			$t3d['records']['sys_filemounts:1'] = array(
				'data' => array(
					'uid' => 1,
					'pid' => 0,
					'hidden' => 0,
					'base' => 1
				),
				'rels' => array()
			);
		$t3d['records']['sys_filemounts:1']['data']['title'] = $data['title'];
		$t3d['records']['sys_filemounts:1']['data']['path'] = str_replace('###TITLE###', $data['title'], $config->getConfig('storage.newroot'));
		if (!isset($t3d['records']['be_groups:' . $group['uid']]))
			$t3d['records']['be_groups:' . $group['uid']] = array(
				'data' => $group,
				'rels' => array(
					'file_mountpoints' => array(
						'type' => 'db',
						'itemArray' => array(
							array(
								'id' => 1,
								'table' => 'sys_filemounts'
							)
						)
					),
					'db_mountpoints' => array(
						'type' => 'db',
						'itemArray' => array(
							array(
								'id' => $home,
								'table' => 'pages'
							)
						)
					)
				)
			);
		$t3d['records']['be_groups:' . $group['uid']]['data']['db_mountpoints'] = $home;
		$t3d['records']['be_groups:' . $group['uid']]['data']['file_mountpoints'] = '1';
		$t3d['records']['be_groups:' . $group['uid']]['data']['title'] = $data['title'];
		if (!isset($t3d['records']['sys_domain:1']))
			$t3d['records']['sys_domain:1'] = array(
				'data' => array(
					'uid' => 1,
					'pid' => $home,
					'crdate' => time(),
					'cruser_id' => ((TYPO3_MODE == 'be') ? ($user->user['uid']) : (0)),
					'hidden' => 0,
				),
				'rels' => array()
			);
		$t3d['records']['sys_domain:1']['data']['domainName'] = $data['url'];
		$t3d['records']['sys_domain:1']['data']['tstamp'] = time();
		// Set the header data about these records and their references.
		if (!isset($t3d['header']['records']['tx_icsutopia_site']))
			$t3d['header']['records']['tx_icsutopia_site'] = array();
		if (!isset($t3d['header']['records']['tx_icsutopia_site']['1']))
			$t3d['header']['records']['tx_icsutopia_site']['1'] = array(
				'uid' => 1,
				'pid' => $home,
				'rels' => array(
					'be_groups:' . $group['uid'] => array(
						'table' => 'be_groups',
						'id' => $group['uid']
					),
					'pages:' . $storage => array(
						'table' => 'pages',
						'id' => $storage
					),
				),
				'softrefs' => array()
			);
		$site = & $t3d['header']['records']['tx_icsutopia_site']['1'];
		$site['title'] = $data['title'];
		$site['size'] = strlen(serialize($t3d['records']['tx_icsutopia_site:1']['data']));
		if (!isset($t3d['header']['records']['be_groups']))
			$t3d['header']['records']['be_groups'] = array();
		if (!isset($t3d['header']['records']['be_groups'][$group['uid']]))
			$t3d['header']['records']['be_groups'][$group['uid']] = array(
				'uid' => $group['uid'],
				'pid' => $group['pid'],
				'rels' => array(
					'sys_filemounts:1' => array(
						'table' => 'sys_filemounts',
						'id' => 1
					),
					'pages:' . $home => array(
						'table' => 'pages',
						'id' => $home
					)
				),
				'softrefs' => array()
			);
		$t3d['header']['records']['be_groups'][$group['uid']]['size'] = strlen(serialize($t3d['records']['be_groups:' . $group['uid']]['data']));
		$t3d['header']['records']['be_groups'][$group['uid']]['title'] = $t3d['records']['be_groups:' . $group['uid']]['data']['title'];
		if (!isset($t3d['header']['records']['sys_filemounts']))
			$t3d['header']['records']['sys_filemounts'] = array();
		if (!isset($t3d['header']['records']['sys_filemounts']['1']))
			$t3d['header']['records']['sys_filemounts']['1'] = array(
				'uid' => 1,
				'pid' => 0,
				'rels' => array(),
				'softrefs' => array()
			);
		$t3d['header']['records']['sys_filemounts']['1']['title'] = $t3d['records']['sys_filemounts:1']['data']['title'];
		$t3d['header']['records']['sys_filemounts']['1']['size'] = strlen(serialize($t3d['records']['sys_filemounts:1']['data']));
		if (!isset($t3d['header']['records']['sys_domain']))
			$t3d['header']['records']['sys_domain'] = array();
		if (!isset($t3d['header']['records']['sys_domain']['1']))
			$t3d['header']['records']['sys_domain']['1'] = array(
				'uid' => 1,
				'pid' => $home,
				'rels' => array(),
				'softrefs' => array()
			);
		$t3d['header']['records']['sys_domain']['1']['title'] = $t3d['records']['sys_domain:1']['data']['domainName'];
		$t3d['header']['records']['sys_domain']['1']['size'] = strlen(serialize($t3d['records']['sys_domain:1']['data']));
		// Set the header pid_lookup about the new records
		if (!isset($t3d['header']['pid_lookup'][$home]))
			$t3d['header']['pid_lookup'][$home] = array();
		if (!isset($t3d['header']['pid_lookup'][$home]['tx_icsutopia_site']))
			$t3d['header']['pid_lookup'][$home]['tx_icsutopia_site'] = array();
		if (!in_array('1', array_keys($t3d['header']['pid_lookup'][$home]['tx_icsutopia_site'])))
			$t3d['header']['pid_lookup'][$home]['tx_icsutopia_site']['1'] = 1;
		if (!isset($t3d['header']['pid_lookup'][$home]['sys_domain']))
			$t3d['header']['pid_lookup'][$home]['sys_domain'] = array();
		if (!in_array('1', array_keys($t3d['header']['pid_lookup'][$home]['sys_domain'])))
			$t3d['header']['pid_lookup'][$home]['sys_domain']['1'] = 1;
		if (!isset($t3d['header']['pid_lookup']['0']))
			$t3d['header']['pid_lookup']['0'] = array();
		if (!isset($t3d['header']['pid_lookup']['0']['be_groups']))
			$t3d['header']['pid_lookup']['0']['be_groups'] = array();
		if (!in_array($group['uid'], array_keys($t3d['header']['pid_lookup']['0']['be_groups'])))
			$t3d['header']['pid_lookup']['0']['be_groups'][$group['uid']] = 1;
		if (!isset($t3d['header']['pid_lookup']['0']['sys_filemounts']))
			$t3d['header']['pid_lookup']['0']['sys_filemounts'] = array();
		if (!in_array('1', array_keys($t3d['header']['pid_lookup']['0']['sys_filemounts'])))
			$t3d['header']['pid_lookup']['0']['sys_filemounts']['1'] = 1;
	}

	function validateInput()
	{
		$data = t3lib_div::_POST('data');
		$this->initExtensions();
		foreach(array_keys($this->extensions) as $extKey)
		{
			if (!$this->extensions[$extKey]->validate($data))
			{
				$this->errors = array_merge($this->errors, $this->extensions[$extKey]->getErrors());
				return false;
			}
			$data = $this->extensions[$extKey]->computeValues($data, 'session');
		}
		//var_dump('Validate');
		//var_dump($data);
		if (!isset($data['tx_icsutopia_site']) || empty($data['tx_icsutopia_site']) || !isset($data['tx_icsutopia_site']['1']) || empty($data['tx_icsutopia_site']['1']))
		{
			$this->errors[] = $GLOBALS['LANG']->getLL('form1.errors.title.empty');
			$this->errors[] = $GLOBALS['LANG']->getLL('form1.errors.url.empty');
			return false;
		}
		if (empty($data['tx_icsutopia_site']['1']['url']))
		{
			$this->errors[] = $GLOBALS['LANG']->getLL('form1.errors.url.empty');
			return false;
		}
		if (!preg_match('@^([A-Z0-9-]+\\.)+[A-Z]+(/.*)?$@i', $data['tx_icsutopia_site']['1']['url']))
		{
			$this->errors[] = $GLOBALS['LANG']->getLL('form1.errors.url.invalid');
			return false;
		}
		if (empty($data['tx_icsutopia_site']['1']['title']))
		{
			$this->errors[] = $GLOBALS['LANG']->getLL('form1.errors.title.empty');
			return false;
		}
		return true;
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/forms/class.utopia_form1.php"]){
include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/forms/class.utopia_form1.php"]);
}
