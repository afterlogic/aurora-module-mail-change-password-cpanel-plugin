<?php
/**
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or AfterLogic Software License
 *
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailChangePasswordCpanelPlugin;

/**
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
{
	/**
	 * @param CApiPluginManager $oPluginManager
	 */
	
	public function init() 
	{
		$this->oMailModule = \Aurora\System\Api::GetModule('Mail');
	
		$this->subscribeEvent('Mail::ChangePassword::before', array($this, 'onBeforeChangePassword'));
	}
	
	/**
	 * 
	 * @param array $aArguments
	 * @param mixed $mResult
	 */
	public function onBeforeChangePassword($aArguments, &$mResult)
	{
		$mResult = true;
		
		$oAccount = $this->oMailModule->GetAccount($aArguments['AccountId']);

		if ($oAccount && $this->checkCanChangePassword($oAccount))
		{
			$mResult = $this->сhangePassword($oAccount, $aArguments['NewPassword']);
		}
	}

	/**
	 * @param CAccount $oAccount
	 * @return bool
	 */
	protected function checkCanChangePassword($oAccount)
	{
		$bFound = in_array("*", $this->getConfig('SupportedServers', array()));
		
		if (!$bFound)
		{
			$oServer = $this->oMailModule->GetServer($oAccount->ServerId);
			if ($oServer && in_array($oServer->Name, $this->getConfig('SupportedServers')))
			{
				$bFound = true;
			}
		}
		return $bFound;
	}
	
	/**
	 * @param CAccount $oAccount
	 */
	protected function сhangePassword($oAccount, $sPassword)
	{
	    $bResult = false;
	    if (0 < strlen($oAccount->IncomingPassword) && $oAccount->IncomingPassword !== $sPassword )
	    {
			$cpanel_host = "127.0.0.1";
			$cpanel_user = $this->getConfig('CpanelUser','');
			$cpanel_pass = $this->getConfig('CpanelPass','');

			$email_user = urlencode($oAccount->Email);
			$email_pass = urlencode($sPassword);
			list($email_login, $email_domain) = explode('@', $oAccount->Email); 
		
			$query = "https://".$cpanel_host.":2083/execute/Email/passwd_pop?email=".$email_user."&password=".$email_pass."&domain=".$email_domain;

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
			curl_setopt($curl, CURLOPT_HEADER,0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
			$header[0] = "Authorization: Basic " . base64_encode($cpanel_user.":".$cpanel_pass) . "\n\r";
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
			curl_setopt($curl, CURLOPT_URL, $query);
			$result = curl_exec($curl);
			if ($result == false) {
				\Aurora\System\Api::Log("curl_exec threw error \"" . curl_error($curl) . "\" for $query");
				curl_close($curl);
				throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Exceptions\Errs::UserManager_AccountNewPasswordUpdateError);
			} else {
				curl_close($curl);
				\Aurora\System\Api::Log("..:: QUERY ::.. ".$query);
				$json_res = json_decode($result,true);
				\Aurora\System\Api::Log("..:: RESULT ::.. ".$result);
				if ((isset($json_res["errors"]))&&($json_res["errors"]!==null))
				{
					throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Exceptions\Errs::UserManager_AccountNewPasswordUpdateError);
				} else {
					$bResult = true;
				}
			}
	    }
	    return $bResult;
	}
}