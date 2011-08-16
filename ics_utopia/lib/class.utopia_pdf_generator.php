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
 * Generate the pdf file with details informations for the 'ics_utopia' extension modules..
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */

require_once(t3lib_extMgm::extPath('fpdf').'class.tx_fpdf.php');
require_once(t3lib_extMgm::extPath('ics_utopia', 'lib/class.utopia_session.php'));

class utopia_pdf_generator
{
	var $_cells = array();
	var $_headers = array();
	var $_widths;
	var $_aligns;
	var $_titleNumbers = array(0, 1);
	var $_emptyPage = false;
	var $_doLn = false;
	
	function utopia_pdf_generator()
	{
		include(t3lib_extMgm::extPath('ics_utopia', 'lib/pdf_styles.inc.php'));
	}
	
	function buildPDF($session)
	{
		global $LANG;
		global $TYPO3_CONF_VARS;
		$this->fpdf =  t3lib_div::makeInstance('utopia_pdf_generator_fpdf');
		$classes = array();
		$classes[1] = 'EXT:ics_utopia/lib/pdf/class.utopia_pdf_form1.php:&utopia_pdf_form1';
		$classes[2] = 'EXT:ics_utopia/lib/pdf/class.utopia_pdf_form2.php:&utopia_pdf_form2';
		$classes[3] = 'EXT:ics_utopia/lib/pdf/class.utopia_pdf_form3.php:&utopia_pdf_form3';
		$classes[4] = 'EXT:ics_utopia/lib/pdf/class.utopia_pdf_form4.php:&utopia_pdf_form4';
		if (isset($TYPO3_CONF_VARS['EXTCONF']['ics_utopia']['pdf']))
			foreach ($TYPO3_CONF_VARS['EXTCONF']['ics_utopia']['pdf'] as $formId => $formClass)
			{
				$classes[$formId] = $formClass;
			}

		$pdfStructure = array(
			array(
				'type' => 'title0',
				'value' => $LANG->getLL('pdf.summary.title'),
			),
		);

		$t3d = utopia_t3d_editor::loadFile($session->getT3DFile());
		foreach ($classes as $id => $classRef)
		{
			$formData = $session->getFormData($id);
			$_procObj = & t3lib_div::getUserObj($classRef, false);
			if (is_object($_procObj) && (get_parent_class($_procObj) == 'utopia_pdf_base'))
			{
				$pdfStructure[] = $_procObj->printPDF($t3d, $formData);
			}
		}
		$this->convertCharsetRecursive($pdfStructure);
		
		$formData = $session->getFormData(1);
		$this->pdfStructure($pdfStructure, preg_replace('/[^A-Z0-9_-]/i', '_', $formData["tx_icsutopia_site"][1]['title']));
	}
	
	function pdfStructure($array, $filename)
	{
		$this->fpdf->AddPage('P', 'A4');
		$this->_emptyPage = true;
		$this->parseElements($array);
		$this->fpdf->Output($filename . ".pdf", "D");
	}
	
	function parseElements($items)
	{
		if (empty($items))
			return;
		foreach ($items as $item)
			$this->parseElement($item);
	}
	
	function parseElement($item)
	{
		switch ($item['type'])
		{
		case 'title0':
		case 'title1':
		case 'title2':
		case 'title3':
		case 'title4':
		case 'title5':
		case 'title6':
		case 'title7':
		case 'title8':
		case 'title9':
			$titleLevel = substr($item['type'], -1);
			$this->makeTitle($titleLevel, $item['value']);
			$this->parseElements($item['paragraphs']);
			break;
		case 'text':
			$this->makeParagraphText($item['value']);
			break;
		case 'dl':
			$this->makeDL($item['items']);
			break;
		case 'table':
			$this->makeTable($item['value']);
			break;
		}
	}
	
	function makeTitle($level, $value)
	{
		$text = $value;
		if ($level != 0)
		{
			$number = $this->_titleNumbers[$level]++;
			$this->_titleNumbers[$level + 1] = 1;
			$text = $number . '. ' . $text;
		}
		$style = $this->_styles['title' . $level];
		$this->fpdf->SetFont($style['family'], $style['style'], $style['size']);
		$this->fpdf->SetLeftMargin($style['left']);
		$this->fpdf->SetRightMargin($style['right']);
		if ($this->_emptyPage)
			$this->_emptyPage = false;
		else
		{
			if ($style['pagebreak'])
				$this->fpdf->AddPage();
			elseif ($style['top'])
				$this->fpdf->Ln($style['top'] / $this->fpdf->k);
		}
		$this->fpdf->SetFont($style['family'], $style['style'], $style['size']);
		$this->fpdf->Cell(0, $style['size'] / $this->fpdf->k, $text, 0, 0, $style['align']);
		$this->fpdf->Ln($style['size'] / $this->fpdf->k);
		$style = $style['paragraph'];
		$this->fpdf->SetFont($style['family'], $style['style'], $style['size']);
		$this->fpdf->SetLeftMargin($style['left']);
		$this->fpdf->SetRightMargin($style['right']);
		$this->_curStyle = $style;
	}
	
	function makeParagraphText($value)
	{
		$this->fpdf->SetFont($this->_curStyle['family'], $this->_curStyle['style'], $this->_curStyle['size']);
		$this->fpdf->Ln($this->_curStyle['top'] / $this->fpdf->k);
		$this->fpdf->Write($this->_curStyle['size'] / $this->fpdf->k, $value);
		$this->fpdf->Ln($this->_curStyle['size'] / $this->fpdf->k);
		//$this->Ln(15);
	}

	function makeTable($value)
	{
		if($value['numCol'] > 0)
		{
			$widths = array();
			$left = array();
			$style = $this->_styles['table'];
			$tableLeft = $this->_curStyle['left'] + $style['left'];
			$tableRight = $this->_curStyle['right'] + $style['right'];
			$tableWidth = 210 /* A4 Portrait */ - ($tableLeft + $tableRight);
			//error_log('Left=' . $tableLeft . ' ; Right=' . $tableRight . ' ; Width=' . $tableWidth);
			$this->fpdf->SetLeftMargin($tableLeft);
			$this->fpdf->SetRightMargin($tableRight);
			$this->fpdf->Ln($style['top']);
			for ($i = 0; $i < $value['numCol']; $i++)
			{
				$widths[$i] = $tableWidth / $value['numCol'];
				$left[$i] = $tableLeft + $i * $tableWidth / $value['numCol'];
			}
			//error_log(var_export(array('header' => $value['header'], 'data' => $value['data'], 'widths' => $widths, 'left' => $left), true));
			$header = $value['header'];
			$this->makeTableHeaders($header, $widths, $left, $style['header']);
			foreach ($value['data'] as $row)
				$this->makeTableRow($row, $widths, $left, $style['body'], $header, $style['header']);
			$this->fpdf->Ln($style['bottom']);
		}
	}
	
	function makeTableHeaders($header, $widths, $left, $style)
	{
		$this->fpdf->SetFont($style['family'], $style['style'], $style['size']);
		$height = 0;
		$lineHeight = $style['size'] / $this->fpdf->k;
		foreach(array_keys($header) as $index)
			$height = max($height, $this->calcHeight($header[$index], $widths[$index], $style['size'] / $this->fpdf->k));
		if ($this->needPageBreak($height))
			$this->fpdf->AddPage();
		$startY = $this->fpdf->GetY();
		foreach(array_keys($header) as $index)
		{
			if ($widths[$index] == 0)
				continue;
			$this->fpdf->Rect($left[$index], $startY, $widths[$index], $height);
			$this->fpdf->SetXY($left[$index], $startY);
			$this->fpdf->MultiCell($widths[$index], $lineHeight, $header[$index], 0, $style['align']);
		}
		$this->fpdf->SetXY($left[0], $startY + $height);
	}

	function makeTableRow($row, $widths, $left, $style, $header, $headerStyle)
	{
		$this->fpdf->SetFont($style['family'], $style['style'], $style['size']);
		$height = 0;
		$lineHeight = $style['size'] / $this->fpdf->k;
		foreach(array_keys($row) as $index)
			$height = max($height, $this->calcHeight($row[$index], $widths[$index], $style['size'] / $this->fpdf->k));
		if ($this->needPageBreak($height))
		{
			$this->fpdf->AddPage();
			$this->makeTableHeaders($header, $widths, $left, $headerStyle);
			$this->fpdf->SetFont($style['family'], $style['style'], $style['size']);
		}
		$startY = $this->fpdf->GetY();
		foreach(array_keys($row) as $index)
		{
			if ($widths[$index] == 0)
				continue;
			$this->fpdf->Rect($left[$index], $startY, $widths[$index], $height);
			$this->fpdf->SetXY($left[$index], $startY);
			$this->fpdf->MultiCell($widths[$index], $lineHeight, $row[$index], 0, $style['align']);
		}
		$this->fpdf->SetXY($left[0], $startY + $height);
	}

	function calcHeight($text, $width, $lineHeight)
	{
		//error_log('Line height:' . $lineHeight);
		if ($width == 0)
			return 0;
		$remaining = $text;
		$tested = '';
		$lines = 1;
		while (strlen($remaining) > 0)
		{
			if (substr($remaining, 0, 1) == "\n")
			{
				$remaining = substr($remaining, 1);
				$lines++;
				$tested = '';
				continue;
			}
			elseif ((substr($remaining, 0, 1) == ' '))
			{
				$tested .= substr($remaining, 0, 1);
				$remaining = substr($remaining, 1);
				continue;
			}
			$pos1 = strpos($remaining, ' ');
			$pos2 = strpos($remaining, "\n");
			if ($pos1 == false) $pos1 = strlen($remaining);
			if ($pos2 == false) $pos2 = strlen($remaining);
			$pos = min($pos1, $pos2);
			$tested .= substr($remaining, 0, $pos);
			if ($this->fpdf->GetStringWidth($tested) > $width)
			{
				if (strpos($tested, ' ') === false)
				{
					$length = strlen($tested);
					while ($this->fpdf->GetStringWidth(substr($tested, 0, --$length)) > $width);
					$remaining = substr($remaining, $length);
				}
				$lines++;
				$tested = '';
			}
			else
			{
				$remaining = substr($remaining, $pos);
			}
			error_log('Reamining="' . $remaining . '" ; Tested="' . $tested . '" ; pos=' . $pos . '.');
		}
		error_log('"' . $text . '" is ' . $lines . ' lines of ' . $width . ' mm width.');
		return $lines * /*$this->getLineHeight()*/$lineHeight;
	}
	
	function needPageBreak($height)
	{
		return ($this->fpdf->GetY()+$h>$this->fpdf->PageBreakTrigger);
	}
	
	function printPDF($session)
	{
		$this->buildPDF($session);
	}
	
	function makeDL($items)
	{
		$style = $this->_styles['dl'];
		$dtStyle = $style['dt'];
		$ddStyle = $style['dd'];
		$dlLeft = $this->_curStyle['left'] + $style['left'];
		$dlRight = $this->_curStyle['right'] + $style['right'];
		$maxLeftCompact = $ddStyle['left'] - $dtStyle['left'];
		$dtLeft = $dlLeft + $dtStyle['left'];
		$ddLeft = $dlLeft + $ddStyle['left'];
		$overflow = false;
		$last = '';
		$this->fpdf->Ln($style['top'] / $this->fpdf->k);
		foreach ($items as $item)
		{
			switch ($item['type'])
			{
			case 'dt':
				$this->fpdf->SetFont($dtStyle['family'], $dtStyle['style'], $dtStyle['size']);
				$this->fpdf->SetLeftMargin($dtLeft);
				$this->fpdf->SetRightMargin($dlRight);
				if (($last == 'dt') && (!$overflow))
					$this->fpdf->Ln(($dtStyle['top'] + $dtStyle['size']) / $this->fpdf->k);
				elseif ($last == 'dd')
					$this->fpdf->Ln(($dtStyle['top'] + $dtStyle['size']) / $this->fpdf->k);
				$width = $this->fpdf->GetStringWidth($item['value']);
				$this->fpdf->Cell($width, $style['size'] / $this->fpdf->k, $item['value'], 0, 0, 'L');
				$overflow = $width > $maxLeftCompact;
				break;
			case 'dd':
				if ($overflow || ($last == ''))
				{
					$this->fpdf->SetLeftMargin($ddLeft);
					$this->fpdf->SetRightMargin($dlRight);
				}
				if ($last == 'dd')
					$this->fpdf->Ln(($ddStyle['top'] + $ddStyle['size']) / $this->fpdf->k);
				elseif (($last == 'dt') && ($overflow))
					$this->fpdf->Ln(($ddStyle['top'] + $ddStyle['size']) / $this->fpdf->k);
				$this->fpdf->SetFont($ddStyle['family'], $ddStyle['style'], $ddStyle['size']);
				if (!$overflow)
				{
					$this->fpdf->Cell($maxLeftCompact - $width);
					//$this->fpdf->SetX($ddLeft);
					$overflow = true;
				}
				$this->fpdf->Cell(0, $ddStyle['size'] / $this->fpdf->k, $item['value'], 0, 0, 'L');	// TODO: Composed rendering.
				break;
			default:
				continue;
			}
			$last = $item['type'];
		}
		if ($last != '')
			$this->fpdf->Ln(((($last == 'dt') ? $dtStyle['size'] : $ddStyle['size']) + $style['bottom']) / $this->fpdf->k);
	}
	
	private function convertCharsetRecursive(array & $elements) {
		if (!$this->csConvObj || !$this->charSet) {
			$obj = ($GLOBALS['LANG']) ? ($GLOBALS['LANG']) : ($GLOBALS['TSFE']);
			$this->csConvObj = $obj->csConvObj;
			$this->charSet = (($GLOBALS['LANG']) ? ($obj->charSet) : ($obj->renderCharset));
		}
		foreach ($elements as $key => & $element) {
			if (is_array($element))
				$this->convertCharsetRecursive($elements[$key]);
			elseif (is_string($element))
				$elements[$key] = $this->csConvObj->conv($element, $this->charSet, 'iso-8859-1');
		}
	}
}

class utopia_pdf_generator_fpdf extends FPDF
{
	function Header()
	{
	    //Police Arial gras 15
	    $this->SetFont('Arial','B',15);
	    //Décalage
	    $this->Cell(80);
	    //Date
		$this->SetFont('Arial','I',8);
	   	$this->Cell(0,10,date("d-m-Y"),0,0,'R');
	    //Saut de ligne
	    $this->Ln(20);
	}
	
	function Footer()
	{
	    //Positionnement à 1,5 cm du bas
	    $this->SetY(-15);
	    //Police Arial italique 8
	    $this->SetFont('Arial','I',8);
	    //Numéro de page aligné à droite
	    $this->Cell(0,10,'Page '.$this->PageNo(),0,0,'R');
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_pdf_generator.php"]){
include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_pdf_generator.php"]);
}
