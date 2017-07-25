<?php

/*
 * Copyright (C) 2002-2015 AfterLogic Corp. (www.afterlogic.com)
 * Distributed under the terms of the license described in LICENSE
 *
 */

class_exists('CApi') or die();

CApi::Inc('common.plugins.change-password');

class CcPanelChangePasswordPlugin extends AApiChangePasswordPlugin
{
	/**
	 * @var
	 */
	protected $oBaseApp;

	/**
	 * @var
	 */
	protected $oAdminAccount;

	/**
	 * @param CApiPluginManager $oPluginManager
	 */
	public function __construct(CApiPluginManager $oPluginManager)
	{
		parent::__construct('1.0', $oPluginManager);
	}

	/**
	 * @param CAccount $oAccount
	 * @return bool
	 */
	protected function isLocalAccount($oAccount)
	{
		return \in_array(\strtolower(\trim($oAccount->IncomingMailServer)), array(
		   'localhost', '127.0.0.1', '::1', '::1/128', '0:0:0:0:0:0:0:1'
		  ));		
	}
	
	/**
	 * @param CAccount $oAccount
	 * @return bool
	 */
	protected function validateIfAccountCanChangePassword($oAccount)
	{
		return ($this->isLocalAccount($oAccount));
	}

	/**
	 * @param CAccount $oAccount
	 */
	public function ChangePasswordProcess($oAccount)
	{
		if (0 < strlen($oAccount->PreviousMailPassword) &&
			$oAccount->PreviousMailPassword !== $oAccount->IncomingMailPassword)
		{
			$cpanel_hostname = CApi::GetConf('plugins.cpanel-change-password.config.hostname', 'localhost');
			$cpanel_username = CApi::GetConf('plugins.cpanel-change-password.config.username', 'local');
			$cpanel_password = CApi::GetConf('plugins.cpanel-change-password.config.password', '');
			$cpanel_rootpass = CApi::GetConf('plugins.cpanel-change-password.config.rootpass', '');

			$email_user = urlencode($oAccount->Email);
			$email_password = urlencode($oAccount->IncomingMailPassword);
			$email_domain = \api_Utils::GetDomainFromEmail($oAccount->Email);
		
			if ($cpanel_rootpass != "") {
				//$query = "https://".$cpanel_hostname.":2087/json-api/listaccts?api.version=1&searchtype=domain&search=".$email_domain;

				// $query = "https://".$cpanel_hostname.":2087/json-api/listaccts?api.version=1";

				$query = "https://".$cpanel_hostname.":2087/json-api/listaccts?api.version=1&searchtype=domain&search=".$email_domain;
				
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
				curl_setopt($curl, CURLOPT_HEADER,0);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
				//$header[0] = "Authorization: Basic " . base64_encode($cpanel_username.":".$cpanel_password) . "\n\r";
				$header[0] = "Authorization: Basic " . base64_encode("root".":".$cpanel_rootpass) . "\n\r";
				curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
				curl_setopt($curl, CURLOPT_URL, $query);
				$result = curl_exec($curl);
				if ($result == false) {
					CApi::Log("curl_exec threw error \"" . curl_error($curl) . "\" for $query");
					curl_close($curl);
					throw new CApiManagerException(Errs::UserManager_AccountNewPasswordUpdateError);
				} else {
					curl_close($curl);
					CApi::Log("..:: QUERY0 ::.. ".$query);
					$json_res = json_decode($result, true);
					CApi::Log("..:: RESULT0 ::.. ".$result);
					if(isset($json_res['data']['acct'][0]['user'])) {
						$cpanel_username = $json_res['data']['acct'][0]['user'];
						CApi::Log("..:: USER ::.. ".$cpanel_username);
					}					
				}
			
				$query = "https://".$cpanel_hostname.":2087/json-api/cpanel?cpanel_jsonapi_user=".$cpanel_username."&cpanel_jsonapi_module=Email&cpanel_jsonapi_func=passwdpop&cpanel_jsonapi_apiversion=2&email=".$email_user."&password=".$email_password."&domain=".$email_domain;				
				
				// $query = "https://".$cpanel_hostname.":2087/json-api/cpanel?cpanel_jsonapi_user=".$cpanel_username."&cpanel_jsonapi_module=Email&cpanel_jsonapi_func=passwd_pop&cpanel_jsonapi_apiversion=3&email=".$email_user."&password=".$email_password."&domain=".$email_domain;
			} else {
				$query = "https://".$cpanel_hostname.":2083/execute/Email/passwd_pop?email=".$email_user."&password=".$email_password."&domain=".$email_domain;
			}

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
			curl_setopt($curl, CURLOPT_HEADER,0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
			//$header[0] = "Authorization: Basic " . base64_encode($cpanel_username.":".$cpanel_password) . "\n\r";
			$header[0] = "Authorization: Basic " . base64_encode("root".":".$cpanel_rootpass) . "\n\r";
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
			curl_setopt($curl, CURLOPT_URL, $query);
			$result = curl_exec($curl);
			if ($result == false) {
				CApi::Log("curl_exec threw error \"" . curl_error($curl) . "\" for $query");
				curl_close($curl);
				throw new CApiManagerException(Errs::UserManager_AccountNewPasswordUpdateError);
			} else {
				curl_close($curl);
				CApi::Log("..:: QUERY ::.. ".$query);
				$json_res = json_decode($result,true);
				CApi::Log("..:: RESULT ::.. ".$result);
				//CApi::Log("..:: DATA ::.. ".print_r($json_res->data,true));
				if (!( (isset($json_res["status"])&&($json_res["status"])) || (!isset($json_res["cpanelresult"]["error"])) ))
				{
					throw new CApiManagerException(Errs::UserManager_AccountNewPasswordUpdateError);
				}
			}
		}
	}
}

return new CcPanelChangePasswordPlugin($this);