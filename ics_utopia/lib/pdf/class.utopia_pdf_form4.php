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
 * PDF Generation extension for form4 for the 'ics_utopia' extension modules.
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */

require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_pdf_base.php'));

class utopia_pdf_form4 extends utopia_pdf_base
{
	/**
	 * Retrieve the representation of the pdf output to write.
	 * If the extension is registered with the same id as a form, its data is given.
	 */
	function printPDF($t3d, $formData)
	{
		global $LANG;
		$pages = explode(',', $formData['tx_icsutopia_site'][1]['base_model']);
		$pagesData = array();
		foreach($pages as $index => $page)
		{
			$pageRec = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('title', 'pages', 'uid = ' . $page);
			$pagesData[] = array(
				'type' => 'dt',
				'value' => $LANG->getLL('pdf.summary.4.field.l' . $index),
			);
			$pagesData[] = array(
				'type' => 'dd',
				'value' => $pageRec[0]['title'],
			);
		}

		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ics_utopia']['selector']))
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ics_utopia']['selector'] as $classRef)
			{
				$objRef = & t3lib_div::getUserObj($classRef, false);
				$pagesData = array_merge($pagesData, $objRef->getPdf($t3d, $formData));
			}
		
		return array(
			'type' => 'title1',
			'value' => $LANG->getLL('pdf.summary.4.title'),
			'paragraphs' => array(
				array(
					'type' => 'dl',
					'items' => $pagesData,
				),
			),
		);
	}
}
