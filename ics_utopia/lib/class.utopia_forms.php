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
 * Render forms for the 'ics_utopia' extension modules.
 * Help rendering both front-end and back-end forms.
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */
require_once(PATH_t3lib.'class.t3lib_diff.php');
require_once(PATH_t3lib.'class.t3lib_tceforms.php');
require_once(PATH_t3lib.'class.t3lib_tceforms_inline.php');
/**
 * Form rendering helper. Enables to render form element from TCA field configuration array in frontend. Use existing functions when in backend.
 * Configuration array is specialized with palette data.
 * 
 * @author In Cité Solution <technique@incitesolution.fr>
 * @package UTOPIA
 */
class utopia_forms
{
	// TCEForms object if in backend.
	var $_tceforms = null; 
		
	//
	var $requiredFields  = array();
	var $requiredAdditional = array();
	var $cachedTSconfig = array();
	var $dynNestedStack = array();
	var $requiredNested = array();
	
	var $inline;
	var $prependFormFieldNames = 'data';
	var $prependFormFieldNames_file = 'data_files';
	var $totalWrap='<hr />|<hr />';
	
	function utopia_forms()
	{
	}
	
	/**
	 * Initialize the form helper.
	 * If mode is BE, the tceforms object is created and initialized.
	 *
	 * @param string The form creation mode. Can be 'fe' or 'be'.
	 */
	function init($mode)
	{
		if (TYPO3_MODE == 'BE')
		{
			$this->_tceforms = t3lib_div::makeInstance('t3lib_TCEforms');
			$tceforms = & $this->_tceforms;
			$tceforms->initDefaultBEmode();
			$tceforms->formName = 'newsite';
			$tceforms->docLarge = 1;
			$tceforms->disableRTE = 0;
			$tceforms->backPath = $GLOBALS['BACK_PATH'];
			$tceforms->enableClickMenu = TRUE;
			$tceforms->enableTabMenu = TRUE;
		}else{
			if (!isset($GLOBALS['ajaxID']) || strpos($GLOBALS['ajaxID'], 't3lib_TCEforms_inline::')!==0)
				$this->inline = t3lib_div::makeInstance('t3lib_TCEforms_inline');
		}
		if (TYPO3_MODE == 'FE')
		{
			$this->backPath = 'typo3/';
			$this->edit_showFieldHelp = 'text'; // Always on.
		}
	}
	
	function getSingleField_SW($table, $field, $row, &$PA)
	{
		if ($this->_tceforms)
			return $this->_tceforms->getSingleField_SW($table, $field, $row, $PA);
		else
		{
			$PA['fieldConf']['config']['form_type'] = $PA['fieldConf']['config']['form_type'] ? $PA['fieldConf']['config']['form_type'] : $PA['fieldConf']['config']['type'];       // Using "form_type" locally in this script

			switch($PA['fieldConf']['config']['form_type']) {
				case 'input':
					$item = $this->getSingleField_typeInput($table,$field,$row,$PA);
					break;
				case 'text':
					$item = $this->getSingleField_typeText($table,$field,$row,$PA);
					break;
				case 'check':
					$item = $this->getSingleField_typeCheck($table,$field,$row,$PA);
					break;
				case 'radio':
					$item = $this->getSingleField_typeRadio($table,$field,$row,$PA);
					break;
				case 'select':
					$item = $this->getSingleField_typeSelect($table,$field,$row,$PA);
					break;
				case 'group':
					$item = $this->getSingleField_typeGroup($table,$field,$row,$PA);
					break;
				case 'none':
					$item = $this->getSingleField_typeNone($table,$field,$row,$PA);
					break;
				case 'user':
					$item = $this->getSingleField_typeUser($table,$field,$row,$PA);
					break;
				case 'flex':
					$item = $this->getSingleField_typeFlex($table,$field,$row,$PA);
					break;
				default:
					$item = $this->getSingleField_typeUnknown($table,$field,$row,$PA);
					break;
			}
 
			return $item;
		}
	}
	
	/**
	 * Fetches language label for key
	 *
	 * @param	string		Language label reference, eg. 'LLL:EXT:lang/locallang_core.php:labels.blablabla'
	 * @return	string		The value of the label, fetched for the current backend language.
	 */
	function sL($str)	{
		return $GLOBALS['LANG']->sL($str);
	}
	
	/**
	 * Returns language label from locallang_core.php
	 * Labels must be prefixed with either "l_" or "m_".
	 * The prefix "l_" maps to the prefix "labels." inside locallang_core.php
	 * The prefix "m_" maps to the prefix "mess." inside locallang_core.php
	 *
	 * @param	string		The label key
	 * @return	string		The value of the label, fetched for the current backend language.
	 */
	function getLL($str)	{
		$content = '';

		switch(substr($str,0,2))	{
			case 'l_':
				$content = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.'.substr($str,2));
			break;
			case 'm_':
				$content = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.'.substr($str,2));
			break;
		}
		return $content;
	}

	/**
	 * Initialize item array (for checkbox, selectorbox, radio buttons)
	 * Will resolve the label value.
	 *
	 * @param	array		The "columns" array for the field (from TCA)
	 * @return	array		An array of arrays with three elements; label, value, icon
	 */
	function initItemArray($fieldValue)	{
		$items = array();
		if (is_array($fieldValue['config']['items']))	{
			reset ($fieldValue['config']['items']);
			while (list($itemName,$itemValue) = each($fieldValue['config']['items']))	{
				$items[] = array($this->sL($itemValue[0]), $itemValue[1], $itemValue[2]);
			}
		}
		return $items;
	}
	
	/**
	 * Get style CSS values for the current field type.
	 *
	 * @param	string		Field type (eg. "check", "radio", "select")
	 * @param	boolean		If set, will return value only if prefixed with CLASS, otherwise must not be prefixed "CLASS"
	 * @return	string		CSS attributes
	 */
	function formElStyleClassValue($type, $class=FALSE)	{
			// Get value according to field:
/*		if (isset($this->fieldStyle[$type]))	{
			$style = trim($this->fieldStyle[$type]);
		} else {
			$style = trim($this->fieldStyle['all']);
		}

			// Check class prefixed:
		if (substr($style,0,6)=='CLASS:')	{
			$out = $class ? trim(substr($style,6)) : '';
		} else {
			$out = !$class ? $style : '';
		}*/
		
		return '';
	}
	
	/**
	 * Get style CSS values for the current field type.
	 *
	 * @param	string		Field type (eg. "check", "radio", "select")
	 * @return	string		CSS attributes
	 * @see formElStyleClassValue()
	 */
	function formElStyle($type)	{
		return $this->formElStyleClassValue($type);
	}
	
	/**
	 * Get class attribute value for the current field type.
	 *
	 * @param	string		Field type (eg. "check", "radio", "select")
	 * @return	string		CSS attributes
	 * @see formElStyleClassValue()
	 */
	function formElClass($type)	{
		//return $this->formElStyleClassValue($type, TRUE);
		return $type;
	}

	/**
	 * Returns parameters to set the width for a <input>/<textarea>-element
	 *
	 * @param	integer		The abstract size value (1-48)
	 * @param	boolean		If this is for a text area.
	 * @return	string		Either a "style" attribute string or "cols"/"size" attribute string.
	 */
	function formWidth($size=48, $textarea=0)	{
		// Input or text-field attribute (size or cols)
		$wAttrib = $textarea?'cols':'size';
		$retVal = ' '.$wAttrib.'="'.$size.'"';
		return $retVal;
	}
		
	/**
	 * Return default "style" / "class" attribute line.
	 *
	 * @param	string		Field type (eg. "check", "radio", "select")
	 * @return	string		CSS attributes
	 */
	function insertDefStyle($type)	{
		$out = '';

		$style = trim($this->defStyle.$this->formElStyle($type));
		$out.= $style?' style="'.htmlspecialchars($style).'"':'';

		$class = $this->formElClass($type);
		$out.= $class?' class="'.htmlspecialchars($class).'"':'';

		return $out;
	}
	
	/**
	 * Sets the current situation of nested tabs and inline levels for a given element.
	 *
	 * @param	string		$itemName: The element the nesting should be stored for
	 * @param	boolean		$setLevel: Set the reverse level lookup - default: true
	 * @return	void
	 */
	protected function registerNestedElement($itemName, $setLevel=true) {
		$dynNestedStack = $this->dynNestedStack;
		if (count($dynNestedStack) && preg_match('/^(.+\])\[(\w+)\]$/', $itemName, $match)) {
			array_shift($match);
			$this->requiredNested[$itemName] = array(
				'parts' => $match,
				'level' => $dynNestedStack,
			);
		}
	}
	
	/**
	 * Takes care of registering properties in requiredFields and requiredElements.
	 * The current hierarchy of IRRE and/or Tabs is stored. Thus, it is possible to determine,
	 * which required field/element was filled incorrectly and show it, even if the Tab or IRRE
	 * level is hidden.
	 *
	 * @param	string		$type: Type of requirement ('field' or 'range')
	 * @param	string		$name: The name of the form field
	 * @param	mixed		$value: For type 'field' string, for type 'range' array
	 * @return	void
	 */
	protected function registerRequiredProperty($type, $name, $value) {
		if ($type == 'field' && is_string($value)) {
			$this->requiredFields[$name] = $value;
				// requiredFields have name/value swapped! For backward compatibility we keep this:
			$itemName = $value;
		} elseif ($type == 'range' && is_array($value)) {
			$this->requiredElements[$name] = $value;
			$itemName = $name;
		}
			// Set the situation of nesting for the current field:
		$this->registerNestedElement($itemName);
	}
	
	/**
	 * Creates value/label pair for a backend module (main and sub)
	 *
	 * @param	string		The module key
	 * @return	string		The rawurlencoded 2-part string to transfer to interface
	 * @access private
	 * @see addSelectOptionsToItemArray()
	 */
	function addSelectOptionsToItemArray_makeModuleData($value)	{
		$label = '';
		// Add label for main module:
		$pp = explode('_',$value);
		
		if (count($pp)>1)	
			$label .= $GLOBALS['LANG']->moduleLabels['tabs'][$pp[0].'_tab'].'>';
		
		// Add modules own label now:
		$label .= $GLOBALS['LANG']->moduleLabels['tabs'][$value.'_tab'];

		return $label;
	}
	
	/**
	 * Returns select statement for MM relations (as used by TCEFORMs etc)
	 * Usage: 3
	 *
	 * @param	array		Configuration array for the field, taken from $TCA
	 * @param	string		Field name
	 * @param	array		TSconfig array from which to get further configuration settings for the field name
	 * @param	string		Prefix string for the key "*foreign_table_where" from $fieldValue array
	 * @return	string		Part of query
	 * @internal
	 * @see t3lib_transferData::renderRecord(), t3lib_TCEforms::foreignTable()
	 */
	public static function exec_foreign_table_where_query($fieldValue, $field = '', $TSconfig = array(), $prefix = '') {
		global $TCA;

		t3lib_div::loadTCA($foreign_table);
		$foreign_table = $fieldValue['config'][$prefix.'foreign_table'];
		$rootLevel = $TCA[$foreign_table]['ctrl']['rootLevel'];

		$fTWHERE = $fieldValue['config'][$prefix.'foreign_table_where'];
		if (@strstr($fTWHERE, '###REC_FIELD_')) {
			$fTWHERE_parts = explode('###REC_FIELD_', $fTWHERE);
			while(list($kk, $vv) = each($fTWHERE_parts)) {
				if ($kk) {
					$fTWHERE_subpart = explode('###', $vv, 2);
					$fTWHERE_parts[$kk] = $TSconfig['_THIS_ROW'][$fTWHERE_subpart[0]].$fTWHERE_subpart[1];
				}
			}
			$fTWHERE = implode('', $fTWHERE_parts);
		}

		$fTWHERE = str_replace('###CURRENT_PID###', intval($TSconfig['_CURRENT_PID']), $fTWHERE);
		$fTWHERE = str_replace('###THIS_UID###', intval($TSconfig['_THIS_UID']), $fTWHERE);
		$fTWHERE = str_replace('###THIS_CID###', intval($TSconfig['_THIS_CID']), $fTWHERE);
		$fTWHERE = str_replace('###STORAGE_PID###', intval($TSconfig['_STORAGE_PID']), $fTWHERE);
		$fTWHERE = str_replace('###SITEROOT###', intval($TSconfig['_SITEROOT']), $fTWHERE);
		$fTWHERE = str_replace('###PAGE_TSCONFIG_ID###', intval($TSconfig[$field]['PAGE_TSCONFIG_ID']), $fTWHERE);
		$fTWHERE = str_replace('###PAGE_TSCONFIG_IDLIST###', $GLOBALS['TYPO3_DB']->cleanIntList($TSconfig[$field]['PAGE_TSCONFIG_IDLIST']), $fTWHERE);
		$fTWHERE = str_replace('###PAGE_TSCONFIG_STR###', $GLOBALS['TYPO3_DB']->quoteStr($TSconfig[$field]['PAGE_TSCONFIG_STR'], $foreign_table), $fTWHERE);

			// rootLevel = -1 is not handled 'properly' here - it goes as if it was rootLevel = 1 (that is pid=0)
		$wgolParts = $GLOBALS['TYPO3_DB']->splitGroupOrderLimit($fTWHERE);
		if ($rootLevel) {
			$queryParts = array(
				'SELECT' => t3lib_BEfunc::getCommonSelectFields($foreign_table, $foreign_table.'.'),
				'FROM' => $foreign_table,
				'WHERE' => $foreign_table.'.pid=0 '.
							t3lib_BEfunc::deleteClause($foreign_table).' '.
							$wgolParts['WHERE'],
				'GROUPBY' => $wgolParts['GROUPBY'],
				'ORDERBY' => $wgolParts['ORDERBY'],
				'LIMIT' => $wgolParts['LIMIT']
			);
		} else {
			//$pageClause = $GLOBALS['BE_USER']->getPagePermsClause(1);
			$pageClause = '';
			if ($foreign_table!='pages') {
				$queryParts = array(
					'SELECT' => t3lib_BEfunc::getCommonSelectFields($foreign_table, $foreign_table.'.'),
					'FROM' => $foreign_table.', pages',
					'WHERE' => 'pages.uid='.$foreign_table.'.pid
								AND pages.deleted=0 '.
								t3lib_BEfunc::deleteClause($foreign_table).
								' '.
								$wgolParts['WHERE'],
					'GROUPBY' => $wgolParts['GROUPBY'],
					'ORDERBY' => $wgolParts['ORDERBY'],
					'LIMIT' => $wgolParts['LIMIT']
				);
			} else {
				$queryParts = array(
					'SELECT' => t3lib_BEfunc::getCommonSelectFields($foreign_table, $foreign_table.'.'),
					'FROM' => 'pages',
					'WHERE' => 'pages.deleted=0
								'.
								$wgolParts['WHERE'],
					'GROUPBY' => $wgolParts['GROUPBY'],
					'ORDERBY' => $wgolParts['ORDERBY'],
					'LIMIT' => $wgolParts['LIMIT']
				);
			}
		}

		return $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
	}

	/**
	 * Adds records from a foreign table (for selector boxes)
	 *
	 * @param	array		The array of items (label,value,icon)
	 * @param	array		The 'columns' array for the field (from TCA)
	 * @param	array		TSconfig for the table/row
	 * @param	string		The fieldname
	 * @param	boolean		If set, then we are fetching the 'neg_' foreign tables.
	 * @return	array		The $items array modified.
	 * @see addSelectOptionsToItemArray(), t3lib_BEfunc::exec_foreign_table_where_query()
	 */
	function foreignTable($items,$fieldValue,$TSconfig,$field,$pFFlag=0)	{
		global $TCA;

			// Init:
		$pF=$pFFlag?'neg_':'';
		$f_table = $fieldValue['config'][$pF.'foreign_table'];
		$uidPre = $pFFlag?'-':'';

			// Get query:
		$res = $this->exec_foreign_table_where_query($fieldValue,$field,$TSconfig,$pF);

			// Perform lookup
		if ($GLOBALS['TYPO3_DB']->sql_error())	{
			echo($GLOBALS['TYPO3_DB']->sql_error()."\n\nThis may indicate a table defined in tables.php is not existing in the database!");
			return array();
		}

			// Get label prefix.
		$lPrefix = $this->sL($fieldValue['config'][$pF.'foreign_table_prefix']);

			// Get icon field + path if any:
		$iField = $TCA[$f_table]['ctrl']['selicon_field'];
		$iPath = trim($TCA[$f_table]['ctrl']['selicon_field_path']);

			// Traverse the selected rows to add them:
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			t3lib_BEfunc::workspaceOL($f_table, $row);

			if (is_array($row))	{
					// Prepare the icon if available:
				if ($iField && $iPath && $row[$iField])	{
					$iParts = t3lib_div::trimExplode(',',$row[$iField],1);
					$icon = '../'.$iPath.'/'.trim($iParts[0]);
				} elseif (t3lib_div::inList('singlebox,checkbox',$fieldValue['config']['renderMode'])) {
					$icon = '../'.TYPO3_mainDir.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],t3lib_iconWorks::getIcon($f_table, $row),'',1);
				} else $icon = '';

					// Add the item:
				$items[] = array(
					//$lPrefix.strip_tags(t3lib_BEfunc::getRecordTitle($f_table,$row)),
					$lPrefix . strip_tags($row[$TCA[$f_table]['ctrl']['label']]),
					$uidPre.$row['uid'],
					$icon
				);
			}
		}
		return $items;
	}
	
	/**
	 * Add selector box items of more exotic kinds.
	 *
	 * @param	array		The array of items (label,value,icon)
	 * @param	array		The "columns" array for the field (from TCA)
	 * @param	array		TSconfig for the table/row
	 * @param	string		The fieldname
	 * @return	array		The $items array modified.
	 */
	function addSelectOptionsToItemArray($items,$fieldValue,$TSconfig,$field)	{
		global $TCA;
		$edit_showFieldHelp='';
		
		// Values from foreign tables:
		if ($fieldValue['config']['foreign_table'])	{
			$items = $this->foreignTable($items,$fieldValue,$TSconfig,$field);
			if ($fieldValue['config']['neg_foreign_table'])	{
				$items = $this->foreignTable($items,$fieldValue,$TSconfig,$field,1);
			}
		}

			// Values from a file folder:
		if ($fieldValue['config']['fileFolder'])	{
			$fileFolder = t3lib_div::getFileAbsFileName($fieldValue['config']['fileFolder']);
			if (@is_dir($fileFolder))	{

					// Configurations:
				$extList = $fieldValue['config']['fileFolder_extList'];
				$recursivityLevels = isset($fieldValue['config']['fileFolder_recursions']) ? t3lib_div::intInRange($fieldValue['config']['fileFolder_recursions'],0,99) : 99;

					// Get files:
				$fileFolder = ereg_replace('\/$','',$fileFolder).'/';
				$fileArr = t3lib_div::getAllFilesAndFoldersInPath(array(),$fileFolder,$extList,0,$recursivityLevels);
				$fileArr = t3lib_div::removePrefixPathFromList($fileArr, $fileFolder);

				foreach($fileArr as $fileRef)	{
					$fI = pathinfo($fileRef);
					$icon = t3lib_div::inList('gif,png,jpeg,jpg', strtolower($fI['extension'])) ? '../'.substr($fileFolder,strlen(PATH_site)).$fileRef : '';
					$items[] = array(
						$fileRef,
						$fileRef,
						$icon
					);
				}
			}
		}

			// If 'special' is configured:
		if ($fieldValue['config']['special'])	{
			switch ($fieldValue['config']['special'])	{
				case 'tables':
					$temp_tc = array_keys($TCA);
					$descr = '';

					foreach($temp_tc as $theTableNames)	{
						if (!$TCA[$theTableNames]['ctrl']['adminOnly'])	{

								// Icon:
							$icon = '../'.TYPO3_mainDir.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],t3lib_iconWorks::getIcon($theTableNames, array()),'',1);

								// Add description texts:
							if ($edit_showFieldHelp)	{
								$GLOBALS['LANG']->loadSingleTableDescription($theTableNames);
								$fDat = $GLOBALS['TCA_DESCR'][$theTableNames]['columns'][''];
								$descr = $fDat['description'];
							}

								// Item configuration:
							$items[] = array(
								$this->sL($TCA[$theTableNames]['ctrl']['title']),
								$theTableNames,
								$icon,
								$descr
							);
						}
					}
				break;
				case 'pagetypes':
					$theTypes = $TCA['pages']['columns']['doktype']['config']['items'];

					foreach($theTypes as $theTypeArrays)	{
							// Icon:
						$icon = $theTypeArrays[1]!='--div--' ? '../'.TYPO3_mainDir.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],t3lib_iconWorks::getIcon('pages', array('doktype' => $theTypeArrays[1])),'',1) : '';

							// Item configuration:
						$items[] = array(
							$this->sL($theTypeArrays[0]),
							$theTypeArrays[1],
							$icon
						);
					}
				break;
				case 'exclude':
					$theTypes = t3lib_BEfunc::getExcludeFields();
					$descr = '';

					foreach($theTypes as $theTypeArrays)	{
						list($theTable, $theField) = explode(':', $theTypeArrays[1]);

							// Add description texts:
						if ($edit_showFieldHelp)	{
							$GLOBALS['LANG']->loadSingleTableDescription($theTable);
							$fDat = $GLOBALS['TCA_DESCR'][$theTable]['columns'][$theField];
							$descr = $fDat['description'];
						}

							// Item configuration:
						$items[] = array(
							ereg_replace(':$','',$theTypeArrays[0]),
							$theTypeArrays[1],
							'',
							$descr
						);
					}
				break;
				case 'explicitValues':
					$theTypes = t3lib_BEfunc::getExplicitAuthFieldValues();

							// Icons:
					$icons = array(
						'ALLOW' => '../'.TYPO3_mainDir.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/icon_ok2.gif','',1),
						'DENY' => '../'.TYPO3_mainDir.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/icon_fatalerror.gif','',1),
					);

						// Traverse types:
					foreach($theTypes as $tableFieldKey => $theTypeArrays)	{

						if (is_array($theTypeArrays['items']))	{
								// Add header:
							$items[] = array(
								$theTypeArrays['tableFieldLabel'],
								'--div--',
							);

								// Traverse options for this field:
							foreach($theTypeArrays['items'] as $itemValue => $itemContent)	{
									// Add item to be selected:
								$items[] = array(
									'['.$itemContent[2].'] '.$itemContent[1],
									$tableFieldKey.':'.ereg_replace('[:|,]','',$itemValue).':'.$itemContent[0],
									$icons[$itemContent[0]]
								);
							}
						}
					}
				break;
				case 'languages':
					$items = array_merge($items,t3lib_BEfunc::getSystemLanguages());
				break;
/*				case 'custom':
						// Initialize:
					$customOptions = $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions'];
					if (is_array($customOptions))	{
						foreach($customOptions as $coKey => $coValue) {
							if (is_array($coValue['items']))	{
									// Add header:
								$items[] = array(
									$GLOBALS['LANG']->sl($coValue['header']),
									'--div--',
								);

									// Traverse items:
								foreach($coValue['items'] as $itemKey => $itemCfg)	{
										// Icon:
									if ($itemCfg[1])	{
										list($icon) = $this->getIcon($itemCfg[1]);
										if ($icon)	$icon = '../'.TYPO3_mainDir.$icon;
									} else $icon = '';

										// Add item to be selected:
									$items[] = array(
										$GLOBALS['LANG']->sl($itemCfg[0]),
										$coKey.':'.ereg_replace('[:|,]','',$itemKey),
										$icon,
										$GLOBALS['LANG']->sl($itemCfg[2]),
									);
								}
							}
						}
					}
				break;*/
				case 'modListGroup':
				case 'modListUser':
					$loadModules = t3lib_div::makeInstance('t3lib_loadModules');
					$loadModules->load($GLOBALS['TBE_MODULES']);

					$modList = $fieldValue['config']['special']=='modListUser' ? $loadModules->modListUser : $loadModules->modListGroup;
					if (is_array($modList))	{
						$descr = '';

						foreach($modList as $theMod)	{
								// Icon:
							$icon = $GLOBALS['LANG']->moduleLabels['tabs_images'][$theMod.'_tab'];
							if ($icon)	{
								$icon = '../'.substr($icon,strlen(PATH_site));
							}

								// Description texts:
							if ($this->edit_showFieldHelp)	{
								$descr = $GLOBALS['LANG']->moduleLabels['labels'][$theMod.'_tablabel'].
											chr(10).
											$GLOBALS['LANG']->moduleLabels['labels'][$theMod.'_tabdescr'];
							}

								// Item configuration:
							$items[] = array(
								$this->addSelectOptionsToItemArray_makeModuleData($theMod),
								$theMod,
								$icon,
								$descr
							);
						}
					}
				break;
			}
		}

			// Return the items:
		return $items;
	}
	
	/**
	 * Merges items into an item-array
	 *
	 * @param	array		The existing item array
	 * @param	array		An array of items to add. NOTICE: The keys are mapped to values, and the values and mapped to be labels. No possibility of adding an icon.
	 * @return	array		The updated $item array
	 */
	function addItems($items,$iArray)	{
		global $TCA;
		if (is_array($iArray))	{
			reset($iArray);
			while(list($value,$label)=each($iArray))	{
				$items[]=array($this->sl($label),$value);
			}
		}
		return $items;
	}

	/**
	 * Returns TSconfig for table/row
	 * Multiple requests to this function will return cached content so there is no performance loss in calling this many times since the information is looked up only once.
	 *
	 * @param	string		The table name
	 * @param	array		The table row (Should at least contain the "uid" value, even if "NEW..." string. The "pid" field is important as well, and negative values will be intepreted as pointing to a record from the same table.)
	 * @param	string		Optionally you can specify the field name as well. In that case the TSconfig for the field is returned.
	 * @return	mixed		The TSconfig values (probably in an array)
	 * @see t3lib_BEfunc::getTCEFORM_TSconfig()
	 */
	function setTSconfig($table,$row,$field='')	{
		$mainKey = $table.':'.$row['uid'];
		if (!isset($this->cachedTSconfig[$mainKey]))	{
			$this->cachedTSconfig[$mainKey] = t3lib_BEfunc::getTCEFORM_TSconfig($table,$row);
		}
		if ($field)	{
			return $this->cachedTSconfig[$mainKey][$field];
		} else {
			return $this->cachedTSconfig[$mainKey];
		}
	}

	/**
	 * Perform user processing of the items arrays of checkboxes, selectorboxes and radio buttons.
	 *
	 * @param	array		The array of items (label,value,icon)
	 * @param	array		The "itemsProcFunc." from fieldTSconfig of the field.
	 * @param	array		The config array for the field.
	 * @param	string		Table name
	 * @param	array		Record row
	 * @param	string		Field name
	 * @return	array		The modified $items array
	 */
	function procItems($items,$iArray,$config,$table,$row,$field)	{
		global $TCA;

		$params = array();
		$params['items'] = &$items;
		$params['config'] = $config;
		$params['TSconfig'] = $iArray;
		$params['table'] = $table;
		$params['row'] = $row;
		$params['field'] = $field;

		t3lib_div::callUserFunction($config['itemsProcFunc'],$params,$this);
		return $items;
	}

	/**
	 * Generation of TCEform elements of the type "input"
	 * This will render a single-line input form field, possibly with various control/validation features
	 *
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeInput($table, $field, $row, &$PA)
	{
		if ($this->_tceforms)
			return $this->_tceforms->getSingleField_typeInput($table, $field, $row, $PA);
		else
		{
			$maxInputWidth = 48;
			
			$extJSCODE = '';
			//Frontend form rendering.
			$config = $PA['fieldConf']['config'];

#			$specConf = $this->getSpecConfForField($table,$row,$field);
//			$specConf = $this->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);
			$size = t3lib_div::intInRange($config['size'] ? $config['size'] : 30,5,$maxInputWidth);
			$evalList = t3lib_div::trimExplode(',',$config['eval'],1);

/*			if($this->renderReadonly || $config['readOnly'])  {
				$itemFormElValue = $PA['itemFormElValue'];
				if (in_array('date',$evalList))	{
					$config['format'] = 'date';
				} elseif (in_array('date',$evalList))	{
					$config['format'] = 'date';
				} elseif (in_array('datetime',$evalList))	{
					$config['format'] = 'datetime';
				} elseif (in_array('time',$evalList))	{
					$config['format'] = 'time';
				}
				if (in_array('password',$evalList))	{
					$itemFormElValue = $itemFormElValue ? '*********' : '';
				}
				return $this->getSingleField_typeNone_render($config, $itemFormElValue);
			}*/

			foreach ($evalList as $func) {
				switch ($func) {
					case 'required':
						$this->registerRequiredProperty('field', $table.'_'.$row['uid'].'_'.$field, $PA['itemFormElName']);
							// Mark this field for date/time disposal:
						if (array_intersect($evalList, array('date', 'datetime', 'time'))) {
							 $this->requiredAdditional[$PA['itemFormElName']]['isPositiveNumber'] = true;
						}
						break;
					default:
						if (substr($func, 0, 3) == 'tx_')	{
							// Pair hook to the one in t3lib_TCEmain::checkValue_input_Eval()
							$evalObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func].':&'.$func);
							if (is_object($evalObj) && method_exists($evalObj, 'deevaluateFieldValue'))	{
								$_params = array(
									'value' => $PA['itemFormElValue']
								);
								$PA['itemFormElValue'] = $evalObj->deevaluateFieldValue($_params);
							}
						}
						break;
				}
			}

			$paramsList = "'".$PA['itemFormElName']."','".implode(',',$evalList)."','".trim($config['is_in'])."',".(isset($config['checkbox'])?1:0).",'".$config['checkbox']."'";
			
			if ((in_array('date',$evalList) || in_array('datetime',$evalList)) && $PA['itemFormElValue']>0){
				// Add server timezone offset to UTC to our stored date
				$PA['itemFormElValue'] += date('Z', $PA['itemFormElValue']);
			}

			$PA['fieldChangeFunc'] = array_merge(array('typo3form.fieldGet'=>'typo3form.fieldGet('.$paramsList.');'), $PA['fieldChangeFunc']);
			$mLgd = ($config['max']?$config['max']:256);
			$iOnChange = implode('',$PA['fieldChangeFunc']);
			// input password or not
			$item = '
				<input type="'.(in_array('password', $evalList)?'password':'text').'" id="'.$PA['itemFormElID'].'" name="'.$PA['itemFormElName'].'" value="'.$PA['itemFormElValue'].'"'.$this->formWidth($size).' maxlength="'.$mLgd.'" />';	// This is the EDITABLE form field. // onchange="'.htmlspecialchars($iOnChange).'"'.$PA['onFocus'].'
		
			//$item .= '<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';			
			// This is the ACTUAL form field - values from the EDITABLE field must be transferred to this field which is the one that is written to the database.
			$extJSCODE .= 'typo3form.fieldSet('.$paramsList.');';
	
			// going through all custom evaluations configured for this field
			foreach ($evalList as $evalData) {
				if (substr($evalData, 0, 3) == 'tx_')	{
					$evalObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$evalData].':&'.$evalData);
					if(is_object($evalObj) && method_exists($evalObj, 'returnFieldJS'))	{
						$extJSCODE .= "\n\nfunction ".$evalData."(value) {\n".$evalObj->returnFieldJS()."\n}\n";
					}
				}
			}

			// Creating an alternative item without the JavaScript handlers.
/*			$altItem = '<input type="hidden" name="'.$PA['itemFormElName'].'_hr" value="" />';
			$altItem.= '<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';*/

			// Wrap a wizard around the item?
//			$item= $this->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'].'_hr',$specConf);

			return $item;
		}
	}

	/**
	 * Generation of TCEform elements of the type "text"
	 * This will render a <textarea> OR RTE area form field, possibly with various control/validation features
	 *
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeText($table,$field,$row,&$PA)
	{
		if ($this->_tceforms)
			return $this->_tceforms->getSingleField_typeText($table, $field, $row, $PA);
		else
		{
			$maxTextareaWidth = 100;
			$charsPerRow = 50;
				// Init config:
			$config = $PA['fieldConf']['config'];

			/*if($this->renderReadonly || $config['readOnly'])  {
				return $this->getSingleField_typeNone_render($config, $PA['itemFormElValue']);
			}*/

				// Setting columns number:
			$cols = t3lib_div::intInRange($config['cols'] ? $config['cols'] : 30, 5, $maxTextareaWidth);

				// Setting number of rows:
			$origRows = $rows = t3lib_div::intInRange($config['rows'] ? $config['rows'] : 5, 1, 200);
			if (strlen($PA['itemFormElValue']) > $charsPerRow*2)	{
				$cols = $maxTextareaWidth;
				$rows = t3lib_div::intInRange(round(strlen($PA['itemFormElValue'])/$charsPerRow), count(explode(chr(10),$PA['itemFormElValue'])), 20);
				if ($rows<$origRows)	$rows = $origRows;
			}
			/*
				// Init RTE vars:
			$RTEwasLoaded = 0;				// Set true, if the RTE is loaded; If not a normal textarea is shown.
			$RTEwouldHaveBeenLoaded = 0;	// Set true, if the RTE would have been loaded if it wasn't for the disable-RTE flag in the bottom of the page...

				// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. Traditionally, this is where RTE configuration has been found.
			$specConf = $this->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);

				// Setting up the altItem form field, which is a hidden field containing the value
			$altItem = '<input type="hidden" name="'.htmlspecialchars($PA['itemFormElName']).'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';
			*/
				// If RTE is generally enabled (TYPO3_CONF_VARS and user settings)
			/*if ($this->RTEenabled) {
				$p = t3lib_BEfunc::getSpecConfParametersFromArray($specConf['rte_transform']['parameters']);
				if (isset($specConf['richtext']) && (!$p['flag'] || !$row[$p['flag']]))	{	// If the field is configured for RTE and if any flag-field is not set to disable it.
					t3lib_BEfunc::fixVersioningPid($table,$row);
					list($tscPID,$thePidValue) = $this->getTSCpid($table,$row['uid'],$row['pid']);

						// If the pid-value is not negative (that is, a pid could NOT be fetched)
					if ($thePidValue >= 0)	{
						$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($tscPID));
						$RTEtypeVal = t3lib_BEfunc::getTCAtypeValue($table,$row);
						$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$table,$field,$RTEtypeVal);

						if (!$thisConfig['disabled'])	{
							if (!$this->disableRTE)	{
								$this->RTEcounter++;

									// Find alternative relative path for RTE images/links:
								$eFile = t3lib_parsehtml_proc::evalWriteFile($specConf['static_write'], $row);
								$RTErelPath = is_array($eFile) ? dirname($eFile['relEditFile']) : '';

									// Get RTE object, draw form and set flag:
								$RTEobj = &t3lib_BEfunc::RTEgetObj();
								$item = $RTEobj->drawRTE($this,$table,$field,$row,$PA,$specConf,$thisConfig,$RTEtypeVal,$RTErelPath,$thePidValue);

									// Wizard:
								$item = $this->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'],$specConf,1);

								$RTEwasLoaded = 1;
							} else {
								$RTEwouldHaveBeenLoaded = 1;
								$this->commentMessages[] = $PA['itemFormElName'].': RTE is disabled by the on-page RTE-flag (probably you can enable it by the check-box in the bottom of this page!)';
							}
						} else $this->commentMessages[] = $PA['itemFormElName'].': RTE is disabled by the Page TSconfig, "RTE"-key (eg. by RTE.default.disabled=0 or such)';
					} else $this->commentMessages[] = $PA['itemFormElName'].': PID value could NOT be fetched. Rare error, normally with new records.';
				} else {
					if (!isset($specConf['richtext']))	$this->commentMessages[] = $PA['itemFormElName'].': RTE was not configured for this field in TCA-types';
					if (!(!$p['flag'] || !$row[$p['flag']]))	 $this->commentMessages[] = $PA['itemFormElName'].': Field-flag ('.$PA['flag'].') has been set to disable RTE!';
				}
			}

				// Display ordinary field if RTE was not loaded.
			if (!$RTEwasLoaded)*/ {
				/*if ($specConf['rte_only'])	{	// Show message, if no RTE (field can only be edited with RTE!)
					$item = '<p><em>'.htmlspecialchars($this->getLL('l_noRTEfound')).'</em></p>';
				} else*/ {
					if ($specConf['nowrap'])	{
						$wrap = 'off';
					} else {
						$wrap = ($config['wrap'] ? $config['wrap'] : 'virtual');
					}

					$classes = array();
					if ($specConf['fixed-font'])	{ $classes[] = 'fixed-font'; }
					if ($specConf['enable-tab'])	{ $classes[] = 'enable-tab'; }

					$formWidthText = $this->formWidthText($cols,$wrap);

						// Extract class attributes from $formWidthText (otherwise it would be added twice to the output)
					if (preg_match('/ class="(.+?)"/',$formWidthText,$res))	{
						$formWidthText = str_replace(' class="'.$res[1].'"','',$formWidthText);
						$classes = array_merge($classes, explode(' ',$res[1]));
					}

					if (count($classes))	{
						$class = ' class="'.implode(' ',$classes).'"';
					} else $class='';

					//$iOnChange = implode('',$PA['fieldChangeFunc']);
					$item.= '
								<textarea name="'.$PA['itemFormElName'].'"'.$formWidthText.$class.' rows="'.$rows.'" wrap="'.$wrap./*'" onchange="'.htmlspecialchars($iOnChange).*/'"'.$PA['onFocus'].'>'.
								t3lib_div::formatForTextarea($PA['itemFormElValue']).
								'</textarea>';
					//$item = $this->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'],$specConf,$RTEwouldHaveBeenLoaded);
				}
			}

				// Return field HTML:
			return $item;
		}
	}

	/**
	 * Returns parameters to set with for a textarea field
	 *
	 * @param	integer		The abstract width (1-48)
	 * @param	string		Empty or "off" (text wrapping in the field or not)
	 * @return	string		The "cols" attribute string (or style from formWidth())
	 * @see formWidth()
	 */
	function formWidthText($size=48,$wrap='')	{
		$wTags = $this->formWidth($size,1);
			// Netscape 6+ seems to have this ODD problem where there WILL ALWAYS be wrapping with the cols-attribute set and NEVER without the col-attribute...
		if (strtolower(trim($wrap))!='off' && $GLOBALS['CLIENT']['BROWSER']=='net' && $GLOBALS['CLIENT']['VERSION']>=5)	{
			$wTags.= ' cols="'.$size.'"';
		}
		return $wTags;
	}	

	/**
	 * Generation of TCEform elements of the type "check"
	 * This will render a check-box OR an array of checkboxes
	 *
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeCheck($table,$field,$row,&$PA)	{
		if ($this->_tceforms)
			return $this->_tceforms->getSingleField_typeCheck($table, $field, $row, $PA);
		else
		{
			$config = $PA['fieldConf']['config'];

			$disabled = '';
			if($this->renderReadonly || $config['readOnly'])  {
				$disabled = ' disabled="disabled"';
			}

				// Traversing the array of items:
			$selItems = $this->initItemArray($PA['fieldConf']);
			if ($config['itemsProcFunc']) $selItems = $this->procItems($selItems,$PA['fieldTSConfig']['itemsProcFunc.'],$config,$table,$row,$field);

			if (!count($selItems))	{
				$selItems[]=array('','');
			}
			if (is_array($PA['itemFormElValue']))
				$PA['itemFormElValue'] = array_reduce(array_keys($PA['itemFormElValue']), create_function('$v1, $v2', 'return $v1 | $v2;'), 0);
			$thisValue = intval($PA['itemFormElValue']);

			$cols = intval($config['cols']);
			if ($cols > 1)	{
				$item.= '<table border="0" cellspacing="0" cellpadding="0" class="typo3-TCEforms-checkboxArray">';
				for ($c=0;$c<count($selItems);$c++) {
					$p = $selItems[$c];
					if(!($c%$cols))	{ $item.='<tr>'; }
					$cBP = $this->checkBoxParams($PA['itemFormElName'],$thisValue,$c,count($selItems),implode('',$PA['fieldChangeFunc']));
					$cBName = $PA['itemFormElName'].'_'.$c;
					$cBID = $PA['itemFormElID'].'_'.$c;
					$item.= '<td nowrap="nowrap">'.
							'<input type="checkbox"'.$this->insertDefStyle('check').' value="1" name="'.$cBName.'"'.$cBP.$disabled.' id="'.$cBID.'" />'.
							$this->wrapLabels('<label for="'.$cBID.'">'.htmlspecialchars($p[0]).'</label>&nbsp;').
							'</td>';
					if(($c%$cols)+1==$cols)	{$item.='</tr>';}
				}
				if ($c%$cols)	{
					$rest=$cols-($c%$cols);
					for ($c=0;$c<$rest;$c++) {
						$item.= '<td></td>';
					}
					if ($c>0)	{ $item.= '</tr>'; }
				}
				$item.= '</table>';
			} else {
				for ($c=0;$c<count($selItems);$c++) {
					$p = $selItems[$c];
					$cBP = $this->checkBoxParams($PA['itemFormElName'],$thisValue,$c,count($selItems),implode('',$PA['fieldChangeFunc']));
					$cBName = $PA['itemFormElName'] . '[' . $c . ']';
					$cBID = $PA['itemFormElID'].'_'.$c;
					$item.= ($c>0?'<br />':'').
							'<input type="checkbox"'.$this->insertDefStyle('check').' value="1" name="'.$cBName.'"'.$cBP.$PA['onFocus'].$disabled.' id="'.$cBID.'" />'.
							htmlspecialchars($p[0]);
				}
			}
			/*if (!$disabled) {
				$item.= '<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($thisValue).'" />';
			}*/

			return $item;
		}
	}

	/**
	 * Creates checkbox parameters
	 *
	 * @param	string		Form element name
	 * @param	integer		The value of the checkbox (representing checkboxes with the bits)
	 * @param	integer		Checkbox # (0-9?)
	 * @param	integer		Total number of checkboxes in the array.
	 * @param	string		Additional JavaScript for the onclick handler.
	 * @return	string		The onclick attribute + possibly the checked-option set.
	 */
	function checkBoxParams($itemName,$thisValue,$c,$iCount,$addFunc='')	{
		/*$onClick = $this->elName($itemName).'.value=this.checked?('.$this->elName($itemName).'.value|'.pow(2,$c).'):('.$this->elName($itemName).'.value&'.(pow(2,$iCount)-1-pow(2,$c)).');'.
					$addFunc;*/
		$str = //' onclick="'.htmlspecialchars($onClick).'"'.
				(($thisValue&pow(2,$c))?' checked="checked"':'');
		return $str;
	}

	/**
	 * Creates a single-selector box
	 * (Render function for getSingleField_typeSelect())
	 *
	 * @param	string		See getSingleField_typeSelect()
	 * @param	string		See getSingleField_typeSelect()
	 * @param	array		See getSingleField_typeSelect()
	 * @param	array		See getSingleField_typeSelect()
	 * @param	array		(Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param	array		Items available for selection
	 * @param	string		Label for no-matching-value
	 * @return	string		The HTML code for the item
	 * @see getSingleField_typeSelect()
	 */
	function getSingleField_typeSelect_single($table,$field,$row,&$PA,$config,$selItems,$nMV_label)	{
		// check against inline uniqueness
		$inlineParent = $this->inline->getStructureLevel(-1);
		if(is_array($inlineParent) && $inlineParent['uid']) {
			if ($inlineParent['config']['foreign_table'] == $table && $inlineParent['config']['foreign_unique'] == $field) {
				$uniqueIds = $this->inline->inlineData['unique'][$this->inline->inlineNames['object'].'['.$table.']']['used'];
				$PA['fieldChangeFunc']['inlineUnique'] = "inline.updateUnique(this,'".$this->inline->inlineNames['object'].'['.$table."]','".$this->inline->inlineNames['form']."','".$row['uid']."');";
			}
				// hide uid of parent record for symmetric relations
			if ($inlineParent['config']['foreign_table'] == $table && ($inlineParent['config']['foreign_field'] == $field || $inlineParent['config']['symmetric_field'] == $field)) {
				$uniqueIds[] = $inlineParent['uid'];
			}
		}

			// Initialization:
		$c = 0;
		$sI = 0;
		$noMatchingValue = 1;
		$opt = array();
		$selicons = array();
		$onlySelectedIconShown = 0;
		$size = intval($config['size']);
		$selectedStyle = ''; // Style set on <select/>

		$disabled = '';

			// Icon configuration:
		if ($config['suppress_icons']=='IF_VALUE_FALSE')	{
			$suppressIcons = !$PA['itemFormElValue'] ? 1 : 0;
		} elseif ($config['suppress_icons']=='ONLY_SELECTED')	{
			$suppressIcons=0;
			$onlySelectedIconShown=1;
		} elseif ($config['suppress_icons']) 	{
			$suppressIcons = 1;
		} else $suppressIcons = 0;

			// Traverse the Array of selector box items:
		$optGroupStart = array();
		foreach($selItems as $p)	{
			$sM = (!strcmp($PA['itemFormElValue'],$p[1])?' selected="selected"':'');
			if ($sM)	{
				$sI = $c;
				$noMatchingValue = 0;
			}

				// Getting style attribute value (for icons):
			if ($config['iconsInOptionTags'])	{
				$styleAttrValue = $this->optionTagStyle($p[2]);
				if ($sM) {
					list($selectIconFile,$selectIconInfo) = $this->getIcon($p[2]);
						if (!empty($selectIconInfo)) {
							$selectedStyle = ' style="background-image: url('.$selectIconFile.'); background-repeat: no-repeat; background-position: 0% 50%; padding: 1px; padding-left: 24px; -webkit-background-size: 0;"';
						}
				}
			}

				// Compiling the <option> tag:
			if (!($p[1] != $PA['itemFormElValue'] && is_array($uniqueIds) && in_array($p[1], $uniqueIds))) {
				if(!strcmp($p[1],'--div--')) {
					$optGroupStart[0] = $p[0];
					$optGroupStart[1] = $styleAttrValue;

				} else {
					if (count($optGroupStart)) {
						if($optGroupOpen) { // Closing last optgroup before next one starts
							$opt[]='</optgroup>' . "\n";
						}
						$opt[]= '<optgroup label="'.t3lib_div::deHSCentities(htmlspecialchars($optGroupStart[0])).'"'.
								($optGroupStart[1] ? ' style="'.htmlspecialchars($optGroupStart[1]).'"' : '').
								' class="c-divider">' . "\n";
						$optGroupOpen = true;
						$c--;
						$optGroupStart = array();
					}
					$opt[]= '<option value="'.htmlspecialchars($p[1]).'"'.
							$sM.
							($styleAttrValue ? ' style="'.htmlspecialchars($styleAttrValue).'"' : '').
							'>'.t3lib_div::deHSCentities(htmlspecialchars($p[0])).'</option>' . "\n";
				}
			}

				// If there is an icon for the selector box (rendered in table under)...:
			if ($p[2] && !$suppressIcons && (!$onlySelectedIconShown || $sM))	{
				list($selIconFile,$selIconInfo)=$this->getIcon($p[2]);
				$iOnClick = $this->elName($PA['itemFormElName']).'.selectedIndex='.$c.'; '.implode('',$PA['fieldChangeFunc']).$this->blur().'return false;';
				$selicons[]=array(
					(!$onlySelectedIconShown ? '<a href="#" onclick="'.htmlspecialchars($iOnClick).'">' : '').
					'<img src="'.$selIconFile.'" '.$selIconInfo[3].' vspace="2" border="0" title="'.htmlspecialchars($p[0]).'" alt="'.htmlspecialchars($p[0]).'" />'.
					(!$onlySelectedIconShown ? '</a>' : ''),
					$c,$sM);
			}
			$c++;
		}

		if($optGroupOpen) { // Closing optgroup if open
			$opt[]='</optgroup>';
			$optGroupOpen = false;
		}

			// No-matching-value:
		if ($PA['itemFormElValue'] && $noMatchingValue && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement'])	{
			$nMV_label = @sprintf($nMV_label, $PA['itemFormElValue']);
			$opt[]= '<option value="'.htmlspecialchars($PA['itemFormElValue']).'" selected="selected">'.htmlspecialchars($nMV_label).'</option>';
		}

			// Create item form fields:
		$sOnChange = 'if (this.options[this.selectedIndex].value==\'--div--\') {this.selectedIndex='.$sI.';} '.implode('',$PA['fieldChangeFunc']);
		if(!$disabled) {
			$item.= '<input type="hidden" name="'.$PA['itemFormElName'].'_selIconVal" value="'.htmlspecialchars($sI).'" />';	// MUST be inserted before the selector - else is the value of the hiddenfield here mysteriously submitted...
		}
		$item.= '<select'.$selectedStyle.' name="'.$PA['itemFormElName'].'"'.
					$this->insertDefStyle('select').
					($size?' size="'.$size.'"':'').
					' onchange="'.htmlspecialchars($sOnChange).'"'.
					$PA['onFocus'].$disabled.'>';
		$item.= implode('',$opt);
		$item.= '</select>';

			// Create icon table:
		if (count($selicons) && !$config['noIconsBelowSelect'])	{
			$item.='<table border="0" cellpadding="0" cellspacing="0" class="typo3-TCEforms-selectIcons">';
			$selicon_cols = intval($config['selicon_cols']);
			if (!$selicon_cols)	$selicon_cols=count($selicons);
			$sR = ceil(count($selicons)/$selicon_cols);
			$selicons = array_pad($selicons,$sR*$selicon_cols,'');
			for($sa=0;$sa<$sR;$sa++)	{
				$item.='<tr>';
				for($sb=0;$sb<$selicon_cols;$sb++)	{
					$sk=($sa*$selicon_cols+$sb);
					$imgN = 'selIcon_'.$table.'_'.$row['uid'].'_'.$field.'_'.$selicons[$sk][1];
					$imgS = ($selicons[$sk][2]?$GLOBALS['BACK_PATH'].'gfx/content_selected.gif':'clear.gif');
					$item.='<td><img name="'.htmlspecialchars($imgN).'" src="'.$imgS.'" width="7" height="10" alt="" /></td>';
					$item.='<td>'.$selicons[$sk][0].'</td>';
				}
				$item.='</tr>';
			}
			$item.='</table>';
		}

		return $item;
	}     
	
	function getSingleField_typeSelect($table, $field, $row, &$PA)
	{
		if ($this->_tceforms)
			return $this->_tceforms->getSingleField_typeSelect($table, $field, $row, $PA);
		else
		{
			//Frontend form rendering.
			global $TCA;

			$config = $PA['fieldConf']['config'];

			$disabled = '';

			// Getting the selector box items from the system
			$selItems = $this->addSelectOptionsToItemArray($this->initItemArray($PA['fieldConf']), $PA['fieldConf'], $this->setTSconfig($table,$row), $field);
			$selItems = $this->addItems($selItems,$PA['fieldTSConfig']['addItems.']);
			if ($config['itemsProcFunc']) 
				$selItems = $this->procItems($selItems, $PA['fieldTSConfig']['itemsProcFunc.'], $config, $table, $row, $field);
	
			// Possibly remove some items:
			$removeItems = t3lib_div::trimExplode(',', $PA['fieldTSConfig']['removeItems'],1);
			foreach($selItems as $tk => $p) {	
				// Checking languages and authMode:
				$languageDeny = $TCA[$table]['ctrl']['languageField'] && !strcmp($TCA[$table]['ctrl']['languageField'], $field);
				$authModeDeny = $config['form_type']=='select' && $config['authMode'];
				if (in_array($p[1],$removeItems) || $languageDeny || $authModeDeny)	{
					unset($selItems[$tk]);
				} elseif (isset($PA['fieldTSConfig']['altLabels.'][$p[1]])) {
					$selItems[$tk][0] = $this->sL($PA['fieldTSConfig']['altLabels.'][$p[1]]);
				}
			}

			// Creating the label for the "No Matching Value" entry.
			$nMV_label = isset($PA['fieldTSConfig']['noMatchingValue_label']) ? $this->sL($PA['fieldTSConfig']['noMatchingValue_label']) : '[ '.$this->getLL('l_noMatchingValue').' ]';

			// Prepare some values:
			$maxitems = intval($config['maxitems']);
	
			// If a SINGLE selector box...
			if ($maxitems<=1)
				$item = $this->getSingleField_typeSelect_single($table,$field,$row,$PA,$config,$selItems,$nMV_label);
/*			} elseif (!strcmp($config['renderMode'],'checkbox'))	{	// Checkbox renderMode
				$item = $this->getSingleField_typeSelect_checkbox($table,$field,$row,$PA,$config,$selItems,$nMV_label);
			} elseif (!strcmp($config['renderMode'],'singlebox'))	{	// Single selector box renderMode
				$item = $this->getSingleField_typeSelect_singlebox($table,$field,$row,$PA,$config,$selItems,$nMV_label);
			} else {	// Traditional multiple selector box:
				$item = $this->getSingleField_typeSelect_multiple($table,$field,$row,$PA,$config,$selItems,$nMV_label);
			}*/

			// Wizards:
			if (!$disabled) {
				$altItem = '<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';
//				$item = $this->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'],$specConf);
			}

			return $item;
		}
	}

	function getSingleField_typeGroup($table, $field, $row, &$PA)
	{
		if ($this->_tceforms)
			return $this->_tceforms->getSingleField_typeGroup($table, $field, $row, $PA);
		else
		{
			//Frontend form rendering.
			// Init:
		$config = $PA['fieldConf']['config'];
		$internal_type = $config['internal_type'];
		$show_thumbs = $config['show_thumbs'];
		$size = intval($config['size']);
		$maxitems = t3lib_div::intInRange($config['maxitems'],0);
		if (!$maxitems)	$maxitems=100000;
		$minitems = t3lib_div::intInRange($config['minitems'],0);
		$allowed = $config['allowed'];
		$disallowed = $config['disallowed'];

		$disabled = '';
		if($this->renderReadonly || $config['readOnly'])  {
			$disabled = ' disabled="disabled"';
		}

		$this->registerRequiredProperty('range', $PA['itemFormElName'], array($minitems,$maxitems,'imgName'=>$table.'_'.$row['uid'].'_'.$field));
		$info='';

			// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. See http://typo3.org/documentation/document-library/doc_core_api/Wizards_Configuratio/.
		//$specConf = $this->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);

			// Acting according to either "file" or "db" type:
		switch((string)$config['internal_type'])	{
			case 'file':	// If the element is of the internal type "file":

					// Creating string showing allowed types:
				$tempFT = t3lib_div::trimExplode(',',$allowed,1);
				if (!count($tempFT))	{$info.='*';}
				foreach($tempFT as $ext)	{
					if ($ext)	{
						$info.=strtoupper($ext).' ';
					}
				}
					// Creating string, showing disallowed types:
				$tempFT_dis = t3lib_div::trimExplode(',',$disallowed,1);
				if (count($tempFT_dis))	{$info.='<br />';}
				foreach($tempFT_dis as $ext)	{
					if ($ext)	{
						$info.='-'.strtoupper($ext).' ';
					}
				}

					// Making the array of file items:
				$itemArray = t3lib_div::trimExplode(',',$PA['itemFormElValue'],1);

					// Creating the element:
				$item .= '
					<div class="file">
						<input type="hidden" name="' . $PA['itemFormElName'] . '" value="' . htmlspecialchars($PA['itemFormElValue']) . '" />
						<div class="files">' . (((count($itemArray) >= $maxitems) || ($disabled != '') || (isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'upload'))) ? ('') : ('
							<input type="file" id="' . $PA['itemFormElID'] . '"'.$this->formWidth().' size="60" class="fileinput" name="' . $PA['itemFormElName_file'] . '" /><p class="filetypes">' . $info . '</p>')) .
					implode(
						'',
						array_map(
							create_function(
								'$val',
								'
	$uniqid = uniqid();
	return \'
							<input type="checkbox" class="checkbox" id="' . $PA['itemFormElID'] . '_\' . $uniqid . \'" name="' . substr($PA['itemFormElName'], 0, -1) . '_remove][]" value="\' . htmlspecialchars($val) . \'" />
							<label class="checkbox" for="' . $PA['itemFormElID'] . '_\' . $uniqid . \'">\' . 
		str_replace(
			\'###FILENAME###\', 
			\'<a target="_blank" href="' . $config['uploadfolder'] . '/\' . $val . \'">\' . htmlspecialchars($val) . \'</a>\', 
			\'' . addslashes($this->sL('LLL:EXT:ics_utopia/mod5/locallang.xml:filedelete')) . '\'
		) . \'</label>\';'),
							$itemArray
						)
					) . '
						</div>
					</div>';
			break;
			/*case 'folder':	// If the element is of the internal type "folder":

					// array of folder items:
				$itemArray = t3lib_div::trimExplode(',', $PA['itemFormElValue'], 1);

					// Creating the element:
				$params = array(
					'size'              => $size,
					'dontShowMoveIcons' => ($maxitems <= 1),
					'autoSizeMax'       => t3lib_div::intInRange($config['autoSizeMax'], 0),
					'maxitems'          => $maxitems,
					'style'             => isset($config['selectedListStyle']) ?
							' style="'.htmlspecialchars($config['selectedListStyle']).'"'
						:	' style="'.$this->defaultMultipleSelectorStyle.'"',
					'info'              => $info,
					'readOnly'          => $disabled
				);

				$item.= $this->dbFileIcons(
					$PA['itemFormElName'],
					'folder',
					'',
					$itemArray,
					'',
					$params,
					$PA['onFocus']
				);
			break;
			case 'db':	// If the element is of the internal type "db":

					// Creating string showing allowed types:
				$tempFT = t3lib_div::trimExplode(',',$allowed,1);
				if (!strcmp(trim($tempFT[0]),'*'))	{
					$info.='<span class="nobr">&nbsp;&nbsp;&nbsp;&nbsp;'.
							htmlspecialchars($this->getLL('l_allTables')).
							'</span><br />';
				} else {
					while(list(,$theT)=each($tempFT))	{
						if ($theT)	{
							$info.='<span class="nobr">&nbsp;&nbsp;&nbsp;&nbsp;'.
									t3lib_iconWorks::getIconImage($theT,array(),$this->backPath,'align="top"').
									htmlspecialchars($this->sL($GLOBALS['TCA'][$theT]['ctrl']['title'])).
									'</span><br />';
						}
					}
				}

				$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
				$itemArray = array();
				$imgs = array();

					// Thumbnails:
				$temp_itemArray = t3lib_div::trimExplode(',',$PA['itemFormElValue'],1);
				foreach($temp_itemArray as $dbRead)	{
					$recordParts = explode('|',$dbRead);
					list($this_table,$this_uid) = t3lib_BEfunc::splitTable_Uid($recordParts[0]);
					$itemArray[] = array('table'=>$this_table, 'id'=>$this_uid);
					if (!$disabled && $show_thumbs)	{
						$rr = t3lib_BEfunc::getRecordWSOL($this_table,$this_uid);
						$imgs[] = '<span class="nobr">'.
								$this->getClickMenu(t3lib_iconWorks::getIconImage($this_table,$rr,$this->backPath,'align="top" title="'.htmlspecialchars(t3lib_BEfunc::getRecordPath($rr['pid'],$perms_clause,15)).' [UID: '.$rr['uid'].']"'),$this_table, $this_uid).
								'&nbsp;'.
								t3lib_BEfunc::getRecordTitle($this_table,$rr,TRUE).' <span class="typo3-dimmed"><em>['.$rr['uid'].']</em></span>'.
								'</span>';
					}
				}
				$thumbsnail='';
				if (!$disabled && $show_thumbs)	{
					$thumbsnail = implode('<br />',$imgs);
				}

					// Creating the element:
				$params = array(
					'size' => $size,
					'dontShowMoveIcons' => ($maxitems<=1),
					'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'],0),
					'maxitems' => $maxitems,
					'style' => isset($config['selectedListStyle']) ? ' style="'.htmlspecialchars($config['selectedListStyle']).'"' : ' style="'.$this->defaultMultipleSelectorStyle.'"',
					'info' => $info,
					'thumbnails' => $thumbsnail,
					'readOnly' => $disabled
				);
				$item.= $this->dbFileIcons($PA['itemFormElName'],'db',implode(',',$tempFT),$itemArray,'',$params,$PA['onFocus'],$table,$field,$row['uid']);

			break;*/
		}

			// Wizards:
		$altItem = '<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';

		return $item;
		}
	}
	
	/**
	 * Handler for unknown types.
	 *
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeUnknown($table,$field,$row,&$PA)	{
		$item='Unknown type: '.$PA['fieldConf']['config']['form_type'].'<br />';

		return $item;
	}

	/**
	 * User defined field type
	 *
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeUser($table,$field,$row,&$PA)	{
		$PA['table']=$table;
		$PA['field']=$field;
		$PA['row']=$row;

		$PA['pObj']=&$this;

		return t3lib_div::callUserFunction($PA['fieldConf']['config']['userFunc'],$PA,$this);
	}
	
 	/**
	 * Add the id and the style property to the field palette
	 *
	 * @param	string		Palette Code
	 * @param	string		The table name for which to open the palette.
	 * @param	string		Palette ID
	 * @param	string		The record array
	 * @return	boolean		is collapsed
	 */
	function wrapPaletteField($code,$table,$row,$palette,$collapsed )	{// Mets le ul autour
		$display = $collapsed ? 'none' : 'block';
		$id = 'TCEFORMS_'.$table.'_'.$palette.'_'.$row['uid'];
		$code = '
		<ul>
			'.$code.'
		</ul>';
		return $code;
	}

	/**
	 * Creates HTML output for a palette
	 *
	 * @param	array		The palette array to print
	 * @return	string		HTML output
	 */
	function printPalette($palArr)	{ // li pour chaque champ, par champ

		// Init color/class attributes:
		$ccAttr2 = $this->colorScheme[2] ? ' bgcolor="'.$this->colorScheme[2].'"' : '';
		$ccAttr2.= $this->classScheme[2] ? ' class="'.$this->classScheme[2].'"' : '';
		$ccAttr4 = $this->colorScheme[4] ? ' style="color:'.$this->colorScheme[4].'"' : '';
		$ccAttr4.= $this->classScheme[4] ? ' class="'.$this->classScheme[4].'"' : '';

		// Traverse palette fields and render them into table rows:
		foreach($palArr as $content)	{
			//var_dump($content);
			$Row[] = '
				<label for="'.$content['TABLE'].'_'.$content['ID'].'_'.$content['FIELD'].'">' . $content['NAME'] . '</label>'
				.$content['ITEM'] . (($content['HELP_TEXT']) ? ('
				<p class="helptext">' . $content['HELP_TEXT'] . '</p>') : (''));
		}
		
		// Final wrapping into the table:
		$out = '
			<li>
				'.implode('
			</li>
			<li>',$Row).'
			</li>';

		return $out;
	}
	/**
	 * Creates a palette (collection of secondary options).
	 *
	 * @param	string		The table name
	 * @param	array		The row array
	 * @param	string		The palette number/pointer
	 * @param	string		Header string for the palette (used when in-form). If not set, no header item is made.
	 * @param	string		Optional alternative list of fields for the palette
	 * @param	string		Optional Link text for activating a palette (when palettes does not have another form element to belong to).
	 * @return	string		HTML code.
	 */
	function getPaletteFieldsFE($table, $row, $palette, $header='', $itemList='', $collapsedHeader=NULL)	{
		
		$fieldsConf = array();
		$fields = explode(',', $itemList);
		t3lib_div::loadTCA($table);
		foreach ($fields as $field)
		{
			$fieldInfo = explode(';', $field);
			$fieldsConf[$fieldInfo[0]] = $GLOBALS['TCA'][$table]['columns'][$fieldInfo[0]];
			if (isset($fieldInfo[1]))
				$fieldsConf[$fieldInfo[0]]['label'] = $fieldInfo[1];
		}
		$parts = $this->loadPaletteElements($table, $row, $fieldsConf);

		$out = '';
		// Put palette together if there are fields in it:
		if (count($parts))
		{
			if ($header)
				$out .= '
				<legend>' . $header . '</legend>
				';
			$paletteHtml = $this->wrapPaletteField($this->printPalette($parts), $table, $row ,$palette, false);
			$out = '
			<fieldset>' . $out . $paletteHtml . '</fieldset>
			';
		}
		
		return $out;
	}
	
	function getPaletteFields($table, $row, $fields, $title)
	{
		if ($this->_tceforms)
		{
			if (is_array($fields))
				$fields = implode(',', $fields);
			return $this->_tceforms->getPaletteFields($table, $row, '', $title, $fields);
		}
		else
		{
			// TODO: Frontend form rendering.
			if (is_array($fields))
				$fields = implode(',', $fields);
			return $this->getPaletteFieldsFE($table, $row, '', $title, $fields);
		}
	}
	
	function getGenPaletteFields($table, $row, $title, $fieldsConf)
	{
		if ($this->_tceforms)
		{
			if (!$this->_tceforms->doPrintPalette)	{
				return '';
			}

			$out = '';
			$parts = $this->loadPaletteElements($table, $row, $fieldsConf);

			// Put palette together if there are fields in it:
			if (count($parts))	{
				if ($title)	{
					$out .= $this->_tceforms->intoTemplate(
						array('HEADER' => htmlspecialchars($title)),
						$this->_tceforms->palFieldTemplateHeader
					);
				}

				if (!method_exists($this->_tceforms, 'wrapPaletteField'))
					$paletteHtml = $this->_tceforms->intoTemplate(array(
							'PALETTE' => $this->_tceforms->printPalette($parts)
						),
						$this->_tceforms->palFieldTemplate
					);
				else
					$paletteHtml = $this->_tceforms->wrapPaletteField($this->_tceforms->printPalette($parts), $table, $row ,'', false);

				$out .= $this->_tceforms->intoTemplate(
					array('PALETTE' => $paletteHtml),
					$this->_tceforms->palFieldTemplate
				);
			}
			return $out;
		}
		else
		{
			$out = '';
			$parts = $this->loadPaletteElements($table, $row, $fieldsConf);
				// Put palette together if there are fields in it:
			if (count($parts))
			{
				if ($title)
					$out .= '
				<legend>' . $title . '</legend>
				';
				$paletteHtml = $this->wrapPaletteField($this->printPalette($parts), $table, $row ,$palette, false);
				$out = '
			<fieldset>
				' . $out . $paletteHtml . '
			</fieldset>
			';
			}
			return $out;
		}

	}

	function loadPaletteElements($table, $row, $fieldsConf)	{
		$parts = array();
		foreach($fieldsConf as $field => $conf)	{
			$elem = $this->getGenSingleField($table,$field,$row,'',1,'','', $conf);
			if (is_array($elem))	{
				$parts[] = $elem;
			}
		}
		return $parts;
	}
	
	function getGenSingleField($table, $field, $row, $altName = '', $palette = 0, $extra = '', $pal = 0, $conf = array())
	{
		global $TCA;
		$out = '';
		$PA = array();
		$PA['altName'] = $altName;
		$PA['palette'] = $palette;
		$PA['extra'] = $extra;
		$PA['pal'] = $pal;

		// Make sure to load full $TCA array for the table:
		if (empty($conf)) 
			t3lib_div::loadTCA($table);

		// Get the TCA configuration for the current field:
		$PA['fieldConf'] = (empty($conf)) ? ($TCA[$table]['columns'][$field]) : ($conf);
		$PA['fieldConf']['config']['form_type'] = $PA['fieldConf']['config']['form_type'] ? $PA['fieldConf']['config']['form_type'] : $PA['fieldConf']['config']['type'];	// Using "form_type" locally in this script

		if ($this->_tceforms)
		{
			// Now, check if this field is configured and editable (according to excludefields + other configuration)
			if (	is_array($PA['fieldConf']) && $PA['fieldConf']['config']['form_type'] != 'passthrough' &&
					($this->_tceforms->RTEenabled || !$PA['fieldConf']['config']['showIfRTE']) &&
					(!$PA['fieldConf']['displayCond'] || $this->_tceforms->isDisplayCondition($PA['fieldConf']['displayCond'], $row))
				)	
			{
				// Init variables:
				$PA['itemFormElName'] = $this->_tceforms->prependFormFieldNames.'['.$table.']['.$row['uid'].']['.$field.']'; // Form field name
				$PA['itemFormElName_file'] = $this->_tceforms->prependFormFieldNames_file.'['.$table.']['.$row['uid'].']['.$field.']'; // Form field name, in case of file uploads
				$PA['itemFormElValue'] = $row[$field]; // The value to show in the form field.
				$PA['itemFormElID'] = $this->_tceforms->prependFormFieldNames.'_'.$table.'_'.$row['uid'].'_'.$field;

				// Create a JavaScript code line which will ask the user to save/update the form due to changing the element. This is used for eg. "type" fields and others configured with "requestUpdate"
				if (($TCA[$table]['ctrl']['type'] && !strcmp($field,$TCA[$table]['ctrl']['type'])) ||
					($TCA[$table]['ctrl']['requestUpdate'] && t3lib_div::inList($TCA[$table]['ctrl']['requestUpdate'],$field)) ||
					($PA['fieldConf']['requestUpdate'])) {
					if($GLOBALS['BE_USER']->jsConfirmation(1))      {
						$alertMsgOnChange = 'if (confirm('.$GLOBALS['LANG']->JScharCode($this->_tceforms->getLL('m_onChangeAlert')).') && TBE_EDITOR_checkSubmit(-1)){ TBE_EDITOR_submitForm() };';
					} else {
						$alertMsgOnChange = 'if (TBE_EDITOR_checkSubmit(-1)){ TBE_EDITOR_submitForm() };';
					}
				} else {
					$alertMsgOnChange = '';
				}

				// Find item
				$item='';
				$PA['label'] = ($PA['altName'] ? $PA['altName'] : $PA['fieldConf']['label']);
				$PA['label'] = $GLOBALS['LANG']->sL($PA['label']);
				// JavaScript code for event handlers:
				$PA['fieldChangeFunc']=array();
				$PA['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = "TBE_EDITOR.fieldChanged('".$table."','".$row['uid']."','".$field."','".$PA['itemFormElName']."');";
				$PA['fieldChangeFunc']['alert'] = $alertMsgOnChange;

				// Based on the type of the item, call a render function:
				$item = $this->getSingleField_SW($table,$field,$row,$PA);
				$renderLanguageDiff = false;
				
				// Create output value:
				if ($PA['fieldConf']['config']['form_type'] == 'user' && $PA['fieldConf']['config']['noTableWrapping'])	{
					$out = $item;
				} elseif ($PA['palette'])	{
					// Array:
					$out = array(
						'NAME'=>$PA['label'],
						'ID'=>$row['uid'],
						'FIELD'=>$field,
						'TABLE'=>$table,
						'ITEM'=>$item,
						'HELP_ICON' => $this->_tceforms->helpTextIcon($table,$field,1) );
					$out = $this->_tceforms->addUserTemplateMarkers($out,$table,$field,$row,$PA);					
					
				} else {
					// String:
					$out = array(
						'NAME'=>$PA['label'],
						'ITEM'=>$item,
						'TABLE'=>$table,
						'ID'=>$row['uid'],
						'HELP_ICON'=>$this->_tceforms->helpTextIcon($table,$field),
						'HELP_TEXT'=>$this->_tceforms->helpText($table,$field),
						'PAL_LINK_ICON'=>'',
						'FIELD'=>$field );
					$out = $this->_tceforms->addUserTemplateMarkers($out,$table,$field,$row,$PA);
					// String:
					$out = $this->_tceforms->intoTemplate($out);
					
				}
			}
		}
		else
		{
			// Now, check if this field is configured and editable (according to excludefields + other configuration)
			if (	is_array($PA['fieldConf']) && $PA['fieldConf']['config']['form_type'] != 'passthrough' &&
					!$PA['fieldConf']['config']['showIfRTE'] && 
					(!$PA['fieldConf']['displayCond'] || t3lib_TCEforms::isDisplayCondition($PA['fieldConf']['displayCond'], $row))
				)
			{
				// Init variables:
				$PA['itemFormElName'] = $this->prependFormFieldNames.'['.$table.']['.$row['uid'].']['.$field.']'; // Form field name
				$PA['itemFormElName_file'] = $this->prependFormFieldNames_file.'['.$table.']['.$row['uid'].']['.$field.']'; // Form field name, in case of file uploads
				$PA['itemFormElValue'] = $row[$field]; // The value to show in the form field.
				$PA['itemFormElID'] = $this->prependFormFieldNames.'_'.$table.'_'.$row['uid'].'_'.$field;

				// Create a JavaScript code line which will ask the user to save/update the form due to changing the element. This is used for eg. "type" fields and others configured with "requestUpdate"
/*				if (($TCA[$table]['ctrl']['type'] && !strcmp($field,$TCA[$table]['ctrl']['type'])) ||
					($TCA[$table]['ctrl']['requestUpdate'] && t3lib_div::inList($TCA[$table]['ctrl']['requestUpdate'],$field)) ||
					($PA['fieldConf']['requestUpdate'])) 
				{
					if($GLOBALS['BE_USER']->jsConfirmation(1))      
					{
						$alertMsgOnChange = 'if (confirm('.$GLOBALS['LANG']->JScharCode($this->_tceforms->getLL('m_onChangeAlert')).') && TBE_EDITOR_checkSubmit(-1)){ TBE_EDITOR_submitForm() };';
					} else 
					{
						$alertMsgOnChange = 'if (TBE_EDITOR_checkSubmit(-1)){ TBE_EDITOR_submitForm() };';
					}
				} else 
				{*/
					$alertMsgOnChange = '';
//				}

				// Find item
				$item='';
				$PA['label'] = ($PA['altName'] ? $PA['altName'] : $PA['fieldConf']['label']);
				$PA['label'] = $GLOBALS['LANG']->sL($PA['label']);
				// JavaScript code for event handlers:
				$PA['fieldChangeFunc']=array();
				//$PA['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = "TBE_EDITOR.fieldChanged('".$table."','".$row['uid']."','".$field."','".$PA['itemFormElName']."');";
				$PA['fieldChangeFunc']['alert'] = $alertMsgOnChange;

				// Based on the type of the item, call a render function:
				$item = $this->getSingleField_SW($table,$field,$row,$PA);
				$renderLanguageDiff = false;
				if(in_array('required', explode(',', $PA['fieldConf']['config']['eval']))){
					$addRequired = '<span class="requiredField">*</span>';
				}else{
					$addRequired ='';
				}
				// Create output value:
				if ($PA['fieldConf']['config']['form_type'] == 'user' && $PA['fieldConf']['config']['noTableWrapping']) {
					$out = $item;
				} elseif ($PA['palette'])	
				{
					// Array:
					$out = array(
						'NAME'=>$PA['label'].$addRequired,
						'ID'=>$row['uid'],
						'FIELD'=>$field,
						'TABLE'=>$table,
						'ITEM'=>$item,
						'HELP_ICON'=>'',//$this->helpTextIcon($table,$field),
						'HELP_TEXT'=>$this->helpText($table,$field),
					);
				
				} else 
				{
					// String:
					$out = array(
						'NAME'=>$PA['label'].$addRequired,
						'ITEM'=>$item,
						'TABLE'=>$table,
						'ID'=>$row['uid'],
						'HELP_ICON'=>'',//$this->helpTextIcon($table,$field),
						'HELP_TEXT'=>$this->helpText($table,$field),
						'PAL_LINK_ICON'=>'',
						'FIELD'=>$field );
					$out = '
					<li>
						<label for="'.$PA['itemFormElID'].'">'.$out["NAME"].'</label>
						'.$out["ITEM"].'' . (($out['HELP_TEXT']) ? ('
						<p class="helptext">' . $out['HELP_TEXT'] . '</p>') : ('')) . '
					</li>';
					
				}
			}
		}			
		// Return value (string or array)
		return $out;
	}
	
	/**
	 * Wrap the whole form fields into the main component.
	 * A <!--###FOOTER###--> marker is added in order to insert the action buttons.
	 *
	 * @param string The html content.
	 * @param string The form title.
	 * @param string A first level footer code.
	 * @return string The compiled HTML code.
	 */
	function wrapTotal($panel, $title, $footer)
	{
		if ($this->_tceforms)
		{
			$parts = explode('|', $this->_tceforms->totalWrap,2);
			$markers = array(
				'###ID_NEW_INDICATOR###' => '',
				'###RECORD_LABEL###' => '',
				'###TABLE_TITLE###' => '', // $title,
				'###PAGE_TITLE###' => $title,
				'###RECORD_ICON###' => '',
			);
			$parts[0] = str_replace(array_keys($markers), array_values($markers), str_replace(' - ', '', $parts[0]));
			$parts[1] = str_replace(array_keys($markers), array_values($markers), $parts[1]);
			return $parts[0] . $panel . $footer . '<!--###FOOTER###-->' . $parts[1];
		}
		else
		{
			// TODO: Frontend rendering.
			$parts = explode('|', $this->totalWrap,2);
			$markers = array(
				'###ID_NEW_INDICATOR###' => '',
				'###RECORD_LABEL###' => '',
				'###TABLE_TITLE###' => '', // $title,
				'###PAGE_TITLE###' => $title,
				'###RECORD_ICON###' => '',
			);
			$parts[0] = str_replace(array_keys($markers), array_values($markers), str_replace(' - ', '', $parts[0]));
			$parts[1] = str_replace(array_keys($markers), array_values($markers), $parts[1]);
			return $parts[0] . '
			<fieldset>
			<legend>'. $title .'</legend>
			<ul>
				' . $panel . $footer . '<!--###FOOTER###-->
			</ul>
			</fieldset>' . $parts[1];
		}
	}
	
	/**
	 * Returns the main action buttons for the site creation course.
	 *
	 * @param boolean Indicates if the previous button is needed.
	 * @param boolean Indicates if the next button is needed.
	 * @return string The HTML code to insert.
	 */
	function getActionButtons($prev = true, $next = true)
	{
		global $LANG;
		if ($this->_tceforms)
		{			
			$content = '<tr class="bgColor2"><td></td><td>';
			if ($prev) $content .= '<input type="button" name="action[prev]_btn" onclick="this.form.elements.namedItem(\'closeDoc\').value = \'1\'; this.form.elements.namedItem(\'action[prev]\').value = \'1\'; this.form.submit();" value="' . $LANG->getLL('action.previous') . '" />
						<input type="hidden" name="closeDoc" value="0" />
						<input type="hidden" name="action[prev]" value="" />';
			if ($next) $content .= '<input type="submit" name="action[next]" value="' . $LANG->getLL('action.next') . '" />';
			return $content . '</td></tr>';
		}
		else
		{
			// TODO: Frontend rendering.
			$content = '';
			if ($prev)
			{
				$content .= '
					<input class="utopia-prev-button" type="button" name="action[prev]_btn" onclick="this.form.elements.namedItem(\'closeDoc\').value = \'1\'; this.form.elements.namedItem(\'action[prev]\').value = \'1\'; this.form.submit();" value="' . $LANG->getLL('action.previous') . '" />' . ((!isset($this->_closeDocShown)) ? ('
					<input type="hidden" name="closeDoc" value="0" />') : ('')) . '
					<input type="hidden" name="action[prev]" value="" />';
				$this->_closeDocShown = 1;
			}
			if ($next) $content .= '
					<input class="utopia-next-button" type="submit" name="action[next]" value="' . $LANG->getLL('action.next') . '" />';
			if ($content)
				$content = '
				<li>' . $content . '
				</li>';
			return $content;
		}
	}
	
	function getSubmitButton($label, $action, $class, $no_check = false)
	{
		$content = '';
		if ($no_check && isset($this->_tceforms))
		{
			$content .= '
					<input type="button" class="' . $class . ' ' . $action . '" name="action[' . $action . ']_btn" onclick="this.form.elements.namedItem(\'closeDoc\').value = \'1\'; this.form.elements.namedItem(\'action[' . $action . ']\').value = \'1\'; this.form.submit();" value="' . $label . '" />
					<input type="hidden" name="action[' . $action . ']" value="" />';
			if (!isset($this->_closeDocShown))
				$content .= '
					<input type="hidden" name="closeDoc" value="0" />';
			$this->_closeDocShown = 1;
		}
		else
		{
			$content .= '
					<input type="submit" class="' . $class . ' ' . $action . '" name="action[' . $action . ']" value="' . $label . '" />';
		}
		return $content;
	}
	
	/**
	 * Return the JS needed before the form fields.
	 *
	 * @return string HTML code containing the scripts.
	 */
	function getTopJS()
	{
		if ($this->_tceforms)
		{
			return $this->_tceforms->printNeededJSFunctions_top();
		}
		else
		{
		}
	}
	
	/**
	 * Return the JS needed after the form fields.
	 *
	 * @return string HTML code containing the scripts.
	 */
	function getBottomJS()
	{
		if ($this->_tceforms)
		{
			return $this->_tceforms->printNeededJSFunctions();
		}
		else
		{
		}
	}

	function doLoadTableDescr($table)	{
		global $TCA;
		return $TCA[$table]['interface']['always_description'];
	}

	/**
	 * Returns help text DESCRIPTION, if configured for.
	 *
	 * @param	string		The table name
	 * @param	string		The field name
	 * @return	string
	 */
	function helpText($table,$field)	{
		if ($GLOBALS['TCA_DESCR'][$table]['columns'][$field] && ($this->edit_showFieldHelp=='text' || $this->doLoadTableDescr($table)))	{
			$fDat = $GLOBALS['TCA_DESCR'][$table]['columns'][$field];
			return '<p class="helpText">'.
					htmlspecialchars(strip_tags($fDat['description'])).
					'</p>';
		}
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_forms.php"]){
include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_forms.php"]);
}
