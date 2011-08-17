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
 * Base class for the import post processing for the 'ics_utopia' extension.
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */

/**
 * The import post processing base class.
 *
 * USE:
 * Extends this class and overrides the functions. The objects are instantiated for easier implementation.
 *
 * @package UTOPIA
 */
abstract class utopia_postAction_base
{
	/**
	 * Execute the action.
	 *
	 * @param array The T3D file array representation resulting from the importation.
	 */
	abstract function doAction(& $t3d);
}
