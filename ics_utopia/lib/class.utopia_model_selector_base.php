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
 * Model selector base for the site create of the 'ics_utopia' extension.
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */

require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_forms.php'));

class utopia_model_selector_base
{
	function init($modelRoot)
	{
	}
	
	function renderFields(& $forms, $formData)
	{
	}
	
	function getPdf(& $t3d, $formData)
	{
	}
	
	function validateData($formData)
	{
	}
	
	function getData($formData)
	{
	}
	
	function updateData(& $t3d, $formData)
	{
	}
	
	function neededLevelReload(& $forms, $levels, $maxLevels)
	{
	}
}