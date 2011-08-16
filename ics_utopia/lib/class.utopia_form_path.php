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

/**
 * The form path management.
 *
 * USE:
 * Not to be used in user code.
 *
 * @author In Cité Solution <technique@incitesolution.fr>
 * @package UTOPIA
 */
class utopia_form_path
{
	var $forms = array();	// List of available forms, in order.
	var $classes = array(); // Dictionary of form classes by form.
	var $cache;				// Cache data.
	var $useCache = null;	// Does the cache is used?
	var $prev = array();	// Previous forms map.
	var $next = array();	// Next forms map.
	
	/**
	 * Build the list of available forms.
	 *
	 * @return int Count of forms.
	 */
	function initList()
	{
		global $TYPO3_CONF_VARS;
		$this->forms[] = 1;
		$this->classes[1] = 'EXT:ics_utopia/lib/forms/class.utopia_form1.php:&utopia_form1';
		$this->forms[] = 2;
		$this->classes[2] = 'EXT:ics_utopia/lib/forms/class.utopia_form2.php:&utopia_form2';
		$this->forms[] = 3;
		$this->classes[3] = 'EXT:ics_utopia/lib/forms/class.utopia_form3.php:&utopia_form3';
		$this->forms[] = 4;
		$this->classes[4] = 'EXT:ics_utopia/lib/forms/class.utopia_form4.php:&utopia_form4';
		if (isset($TYPO3_CONF_VARS['EXTCONF']['ics_utopia']['forms']))
			foreach ($TYPO3_CONF_VARS['EXTCONF']['ics_utopia']['forms'] as $formId => $formClass)
			{
				$this->forms[] = $formId;
				$this->classes[$formId] = $formClass;
			}
		$config = t3lib_div::makeInstance('utopia_config');
		$this->cache = $config->getConfig('pathcache');
		if (($this->cache == null) || !$this->checkCache())
			$this->cache = array(
				'availableHash' => md5(serialize($this->forms)), 
				'forms' => array(), 
				'prev' => array(), 
				'next' => array(), 
				'disabledHash' => '',
				'orderHash' => ''
			);
		if ($this->useCache)
		{
			$this->forms = $this->cache['forms'];
			$this->next = $this->cache['next'];
			$this->prev = $this->cache['prev'];
		}
		return count($this->forms);
	}
	
	/**
	 * Check if cache is valid.
	 *
	 * @return boolean Whether the cache is valid or not.
	 */
	function checkCache()
	{
		if ($this->useCache !== null)
			return $this->useCache;
		$config = t3lib_div::makeInstance('utopia_config'); // TODO: Use registry.
		$disabled = $config->getDisabledExts('form');
		$order = $config->getConfig('forms.order');
		return $this->useCache = (($this->cache['availableHash'] == md5(serialize($this->forms))) &&
			($this->cache['disabledHash'] == md5(serialize($disabled))) &&
			($this->cache['orderHash'] == md5(serialize($order))));
	}
	
	/**
	 * Remove the disabled forms from the list.
	 *
	 * @return int Count of forms
	 */
	function removeDisabled()
	{
		if ($this->useCache)
			return count($this->form);
		$config = t3lib_div::makeInstance('utopia_config');
		$disabled = $config->getDisabledExts('form');
		$this->forms = array_diff($this->forms, $disabled);
		$this->cache['disabledHash'] = md5(serialize($disabled));
		return count($this->forms);
	}
	
	/**
	 * Sort the forms depending on the configuration. The four static forms are statically placed first.
	 */
	function sort()
	{
		if ($this->useCache)
			return;
		$config = t3lib_div::makeInstance('utopia_config');
		$order = $config->getConfig('forms.order');
		$this->cache['orderHash'] = md5(serialize($order));
		if ($order)
			$order = explode(',', $order);
		else
			$order = array();
		$order = array_map(create_function('$val', 'return (is_numeric($val)) ? (intval($val)) : ($val);'), $order);
		$order = array_merge(array(1, 2, 3, 4), array_diff($order, array(1, 2, 3, 4)));
		$out = array_diff($this->forms, $order);
		$this->forms = array_merge(array_intersect($order, $this->forms), $out);
		$this->cache['forms'] = $this->forms;
	}
	
	function buildAssocs()
	{
		if ($this->useCache)
			return;
		$last = null;
		foreach ($this->forms as $form)
		{
			if ($last !== null)
			{
				$this->next[$last] = $form;
				$this->prev[$form] = $last;
			}
			$last = $form;
		}
		$this->cache['prev'] = $this->prev;
		$this->cache['next'] = $this->next;
		$config = t3lib_div::makeInstance('utopia_config'); // TODO: Use registry
		$config->setConfig('pathcache', $this->cache);
		// $config->saveConfig();
	}
	
	function getNext($formId)
	{
		if (isset($this->next[$formId]))
			return $this->next[$formId];
		return null;
	}
	
	function hasNext($formId)
	{
		return (isset($this->next[$formId]));
	}
	
	function getPrevious($formId)
	{
		if (isset($this->prev[$formId]))
			return $this->prev[$formId];
		return null;
	}
	
	function hasPrevious($formId)
	{
		return (isset($this->prev[$formId]));
	}
	
	function getClassRef($formId)
	{
		if (isset($this->classes[$formId]))
			return $this->classes[$formId];
		return null;
	}
	
	function getFirst()
	{
		$first = array_diff($this->forms, array_keys($this->prev));
		if (count($first))
			return array_shift($first);
		return null;
	}
	
	function getLast()
	{
		$last = array_diff($this->forms, array_keys($this->next));
		if (count($last))
			return array_shift($last);
		return null;
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_form_path.php"]){
include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_form_path.php"]);
}
