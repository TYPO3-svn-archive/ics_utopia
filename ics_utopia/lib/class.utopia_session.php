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
 * Preserve and manage the session data for the 'ics_utopia' extension modules.
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */

require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_t3d_editor.php'));
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_config.php'));

/**
 * The session management. Manage all the site creation session.
 * It have two modes: the session is saved only using TYPO3's session management or the session is saved in the related T3D file and the TYPO3's session only contains the reference to it.
 *
 * USE:
 * Instanciate the class.
 * Use the get/setFormData functions to retrieve or define form data.
 * Use the get/setT3DFile functions to manage the T3D file location.
 * Use the saveSession function to save the session before the script exits.
 *
 * @author In Cité Solution <technique@incitesolution.fr>
 * @package UTOPIA
 */
class utopia_session
{
	var $_t3dHeader = null;	// T3D header content cache.
	var $_session = null; // UTOPIA's session content.
	var $extKey = 'ics_utopia'; // The extension key.

	// Define a TYPO3's session key data.
	function _setKey($key, $data)
	{
		$user = & self::getUser();
		$user->setAndSaveSessionData($key, $data);
	}
	
	// Retrieve a TYPO3's session key data.
	function _getKey($key)
	{
		$user= & self::getUser();
		return $user->getSessionData($key);
	}
	
	/**
	 * Retrieve the current user object. It loads the user depending on the TYPO3_MODE.
	 * 
	 * @return object Current user.
	 */
	function & getUser()
	{
		if (TYPO3_MODE == 'BE')
			$user = & $GLOBALS['BE_USER'];
		else
			$user = & $GLOBALS['TSFE']->fe_user;
		return $user;
	}
	
	/**
	 * Initialize the session object. Retrieve the session data from the TYPO3's session or the related T3D file.
	 */
	function utopia_session()
	{
		$this->_session = self::_getKey($this->extKey);
		
		if (!is_array($this->_session)){
			
			$user = $this->getUser();
			$this->_session = array(
				'creator' => array(
					'type' => strtolower(TYPO3_MODE),
					'id' => $user->user['uid']
				),
			);
		}
		if (isset($this->_session['t3d']) && file_exists($this->_session['t3d']))
		{
			$this->_loadT3DHeader();
			//var_dump($this->_t3dHeader['meta']['session']);
			$this->_session = $this->_t3dHeader['meta']['session'];
			if(!is_array($this->_session['creator']))
			{
				$user = $this->getUser();
				$this->_session['creator']['type'] = strtolower(TYPO3_MODE);
				$this->_session['creator']['id'] = $user->user['uid'];		
			}
			//var_dump($this->_session);
		}
	}
	
	/**
	 * Retrieve the specified form data.
	 * 
	 * @param string The form identifier.
	 * @return array The form data.
	 */
	function getFormData($formId)
	{
		// TODO: Récupérer les données d'un formulaire à partir de la session.
		if (isset($this->_session['forms'][$formId]))
			return $this->_session['forms'][$formId];
		return array();
	}
	
	/**
	 * Define the specified form data.
	 * 
	 * @param string The form identifier.
	 * @param array The form data.
	 */
	function setFormData($formId, $data)
	{
		// TODO: Définir les données d'un formulaire dans la session.
		if (!isset($this->_session['forms']))
			$this->_session['forms'] = array();
		$this->_session['forms'][$formId] = $data;
	}
	
	/**
	 * Retrieve the relative T3D file path if set.
	 *
	 * @return string The file path if set. <code>null</code> if not set.
	 */
	function getT3DFile()
	{
		if (isset($this->_session['t3d']))
			return $this->_session['t3d'];
		return null;
	}
	
	/**
	 * Define the relative T3D file path;
	 *
	 * @param string The file path.
	 */
	function setT3DFile($filename)
	{
		$this->_session['t3d'] = $filename;
	}

	/**
	 * Retrieve a session key value. If the key is not found, <code>null</code> is returned.
	 * 
	 * @param array Complete key path. The path is represented as an array of indexes to pass through.
	 * @return string The key value.
	 */
	function get($key)
	{
		$final = $key;
		$result = & $this->_session;
		while (count($final) > 1)
		{
			if (!isset($result[$final[0]]))
				return null;
			$result = & $result[array_shift($final)];
		}
		return $result[$final[0]];
	}
	
	/**
	 * Define a session key value.
	 *
	 * @param array Complete key path. The path is represented as an array of indexes to pass through.
	 * @param string Key value.
	 * @see getConfig()
	 */
	function set($key, $value)
	{
		$final = $key;
		$result = & $this->_session;
		while (count($final) > 1)
		{
			if (!isset($result[$final[0]]))
				$result[$final[0]] = array();
			$result = & $result[array_shift($final)];
		}
		$result[$final[0]] = $value;
	}
	
	// Loads the relative T3D file header data.
	function _loadT3DHeader()
	{
		//var_dump('Load');
		$this->_t3dHeader = utopia_t3d_editor::loadFile($this->_session['t3d']);
		//var_dump($this->_t3dHeader);
		$this->_t3dHeader = $this->_t3dHeader['header'];
		//var_dump($this->_t3dHeader);
	}
	
	/**
	 * Write the new session data to the TYPO3's session manager and to the relative T3D file if set.
	 */
	function saveSession()
	{

		if (!isset($this->_session['t3d']))
			$this->_setKey($this->extKey, $this->_session);
		else
		{
			if(!is_array($this->_session['creator']))
			{
				$user = $this->getUser();
				$this->_session['creator']['type'] = strtolower(TYPO3_MODE);
				$this->_session['creator']['id'] = $user->user['uid'];		
			}
			
			// TODO: Mettre à jour le fichier t3d avec les nouvelles données de session. Ne pas utiliser l'en-tête en cache.
			$t3d = utopia_t3d_editor::loadFile($this->_session['t3d'], 1);

			$t3d['header']['meta']['session'] = $this->_session;
			
			utopia_t3d_editor::saveFile($this->_session['t3d'], $t3d);
			
			$this->_setKey($this->extKey, array( 't3d' => $this->_session['t3d'] ));
		}
	}
	
	/**
	 * Forget all session data.
	 */
	function reset()
	{
		$this->_session = array();
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_session.php"]){
include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_session.php"]);
}
