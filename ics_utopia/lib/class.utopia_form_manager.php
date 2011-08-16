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
 * Form manager for the 'ics_utopia' extension.
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */

require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_config.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_form_base.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_session.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/forms/class.utopia_form_last.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_form_path.php'));
require_once(t3lib_extMgm::extPath('lang', 'lang.php'));

/**
 * The forms management. Manage the navigation between forms.
 *
 * USE:
 * Not intended to be instanciated. Use the calling object this pointer.
 *
 * CONTRACT:
 * Callers must implements these functions and attributes:
 * - saveFormData($formId, $form)
 * - updateT3D($form, $formId) which calls the local updateT3D. It is designed to enable to make some settings before update.
 * - $_session
 *
 * @author In Cité Solution <technique@incitesolution.fr>
 * @package UTOPIA
 */
class utopia_form_manager
{
	function initPath()
	{
		$this->_formPath = t3lib_div::makeInstance('utopia_form_path');
		$formPath = & $this->_formPath;
		$formPath->initList();
		$formPath->removeDisabled();
		$formPath->sort();
		$formPath->buildAssocs();
		$this->tca_descr = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ics_utopia']['CSH'];
	}

	function & previousAction(& $formId)
	{
		$form = & self::getForm($formId);
		if ($form !== null)
		{
			$this->saveFormData($formId, $form);
			$formId = self::getPreviousFormId($formId);
			$form = & self::getForm($formId);
		}
		return $form;
	}
	
	function & nextAction(& $formId)
	{
		$form = & self::getForm($formId);
		if ($form !== null)
		{
			$this->saveFormData($formId, $form);
			if ($form->validateInput())
			{
				$this->updateT3D($form, $formId);
				$formId = self::getNextFormId($formId);
				$form = & self::getForm($formId);
			}
		}
		return $form;
	}
	
	function & getForm($formId)
	{
		$form = null;
		$classRef = $this->_formPath->getClassRef($formId);
		if ($classRef != null)
		{
			$form = & t3lib_div::getUserObj($classRef, false);
			if (isset($this->tca_descr[$formId]))
			{
				$tables = array_keys($this->tca_descr[$formId]);
				foreach ($this->tca_descr[$formId] as $table => $files)
				{
					unset($GLOBALS['TCA_DESCR'][$table]['columns']);
					foreach ($files as $file)
						t3lib_extMgm::addLLrefForTCAdescr($table, $file);
				}
				//unset($this->tca_descr[$formId]);
				if (!isset($GLOBALS['LANG']))
					$LANG = new language($GLOBALS['TSFE']->lang);
				else
					$LANG = & $GLOBALS['LANG'];
				foreach ($tables as $table)
					$LANG->loadSingleTableDescription($table);
			}
		}
		if ($formId == 'last')
		{
			$form = t3lib_div::makeInstance('utopia_form_last');
			$form->init($this->_session);
		}
		return $form;
	}
	
	function getNextFormId($formId)
	{
		if ($formId != 'last')
		{
			if ($this->_formPath->hasNext($formId))
				return $this->_formPath->getNext($formId);
			else
				return 'last';
		}
		return $formId;
	}
	
	function getPreviousFormId($formId)
	{
		if ($formId == 'last')
			return $this->_formPath->getLast();
		if ($this->_formPath->hasPrevious($formId))
			return $this->_formPath->getPrevious($formId);
		return $formId;
	}
	
	function updateT3D(& $form, $formId)
	{
		$t3dFile = $this->_session->getT3DFile();
		// If true, were are on form 4 after filling the forms 1 to 3 and going to next.
		$t3d = null;
		if ($t3dFile == null)
		{
			if ($formId == 4) // Make sure it is the good one.
			{
				$form->updateData($t3d, $this->_session->getFormData($formId), $this->_session->get(array('forms', 1, 'tx_icsutopia_site', '1', 'title')), $this->_session->get(array('creator')));
				for ($i = 1; $i < 4; ++$i)
				{
					$other = self::getForm($i);
					if (!$other)
						continue;
					$data = $this->_session->getFormData($i);
					$other->updateData($t3d, $data);
				}
				$t3d['header']['meta']['title'] = $t3d['records']['tx_icsutopia_site:1']['data']['title'];
				$t3dFile = PATH_site . uniqid('typo3temp/utopia') . '.t3d';
				$this->_session->setT3DFile($t3dFile);
			}
		}
		// In this case, we load and update.
		else
		{
			$t3d = utopia_t3d_editor::loadFile($t3dFile, 1);
			$form->updateData($t3d, $this->_session->getFormData($formId), $this->_session->get(array('forms', 1, 'tx_icsutopia_site', '1', 'title')), $this->_session->get(array('creator')));
			if ($formId == 4) // If we were on form 4, there was a regeneration of the file. So, we update it again for first forms.
			{
				for ($i = 1; $i < 4; ++$i)
				{
					$other = self::getForm($i);
					if (!$other)
						continue;
					$data = $this->_session->getFormData($i);
					$other->updateData($t3d, $data);
				}
				$t3d['header']['meta']['title'] = $t3d['records']['tx_icsutopia_site:1']['data']['title'];
				// Add update to extension forms.
			}
		}
		// Now write if defined.
		if ($t3d != null)
			utopia_t3d_editor::saveFile($t3dFile, $t3d);
	}
	
	function getStartFormId()
	{
		return $this->_formPath->getFirst();
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_form_manager.php"]){
include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_form_manager.php"]);
}
