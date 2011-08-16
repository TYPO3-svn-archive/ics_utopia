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
 * Base class for the form extension for the 'ics_utopia' extension modules.
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */

require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_forms.php'));

/**
 * The site creation forms base class.
 *
 * USE:
 * Extends this class and overrides the functions. The objects are instantiated to make the object state.
 *
 * @package UTOPIA
 */
class utopia_form_base
{
	var $_forms = null;
	var $errors = array();

	/**
	 * Render this form as html code. Initialize the form helper. Must be called in derived classes.
	 *
	 * @param string The current rendering mode. Can be 'be' or 'fe'.
	 */
	function renderForm($mode, $formData)
	{
		$this->_forms = t3lib_div::makeInstance('utopia_forms');
		$this->_forms->init($mode);
		return '';
	}
	
	/**
	 * Retrieve the form data. The form data is needed to show the form again with previous input.
	 *
	 * @return array The data.
	 */
	function getFormData()
	{
		return array();
	}
	
	/**
	 * Update the T3D file data against the inputed form values.
	 * 
	 * @param array The array representation of the whole T3D file.
	 * @param array The form data representing the inputed values.
	 */
	function updateData(& $t3d, $formData)
	{
	}
	
	/**
	 * Check the user input.
	 *
	 * @return boolean Indicate if the input is valid.
	 */
	function validateInput()
	{
		return true;
	}
	
	/**
	 * Retrieve the form helper. This function has not to be overridden.
	 *
	 * @return The form helper reference.
	 */
	function & getForms()
	{
		return $this->_forms;
	}
	
	/**
	 * Retrieve validation errors
	 *
	 * @return array The list of errors.
	 */
	function getErrors()
	{
		return $this->errors;
	}
}
