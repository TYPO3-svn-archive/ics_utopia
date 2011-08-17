<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Pierrick Caillon <pierrick@in-cite.net>
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
 * Import post processing for the 'ics_utopia' extension.
 * Post processing of constants for page ids.
 *
 * @author	Pierrick Caillon <pierrick@in-cite.net>
 */

require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_postAction_base.php'));

/**
 * The import post processing class for template constants manipulation.
 * Work on constant branch for page ids.
 *
 * USE:
 * Not designed to be called by user code.
 *
 * @package UTOPIA
 */
class tx_icsutopia_Templates_PagePostAction extends utopia_postAction_base {
	/**
	 * Execute the action.
	 *
	 * @param array The T3D file array representation resulting from the importation. The new index 'import_mapId' maps the file ids to the imported ids, by tables.
	 */
	function doAction(& $t3d)
	{
		global $TYPO3_DB, $TCA;
		if (empty($t3d['import_mapId']['tx_icsutopia_site'])) {
			return;
		}
		if (empty($t3d['import_mapId']['sys_template'])) {
			return;
		}
		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ics_utopia']);
		if (empty($conf['prefix'])) {
			return;
		}
		if ($conf['prefix']{strlen($conf['prefix']) - 1} != '.') {
			$conf['prefix'] .= '.';
		}
		$templates = $TYPO3_DB->exec_SELECTgetRows(
			'uid, constants', 
			'sys_template', 
			'uid IN (' . implode(',', $t3d['import_mapId']['sys_template']) . ')'
		);
		$updates = array();
		foreach ($templates as $template) {
			$constants = explode(PHP_EOL, $template['constants']);
			foreach ($constants as $id => $line) {
				$line = t3lib_div::trimExplode('=', $line);
				if (count($line) == 2) {
					if (t3lib_div::isFirstPartOfStr($line[0], $conf['prefix']) && is_numeric($line[1]) && isset($t3d['import_mapId']['pages'][intval($line[1])])) {
						$line[1] = $t3d['import_mapId']['pages'][intval($line[1])];
						$constants[$id] = implode(' = ', $line);
					}
				}
			}
			$updates[$template['uid']] = array(
				'constants' => implode(PHP_EOL, $constants)
			);
		}
		foreach ($updates as $id => $update) {
			$TYPO3_DB->exec_UPDATEquery(
				'sys_template',
				'uid = ' . $id,
				$update
			);
		}
	}
}
