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
 * Extension to the system class tx_impexp to support the directory change.
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */

/**
 * Extends the functionalities provided by tx_impexp.
 * Adds the support of changing the destination directory for fileadmin files while creating the export.
 *
 * USE:
 * Not to be called by user code.
 *
 * @author In Cité Solution <technique@incitesolution.fr>
 * @package UTOPIA
 */
class ux_tx_impexp extends tx_impexp
{
	function computeNewPath($oldroot, $newroot, $path)
	{
		if (!t3lib_div::isFirstPartOfStr($path, 'fileadmin/'))
			return $path;
		if (empty($oldroot))
			return 'fileadmin/' . $newroot . substr($path, 10);
		if (substr($oldroot, -1, 1) == '/')
			$oldroot = substr($oldroot, 0, -1);
		$oldroot = explode('/', $oldroot);
		$path = substr($path, 10);
		$oldpath = $path;
		$path = explode('/', $path);
		while ((!empty($oldroot)) && (count($path) > 1) &&
			((empty($oldroot[0])) || ($oldroot[0] == $path[0])))
		{
			array_shift($oldroot);
			array_shift($path);
		}
		if (empty($oldroot))
		{
			return 'fileadmin/' .$newroot . implode('/', $path);
		}
		return 'fileadmin/' . $newroot . $oldpath;
	}

	/**
	 * This adds all files in relations.
	 * Call this method AFTER adding all records including relations.
	 *
	 * @param string Fileadmin subfolder where to move fileadmin's exported files. (optional)
	 * @return	void
	 * @see export_addDBRelations()
	 */
	function export_addFilesFromRelations($fileadminSubpath = '', $fileadminRemovePath = '')	{
//echo '<pre>';
			// Traverse all "rels" registered for "records"
		if (is_array($this->dat['records']))	{
			reset($this->dat['records']);
			while(list($k)=each($this->dat['records']))	{
				$recInfo = explode(':', $k);
				if (is_array($this->dat['records'][$k]['rels']))	{
					reset($this->dat['records'][$k]['rels']);
					while(list($fieldname,$vR)=each($this->dat['records'][$k]['rels']))	{

							// For all file type relations:
						if ($vR['type']=='file')	{
							foreach($vR['newValueFiles'] as $key => $fI)	{
								if (isset($fI['relFileName']) && (strpos($fI['relFileName'], 'fileadmin/') !== false) && (strpos($fI['relFileName'], 'fileadmin/' . $fileadminSubpath) === false))
									$this->dat['records'][$k]['rels'][$fieldname]['newValueFiles'][$key]['relFileName'] = $fI['relFileName'] = $this->computeNewPath($fileadminRemovePath, $fileadminSubpath, $fI['relFileName']);
								$this->export_addFile($fI, $k, $fieldname);
									// Remove the absolute reference to the file so it doesn't expose absolute paths from source server:
								unset($this->dat['records'][$k]['rels'][$fieldname]['newValueFiles'][$key]['ID_absFile']);
							}
						}

							// For all flex type relations:
						if ($vR['type']=='flex')	{
							if (is_array($vR['flexFormRels']['file']))	{
								foreach($vR['flexFormRels']['file'] as $key => $subList)	{
									foreach($subList as $subKey => $fI)	{
										if (isset($fI['relFileName']) && (strpos($fI['relFileName'], 'fileadmin/') !== false) && (strpos($fI['relFileName'], 'fileadmin/' . $fileadminSubpath) === false))
											$this->dat['records'][$k]['rels'][$fieldname]['flexFormRels']['file'][$key][$subKey]['relFileName'] = $fI['relFileName'] = $this->computeNewPath($fileadminRemovePath, $fileadminSubpath, $fI['relFileName']);
										$this->export_addFile($fI, $k, $fieldname);
											// Remove the absolute reference to the file so it doesn't expose absolute paths from source server:
										unset($this->dat['records'][$k]['rels'][$fieldname]['flexFormRels']['file'][$key][$subKey]['ID_absFile']);
									}
								}
							}

								// DB oriented soft references in flex form fields:
							if (is_array($vR['flexFormRels']['softrefs']))	{
								foreach($vR['flexFormRels']['softrefs'] as $key => $subList)	{
									foreach($subList['keys'] as $spKey => $elements)	{
										foreach($elements as $subKey => $el)	{
											$headerIndex = implode(':', array($fieldname, $spKey, $subKey));
											if ($el['subst']['type'] === 'file' && $this->includeSoftref($el['subst']['tokenID']))	{

													// Create abs path and ID for file:
												$ID_absFile = t3lib_div::getFileAbsFileName(PATH_site.$el['subst']['relFileName']);
												$ID = md5($ID_absFile);

												if ($ID_absFile)	{
													if (!$this->dat['files'][$ID])	{
														$fI = array(
															'filename' => basename($ID_absFile),
															'ID_absFile' => $ID_absFile,
															'ID' => $ID,
															'relFileName' => $el['subst']['relFileName']
														);
														if (isset($fI['relFileName']) && strpos($fI['relFileName'], 'fileadmin/') !== false)
															$fI['relFileName'] = $this->computeNewPath($fileadminRemovePath, $fileadminSubpath, $fI['relFileName']);
														$this->export_addFile($fI, '_SOFTREF_');
													}
													$this->dat['records'][$k]['rels'][$fieldname]['flexFormRels']['softrefs'][$key]['keys'][$spKey][$subKey]['file_ID'] = $ID;
												}
											}
											if (($el['subst']['type'] === 'file') && isset($this->dat['header']['records'][$recInfo[0]][$recInfo[1]]['softrefs'][$headerIndex]['subst']['relFileName']) && (strpos($this->dat['header']['records'][$recInfo[0]][$recInfo[1]]['softrefs'][$headerIndex]['subst']['relFileName'], 'fileadmin/') !== false) && (strpos($el['subst']['relFileName'], 'fileadmin/' . $fileadminSubpath) === false))
												$this->dat['header']['records'][$recInfo[0]][$recInfo[1]]['softrefs'][$headerIndex]['subst']['relFileName'] = $this->computeNewPath($fileadminRemovePath, $fileadminSubpath, $this->dat['header']['records'][$recInfo[0]][$recInfo[1]]['softrefs'][$headerIndex]['subst']['relFileName']);
											if (($el['subst']['type'] === 'file') && isset($el['subst']['relFileName']) && (strpos($el['subst']['relFileName'], 'fileadmin/') !== false) && (strpos($el['subst']['relFileName'], 'fileadmin/' . $fileadminSubpath) === false))
												$this->dat['records'][$k]['rels'][$fieldname]['flexFormRels']['softrefs'][$key]['keys'][$spKey][$subKey]['subst']['relFileName'] = $this->computeNewPath($fileadminRemovePath, $fileadminSubpath, $el['subst']['relFileName']);
											if (($el['subst']['type'] === 'file') && isset($el['subst']['tokenValue']) && (strpos($el['subst']['tokenValue'], 'fileadmin/') !== false) && (strpos($el['subst']['tokenValue'], 'fileadmin/' . $fileadminSubpath) === false))
												$this->dat['records'][$k]['rels'][$fieldname]['flexFormRels']['softrefs'][$key]['keys'][$spKey][$subKey]['subst']['tokenValue'] = $this->computeNewPath($fileadminRemovePath, $fileadminSubpath, $el['subst']['tokenValue']);
										}
									}
								}
							}
						}

							// In any case, if there are soft refs:
						if (is_array($vR['softrefs']['keys']))	{
							foreach($vR['softrefs']['keys'] as $spKey => $elements)	{
								foreach($elements as $subKey => $el)	{
									$headerIndex = implode(':', array($fieldname, $spKey, $subKey));
									if ($el['subst']['type'] === 'file' && $this->includeSoftref($el['subst']['tokenID']))	{

											// Create abs path and ID for file:
										$ID_absFile = t3lib_div::getFileAbsFileName(PATH_site.$el['subst']['relFileName']);
										$ID = md5($ID_absFile);

										if ($ID_absFile)	{
											if (!$this->dat['files'][$ID])	{
												$fI = array(
													'filename' => basename($ID_absFile),
													'ID_absFile' => $ID_absFile,
													'ID' => $ID,
													'relFileName' => $el['subst']['relFileName']
												);
												if (isset($fI['relFileName']) && strpos($fI['relFileName'], 'fileadmin/') !== false)
													$fI['relFileName'] = $this->computeNewPath($fileadminRemovePath, $fileadminSubpath, $fI['relFileName']);
												$this->export_addFile($fI, '_SOFTREF_');
											}
											$this->dat['records'][$k]['rels'][$fieldname]['softrefs']['keys'][$spKey][$subKey]['file_ID'] = $ID;
										}
									}
									if (($el['subst']['type'] === 'file') && isset($this->dat['header']['records'][$recInfo[0]][$recInfo[1]]['softrefs'][$headerIndex]['subst']['relFileName']) && (strpos($this->dat['header']['records'][$recInfo[0]][$recInfo[1]]['softrefs'][$headerIndex]['subst']['relFileName'], 'fileadmin/') !== false) && (strpos($el['subst']['relFileName'], 'fileadmin/' . $fileadminSubpath) === false))
										$this->dat['header']['records'][$recInfo[0]][$recInfo[1]]['softrefs'][$headerIndex]['subst']['relFileName'] = $this->computeNewPath($fileadminRemovePath, $fileadminSubpath, $this->dat['header']['records'][$recInfo[0]][$recInfo[1]]['softrefs'][$headerIndex]['subst']['relFileName']);
									if (($el['subst']['type'] === 'file') && isset($el['subst']['relFileName']) && (strpos($el['subst']['relFileName'], 'fileadmin/') !== false) && (strpos($el['subst']['relFileName'], 'fileadmin/' . $fileadminSubpath) === false))
										$this->dat['records'][$k]['rels'][$fieldname]['softrefs']['keys'][$spKey][$subKey]['subst']['relFileName'] = $this->computeNewPath($fileadminRemovePath, $fileadminSubpath, $el['subst']['relFileName']);
									if (($el['subst']['type'] === 'file') && isset($el['subst']['tokenValue']) && (strpos($el['subst']['tokenValue'], 'fileadmin/') !== false) && (strpos($el['subst']['tokenValue'], 'fileadmin/' . $fileadminSubpath) === false))
										$this->dat['records'][$k]['rels'][$fieldname]['softrefs']['keys'][$spKey][$subKey]['subst']['tokenValue'] = $this->computeNewPath($fileadminRemovePath, $fileadminSubpath, $el['subst']['tokenValue']);
								}
							}
						}
					}
				}
			}
		} else $this->error('There were no records available.');
//echo '</pre>';
	}

	/**
	 * Adds a files content to the export memory
	 *
	 * @param	array		File information with three keys: "filename" = filename without path, "ID_absFile" = absolute filepath to the file (including the filename), "ID" = md5 hash of "ID_absFile". "relFileName" is optional for files attached to records, but mandatory for soft referenced files (since the relFileName determines where such a file should be stored!)
	 * @param	string		If the file is related to a record, this is the id on the form [table]:[id]. Information purposes only.
	 * @param	string		If the file is related to a record, this is the field name it was related to. Information purposes only.
	 * @return	void
	 */
	function export_addFile($fI, $recordRef='', $fieldname='')	{
		if (@is_file($fI['ID_absFile']))	{
			if (filesize($fI['ID_absFile']) < $this->maxFileSize)	{
				$fileRec = array();
				$fileRec['filesize'] = filesize($fI['ID_absFile']);
				$fileRec['filename'] = basename($fI['ID_absFile']);
				$fileRec['filemtime'] = filemtime($fI['ID_absFile']);
				if ($recordRef)	{
					$fileRec['record_ref'] = $recordRef.'/'.$fieldname;
				}
				if ($fI['relFileName'])	{
					$fileRec['relFileName'] = $fI['relFileName'];
				}

					// Setting this data in the header
				$this->dat['header']['files'][$fI['ID']] = $fileRec;

					// ... and for the recordlisting, why not let us know WHICH relations there was...
				if ($recordRef && $recordRef!=='_SOFTREF_')	{
					$refParts = explode(':',$recordRef,2);
					if (!is_array($this->dat['header']['records'][$refParts[0]][$refParts[1]]['filerefs']))	{
						$this->dat['header']['records'][$refParts[0]][$refParts[1]]['filerefs'] = array();
					}
					$this->dat['header']['records'][$refParts[0]][$refParts[1]]['filerefs'][] = $fI['ID'];
				}

					// ... and finally add the heavy stuff:
				$fileRec['content'] = t3lib_div::getUrl($fI['ID_absFile']);
				$fileRec['content_md5'] = md5($fileRec['content']);
				$this->dat['files'][$fI['ID']] = $fileRec;


					// For soft references, do further processing:
				if ($recordRef === '_SOFTREF_')	{

						// RTE files?
					if ($RTEoriginal = $this->getRTEoriginalFilename(basename($fI['ID_absFile'])))	{
						$RTEoriginal_absPath = dirname($fI['ID_absFile']).'/'.$RTEoriginal;
						if (@is_file($RTEoriginal_absPath))	{

							$RTEoriginal_ID = md5($RTEoriginal_absPath);

							$fileRec = array();
							$fileRec['filesize'] = filesize($RTEoriginal_absPath);
							$fileRec['filename'] = basename($RTEoriginal_absPath);
							$fileRec['filemtime'] = filemtime($RTEoriginal_absPath);
							$fileRec['record_ref'] = '_RTE_COPY_ID:'.$fI['ID'];
							$this->dat['header']['files'][$fI['ID']]['RTE_ORIG_ID'] = $RTEoriginal_ID;

								// Setting this data in the header
							$this->dat['header']['files'][$RTEoriginal_ID] = $fileRec;

								// ... and finally add the heavy stuff:
							$fileRec['content'] = t3lib_div::getUrl($RTEoriginal_absPath);
							$fileRec['content_md5'] = md5($fileRec['content']);
							$this->dat['files'][$RTEoriginal_ID] = $fileRec;
						} else {
							$this->error('RTE original file "'.substr($RTEoriginal_absPath,strlen(PATH_site)).'" was not found!');
						}
					}

						// Files with external media?
						// This is only done with files grabbed by a softreference parser since it is deemed improbable that hard-referenced files should undergo this treatment.
					$this->export_addFile_external($fI, $fileRec['content']);
				}

			} else  $this->error($fI['ID_absFile'].' was larger ('.t3lib_div::formatSize(filesize($fI['ID_absFile'])).') than the maxFileSize ('.t3lib_div::formatSize($this->maxFileSize).')! Skipping.');
		} else $this->error($fI['ID_absFile'].' was not a file! Skipping.');
	}

	function is_file($filePath)
	{
		if (@is_file($filePath))
			return true;
		if (strpos($filePath, '#') !== false)
			$filePath = substr($filePath, 0, strpos($filePath, '#'));
		if (@is_file($filePath))
			return true;
		if (strpos($filePath, '?') !== false)
			$filePath = substr($filePath, 0, strpos($filePath, '?'));
		return @is_file($filePath);
	}
	
	function export_addFile_external($fI, $content)
	{
		$html_fI = pathinfo(basename($fI['ID_absFile']));
		if ($this->includeExtFileResources && t3lib_div::inList($this->extFileResourceExtensions,strtolower($html_fI['extension'])))	{
			$uniquePrefix = '###'.md5(time()).'###';

			$prefixedMedias = $this->export_addFile_getMedias($uniquePrefix, $html_fI['extension'], $content);

			$htmlResourceCaptured = FALSE;
			foreach($prefixedMedias as $k => $v)	{
				if ($k%2)	{
					$EXTres_absPath = t3lib_div::resolveBackPath(dirname($fI['ID_absFile']).'/'.$v);
					$EXTres_absPath = t3lib_div::getFileAbsFileName($EXTres_absPath);
					if ($EXTres_absPath && t3lib_div::isFirstPartOfStr($EXTres_absPath,PATH_site.$this->fileadminFolderName.'/') && $this->is_file($EXTres_absPath))	{

						$htmlResourceCaptured = TRUE;
						$EXTres_ID = md5($EXTres_absPath);
						$this->dat['header']['files'][$fI['ID']]['EXT_RES_ID'][] = $EXTres_ID;
						$prefixedMedias[$k] = '{EXT_RES_ID:'.$EXTres_ID.'}';

							// Add file to memory if it is not set already:
						if (!isset($this->dat['header']['files'][$EXTres_ID]))		{
							$fileRec = array();
							$fileRec['filesize'] = filesize($EXTres_absPath);
							$fileRec['filename'] = basename($EXTres_absPath);
							$fileRec['filemtime'] = filemtime($EXTres_absPath);
							$fileRec['record_ref'] = '_EXT_PARENT_:'.$fI['ID'];

							$fileRec['parentRelFileName'] = $v;		// Media relative to the HTML file.

								// Setting this data in the header
							$this->dat['header']['files'][$EXTres_ID] = $fileRec;

								// ... and finally add the heavy stuff:
							$fileRec['content'] = t3lib_div::getUrl($EXTres_absPath);
							$fileRec['content_md5'] = md5($fileRec['content']);
							$this->dat['files'][$EXTres_ID] = $fileRec;
							$this->export_addFile_external(array(
									'ID_absFile' => $EXTres_absPath,
									'ID' => $EXTres_ID
								), $fileRec['content']);
						}
					}
				}
			}

			if ($htmlResourceCaptured)	{
				$this->dat['files'][$fI['ID']]['tokenizedContent'] = implode('', $prefixedMedias);
			}
		}
	}

	function export_addFile_getMedias($uniquePrefix, $ext, $content)
	{
		if (strtolower($ext)==='css')	{
			return explode($uniquePrefix, eregi_replace('(url[[:space:]]*\([[:space:]]*["\']?)([^"\')]*)(["\']?[[:space:]]*\))', '\1'.$uniquePrefix.'\2'.$uniquePrefix.'\3', $content));
		} else {	// html, htm:
			$htmlParser = t3lib_div::makeInstance('t3lib_parsehtml');
			return explode($uniquePrefix, $htmlParser->prefixResourcePath($uniquePrefix,$content,array(),$uniquePrefix));
		}
		return array($content);
	}

	/**
	 * Add file relation entries for a record's rels-array
	 *
	 * @param	array		Array of file IDs
	 * @param	array		Output lines array (is passed by reference and modified)
	 * @param	string		Pre-HTML code
	 * @param	string		Alternative HTML color class to use.
	 * @param	string		Token ID if this is a softreference (in which case it only makes sense with a single element in the $rels array!)
	 * @return	void
	 * @access private
	 * @see singleRecordLines()
	 */
	function addFiles($rels,&$lines,$preCode,$htmlColorClass='',$tokenID='')	{

		foreach($rels as $ID)	{

				// Process file:
			$pInfo = array();
			$fI = $this->dat['header']['files'][$ID];
			if (!is_array($fI))	{
				if (!$tokenID || $this->includeSoftref($tokenID))	{
					$pInfo['msg'] = 'MISSING FILE: '.$ID;
					$this->error('MISSING FILE: '.$ID,1);
				} else {
					return;
				}
			}
			$pInfo['preCode'] = $preCode.'&nbsp;&nbsp;&nbsp;&nbsp;<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/rel_file.gif','width="13" height="12"').' title="' . $ID . '" align="top" alt="" />';
			$pInfo['title'] = htmlspecialchars($fI['filename']) .  ' (' . dirname($fI['parentRelFileName'] . $fI['relFileName']) . ')';
			$pInfo['ref'] = 'FILE';
			$pInfo['size'] = $fI['filesize'];
			$pInfo['class'] = $htmlColorClass ? $htmlColorClass : 'bgColor3';
			$pInfo['type'] = 'file';

				// If import mode and there is a non-RTE softreference, check the destination directory:
			if ($this->mode==='import' && $tokenID && !$fI['RTE_ORIG_ID'])	{
				if (isset($fI['parentRelFileName']))	{
					$pInfo['msg'] = 'Seems like this file is already referenced from within an HTML/CSS file. That takes precedence. ';
				} else {
					$testDirPrefix = dirname($fI['relFileName']).'/';
					$testDirPrefix2 = $this->verifyFolderAccess($testDirPrefix);

					if (!$testDirPrefix2)	{
						$pInfo['msg'] = 'ERROR: There are no available filemounts to write file in! ';
					} elseif (strcmp($testDirPrefix,$testDirPrefix2))	{
						$pInfo['msg'] = 'File will be attempted written to "'.$testDirPrefix2.'". ';
					}
				}


					// Check if file exists:
				if (@file_exists(PATH_site.$fI['relFileName']))	{
					if ($this->update)	{
						$pInfo['updatePath'].= 'File exists.';
					} else {
						$pInfo['msg'].= 'File already exists! ';
					}
				}

					// Check extension:
				$fileProcObj = &$this->getFileProcObj();
				if ($fileProcObj->actionPerms['newFile'])	{
					$testFI = t3lib_div::split_fileref(PATH_site.$fI['relFileName']);
					if (!$this->allowPHPScripts && !$fileProcObj->checkIfAllowed($testFI['fileext'], $testFI['path'], $testFI['file']))	{
						$pInfo['msg'].= 'File extension was not allowed!';
					}
				} else $pInfo['msg'] = 'You user profile does not allow you to create files on the server!';
			}

			$pInfo['showDiffContent'] = substr($this->fileIDMap[$ID],strlen(PATH_site));

			$lines[] = $pInfo;
			unset($this->remainHeader['files'][$ID]);

				// RTE originals:
			if ($fI['RTE_ORIG_ID'])	{
				$ID = $fI['RTE_ORIG_ID'];
				$pInfo = array();
				$fI = $this->dat['header']['files'][$ID];
				if (!is_array($fI))	{
					$pInfo['msg'] = 'MISSING RTE original FILE: '.$ID;
					$this->error('MISSING RTE original FILE: '.$ID,1);
				}

				$pInfo['showDiffContent'] = substr($this->fileIDMap[$ID],strlen(PATH_site));

				$pInfo['preCode'] = $preCode.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/rel_file.gif','width="13" height="12"').' align="top" alt="" />';
				$pInfo['title'] = htmlspecialchars($fI['filename']).' <em>(Original)</em>';
				$pInfo['ref'] = 'FILE';
				$pInfo['size'] = $fI['filesize'];
				$pInfo['class'] = $htmlColorClass ? $htmlColorClass : 'bgColor3';
				$pInfo['type'] = 'file';
				$lines[] = $pInfo;
				unset($this->remainHeader['files'][$ID]);
			}

				// External resources:
			if (is_array($fI['EXT_RES_ID']))	{
				/*foreach($fI['EXT_RES_ID'] as $ID)	{
					$pInfo = array();
					$fI = $this->dat['header']['files'][$ID];
					if (!is_array($fI))	{
						$pInfo['msg'] = 'MISSING External Resource FILE: '.$ID;
						$this->error('MISSING External Resource FILE: '.$ID,1);
					} else {
						$pInfo['updatePath'] = $fI['parentRelFileName'];
					}

					$pInfo['showDiffContent'] = substr($this->fileIDMap[$ID],strlen(PATH_site));

					$pInfo['preCode'] = $preCode.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/rel_file.gif','width="13" height="12"').' align="top" alt="" />';
					$pInfo['title'] = htmlspecialchars($fI['filename']).' <em>(Resource)</em>';
					$pInfo['ref'] = 'FILE';
					$pInfo['size'] = $fI['filesize'];
					$pInfo['class'] = $htmlColorClass ? $htmlColorClass : 'bgColor3';
					$pInfo['type'] = 'file';
					$lines[] = $pInfo;
					unset($this->remainHeader['files'][$ID]);
				}*/
				$this->addFiles_external($fI['EXT_RES_ID'], $lines, $preCode, $htmlColorClass);
			}
		}
	}
	
	var $filesLevels = array();
	var $level = 0;
	
	function addFiles_external($extResIDs, & $lines,$preCode,$htmlColorClass='')
	{
		$curlevel = $this->level++;
		$this->filesLevels[$curlevel] = $extResIDs;
		foreach($extResIDs as $ID)	{
			$pInfo = array();
			$fI = $this->dat['header']['files'][$ID];
			if (!is_array($fI))	{
				$pInfo['msg'] = 'MISSING External Resource FILE: '.$ID;
				$this->error('MISSING External Resource FILE: '.$ID,1);
			} else {
				$pInfo['updatePath'] = $fI['parentRelFileName'];
			}

			$pInfo['showDiffContent'] = substr($this->fileIDMap[$ID],strlen(PATH_site));

			$pInfo['preCode'] = $preCode.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/rel_file.gif','width="13" height="12"').' title="' . $ID . '" align="top" alt="" />';
			$pInfo['title'] = htmlspecialchars($fI['filename']).' <em>(Resource)</em> (' . dirname($fI['parentRelFileName'] . $fI['relFileName']) . ')';
			$pInfo['ref'] = 'FILE';
			$pInfo['size'] = $fI['filesize'];
			$pInfo['class'] = $htmlColorClass ? $htmlColorClass : 'bgColor3';
			$pInfo['type'] = 'file';
			$lines[] = $pInfo;
			unset($this->remainHeader['files'][$ID]);
			if (is_array($fI['EXT_RES_ID']))
			{
				$found = false;
				for ($i = 0; $i < $curlevel; $i++)
					$found = $found || in_array($ID, $this->filesLevels[$i]);
				if (!$found)
					$this->addFiles_external($fI['EXT_RES_ID'], $lines, $preCode . '&nbsp;&nbsp;&nbsp;&nbsp;', $htmlColorClass);
			}
		}
		$this->level--;
		unset($this->filesLevels[$curlevel]);
	}	

	/**
	 * Create file in directory and return the new (unique) filename
	 *
	 * @param	string		Directory prefix, relative, with trailing slash
	 * @param	string		Filename (without path)
	 * @param	string		File ID from import memory
	 * @param	string		Table for which the processing occurs
	 * @param	string		UID of record from table
	 * @return	string		New relative filename, if any
	 */
	function processSoftReferences_saveFile_createRelFile($origDirPrefix, $fileName, $fileID, $table, $uid)	{

			// If the fileID map contains an entry for this fileID then just return the relative filename of that entry; we don't want to write another unique filename for this one!
		if ($this->fileIDMap[$fileID])	{
			return substr($this->fileIDMap[$fileID],strlen(PATH_site));
		}

			// Verify FileMount access to dir-prefix. Returns the best alternative relative path if any
		$dirPrefix = $this->verifyFolderAccess($origDirPrefix);

		if ($dirPrefix && (!$this->update || $origDirPrefix===$dirPrefix) && $this->checkOrCreateDir($dirPrefix))	{
			$fileHeaderInfo = $this->dat['header']['files'][$fileID];
			$updMode = $this->update && $this->import_mapId[$table][$uid]===$uid && $this->import_mode[$table.':'.$uid]!=='as_new';
				// Create new name for file:
			if ($updMode)	{	// Must have same ID in map array (just for security, is not really needed) and NOT be set "as_new".
				$newName = PATH_site.$dirPrefix.$fileName;
			} else {
					// Create unique filename:
				$fileProcObj = &$this->getFileProcObj();
				$newName = $fileProcObj->getUniqueName($fileName, PATH_site.$dirPrefix);
			}
#debug($newName,'$newName');

				// Write main file:
			if ($this->writeFileVerify($newName, $fileID))	{

					// If the resource was an HTML/CSS file with resources attached, we will write those as well!
				if (is_array($fileHeaderInfo['EXT_RES_ID']))	{
#debug($fileHeaderInfo['EXT_RES_ID']);
					/*$tokenizedContent = $this->dat['files'][$fileID]['tokenizedContent'];
					$tokenSubstituted = FALSE;

					$fileProcObj = &$this->getFileProcObj();

					if ($updMode)	{
						foreach($fileHeaderInfo['EXT_RES_ID'] as $res_fileID)	{
							if ($this->dat['files'][$res_fileID]['filename'])	{

									// Resolve original filename:
								$relResourceFileName = $this->dat['files'][$res_fileID]['parentRelFileName'];
								$absResourceFileName = t3lib_div::resolveBackPath(PATH_site.$origDirPrefix.$relResourceFileName);
								$absResourceFileName = t3lib_div::getFileAbsFileName($absResourceFileName);
								if ($absResourceFileName && t3lib_div::isFirstPartOfStr($absResourceFileName,PATH_site.$this->fileadminFolderName.'/'))	{
									$destDir = substr(dirname($absResourceFileName).'/',strlen(PATH_site));
									if ($this->verifyFolderAccess($destDir, TRUE) && $this->checkOrCreateDir($destDir))	{
										$this->writeFileVerify($absResourceFileName, $res_fileID);
									} else $this->error('ERROR: Could not create file in directory "'.$destDir.'"');
								} else $this->error('ERROR: Could not resolve path for "'.$relResourceFileName.'"');

								$tokenizedContent = str_replace('{EXT_RES_ID:'.$res_fileID.'}', $relResourceFileName, $tokenizedContent);
								$tokenSubstituted = TRUE;
							}
						}
					} else {
							// Create the resouces directory name (filename without extension, suffixed "_FILES")
						$resourceDir = dirname($newName).'/'.ereg_replace('\.[^.]*$','',basename($newName)).'_FILES';
						if (t3lib_div::mkdir($resourceDir))	{
							foreach($fileHeaderInfo['EXT_RES_ID'] as $res_fileID)	{
								if ($this->dat['files'][$res_fileID]['filename'])	{
									$absResourceFileName = $fileProcObj->getUniqueName($this->dat['files'][$res_fileID]['filename'], $resourceDir);
									$relResourceFileName = substr($absResourceFileName, strlen(dirname($resourceDir))+1);
									$this->writeFileVerify($absResourceFileName, $res_fileID);

									$tokenizedContent = str_replace('{EXT_RES_ID:'.$res_fileID.'}', $relResourceFileName, $tokenizedContent);
									$tokenSubstituted = TRUE;
								}
							}
						}
					}

						// If substitutions has been made, write the content to the file again:
					if ($tokenSubstituted)	{
						t3lib_div::writeFile($newName, $tokenizedContent);
					}*/
					$this->processSoftReferences_saveFile_createRelFile_external(dirname($newName) . '/', $newName, $this->dat['files'][$fileID]['tokenizedContent'], $fileHeaderInfo['EXT_RES_ID']);
				}

				return substr($newName, strlen(PATH_site));
			}
		}
	}

	function processSoftReferences_saveFile_createRelFile_external($origDirPrefix, $fileName, $tokenizedContent, $extResIDs)	{
					$tokenSubstituted = FALSE;

					$fileProcObj = &$this->getFileProcObj();

					if ($updMode)	{
						foreach($extResIDs as $res_fileID)	{
							if ($this->dat['files'][$res_fileID]['filename'] && isset($this->dat['files'][$res_fileID]['parentRelFileName']))	{

									// Resolve original filename:
								$relResourceFileName = $this->dat['files'][$res_fileID]['parentRelFileName'];
								$absResourceFileName = t3lib_div::resolveBackPath(PATH_site.$origDirPrefix.$relResourceFileName);
								$absResourceFileName = t3lib_div::getFileAbsFileName($absResourceFileName);
								if ($absResourceFileName && t3lib_div::isFirstPartOfStr($absResourceFileName,PATH_site.$this->fileadminFolderName.'/'))	{
									$destDir = substr(dirname($absResourceFileName).'/',strlen(PATH_site));
									if ($this->verifyFolderAccess($destDir, TRUE) && $this->checkOrCreateDir($destDir))	{
										$this->writeFileVerify($absResourceFileName, $res_fileID);
									} else $this->error('ERROR: Could not create file in directory "'.$destDir.'"');
								} else $this->error('ERROR: Could not resolve path for "'.$relResourceFileName.'"');

								if (isset($this->dat['header']['files'][$res_fileID]['EXT_RES_ID']))
									$this->processSoftReferences_saveFile_createRelFile_external(dirname($absResourceFileName) . '/', $absResourceFileName, $this->dat['files'][$res_fileID]['tokenizedContent'], $this->dat['header']['files'][$res_fileID]['EXT_RES_ID']);

								$tokenizedContent = str_replace('{EXT_RES_ID:'.$res_fileID.'}', $relResourceFileName, $tokenizedContent);
								$tokenSubstituted = TRUE;
							}
						}
					} else {
							// Create the resouces directory name (filename without extension, suffixed "_FILES")
						//if (t3lib_div::mkdir($resourceDir))	{
							foreach($extResIDs as $res_fileID)	{
								if ($this->dat['files'][$res_fileID]['filename'] && isset($this->dat['files'][$res_fileID]['parentRelFileName']))	{
									//$absResourceFileName = $fileProcObj->getUniqueName($this->dat['files'][$res_fileID]['filename'], $resourceDir);
									$relResourceFileName = $this->dat['files'][$res_fileID]['parentRelFileName'];
									$absResourceFileName = t3lib_div::resolveBackPath(/*PATH_site.*/$origDirPrefix.$relResourceFileName);
									//$relResourceFileName = substr($absResourceFileName, strlen(dirname($resourceDir))+1);
									if (!file_exists(dirname($absResourceFileName)))
										//t3lib_div::mkdir(dirname($absResourceFileName));
										t3lib_div::mkdir_deep(PATH_site, dirname(substr($absResourceFileName, strlen(PATH_site))));
									$this->writeFileVerify($absResourceFileName, $res_fileID);

									if (isset($this->dat['header']['files'][$res_fileID]['EXT_RES_ID']))
										$this->processSoftReferences_saveFile_createRelFile_external(dirname($absResourceFileName) . '/', $absResourceFileName, $this->dat['files'][$res_fileID]['tokenizedContent'], $this->dat['header']['files'][$res_fileID]['EXT_RES_ID']);

									$tokenizedContent = str_replace('{EXT_RES_ID:'.$res_fileID.'}', $relResourceFileName, $tokenizedContent);
									$tokenSubstituted = TRUE;
								}
							}
						//}
					}

						// If substitutions has been made, write the content to the file again:
					if ($tokenSubstituted)	{
						t3lib_div::writeFile($fileName, $tokenizedContent);
					}
	}
}
