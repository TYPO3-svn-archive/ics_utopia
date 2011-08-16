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
 * Configuration manager for the 'ics_utopia' extension. Mainly enables to read the configuration. Also provide updating the configuration.
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */

require_once(PATH_t3lib.'class.t3lib_install.php');

/**
 * The configuration management. Load and save configuration.
 *
 * USE:
 * Instantiate the class.
 * To get configuration, use getConfig($key) where $key is the complete name of the key i.e. "some.thing".
 * To set configuration, use setConfig($key, $value). After that, use saveConfig() to apply changes to the futur loads.
 *
 * @author In Cité Solution <technique@incitesolution.fr>
 * @package UTOPIA
 */
class utopia_config
{
	var $extKey = 'ics_utopia';
	var $_conf = null;	//Extension settings.
	
	/**
	 * Initialize the configuration from the global vars. Use the default values if not defined.
	 */
	function utopia_config()
	{
		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]))
		{
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]))
				$this->_conf = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey];
			elseif (!empty($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]))
			{
				$this->_conf = @unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
				if (($this->_conf === false) && ($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey] != serialize(false)))
					$this->_conf = array();
			}
		}
		if ($this->_conf == null)
			$this->_conf = array();
		$this->_conf = t3lib_div::array_merge_recursive_overrule(self::_defaultValues(), $this->_conf);
	}
	
	// Default value set.
	// TODO: Update this whenever new values are defined in ext_conf_template.txt.
	function _defaultValues()
	{
		return array(
			'template.' => array(
				'root' => 0,
				'beusers' => '',
				'begroup' => 0,
				'storage' => 'Storage',
				'createfe' => 0,
				'fegroup' => 'Private access'
			),
			'forms.' => array(
				'order' => '',
			),
			'disabledexts' => '',
			'mail.' => array(
				'adminfile' => '',
				'adminsubject' => 'New site',
				'acceptfile' => '',
				'acceptsubject' => 'Site accepted',
				'rejectfile' => '',
				'rejectsubject' => 'Site rejected',
				'adminusers' => '',
			),
			'storage.' => array(
				'requests' => '',
				'archives' => '',
				'newroot' => '###FILE###/',
			),
			'import.' => array(
				'statics' => 'sys_languages',
			),
		);
	}

	/**
	 * Retrieve a configuration key value. If the key is not found, <code>null</code> is returned.
	 * 
	 * @param string Complete key path. Like "some.thing" or "myvalue" or "my.deepfully.defined.value".
	 * @return string The key value.
	 */
	function getConfig($key)
	{
		$final = explode('.', $key);
		$result = $this->_conf;
		while (count($final) > 1)
		{
			if (!isset($result[$final[0] . '.']))
				return null;
			$result = $result[array_shift($final) . '.'];
		}
		return $result[$final[0]];
	}
	
	/**
	 * Define a configuration key value.
	 *
	 * @param string Complete key path.
	 * @param string Key value.
	 * @see getConfig()
	 */
	function setConfig($key, $value)
	{
		$final = explode('.', $key);
		$result = $this->_conf;
		while (count($final) > 1)
		{
			if (!isset($result[$final[0] . '.']))
				$result[$final[0] . '.'] = array();
			$result = & $result[array_shift($final) . '.'];
		}
		$result[$final[0]] = $value;
	}
	
	/**
	 * Save the configuration file to the localconf.
	 */
	function saveConfig()
	{
		// Instance of install tool
		$instObj = new t3lib_install;
		$instObj->allowUpdateLocalConf = 1;
		$instObj->updateIdentity = $this->extKey;

		// Get lines from localconf file
		$lines = $instObj->writeToLocalconf_control();
		$instObj->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'EXT\'][\'extConf\'][\''.$this->extKey.'\']', serialize($this->_conf));	// This will be saved only if there are no linebreaks in it !
		$instObj->writeToLocalconf_control($lines);

		t3lib_extMgm::removeCacheFiles();
	}
	
	/**
	 * Retrieve the list of the disabled extensions for a specific type.
	 *
	 * @param string Type of extension.
	 * @return array The ids of the extensions.
	 */
	function getDisabledExts($type)
	{
		$exts = array();
		$disabled = explode(',', $this->getConfig('disabledexts'));
		foreach($disabled as $extinfo)
		{
			$extinfo = explode(':', $extinfo, 2);
			if ($extinfo[0] == $type)
				$exts[] = (is_numeric($extinfo[1])) ? (intval($extinfo[1])) : ($extinfo[1]);
		}
		return $exts;
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_config.php"]){
include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_config.php"]);
}
