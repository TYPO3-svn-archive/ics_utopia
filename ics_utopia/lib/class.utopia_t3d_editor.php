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
 * Manager for the t3d file format for the 'ics_utopia' extension. Wraps some typo3 functions and do the magic concerning the update of theses files.
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */

require_once (t3lib_extMgm::extPath('impexp').'class.tx_impexp.php');
require_once (PATH_t3lib.'class.t3lib_browsetree.php');

class utopia_t3d_editor
{
	// string -> array;
	function loadFile($filename, $all = 0)
	{
		$impexp = t3lib_div::makeInstance('tx_impexp');
		//var_dump(pathinfo($filename));
		$impexp->loadFile($filename, $all);
		return $impexp->dat;
	}
	
	// string, array ->;
	function saveFile($filename, $data)
	{
		$impexp = t3lib_div::makeInstance('tx_impexp');
		$impexp->dat = $data;
		$impexp->compress = false;
		$res = $impexp->compileMemoryToFileContent('t3d');
		t3lib_div::writeFile($filename, $res);
	}

	/**************************
	 *
	 * EXPORT FUNCTIONS
	 *
	 **************************/

	function exportData($inData)    {
			global $TCA, $LANG;

					// BUILDING EXPORT DATA:

					// Processing of InData array values:
			$inData['pagetree']['maxNumber'] = t3lib_div::intInRange($inData['pagetree']['maxNumber'],1,10000,100);
			$inData['listCfg']['maxNumber'] = t3lib_div::intInRange($inData['listCfg']['maxNumber'],1,10000,100);
			$inData['maxFileSize'] = t3lib_div::intInRange($inData['maxFileSize'],1,10000,1000);
			$inData['filename'] = trim(preg_replace('#[^[:alnum:]./_-]*#','',preg_replace('#\.(t3d|xml)$#','',$inData['filename'])));
			if (strlen($inData['filename']))        {
//					$inData['filename'].= $inData['filetype']=='xml' ? '.xml' : '.t3d';
					$inData['filename'].= '.t3d';
			}

					// Set exclude fields in export object:
			if (!is_array($inData['exclude']))      {
					$inData['exclude'] = array();
			}

					// Create export object and configure it:
			$this->export = t3lib_div::makeInstance('tx_impexp');
			$this->export->init(0,'export');
			$this->export->setCharset($LANG->charSet);

			$this->export->maxFileSize = $inData['maxFileSize']*1024;
			$this->export->excludeMap = (array)$inData['exclude'];
			$this->export->softrefCfg = (array)$inData['softrefCfg'];
			$this->export->extensionDependencies = (array)$inData['extension_dep'];
			$this->export->showStaticRelations = $inData['showStaticRelations'];

			$this->export->includeExtFileResources = !$inData['excludeHTMLfileResources'];
#debug($inData);
					// Static tables:
			if (is_array($inData['external_static']['tables']))     {
					$this->export->relStaticTables = $inData['external_static']['tables'];
			}

					// Configure which tables external relations are included for:
			if (is_array($inData['external_ref']['tables']))        {
					$this->export->relOnlyTables = $inData['external_ref']['tables'];
			}
			$this->export->setHeaderBasics();

			if ($inData['creator']['id'])
				$user = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'`username` AS `username`, ' .
					'`' . (($inData['creator']['type'] == 'BE') ? ('realName') : ('name')) . '` AS `name`, ' .
					'`email` AS `email`',
					'`' . (($inData['creator']['type'] == 'BE') ? ('be_users') : ('fe_users')) . '`',
					'`uid` = ' . $inData['creator']['id']
				);
			else
				$user = array(array());

					// Meta data setting:
			$this->export->setMetaData(
					$inData['meta']['title'],
					$inData['meta']['description'],
					$inData['meta']['notes'],
					$user[0]['username'], 
					$user[0]['name'],
					$user[0]['email']
			);
			if ($inData['meta']['thumbnail'])       {
					$tempDir = $this->userTempFolder();
					if ($tempDir)   {
							$thumbnails = t3lib_div::getFilesInDir($tempDir,'png,gif,jpg',1);
							$theThumb = $thumbnails[$inData['meta']['thumbnail']];
							if ($theThumb)  {
									$this->export->addThumbnail($theThumb);
							}
					}
			}


					// Configure which records to export
			if (is_array($inData['record']))        {
					foreach($inData['record'] as $ref)      {
							$rParts = explode(':',$ref);
							$this->export->export_addRecord($rParts[0],t3lib_BEfunc::getRecord($rParts[0],$rParts[1]));
					}
			}

					// Configure which tables to export
			if (is_array($inData['list']))  {
					foreach($inData['list'] as $ref)        {
							$rParts = explode(':',$ref);
							if ($GLOBALS['BE_USER']->check('tables_select',$rParts[0]))     {
									$res = $this->exec_listQueryPid($rParts[0],$rParts[1],t3lib_div::intInRange($inData['listCfg']['maxNumber'],1));
									while($subTrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))   {
											$this->export->export_addRecord($rParts[0],$subTrow);
									}
							}
					}
			}

					// Pagetree
			if (isset($inData['pagetree']['id']))   {
				//var_dump($inData['pagetree']['id']);
				//var_dump($inData['pagetree']['levels']);
					if ($inData['pagetree']['levels']==-1)  {       // Based on click-expandable tree
							$pagetree = t3lib_div::makeInstance('localPageTree');

							$tree = $pagetree->ext_tree($inData['pagetree']['id'],''/*$this->filterPageIds($this->export->excludeMap)*/);
							//var_dump($tree);
							$this->treeHTML = $pagetree->printTree($tree);

							$idH = $pagetree->buffer_idH;
#                               debug($pagetree->buffer_idH);
					} elseif ($inData['pagetree']['levels']==-2)    {       // Only tables on page
							$this->addRecordsForPid($inData['pagetree']['id'],$inData['pagetree']['tables'],$inData['pagetree']['maxNumber']);
					} else {        // Based on depth
									// Drawing tree:
									// If the ID is zero, export root
							//var_dump('blabla');
							if (!$inData['pagetree']['id'] && $GLOBALS['BE_USER']->isAdmin())       {
									$sPage = array(
											'uid' => 0,
											'title' => 'ROOT'
									);
							} else {
									$sPage = t3lib_BEfunc::getRecordWSOL('pages',$inData['pagetree']['id'],'*',''/*' AND '.$this->perms_clause*/);
							}
							//var_dump($sPage);
							if (is_array($sPage))   {
									$pid = $inData['pagetree']['id'];
									$tree = t3lib_div::makeInstance('t3lib_pageTree');
									$tree->init(''/*'AND '.$this->perms_clause/*.$this->filterPageIds($this->export->excludeMap)*/);

									$HTML = t3lib_iconWorks::getIconImage('pages',$sPage,$GLOBALS['BACK_PATH'],'align="top"');
									$tree->tree[] = Array('row'=>$sPage,'HTML'=>$HTML);
									$tree->buffer_idH = array();
									if ($inData['pagetree']['levels']>0)    {
											$tree->getTree($pid,$inData['pagetree']['levels'],'');
									}

									$idH = array();
									$idH[$pid]['uid'] = $pid;
									if (count($tree->buffer_idH))   {
											$idH[$pid]['subrow'] = $tree->buffer_idH;
									}

									$pagetree = t3lib_div::makeInstance('localPageTree');
									$this->treeHTML = $pagetree->printTree($tree->tree);
#debug($idH);
							}
					}
							// In any case we should have a multi-level array, $idH, with the page structure here (and the HTML-code loaded into memory for nice display...)
					if (is_array($idH))     {
							$flatList = $this->export->setPageTree($idH);   // Sets the pagetree and gets a 1-dim array in return with the pages (in correct submission order BTW...)
							reset($flatList);
							while(list($k)=each($flatList)) {
									$this->export->export_addRecord('pages',t3lib_BEfunc::getRecord('pages',$k));
									$this->addRecordsForPid($k,$inData['pagetree']['tables'],$inData['pagetree']['maxNumber']);
							}
					}
			}

					// After adding ALL records we set relations:
#               debug($this->export->relOnlyTables);
#               if (count($this->export->relOnlyTables))        {
					for($a=0;$a<10;$a++)    {
							$addR = $this->export->export_addDBRelations($a);
							if (!count($addR)) break;
					}
#               }

		$config = t3lib_div::makeInstance('utopia_config');
		$newPath = $config->getConfig('storage.newroot');
		$siteName = uniqid('site');
		if (isset($inData['siteTitle'])) {
			$siteName = $inData['siteTitle'];
			if ($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'])
				$siteName = iconv($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'], 'ASCII//TRANSLIT', $siteName);
			$siteName = preg_replace('/[^a-z0-9_-]/i', '_', $siteName);
		}
		if (strpos($newPath, '###TITLE###') === false)
		{
			if (($newPath != '') && (substr($newPath, -1, 1) != '/'))
				$newPath .= '/';
			$newPath .= $siteName . '/';
		}
		else
		{
			$newPath = str_replace('###TITLE###', preg_replace('/[^A-Z0-9_]/i', '_', $siteName), $newPath);
			if (substr($newPath, -1, 1) != '/')
				$newPath .= '/';
		}
					// Finally files are added:
			$this->export->export_addFilesFromRelations($newPath, $config->getConfig('storage.oldroot'));  // MUST be after the DBrelations are set so that files from ALL added records are included!
#debug($this->export->dat['header']);
//var_dump(md5(serialize($this->export->dat['header']['files'])));
			return $this->export->dat;
	}


	function addRecordsForPid($k, $tables, $maxNumber)      {
			global $TCA;

			if (is_array($tables))  {
					reset($TCA);
					while(list($table)=each($TCA))  {
							if ($table!='pages' && (in_array($table,$tables) || in_array('_ALL',$tables)))  {
									if ($GLOBALS['BE_USER']->check('tables_select',$table) && !$TCA[$table]['ctrl']['is_static'])   {
											$res = $this->exec_listQueryPid($table,$k,t3lib_div::intInRange($maxNumber,1));
											while($subTrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))   {
													$this->export->export_addRecord($table,$subTrow);
											}
									}
							}
					}
			}
	}

	function exec_listQueryPid($table,$pid,$limit)  {
			global $TCA, $LANG;

			$orderBy = $TCA[$table]['ctrl']['sortby'] ? 'ORDER BY '.$TCA[$table]['ctrl']['sortby'] : $TCA[$table]['ctrl']['default_sortby'];
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'*',
							$table,
							'pid='.intval($pid).
									t3lib_BEfunc::deleteClause($table).
									t3lib_BEfunc::versioningPlaceholderClause($table),
							'',
							$GLOBALS['TYPO3_DB']->stripOrderBy($orderBy),
							$limit
					);

					// Warning about hitting limit:
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == $limit) {
					$this->content.= $this->doc->section($LANG->getLL('execlistqu_maxNumberLimit'),sprintf($LANG->getLL('makeconfig_anSqlQueryReturned',1),$limit),0,1, 2);
			}

			return $res;
	}

	/**
	 * Import part of module
	 *
	 * @param	array		Content of POST VAR tx_impexp[]..
	 * @return	void		Setting content in $this->content
	 */
	function importData($inData, &$data)	{
		global $TCA,$LANG,$BE_USER;

		$import = t3lib_div::makeInstance('tx_impexp');
		$import->init(0,'import');
		$import->update = $inData['do_update'];
		$import->import_mode = $inData['import_mode'];
		$import->enableLogging = $inData['enableLogging'];
		$import->global_ignore_pid = $inData['global_ignore_pid'];
		$import->force_all_UIDS = $inData['force_all_UIDS'];
		$import->showDiff = !$inData['notShowDiff'];
		$import->allowPHPScripts = $inData['allowPHPScripts'];
		$import->softrefInputValues = $inData['softrefInputValues'];

			// Perform import or preview depending:
		$overviewContent = '';
		$inFile = t3lib_div::getFileAbsFileName($inData['file']);
		if ($inFile && @is_file($inFile))	{
			$trow = array();
			if ($import->loadFile($inFile,1))	{
					if ($inData['import_file'])	{
					$import->importData($inData['pid']);
					t3lib_BEfunc::getSetUpdateSignal('updatePageTree');
				}
				$overviewContent = $import->displayContentOverview();
			}
		}
		$data = $import->dat;
		$data['import_mapId'] = $import->import_mapId;
			// Print overview:
		return $overviewContent;
	}
}

/**
 * Extension of the page tree class. Used to get the tree of pages to export.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_impexp
 */
class localPageTree extends t3lib_browseTree {

	/**
	 * Initialization
	 *
	 * @return	void
	 */
	function localPageTree() {
		$this->init();
	}

	/**
	 * Wrapping title from page tree.
	 *
	 * @param	string		Title to wrap
	 * @param	mixed		(See parent class)
	 * @return	string		Wrapped title
	 */
	function wrapTitle($title,$v)	{
		$title = (!strcmp(trim($title),'')) ? '<em>['.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.no_title',1).']</em>' : htmlspecialchars($title);
		return $title;
	}

	/**
	 * Wrapping Plus/Minus icon
	 *
	 * @param	string		Icon HTML
	 * @param	mixed		(See parent class)
	 * @param	mixed		(See parent class)
	 * @return	string		Icon HTML
	 */
	function PM_ATagWrap($icon,$cmd,$bMark='')	{
		return $icon;
	}

	/**
	 * Wrapping Icon
	 *
	 * @param	string		Icon HTML
	 * @param	array		Record row (page)
	 * @return	string		Icon HTML
	 */
	function wrapIcon($icon,$row)	{
		return $icon;
	}

	/**
	 * Select permissions
	 *
	 * @return	string		SQL where clause
	 */
	function permsC()	{
		return $this->BE_USER->getPagePermsClause(1);
	}

	/**
	 * Tree rendering
	 *
	 * @param	integer		PID value
	 * @param	string		Additional where clause
	 * @return	array		Array of tree elements
	 */
	function ext_tree($pid, $clause='')	{

			// Initialize:
		$this->init(' AND '.$this->permsC().$clause);

			// Get stored tree structure:
		$this->stored = unserialize($this->BE_USER->uc['browseTrees']['browsePages']);

			// PM action:
		$PM = t3lib_div::intExplode('_',t3lib_div::_GP('PM'));

			// traverse mounts:
		$titleLen = intval($this->BE_USER->uc['titleLen']);
		$treeArr = array();

		$idx = 0;

			// Set first:
		$this->bank = $idx;
		$isOpen = $this->stored[$idx][$pid] || $this->expandFirst;

		$curIds = $this->ids;	// save ids
		$this->reset();
		$this->ids = $curIds;

			// Set PM icon:
		$cmd = $this->bank.'_'.($isOpen?'0_':'1_').$pid;
		$icon = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/'.($isOpen?'minus':'plus').'only.gif','width="18" height="16"').' align="top" alt="" />';
		$firstHtml = $this->PM_ATagWrap($icon,$cmd);

		if ($pid>0)	{
			$rootRec = t3lib_befunc::getRecordWSOL('pages',$pid);
			$firstHtml.= $this->wrapIcon(t3lib_iconWorks::getIconImage('pages',$rootRec,$this->backPath,'align="top"'),$rootRec);
		} else {
			$rootRec = array(
				'title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
				'uid' => 0
			);
			$firstHtml.= $this->wrapIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/i/_icon_website.gif','width="18" height="16"').' align="top" alt="" />',$rootRec);
		}
		$this->tree[] = array('HTML'=>$firstHtml, 'row'=>$rootRec);
		if ($isOpen)	{
				// Set depth:
			$depthD = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/blank.gif','width="18" height="16"').' align="top" alt="" />';
			if ($this->addSelfId)	$this->ids[] = $pid;
			$this->getTree($pid,999,$depthD);

			$idH = array();
			$idH[$pid]['uid'] = $pid;
			if (count($this->buffer_idH))	$idH[$pid]['subrow'] = $this->buffer_idH;
			$this->buffer_idH = $idH;

		}

			// Add tree:
		$treeArr = array_merge($treeArr,$this->tree);

		return $treeArr;
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_t3d_editor.php"]){
include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_t3d_editor.php"]);
}
