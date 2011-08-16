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
 * Notification mail sender for the 'ics_utopia' extension.
 *
 * @author	In Cité Solution <technique@incitesolution.fr>
 */

require_once(PATH_t3lib . 'class.t3lib_htmlmail.php');

class utopia_mail_notify
{
	function notify($sender, $recipient, $markers, $key)
	{		
	
		$config = t3lib_div::makeInstance('utopia_config');		
		$infoSession = t3lib_div::makeInstance('utopia_session');
		
		$fileKey = $key.'file';
		$adminMailSubject = $key.'subject';
		
								
		list($admin_type, $admin) = explode("_", strtolower($recipient));
		
		if($admin_type == 'be')
			$admins = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('`realName` as `name`, `email`, `lang`', '`'.$admin_type.'_users`', 'uid = '.$admin);
		else
			$admins = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('`name`, `email`', '`'.$admin_type.'_users`', 'uid = '.$admin);
			
			
		$sender = explode("_", strtolower($sender));		
		
		if($sender[0] == 'be')
			$sender = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('`realName` as `name`, `email`, `lang`', $sender[0] . '_users', 'uid = ' . $sender[1]);
		else
			$sender = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('`name`, `email`', $sender[0] . '_users', 'uid = ' . $sender[1]);
			
			
		if(is_null($config->getConfig($fileKey.'.'.$admins[0]['lang'])))
		{
			$f = $config->getConfig($fileKey);
			$adminSubjKey = $config->getConfig($adminMailSubject);
		}
		else
		{
			$f = $config->getConfig($fileKey.'.'.$admins[0]['lang']);
			$adminSubjKey = $config->getConfig($adminMailSubject.'.'.$admins[0]['lang']);
		}

		
		if(file_exists(PATH_site.$f.'.'.$admins[0]['lang']))
		{
			$message = file_get_contents (PATH_site.$f.'.'.$admins[0]['lang']);
			$this->sendNotification(PATH_site.$f.'.'.$admins[0]['lang'],$adminSubjKey ,$admins[0]['email'], $sender[0], $markers);			

		}
		elseif(file_exists(PATH_site.$f))
		{
			$message = file_get_contents (PATH_site.$f);
			$this->sendNotification(PATH_site.$f, $adminSubjKey,$admins[0]['email'], $sender[0], $markers);
		}
	}
	
	function sendNotification($templatename, $subject, $users, $sender, $markers)
	{
		$mailhtml = file_get_contents($templatename); 	
			
		$message = str_replace(array_keys($markers), array_values($markers), $mailhtml);
				
		$htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');
		$htmlmail->start();
		$htmlmail->subject = $subject;
		$htmlmail->from_email = $sender['email'];
		$htmlmail->from_name = $sender['name'];
		$htmlmail->replyto_email = $sender['email'];
		$htmlmail->replyto_name = $sender['name'];

		$tempname = tempnam('/tmp/', 'mail');
		file_put_contents($tempname, $message);		
		$htmlmail->addHtml($tempname);
		unlink($tempname);
		$htmlmail->send($users);
	}
	
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_mail_notify.php"]){
include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ics_utopia/lib/class.utopia_mail_notify.php"]);
}
