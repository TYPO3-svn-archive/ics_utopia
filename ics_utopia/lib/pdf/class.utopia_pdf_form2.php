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
 * PDF Generation extension for form2 for the 'ics_utopia' extension modules.
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */

require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_pdf_base.php'));

class utopia_pdf_form2 extends utopia_pdf_base
{
	/**
	 * Retrieve the representation of the pdf output to write.
	 * If the extension is registered with the same id as a form, its data is given.
	 */
	function printPDF($t3d, $formData)
	{
		global $LANG;
		$beUsers = array(
			'numCol' => 5,
			'header' => array(
				$LANG->getLL('pdf.summary.2.table.users.colname.1'),
				$LANG->getLL('pdf.summary.2.table.users.colname.2'),
				$LANG->getLL('pdf.summary.2.table.users.colname.3'),
				$LANG->getLL('pdf.summary.2.table.users.colname.4'),
				$LANG->getLL('pdf.summary.2.table.users.colname.5')
			),
			'data' => array()
		);

		foreach($formData['be_users'] as $cle => $val)
		{
			if(!empty($val['realName']) && !empty($val['username']) && !empty($val['password']) && !empty($val['email']))
			{
				$beUsers['data'][] = array(
					$val['origUid'][1],
					$val['realName'],
					$val['username'],
					$val['password'],
					$val['email'],
				);
			}
		}

		return array(
			'type' => 'title1',
			'value' => $LANG->getLL('pdf.summary.2.title'),
			'paragraphs' => array(
				array(
					'type' => 'table',
					'value' => $beUsers,
				),
			),
		);
	}
}
