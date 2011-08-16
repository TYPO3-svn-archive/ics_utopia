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
 * Fourth form for the site create of the 'ics_utopia' extension.
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
class utopia_form4 extends utopia_form_base
{
	var $level = 1; // TODO: Make this var as settings.

	function renderForm($mode, $formData)
	{
		parent::renderForm($mode, $formData);
		if (empty($formData))
			$formData = array();
		if (!isset($formData['tx_icsutopia_site']) || empty($formData['tx_icsutopia_site']))
			$formData['tx_icsutopia_site'] = array();
		if (!isset($formData['tx_icsutopia_site']['1']))
			$formData['tx_icsutopia_site']['1'] = array('uid' => 1);
		else
			$formData['tx_icsutopia_site']['1']['uid'] = 1;
		$config = t3lib_div::makeInstance('utopia_config');
		$fields = array();
		for ($i = 0; $i < $this->level; ++$i)
		{
			$fields['level' . $i] = array(
				'label' => $GLOBALS['LANG']->getLL('form4.field.l' . $i),
				'config' => array(
					"type" => "select",	
					"foreign_table" => "pages",	
					"foreign_table_where" => "AND pages.pid = " . (($i) ? ('###REC_FIELD_level' . ($i - 1) . '###') : ($config->getConfig('template.root'))) . " AND pages.hidden = 0 AND pages.deleted = 0 ORDER BY pages.title",	
					"size" => 1,
					"minitems" => 1,
					"maxitems" => 1,
				)
			);
			if ($i < $this->level - 1)
				$fields['level' . $i]['requestUpdate'] = '1';
			if ($i)
				$fields['level' . $i] = t3lib_div::array_merge_recursive_overrule($fields['level' . $i], array(
						'displayCond' => 'FIELD:level' . ($i - 1) . ':REQ:true',
						'config' => array(
							'disableNoMatchingValueElement' => '1',
						)
					));
		};
		// Ajouter le support des icônes.
		if (!isset($formData['tx_icsutopia_site']['1']['base_model']) || empty($formData['tx_icsutopia_site']['1']['base_model']) || ($formData['tx_icsutopia_site']['1']['base_model'] == ','))
		{
			$fields['level0']['config']['items'] = array(
				array('', '')
			);
			$row = array(
			);
			for ($i = 0; $i < $this->level; ++$i)
				$row['level' . $i] = '';
		}
		else
		{
			$row = split(',', $formData['tx_icsutopia_site']['1']['base_model']);
			for ($i = 0; $i < $this->level; ++$i)
			{
				if (!isset($row[$i]) || empty($row[$i]))
				{
					$fields['level' . $i]['config']['items'] = array(
						array('', '')
					);
					$row['level' . $i] = '';
				}
				else
				{
					$row['level' . $i] = $row[$i];
					unset($row[$i]);
				}
			}
		}
		$row['uid'] = $formData['tx_icsutopia_site']['1']['uid'];
		$panel = $this->_forms->getGenPaletteFields('tx_icsutopia_site', $row, $GLOBALS['LANG']->getLL('form4.design'), $fields);

		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ics_utopia']['selector']) && isset($formData['tx_icsutopia_site']['1']['level' . ($this->level - 1)]))
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ics_utopia']['selector'] as $classRef)
			{
				$objRef = & t3lib_div::getUserObj($classRef, false);
				$objRef->init($formData['tx_icsutopia_site']['1']['level' . ($this->level - 1)]);
				$panel .= $objRef->renderFields($this->_forms, $formData);
			}

		if(TYPO3_MODE == 'BE')
		{
			$panel .= '<tr class="bgColor2"><td></td><td>';
			$panel .= $this->_forms->getSubmitButton($GLOBALS['LANG']->getLL('action.update'), 'update', 'form4');
			$panel .= '</td></tr>';
		}
		else
		{
			$panel .= '<li class="utopia-form-buttons">' . $this->_forms->getSubmitButton($GLOBALS['LANG']->getLL('action.update'), 'update', 'form4') . '</li>';
		}
		$content = $this->_forms->wrapTotal($panel, $GLOBALS['LANG']->getLL('form4.title'), '');
		return $content;
	}
	
	function getFormData()
	{
		$data = t3lib_div::_POST('data');
		if (empty($data))
			$data = array();
		if (!isset($data['tx_icsutopia_site']) || empty($data['tx_icsutopia_site']))
			$data['tx_icsutopia_site'] = array();
		$config = t3lib_div::makeInstance('utopia_config');
		if (!isset($data['tx_icsutopia_site']['1']))
		{
			$data['tx_icsutopia_site'][1] = array();
			$this->autoSelect($config->getConfig('template.root'), 0);
		}
		else
		{
			$data['tx_icsutopia_site']['1']['base_model'] = implode(',', $this->loadData($data, $config->getConfig('template.root'), 0));
		}

		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ics_utopia']['selector']) && isset($data['tx_icsutopia_site']['1']['level' . ($this->level - 1)]))
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ics_utopia']['selector'] as $classRef)
			{
				$objRef = & t3lib_div::getUserObj($classRef, false);
				$objRef->init($data['tx_icsutopia_site']['1']['level' . ($this->level - 1)]);
				$data = $objRef->getData($data);
			}
		return $data;
	}

	function autoSelect($root, $level)
	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid', 
			'pages', 
			'pid = ' . $root . ' AND hidden = 0 AND deleted = 0'
		);
		if (count($res) == 1)
		{
			$data['tx_icsutopia_site']['1']['level' . $level] = $res[0]['uid'];
			++$level;
			if ($level < $this->level)
				$this->autoSelect($res[0]['uid'], $level);
		}
	}
	
	function loadData(&$data, $root, $level)
	{
		$result = array();
		if (isset($data['tx_icsutopia_site']['1']['level' . $level]))
		{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'1', 
				'pages', 
				'uid = ' . $data['tx_icsutopia_site']['1']['level' . $level] . ' AND pid = ' . $root . ' AND hidden = 0 AND deleted = 0'
			);
			if (count($res) == 0)
			{
				unset($data['tx_icsutopia_site']['1']['level' . $level]);
				$result[] = '';
			}
			else
				$result[] = $data['tx_icsutopia_site']['1']['level' . $level];
			++$level;
			if ($level < $this->level)
				$result = array_merge($result, $this->loadData($data, $result[0], $level));
		}
		return $result;
	}
	
	function updateData(& $t3d, $formData, $title, $creator)
	{
		// Get global data
		$user = utopia_session::getUser();
		$config = t3lib_div::makeInstance('utopia_config');
		$group = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'be_groups', 'uid = ' . $config->getConfig('template.begroup'));
		if (isset($group[0])) $group = $group[0];
		$staticTables = $config->getConfig('import.statics');
		$staticTables = t3lib_div::trimExplode(',', $staticTables, 1);
		// Generate the t3d file.
		$export = t3lib_div::makeInstance('utopia_t3d_editor');
		$id = explode(',', $formData['tx_icsutopia_site']['1']['base_model']);
		$id = $id[$this->level - 1];
		$t3d = $export->exportData($inData = array(
			'siteTitle' => $title,
			'pagetree' => array(
				'id' => $id,
				'levels' => 999,
				'tables' => array(
					'_ALL',
				),
				'maxNumber' => 10000,
			),
			'external_ref' => array(
				'tables' => array(
					'_ALL',
				),
			),
			'external_static' => array(
				'tables' => $staticTables,
			),
			'maxFileSize' => 10000,
			'extension_dep' => t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXT']['extList']),
			'creator' => $creator
		));
		//var_dump(md5(serialize($t3d['header']['files'])));
		//$export->saveFile(PATH_site . 'typo3temp/temp.xml', $t3d);
		// Get additional global data
		if (!isset($t3d['header']['records'])) // Workaround FE
			return;
		
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
		$t3d['records']['tx_icsutopia_site:1']['data']['base_model'] = $formData['tx_icsutopia_site']['1']['base_model'];
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
		$site['size'] = strlen(serialize($t3d['records']['tx_icsutopia_site:1']['data']));
		// Set the header pid_lookup about the new records
		if (!isset($t3d['header']['pid_lookup'][$home]))
			$t3d['header']['pid_lookup'][$home] = array();
		if (!isset($t3d['header']['pid_lookup'][$home]['tx_icsutopia_site']))
			$t3d['header']['pid_lookup'][$home]['tx_icsutopia_site'] = array();
		if (!in_array('1', array_keys($t3d['header']['pid_lookup'][$home]['tx_icsutopia_site'])))
			$t3d['header']['pid_lookup'][$home]['tx_icsutopia_site']['1'];

		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ics_utopia']['selector']))
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ics_utopia']['selector'] as $classRef)
			{
				$objRef = & t3lib_div::getUserObj($classRef, false);
				$objRef->init($home);
				$data = $objRef->updateData($t3d, $formData);
			}
	}

	function validateInput()
	{
		$data = $this->getFormData();
		if (!isset($data['tx_icsutopia_site']['1']['level' . ($this->level - 1)]) || !is_numeric($data['tx_icsutopia_site']['1']['level' . ($this->level - 1)]))
		{
			$this->errors[] = $GLOBALS['LANG']->getLL('form4.errors.noselect');
			return false;
		}

		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ics_utopia']['selector']))
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ics_utopia']['selector'] as $classRef)
			{
				$objRef = & t3lib_div::getUserObj($classRef, false);
				$objRef->init($data['tx_icsutopia_site']['1']['level' . ($this->level - 1)]);
				if (!$objRef->validateData($data, $this))
					return false;
			}
		return true;
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/forms/class.utopia_form4.php"]){
include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/forms/class.utopia_form4.php"]);
}
