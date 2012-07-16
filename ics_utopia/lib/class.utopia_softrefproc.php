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
 * Extension to the system t3lib_softrefproc class for the 'ics_utopia' extension.
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */

require_once (PATH_t3lib.'class.t3lib_softrefproc.php');

/**
 * Extends the functionalities provided by t3lib_softrecproc.
 * Change the file exists testing features.
 *
 * USE:
 * Not to be called by user code.
 *
 * @author In Cité Solution <technique@incitesolution.fr>
 * @package UTOPIA
 */
class ux_t3lib_softrefproc extends t3lib_softrefproc
{
	static function is_file($filePath)
	{
		//var_dump(1, $filePath);
		if (@is_file($filePath))
			return true;
		if (strpos($filePath, '#') !== false)
			$filePath = substr($filePath, 0, strpos($filePath, '#'));
		//var_dump(2, $filePath);
		if (@is_file($filePath))
			return true;
		if (strpos($filePath, '?') !== false)
			$filePath = substr($filePath, 0, strpos($filePath, '?'));
		//var_dump(3, $filePath);
		return @is_file($filePath);
	}
	
	/**
	 * Finding image tags in the content.
	 * All images that are not from external URLs will be returned with an info text
	 * Will only return files in fileadmin/ and files in uploads/ folders which are prefixed with "RTEmagic[C|P]_" for substitution
	 * Any "clear.gif" images are ignored.
	 *
	 * @param	string		The input content to analyse
	 * @param	array		Parameters set for the softref parser key in TCA/columns
	 * @return	array		Result array on positive matches, see description above. Otherwise false
	 */
	function findRef_images($content, $spParams)	{

			// Start HTML parser and split content by image tag:
		$htmlParser = t3lib_div::makeInstance('t3lib_parsehtml');
		$splitContent = $htmlParser->splitTags('img',$content);
		$elements = array();

			// Traverse splitted parts:
		foreach($splitContent as $k => $v)	{
			if ($k%2)	{

					// Get file reference:
				$attribs = $htmlParser->get_tag_attributes($v);
				$srcRef = t3lib_div::htmlspecialchars_decode($attribs[0]['src']);
				$pI = pathinfo($srcRef);

					// If it looks like a local image, continue. Otherwise ignore it.
				$absPath = t3lib_div::getFileAbsFileName(PATH_site.$srcRef);
				if (!$pI['scheme'] && !$pI['query'] && $absPath && $srcRef!=='clear.gif')	{

						// Initialize the element entry with info text here:
					$tokenID = $this->makeTokenID($k);
					$elements[$k] = array();
					$elements[$k]['matchString'] = $v;

						// If the image seems to be from fileadmin/ folder or an RTE image, then proceed to set up substitution token:
					if (t3lib_div::isFirstPartOfStr($srcRef,$this->fileAdminDir.'/') || (t3lib_div::isFirstPartOfStr($srcRef,'uploads/') && ereg('^RTEmagicC_',basename($srcRef))))	{
							// Token and substitute value:
						if (@strstr($splitContent[$k], $attribs[0]['src']))	{	// Make sure the value we work on is found and will get substituted in the content (Very important that the src-value is not DeHSC'ed)
							$splitContent[$k] = str_replace($attribs[0]['src'], '{softref:'.$tokenID.'}', $splitContent[$k]);	// Substitute value with token (this is not be an exact method if the value is in there twice, but we assume it will not)
							$elements[$k]['subst'] = array(
								'type' => 'file',
								'relFileName' => $srcRef,
								'tokenID' => $tokenID,
								'tokenValue' => $attribs[0]['src'],
							);
							if (!self::is_file($absPath))	{	// Finally, notice if the file does not exist.
								$elements[$k]['error'] = 'File does not exist!';
							}
						} else {
							$elements[$k]['error'] = 'Could not substitute image source with token!';
						}
					}
				}
			}
		}
	}

	/**
	 * Processing the content expected from a TypoScript template
	 * This content includes references to files in fileadmin/ folders and file references in HTML tags like <img src="">, <a href=""> and <form action="">
	 *
	 * @param	string		The input content to analyse
	 * @param	array		Parameters set for the softref parser key in TCA/columns
	 * @return	array		Result array on positive matches, see description above. Otherwise false
	 */
	function findRef_TStemplate($content, $spParams)	{
		$elements = array();

			// First, try to find images and links:
		$htmlParser = t3lib_div::makeInstance('t3lib_parsehtml');
		$splitContent = $htmlParser->splitTags('img,a,form', $content);

			// Traverse splitted parts:
		foreach($splitContent as $k => $v)	{
			if ($k%2)	{

				$attribs = $htmlParser->get_tag_attributes($v);

				$attributeName = '';
				switch($htmlParser->getFirstTagName($v))	{
					case 'img':
						$attributeName = 'src';
					break;
					case 'a':
						$attributeName = 'href';
					break;
					case 'form':
						$attributeName = 'action';
					break;
				}

					// Get file reference:
				if (isset($attribs[0][$attributeName]))	{
					$srcRef = t3lib_div::htmlspecialchars_decode($attribs[0][$attributeName]);

						// Set entry:
					$tokenID = $this->makeTokenID($k);
					$elements[$k] = array();
					$elements[$k]['matchString'] = $v;

						// OK, if it looks like a local file from fileadmin/, include it:
					$pI = pathinfo($srcRef);
					$absPath = t3lib_div::getFileAbsFileName(PATH_site.$srcRef);
					if (t3lib_div::isFirstPartOfStr($srcRef,$this->fileAdminDir.'/') && !$pI['query'] && $absPath)	{

							// Token and substitute value:
						if (@strstr($splitContent[$k], $attribs[0][$attributeName]))	{	// Very important that the src-value is not DeHSC'ed
							$splitContent[$k] = str_replace($attribs[0][$attributeName], '{softref:'.$tokenID.'}', $splitContent[$k]);
							$elements[$k]['subst'] = array(
								'type' => 'file',
								'relFileName' => $srcRef,
								'tokenID' => $tokenID,
								'tokenValue' => $attribs[0][$attributeName],
							);
							if (!self::is_file($absPath))	{
								$elements[$k]['error'] = 'File does not exist!';
							}
						} else {
							$elements[$k]['error'] = 'Could not substitute attribute ('.$attributeName.') value with token!';
						}
					}
				}
			}
		}
		$content = implode('', $splitContent);

			// Process free fileadmin/ references as well:
		$content = $this->fileadminReferences($content, $elements);

			// Return output:
		if (count($elements))	{
			$resultArray = array(
				'content' => $content,
				'elements' => $elements
			);
			return $resultArray;
		}
	}

	/**
	 * Searches the content for a reference to a file in "fileadmin/".
	 * When a match is found it will get substituted with a token.
	 *
	 * @param	string		Input content to analyse
	 * @param	array		Element array to be modified with new entries. Passed by reference.
	 * @return	string		Output content, possibly with tokens inserted.
	 */
	function fileadminReferences($content, &$elements)	{

			// Fileadmin files are found
		$parts = preg_split("/([^[:alnum:]]+)(".$this->fileAdminDir."\/[^[:space:]\"'<>]*)/", ' '.$content.' ',10000, PREG_SPLIT_DELIM_CAPTURE);

			// Traverse files:
		foreach($parts as $idx => $value)	{
			if ($idx%3 == 2)	{

				if (($parts[$idx - 1] == '(') && (substr($value, -2) == ');'))
					$value = substr($value, 0, -2);
					
					// when file is found, set up an entry for the element:
				$tokenID = $this->makeTokenID('fileadminReferences:'.$idx);
				$elements['fileadminReferences.'.$idx] = array();
				$elements['fileadminReferences.'.$idx]['matchString'] = $value;
				$elements['fileadminReferences.'.$idx]['subst'] = array(
					'type' => 'file',
					'relFileName' => $value,
					'tokenID' => $tokenID,
					'tokenValue' => $value,
				);
				$parts[$idx] = '{softref:'.$tokenID.'}';

					// Check if the file actually exists:
				$absPath = t3lib_div::getFileAbsFileName(PATH_site.$value);
				if (!self::is_file($absPath))	{
					$elements['fileadminReferences.'.$idx]['error'] = 'File does not exist!';
				}
			}
		}
#debug($parts);
			// Implode the content again, removing prefixed and trailing white space:
		return substr(implode('',$parts),1,-1);
	}

	/**
	 * Recompile a TypoLink value from the array of properties made with getTypoLinkParts() into an elements array
	 *
	 * @param	array		TypoLink properties
	 * @param	array		Array of elements to be modified with substitution / information entries.
	 * @param	string		The content to process.
	 * @param	integer		Index value of the found element - user to make unique but stable tokenID
	 * @return	string		The input content, possibly containing tokens now according to the added substitution entries in $elements
	 * @see getTypoLinkParts()
	 */
	function setTypoLinkPartsElement($tLP, &$elements, $content, $idx)	{

			// Initialize, set basic values. In any case a link will be shown
		$tokenID = $this->makeTokenID('setTypoLinkPartsElement:'.$idx);
		$elements[$tokenID.':'.$idx] = array();
		$elements[$tokenID.':'.$idx]['matchString'] = $content;

			// Based on link type, maybe do more:
		switch ((string)$tLP['LINK_TYPE'])	{
			case 'mailto':
			case 'url':
					// Mail addresses and URLs can be substituted manually:
				$elements[$tokenID.':'.$idx]['subst'] = array(
					'type' => 'string',
					'tokenID' => $tokenID,
					'tokenValue' => $tLP['url'],
				);
					// Output content will be the token instead:
				$content = '{softref:'.$tokenID.'}';
			break;
			case 'file':
					// Process files found in fileadmin directory:
				if (!$tLP['query'])	{	// We will not process files which has a query added to it. That will look like a script we don't want to move.
					if (t3lib_div::isFirstPartOfStr($tLP['filepath'],$this->fileAdminDir.'/'))	{	// File must be inside fileadmin/

							// Set up the basic token and token value for the relative file:
						$elements[$tokenID.':'.$idx]['subst'] = array(
							'type' => 'file',
							'relFileName' => $tLP['filepath'],
							'tokenID' => $tokenID,
							'tokenValue' => $tLP['filepath'],
						);

							// Depending on whether the file exists or not we will set the
						$absPath = t3lib_div::getFileAbsFileName(PATH_site.$tLP['filepath']);
						if (!self::is_file($absPath))	{
							$elements[$tokenID.':'.$idx]['error'] = 'File does not exist!';
						}

							// Output content will be the token instead
						$content = '{softref:'.$tokenID.'}';
					} else return $content;
				} else return $content;
			break;
			case 'page':
					// Rebuild page reference typolink part:
				$content = '';

					// Set page id:
				if ($tLP['page_id'])	{
					$content.= '{softref:'.$tokenID.'}';
					$elements[$tokenID.':'.$idx]['subst'] = array(
						'type' => 'db',
						'recordRef' => 'pages:'.$tLP['page_id'],
						'tokenID' => $tokenID,
						'tokenValue' => $tLP['alias'] ? $tLP['alias'] : $tLP['page_id'],	// Set page alias if that was used.
					);
				}

					// Add type if applicable
				if (strlen($tLP['type']))	{
					$content.= ','.$tLP['type'];
				}

					// Add anchor if applicable
				if (strlen($tLP['anchor']))	{
					if (t3lib_div::testInt($tLP['anchor']))	{	// Anchor is assumed to point to a content elements:
							// Initialize a new entry because we have a new relation:
						$newTokenID = $this->makeTokenID('setTypoLinkPartsElement:anchor:'.$idx);
						$elements[$newTokenID.':'.$idx] = array();
						$elements[$newTokenID.':'.$idx]['matchString'] = 'Anchor Content Element: '.$tLP['anchor'];

						$content.= '#{softref:'.$newTokenID.'}';
						$elements[$newTokenID.':'.$idx]['subst'] = array(
							'type' => 'db',
							'recordRef' => 'tt_content:'.$tLP['anchor'],
							'tokenID' => $newTokenID,
							'tokenValue' => $tLP['anchor'],
						);
					} else {	// Anchor is a hardcoded string
						$content.= '#'.$tLP['type'];
					}
				}
			break;
			default:
				{
					$elements[$tokenID.':'.$idx]['error'] = 'Couldn\t decide typolink mode.';
					return $content;
				}
			break;
		}

			// Finally, for all entries that was rebuild with tokens, add target and class in the end:
		if (strlen($content) && strlen($tLP['target']))	{
			$content.= ' '.$tLP['target'];
			if (strlen($tLP['class']))	{
				$content.= ' '.$tLP['class'];
			}
		}

			// Return rebuilt typolink value:
		return $content;
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_softrefproc.php"]){
include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_softrefproc.php"]);
}
