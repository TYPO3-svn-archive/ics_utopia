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
 * First form extension base class for the site create of the 'ics_utopia' extension.
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */

/**
 * The site creation first form extension base class.
 *
 * USE:
 * Extends this class and overrides the functions. The objects are instantiated to make the object state.
 *
 * @package UTOPIA
 */
class utopia_first_form_base
{
	var $errors = array();
	
	/**
	 * Retrieve the list of hidden form fields. These must be computed by generated ones.
	 *
	 * @param array The list of available fields use to check if our fields is not already hidden.
	 * @return array The list of hidden fields names.
	 */
	function getHiddenFields($available)
	{
		return array();
	}
	
	/**
	 * Retrieve the list of the added fields.
	 *
	 * @return array The list of the fields names.
	 */
	function getNewFields()
	{
		return array();
	}
	
	/**
	 * Retrieve a field's configuration. The concerned table is ics_utopia_site.
	 *
	 * @param string The field name.
	 * @return array The TCA configuration of the field. /*Not implemented: The special value "table" can be added to the array.
	 */
	function getFieldConf($field)
	{
		return array();
	}
	
	/**
	 * Convert the values of the data array from and to the internal representation
	 *
	 * @param array The data array.
	 * @param string The direction of the convertion. Can be "form" or "session". It is the destination. If invalid, nothing is done.
	 * @return array The new data array.
	 */
	function computeValues($data, $dir)
	{
		return $data;
	}
	
	/**
	 * Validate the form input.
	 *
	 * @param array The form input data.
	 * @return boolean If the input is valid or not.
	 */
	function validate($data)
	{
		return true;
	}
	
	/**
	 * Retrieve validation errors
	 *
	 * @return array The list of errors
	 */
	function getErrors()
	{
		return $this->errors;
	}
}
