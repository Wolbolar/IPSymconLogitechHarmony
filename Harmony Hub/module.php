<?
// Modul für Harmony Hub

class HarmonyHubAPI extends IPSModule
{
		
    public function Create()
    {
		//Never delete this line!
        parent::Create();
		
		//These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
		
        $this->RegisterPropertyBoolean("Open", true);
		$this->RegisterPropertyString("Name", "");
		$this->RegisterPropertyString("Firmware", "");
		$this->RegisterPropertyString("HarmonyUser", "");
		$this->RegisterPropertyInteger("HubID", 0);
		$this->RegisterPropertyBoolean("Alexa", false);
    }

    public function ApplyChanges()
    {
		//Never delete this line!
        parent::ApplyChanges();
		
		
		$this->ValidateConfiguration();	
	
    }

		/**
        * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
        * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
        *
        *
        */
	protected $lockgetConfig = false;	

	
	private function ValidateConfiguration()
	{
		
		$change = false;
		
		$HubID = $this->ReadPropertyInteger('HubID');
		if ( $HubID === 0)
			{
				// HubID darf muss vorhanden sein
				$this->SetStatus(207);
			}
		elseif ( $HubID != 0)	
			{
				$this->SetupHarmony();
				// Status Aktiv
				$this->SetStatus(102);
			}
		// Alexa Link anlegen
		if($this->ReadPropertyBoolean('Alexa'))
			{
				$this->CreateAlexaLinks();
			}
		else
			{
				$this->DeleteAlexaLinks();
			}	
	}
	
	
	protected function GetBearerToken()
	{
		$ConfiguratorID = $this->GetConfiguratorID();
		$bearertoken = IPS_GetProperty($ConfiguratorID, "Token");
		return $bearertoken;
	}
	
	//Profile zuweisen und Aktionen anlegen
	public function SetupHarmony()
	{
		$HubID = $this->ReadPropertyInteger('HubID');
		$ConfiguratorID = $this->GetConfiguratorID();
		$ImportCategoryID = IPS_GetProperty($ConfiguratorID, "ImportCategoryID");
		$hubname = $this->ReadPropertyString('Name');
		$activitiesjson = $this->GetHubActivities($HubID);
		
		//Activity Profil Logitech API anlegen
		$varidprofile = $this->SetLogitechAPIActivityProfile($activitiesjson, $HubID);
				
		//Harmony Aktivity Link setzten
		$this->CreateAktivityLink($ImportCategoryID, $hubname, $HubID);
		
		//Aktivität aktualisieren
		$statehubdigest = $this->GetHubStateDigest($HubID);
		
		$code = $statehubdigest["status"];
		$msg = $statehubdigest["message"];
			if ($code == 504)
			{
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is offline. Message: ".$msg);
				$response = array("status" => $code, "message" => $msg);	 
			}
			elseif ($code == 200) //success
			{
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is online. Message ".$msg." mit Status ".$code);
				$version = $statehubdigest["version"];
				$configVersion = $statehubdigest["configversion"];
				$currentAvActivity = $statehubdigest["currentAvActivity"];
				$currentActivities = $statehubdigest["currentActivities"]; // Array
				$syncStatus = $statehubdigest["syncStatus"];
				$activityStatus = $statehubdigest["activityStatus"];
				$response = array("status" => $code, "message" => $msg, "version" => $version, "configversion" => $configVersion, "currentAvActivity" => $currentAvActivity, "currentActivities" => $currentActivities, "syncStatus" => $syncStatus, "activityStatus" => $activityStatus );	
				$this->SendDebug("Logitech Harmony Hub","Harmony Hub Activity is ".$currentAvActivity,0);
				SetValueInteger($this->GetIDForIdent("HarmonyActivityAPI".$HubID), $currentAvActivity);
			}
		return $response;	
	}
	
	protected function SetLogitechAPIActivityProfile($activitiesjson, $HubID)
	{
		$hubactivities = json_decode($activitiesjson);
		$code = $hubactivities->code;
		$msg =  $hubactivities->msg;
		if ($code == 504)
			{
				$this->SendDebug("Logitech Harmony Hub","Harmony Hub is offline. Code ".$code.", Message: ".$msg,0);
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is offline. Code ".$code.", Message: ".$msg);
				$response = array("code" => $code, "msg" => $msg);
			}
			elseif ($code == 200) //success
			{
				$this->SendDebug("Logitech Harmony Hub","Harmony Hub is online. Code ".$code.", Message: ".$msg,0);
				$data =  $hubactivities->data;
				$activities = $data->activities;
				$ProfileAssActivities = array();
				$assid = 1;
				$hubactivities = array();
				foreach ($activities as $key => $activity)
						{
							$activityID = $key;
							$type = $activity->type;
							$name = $activity->name;
							$isAV = $activity->isAV;
							$image = $activity->image;
							$commands = $activity->commands;
							$hubactivities[$assid] = array("activityid" => $activityID, "name" => $name, "type" => $type, "isAV" => $isAV, "image" => $image, "commands" => $commands);
							$ProfileAssActivities[$assid] = Array($activityID, utf8_decode($name),  "", -1);
							$assid++;
						}
				$profilemax = count($ProfileAssActivities);
				$this->RegisterProfileIntegerHarmonyAss("LogitechHarmony.ActivityAPI.".$HubID , "Popcorn", "", "", -1 , ($profilemax+1), 0, 0, $ProfileAssActivities);
				$this->SendDebug("Harmony Hub:","Set Profile :".print_r($ProfileAssActivities,true),0);
				$varidprofile = $this->RegisterVariableInteger("HarmonyActivityAPI".$HubID, "Harmony Activity API", "LogitechHarmony.ActivityAPI.".$HubID, 1);
				$this->EnableAction("HarmonyActivityAPI".$HubID);		
				$response = array("Varidprofile" => $varidprofile, "code" => $code, "msg" => $msg, "activities" => $hubactivities);		
			 }
		return $response; 
	}
		
	public function SetupActivityScripts()
	{
		$HubID = $this->ReadPropertyInteger('HubID');
		$ConfiguratorID = $this->GetConfiguratorID();
		$ImportCategoryID = IPS_GetProperty($ConfiguratorID, "ImportCategoryID");
		$hubname = $this->ReadPropertyString('Name');
		$activitiesjson = $this->GetHubActivities($HubID);
		$hubactivities = json_decode($activitiesjson);
		$code = $hubactivities->code;
		$msg =  $hubactivities->msg;
		$data =  $hubactivities->data;
		$activities = $data->activities;
		//Prüfen ob Kategorie schon existiert
		$HubCategoryID = @IPS_GetObjectIDByIdent("CatLogitechHubAPI_".$HubID, $ImportCategoryID);
		if ($HubCategoryID === false)
			{
				$HubCategoryID = IPS_CreateCategory();
				IPS_SetName($HubCategoryID, "Logitech ".$hubname);
				IPS_SetIdent($HubCategoryID, "CatLogitechHubAPI_".$HubID);
				IPS_SetInfo($HubCategoryID, $HubID);
				IPS_SetParent($HubCategoryID, $ImportCategoryID);
			}	
		//Prüfen ob Unterkategorie schon existiert
		$MainCatID = @IPS_GetObjectIDByIdent("LogitechAPIActivitiesScripts_".$HubID, $HubCategoryID);
		if ($MainCatID === false)
			{
			$MainCatID = IPS_CreateCategory();
			$this->SendDebug("Harmony Hub:","Es wurde keine Kategorie gefunden, neue Kategorie mit ObjektId ".$MainCatID."angelegt",0);
			IPS_SetName($MainCatID, $hubname." Aktivitäten");
			IPS_SetInfo($MainCatID, $hubname." Aktivitäten");
			//IPS_SetIcon($MainCatID, $Quellobjekt['ObjectIcon']);
			//IPS_SetPosition($MainCatID, $Quellobjekt['ObjectPosition']);
			//IPS_SetHidden($MainCatID, $Quellobjekt['ObjectIsHidden']);
			IPS_SetIdent($MainCatID, "LogitechAPIActivitiesScripts_".$HubID);
			IPS_SetParent($MainCatID, $HubCategoryID);
			}
			
		foreach ($activities as $activityid => $activity)
			{
				$activityname = $activity->name;
				//Prüfen ob Script schon existiert
				$ScriptIDOn = $this->CreateActivityScript($activityid, $MainCatID, $HubID, $activityname, "On");
				$ScriptIDOff = $this->CreateActivityScript($activityid, $MainCatID, $HubID, $activityname, "Off");
				$ScriptIDOff = $this->CreateActivityScript($activityid, $MainCatID, $HubID, $activityname, "Toggle");
			}
		$ScriptIDPowerOff = $this->CreateActivityScriptPowerOff($MainCatID, $HubID);		
	}
		
	protected function CreateActivityScript($activityid, $MainCatID, $HubID, $activityname, $type)
	{
		
		$Scriptname = $this->ReplaceSpecialCharacters($activityname);
		$Scriptname = $Scriptname." ".$type;
		$Ident = "Script_".$activityid."_".$type;
		$ScriptID = @IPS_GetObjectIDByIdent($Ident, $MainCatID);
								
		if ($ScriptID === false)
			{
				$ScriptID = IPS_CreateScript(0);
				$this->SendDebug("Harmony Hub:","Es wurde kein Skript gefunden, neues Skript für Aktivität ".$Scriptname." angelegt.",0);
				IPS_SetName($ScriptID, $Scriptname);
				IPS_SetParent($ScriptID, $MainCatID);
				IPS_SetIdent($ScriptID, $Ident);
				if($type == "On")
				{
					$content = '<?
Switch ($_IPS[\'SENDER\']) 
    { 
    Default: 
    Case "RunScript": 
		HarmonyHubAPI_StartActivityAPI('.$this->InstanceID.', '.$HubID.', '.$activityid.');
    Case "Execute": 
        HarmonyHubAPI_StartActivityAPI('.$this->InstanceID.', '.$HubID.', '.$activityid.');
    Case "TimerEvent": 
        break; 

    Case "Variable": 
    Case "AlexaSmartHome": // Schalten durch den Alexa SmartHomeSkill
           
    if ($_IPS[\'VALUE\'] == True) 
        { 
            // einschalten
            HarmonyHubAPI_StartActivityAPI('.$this->InstanceID.', '.$HubID.', '.$activityid.');  
        } 
    else 
        { 
            //ausschalten
            HarmonyHubAPI_EndActivity('.$this->InstanceID.', '.$HubID.', '.$activityid.');
        } 
       break;
    Case "WebFront":        // Zum schalten im Webfront 
        HarmonyHubAPI_StartActivityAPI('.$this->InstanceID.', '.$HubID.', '.$activityid.');   
    }  
?>';
					
				}
				elseif($type == "Off")
				{
					$content = '<?
Switch ($_IPS[\'SENDER\']) 
    { 
    Default: 
    Case "RunScript": 
		HarmonyHubAPI_EndActivity('.$this->InstanceID.', '.$HubID.', '.$activityid.');
    Case "Execute": 
        HarmonyHubAPI_EndActivity('.$this->InstanceID.', '.$HubID.', '.$activityid.');
    Case "TimerEvent": 
        break; 

    Case "Variable": 
    Case "AlexaSmartHome": // Schalten durch den Alexa SmartHomeSkill
           
    if ($_IPS[\'VALUE\'] == True) 
        { 
            // einschalten
            HarmonyHubAPI_StartActivityAPI('.$this->InstanceID.', '.$HubID.', '.$activityid.');  
        } 
    else 
        { 
            //ausschalten
            HarmonyHubAPI_EndActivity('.$this->InstanceID.', '.$HubID.', '.$activityid.');
        } 
       break;
    Case "WebFront":        // Zum schalten im Webfront 
        HarmonyHubAPI_EndActivity('.$this->InstanceID.', '.$HubID.', '.$activityid.');   
    }  
?>';
				}
				elseif($type == "Toggle")
				{
					$content = '<?
Switch ($_IPS[\'SENDER\']) 
    { 
    Default: 
    Case "RunScript": 
		HarmonyHubAPI_StartActivityAPI('.$this->InstanceID.', '.$HubID.', '.$activityid.');
    Case "Execute": 
        HarmonyHubAPI_StartActivityAPI('.$this->InstanceID.', '.$HubID.', '.$activityid.');
    Case "TimerEvent": 
        break; 

    Case "Variable": 
    Case "AlexaSmartHome": // Schalten durch den Alexa SmartHomeSkill
           
    if ($_IPS[\'VALUE\'] == True) 
        { 
            // einschalten
            HarmonyHubAPI_StartActivityAPI('.$this->InstanceID.', '.$HubID.', '.$activityid.');  
        } 
    else 
        { 
            //ausschalten
            HarmonyHubAPI_EndActivity('.$this->InstanceID.', '.$HubID.', '.$activityid.');
        } 
       break;
    Case "WebFront":        // Zum schalten im Webfront 
        HarmonyHubAPI_StartActivityAPI('.$this->InstanceID.', '.$HubID.', '.$activityid.');   
    }  
?>';
				}
				IPS_SetScriptContent($ScriptID, $content);
			}
		return $ScriptID;
	}
	
	protected function CreateActivityScriptPowerOff($MainCatID, $HubID)
	{
		$Scriptname = "PowerOffHubAV";
		$Ident = "PowerOffHubAV_Script_".$HubID;
		$ScriptID = @IPS_GetObjectIDByIdent($Ident, $MainCatID);
								
		if ($ScriptID === false)
			{
				$ScriptID = IPS_CreateScript(0);
				IPS_SetName($ScriptID, $Scriptname);
				IPS_SetParent($ScriptID, $MainCatID);
				IPS_SetIdent($ScriptID, $Ident);
				$content = "<? 
				// End the current AV activity and power off all AV devices controlled by the hub.
				HarmonyHubAPI_PowerOffHubAV(".$this->InstanceID.", ".$HubID."); ?>";
				IPS_SetScriptContent($ScriptID, $content);
			}
		return $ScriptID;
	}
	
	protected function ReplaceSpecialCharacters($string)
	{
		$string = str_replace('Ã¼', 'ü', $string);
		return $string;
	}
	
	protected function CreateIdent($str)
	{
	$search = array("ä", "ö", "ü", "ß", "Ä", "Ö", 
					"Ü", "&", "é", "á", "ó", 
					" :)", " :D", " :-)", " :P", 
					" :O", " ;D", " ;)", " ^^", 
					" :|", " :-/", ":)", ":D", 
					":-)", ":P", ":O", ";D", ";)", 
					"^^", ":|", ":-/", "(", ")", "[", "]", 
					"<", ">", "!", "\"", "§", "$", "%", "&", 
					"/", "(", ")", "=", "?", "`", "´", "*", "'", 
					"-", ":", ";", "²", "³", "{", "}", 
					"\\", "~", "#", "+", ".", ",", 
					"=", ":", "=)");
	$replace = array("ae", "oe", "ue", "ss", "Ae", "Oe", 
					 "Ue", "und", "e", "a", "o", "", "", 
					 "", "", "", "", "", "", "", "", "", 
					 "", "", "", "", "", "", "", "", "", 
					 "", "", "", "", "", "", "", "", "", 
					 "", "", "", "", "", "", "", "", "", 
					 "", "", "", "", "", "", "", "", "", 
					 "", "", "", "", "", "", "", "", "", "");
	$str = str_replace($search, $replace, $str);
	$str = str_replace(' ', '_', $str); // Replaces all spaces with underline.
	$how = '_';
	//$str = strtolower(preg_replace("/[^a-zA-Z0-9]+/", trim($how), $str));
	$str = preg_replace("/[^a-zA-Z0-9]+/", trim($how), $str);
	return $str;
	}
	
	//Link für Harmony Activity anlegen
	protected function CreateAktivityLink($CategoryID, $hubname, $hubid)
	{
		//Prüfen ob Kategorie schon existiert
		$HubCategoryID = @IPS_GetObjectIDByIdent("CatLogitechHubAPI_".$hubid, $CategoryID);
		if ($HubCategoryID === false)
			{
				$HubCategoryID = IPS_CreateCategory();
				IPS_SetName($HubCategoryID, "Logitech ".$hubname);
				IPS_SetIdent($HubCategoryID, "CatLogitechHubAPI_".$hubid);
				IPS_SetInfo($HubCategoryID, $hubid);
				IPS_SetParent($HubCategoryID, $CategoryID);
			}	
		//Prüfen ob Instanz schon vorhanden
		$InstanzID = @IPS_GetObjectIDByIdent("HarmonyHubActivities_".$hubid, $HubCategoryID);
		if ($InstanzID === false)
			{
				$InsID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
				IPS_SetName($InsID, $hubname." Aktivitäten"); // Instanz benennen
				IPS_SetIdent($InsID, "HarmonyHubActivities_".$hubid);
				IPS_SetParent($InsID, $HubCategoryID); // Instanz einsortieren unter dem Objekt mit der ID "$HubCategoryID"

				// Anlegen eines neuen Links für Harmony Aktivity
				$LinkID = IPS_CreateLink();             // Link anlegen
				IPS_SetName($LinkID, "Logitech Harmony Hub Activity"); // Link benennen
				IPS_SetParent($LinkID, $InsID); // Link einsortieren 
				IPS_SetLinkTargetID($LinkID, $this->GetIDForIdent("HarmonyActivityAPI".$hubid));    // Link verknüpfen
			}	
	}
	
	protected function GetConfiguratorID()
	{
		$InstanzenListe = IPS_GetInstanceListByModuleID("{37D1B484-B5A5-4C0D-AE53-0DD022923248}");
		$ConfiguratorID = $InstanzenListe[0];
		return $ConfiguratorID;
	}
	
	protected function GetLogitechURIBase()
	{
		$uribase = "https://home.myharmony.com/cloudapi";
		return $uribase;
	}
	
	/*DISCOVER
	 *Enumerate all hubs in the user's Harmony account, sending a discovery request to each.
	 *
	 * Note: most users will only have one hub, but it is possible to configure multiple hubs within the same account.
	 * Response Status
	 * 200 --- Success
	 * 4xx --- Client error
	 * 5xx --- Server error
	 * Response Body
	 * On success, a JSON document including a map hubId->response listing the hub responses. The hub ID keys in this map are used as arguments in other API requests.
	 *
	 * Offline hubs have a response status of 504.
	 */
    public function Discover()
	{
		$discovercommand = "/discover";
		$type = "GET";
		$discoverjson =  $this->SendHarmonyAPI($discovercommand, $type);
		/*
		$discover = json_decode($discoverjson);
		$hubs = $discover->hubs;
		$response = array();
		foreach ($hubs as $key => $hub)
		{
			IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub ID: ".$key); // ID Harmony Hub
			$status = $hubs->$key->status;
			if ($status == 504)
			{
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is offline"); 
				$message = $hubs->$key->message;
				$name = $hubs->$key->name;
				IPS_LogMessage("Logitech Harmony Hub:", "message from ".$name.": ".$message);
				$response[] = array("status" => $status, "message" => $message, "name" => $name);
			}
			elseif ($status == 200) //success
			{
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is online"); 
				$message = $hubs->$key->message;
				$response = $hubs->$key->response;
				$code = $response->code;
				$msg = $response->msg;
				$data = $response->data;
				$firmware = $data->fw;
				$name = $data->name;
				$user = $data->user;
				$response[] = array("status" => $status, "message" => $message, "code" => $code, "msg" => $msg, "firmware" => $firmware, "name" => $name, "user" => $user);
			}
		}
		return $response;
		*/
		return $discoverjson;
	}
	
	/* DISCOVER HUB
	* Request discovery information from a specific hub.
	* /hub/{{hubId}}/discover
	* Response Status
	* 200 --- Success
	* 4xx --- Client error
	* 5xx --- Server error
	* 504 --- Hub could not be reached
	*/
	public function DiscoverHub(int $hubId)
	{
		$discoverhubcommand = "/hub/".$hubId."/discover";
		$type = "GET";
		$discoverhubjson =  $this->SendHarmonyAPI($discoverhubcommand, $type);
		/*
		$discoverhub = json_decode($discoverhubjson);
		$code = $discoverhub->code;
		$msg = $discoverhub->msg;
		$data = $discoverhub->data;
		$firmware = $data->fw;
		$name = $data->name;
		$user = $data->user;
		$response = array("code" => $code, "msg" => $msg, "firmware" => $firmware, "name" => $name, "user" => $user);
		return $response;
		*/
		return $discoverhubjson;
	}
	
	/* GET ACTIVITIES
	* Get all activities controlled by all hubs.
	*/
	public function GetActivities()
	{
		$getactivitiescommand = "/activity/all";
		$type = "GET";
		$getactivitiesjson =  $this->SendHarmonyAPI($getactivitiescommand, $type);
		/*
		$getactivities = json_decode($getactivitiesjson);
		$hubs = $getactivities->hubs;
		$response = array();
		foreach ($hubs as $key => $hub)
		{
			IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub ID: ".$key); // ID Harmony Hub
			$status = $hubs->$key->status;
			if ($status == 504)
			{
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is offline"); 
				$message = $hubs->$key->message;
				$name = $hubs->$key->name;
				IPS_LogMessage("Logitech Harmony Hub:", "message from ".$name.": ".$message);
				$response[] = array("status" => $status, "message" => $message, "name" => $name);
		
			}
			elseif ($status == 200) //success
			{
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is online"); 
				$message = $hubs->$key->message;
				$response = $hubs->$key->response;
				$code = $response->code;
				$msg = $response->msg;
				IPS_LogMessage("Logitech Harmony Hub:", "Message ".$message." mit Status ".$code);
				$data = $response->data;
				$activities = $data->activities;
				$hubactivities = array();
				foreach ($activities as $key => $activity)
				{
						$activityID = $key;
						$type = $activities->$activityID->type;
						$name = $activities->$activityID->name;
						IPS_LogMessage("Logitech Harmony Hub:", "message from ".$name.": ".$message);
						$hubactivities[] = array("activityid" => $activityID, "name" => $name, "type" => $type);
						
				}
				$response[] = array("status" => $status, "message" => $message, "code" => $code, "msg" => $msg, "activities" => $hubactivities);
		
			}	
		}
		return $response;
		*/
		return $getactivitiesjson;
	}
	
	/* GET HUB ACTIVITIES
	* Get all activities controlled by a specific hub.
	*/
	public function GetHubActivities(int $hubId)
	{
		$gethubactivitiescommand = "/hub/".$hubId."/activity/all";
		$type = "GET";
		$gethubactivitiesjson =  $this->SendHarmonyAPI($gethubactivitiescommand, $type);
		return $gethubactivitiesjson;
	}
	
	/* GET STATE DIGESTS
	* Get the state digest of each hub.
    * Response Body
	* On success, a JSON document including a map hubId->response listing the hub responses.
	*
	* A successful hub response contains a digest of the hub's state.
	*
	* Offline hubs have a response status of 504.
	*
	* State Digest
	*
	* version --- state version; incremented on every state change.
	* configVersion --- configuration version.
	* currentAvActivity --- current audiovisual (AV) activity ID. For each hub, only one AV activity can be active at any time. A value of -1 indicates that no AV activity is currently active.
	* currentActivities --- list of current activities, including AV and non-AV activities.
	* syncStatus --- 0--No sync, 1--Sync in progress, 2--Sync failed, 3--Sync conflicted, 4--Resync in progress
	* activityStatus --- 0--No activity, 1--Activity starting, 2--Activity running, 3--Powering off
    */
	public function  GetStateDigest()
	{
		$statedigestcommand = "/state";
		$type = "GET";
		$statedigestjson =  $this->SendHarmonyAPI($statedigestcommand, $type);
		/*
		$statedigest = json_decode($statedigestjson);
		$hubs = $statedigest->hubs;
		$response = array();
		foreach ($hubs as $key => $hub)
		{
			IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub ID: ".$key);
			$status = $hubs->$key->status;
			if ($status == 504)
			{
				$message = $hubs->$key->message;
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is offline. Message: ".$message);
				$response[] = array("status" => $status, "message" => $message);	 
			}
			elseif ($status == 200) //success
			{
				$message = $hubs->$key->message;
				$response = $hubs->$key->response;
				$code = $response->code;
				$msg = $response->msg;
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is online. Message ".$message." mit Status ".$code);
				$data = $response->data;
				$version = $data->version;
          		$configVersion = $data->configVersion;
				$currentAvActivity = $data->currentAvActivity;
				$currentActivities = $data->currentActivities;
		        $syncStatus = $data->syncStatus;
          		$activityStatus = $data->activityStatus;
				$response[] = array("status" => $status, "message" => $message, "version" => $version, "configversion" => $configVersion, "currentAvActivity" => $currentAvActivity, "currentActivities" => $currentActivities, "syncStatus" => $syncStatus, "activityStatus" => $activityStatus );	
			}
			
		}
		return $response; */
		return $statedigestjson;
	}
	
	/* GET HUB STATE DIGEST
	* Get the state digest of a specific hub.
	*
	*/
	//GetHubStateDigest(1);
	public function  GetHubStateDigest(int $hubId)
	{
		$statehubdigestcommand = "/hub/".$hubId."/state";
		$type = "GET";
		$statehubdigestjson =  $this->SendHarmonyAPI($statehubdigestcommand, $type);
		
		$statehubdigest = json_decode($statehubdigestjson);
		$code = $statehubdigest->code;
		$msg = $statehubdigest->msg;
			if ($code == 504)
			{
				$this->SendDebug("Logitech Harmony Hub","Harmony Hub is offline. Message: ".$msg,0);
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is offline. Message: ".$msg);
				$response = array("status" => $code, "message" => $msg);	 
			}
			elseif ($code == 200) //success
			{
				$this->SendDebug("Logitech Harmony Hub","Harmony Hub is online. Message ".$msg." mit Status ".$code,0);
				$data = $statehubdigest->data;
				$version = $data->version;
				$configVersion = $data->configVersion;
				$currentAvActivity = $data->currentAvActivity;
				$currentActivities = $data->currentActivities; // Array
				$syncStatus = $data->syncStatus;
				$activityStatus = $data->activityStatus;
				$response = array("status" => $code, "message" => $msg, "version" => $version, "configversion" => $configVersion, "currentAvActivity" => $currentAvActivity, "currentActivities" => $currentActivities, "syncStatus" => $syncStatus, "activityStatus" => $activityStatus );	
				$this->SendDebug("Logitech Harmony Hub","Harmony Hub Activity is ".$currentAvActivity,0);
				SetValueInteger($this->GetIDForIdent("HarmonyActivityAPI".$hubId), $currentAvActivity);	
			}
		return $response;
	}
	
	/*POWER OFF AV
	* End all current AV activities and power off all AV devices controlled by all hubs.
	*/
	public function PowerOffAV()
	{
		$poweroffavcommand = "/activity/off";
		$type = "POST";
		$poweroffavjson =  $this->SendHarmonyAPI($poweroffavcommand, $type);
		/*
		$poweroffav = json_decode($poweroffavjson);
		$hubs = $poweroffav->hubs;
		$response = array();
		foreach ($hubs as $key => $hub)
		{
			IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub ID: ".$key);
			$status = $hubs->$key->status;
			if ($status == 504)
			{
				$message = $hubs->$key->message;
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is offline. Message: ".$message);
				$response[] = array("status" => $status, "message" => $message);	 
			}
			elseif ($status == 200) //success
			{
				$message = $hubs->$key->message;
				$response = $hubs->$key->response;
				$code = $response->code;
				$msg = $response->msg;
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is online. Message ".$message." mit Status ".$code);
				$response[] = array("status" => $status, "message" => $message);	
			}	
		}
		return $response;
		*/
		return $poweroffavjson;
	}
	
	/*POWER OFF HUB AV
	* End the current AV activity and power off all AV devices controlled a specific hub.*/
	public function PowerOffHubAV(int $hubId)
	{
		$poweroffhubavcommand = "/hub/".$hubId."/activity/off";
		$type = "POST";
		$poweroffhubavjson =  $this->SendHarmonyAPI($poweroffhubavcommand, $type);
		/*
		$poweroffhubav = json_decode($poweroffhubavjson);
		$code = $poweroffhubav->code;
		$msg = $poweroffhubav->msg;		
		if ($code == 504)
		{
			IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is offline. Message: ".$msg);
			$response = array("status" => $code, "message" => $msg);	 
		}
		elseif ($code == 200) //success
		{
			IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is offline. Message: ".$msg);
			$response = array("status" => $code, "message" => $msg);	
		}	
		return $response;
		*/
		return $poweroffhubavjson;
	}

	/*START ACTIVITY
	* Start an activity.
	*
	* If the activity is an AV activity, the hub's current AV activity (if any) will be ended. Starting a non-AV activity does not affect the current AV activity.*/			
	//StartActivity(1, 12345);
	public function StartActivityAPI(int $hubId, int $activityId)
	{
		$startactivitycommand = "/hub/".$hubId."/activity/".$activityId."/start";
		$type = "POST";
		$startactivityjson =  $this->SendHarmonyAPI($startactivitycommand, $type);
		/*
		$startactivity = json_decode($startactivityjson);
		$code =  $startactivity->code;
		$msg =  $startactivity->msg;	
		if ($code == 504)
		{
			IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is offline. Message: ".$msg);
			$response = array("status" => $code, "message" => $msg);	 
		}
		elseif ($code == 200) //success
		{
			IPS_LogMessage("Logitech Harmony Hub:", "Aktivität gestartet: ".$activityId.", Code: ".$code.", Message: ".$msg);
			$response = array("status" => $code, "message" => $msg);	
		}	
		return $response;
		*/
		return $startactivityjson;
	}
	
	/* END ACTIVITY
	* End an activity.
	*/
	//EndActivity (1, 12345);
	public function EndActivity (int $hubId, int $activityId)
	{
		$endactivitycommand = "/hub/".$hubId."/activity/".$activityId."/end";
		$type = "POST";
		$endactivityjson =  $this->SendHarmonyAPI($endactivitycommand, $type);
		/*
		$endactivity = json_decode($endactivityjson);
		$code =  $endactivity->code;
		$msg =  $endactivity->msg;
		if ($code == 504)
		{
			IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is offline. Message: ".$msg);
			$response = array("status" => $code, "message" => $msg);	 
		}
		elseif ($code == 200) //success
		{
			IPS_LogMessage("Logitech Harmony Hub:", "Aktivität beendet: ".$activityId.", Code: ".$code.", Message: ".$msg);
			$response = array("status" => $code, "message" => $msg);	
		}	
		return $response;
		*/
		return $endactivityjson;
	}
	
	/* Send to Logitech API
	* 
	*/
	public function SendHarmonyAPI(string $command, string $type)
	{
		$authorization = "Authorization: Bearer ".$this->GetBearerToken();
		$uribase = $this->GetLogitechURIBase();
		$url =  $uribase.$command;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, "IPSymcon4");
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array( $authorization ));
		if($type == "GET")
		{
			curl_setopt($ch, CURLOPT_URL,$url);
		}
		elseif($type == "POST")
		{
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
		}
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
		
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
		$result=curl_exec ($ch);
		$this->SendDebug("Harmony Hub:","Send Command: ".print_r($url,true),0);
		$this->SendDebug("Harmony Hub:","Response: ".print_r($result,true),0);
		curl_close ($ch);
		return $result;
	}
	
	protected function RegisterTimer($ident, $interval, $script)
	{
		$id = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);

		if ($id && IPS_GetEvent($id)['EventType'] <> 1)
		{
		  IPS_DeleteEvent($id);
		  $id = 0;
		}

		if (!$id)
		{
		  $id = IPS_CreateEvent(1);
		  IPS_SetParent($id, $this->InstanceID);
		  IPS_SetIdent($id, $ident);
		}

		IPS_SetName($id, $ident);
		IPS_SetHidden($id, true);
		IPS_SetEventScript($id, "\$id = \$_IPS['TARGET'];\n$script;");

		if (!IPS_EventExists($id)) throw new Exception("Ident with name $ident is used for wrong object type");

		if (!($interval > 0))
		{
		  IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, 1);
		  IPS_SetEventActive($id, false);
		}
		else
		{
		  IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $interval);
		  IPS_SetEventActive($id, true);
		}
	}
	
	
	
	
################## DUMMYS / WOARKAROUNDS - protected

   
    protected function RequireParent($ModuleID, $Name = '')
    {

        $instance = IPS_GetInstance($this->InstanceID);
        if ($instance['ConnectionID'] == 0)
        {

            $parentID = IPS_CreateInstance($ModuleID);
            $instance = IPS_GetInstance($parentID);
            if ($Name == '')
                IPS_SetName($parentID, $instance['ModuleInfo']['ModuleName']);
            else
                IPS_SetName($parentID, $Name);
            IPS_ConnectInstance($this->InstanceID, $parentID);
        }
    }

    private function SetValueBoolean($Ident, $value)
    {
        $id = $this->GetIDForIdent($Ident);
        if (GetValueBoolean($id) <> $value)
        {
            SetValueBoolean($id, $value);
            return true;
        }
        return false;
    }

    private function SetValueInteger($Ident, $value)
    {
        $id = $this->GetIDForIdent($Ident);
        if (GetValueInteger($id) <> $value)
        {
            SetValueInteger($id, $value);
            return true;
        }
        return false;
    }

    private function SetValueString($Ident, $value)
    {
        $id = $this->GetIDForIdent($Ident);
        if (GetValueString($id) <> $value)
        {
            SetValueString($id, $value);
            return true;
        }
        return false;
    }

    protected function SetStatus($InstanceStatus)
    {
        if ($InstanceStatus <> IPS_GetInstance($this->InstanceID)['InstanceStatus'])
            parent::SetStatus($InstanceStatus);
    }
		
	################## DATAPOINT RECEIVE FROM CHILD
	
	
	public function RequestAction($Ident, $Value)
    {
        $hubId = $this->ReadPropertyInteger('HubID');
		if($Ident == "HarmonyActivityAPI".$hubId)
		{
			$activityId = $Value;
			$this->StartActivityAPI($hubId, $activityId);
			SetValue($this->GetIDForIdent($Ident), $Value);
		}
    }
	
	//Profile
	protected function RegisterProfileIntegerHarmony($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	{
        
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 1);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 1)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
		IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
        
    }
	
	protected function RegisterProfileIntegerHarmonyAss($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Associations)
	{
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } 
		/*
		else {
            //undefiened offset
			$MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        */
        $this->RegisterProfileIntegerHarmony($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits);
        
		//boolean IPS_SetVariableProfileAssociation ( string $ProfilName, float $Wert, string $Name, string $Icon, integer $Farbe )
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }	
		
	//Configuration Form
	public function GetConfigurationForm()
	{
		$alexashsobjid = $this->GetAlexaSmartHomeSkill();
		$formhead = $this->FormHead();
		$formselection = $this->FormSelection();
		$formactions = $this->FormActions();
		$formelementsend = '{ "type": "Label", "label": "__________________________________________________________________________________________________" }';
		$formstatus = $this->FormStatus();
			
		if($alexashsobjid > 0)
		{
			return	'{ '.$formhead.$formselection.$formelementsend.'],'.$formactions.$formstatus.' }';
		}
		else
		{
			return	'{ '.$formhead.$formelementsend.'],'.$formactions.$formstatus.' }';
		}	
	}
				
	protected function FormSelection()
	{			 
		$AlexaSmartHomeSkill = $this->GetAlexaSmartHomeSkill();
		if($AlexaSmartHomeSkill == false)
		{
			$form = '';
		}
		else
		{
			$form = '{ "type": "Label", "label": "Alexa Smart Home Skill is available in IP-Symcon"},
				{ "type": "Label", "label": "Would you like to create links for Alexa for Harmony actions in the SmartHomeSkill instace?" },
				{ "type": "CheckBox", "name": "Alexa", "caption": "Create links for Amazon Echo / Dot" },';
		}	
		return $form;
	}
		
	protected function FormHead()
	{
		$name = $this->ReadPropertyString("Name");
		$firmware = $this->ReadPropertyString("Firmware");
		$harmonyuser = $this->ReadPropertyString("HarmonyUser");
		$hubid = $this->ReadPropertyInteger("HubID");
		
		$form = '"elements":
            [
                { "type": "Label", "label": "Harmony hub name, hub id etc. can not be changed, please use the Harmony configurator to setup the Harmony hubs." },
				{ "type": "Label", "label": "If you want to change the Harmony hub name you can do this in the MyHarmony App from Logitech." },
				{ "type": "Label", "label": "Name of the Logitech Harmony hub:" },
				{ "type": "Label", "label": "Name: '.$name.'" },
				{ "type": "Label", "label": "Logitech Harmony hub id:" },
				{ "type": "Label", "label": "Hub ID: '.$hubid.'" },
				{ "type": "Label", "label": "Firmware from the Logitech Harmony hub:" },
				{ "type": "Label", "label": "Firmware: '.$firmware.'" },
				{ "type": "Label", "label": "Logitech Harmony Hub user:" },
				{ "type": "Label", "label": "Hub User: '.$harmonyuser.'" },';
			
		return $form;
	}
			
	protected function FormActions()
	{
		$form = '"actions":
			[
				{ "type": "Label", "label": "Discover hub information:" },
				{ "type": "Button", "label": "Discover hub", "onClick": "HarmonyHubAPI_DiscoverHub($id);" },
				{ "type": "Label", "label": "Refresh Harmony hub activities:" },
				{ "type": "Button", "label": "Refresh activities", "onClick": "HarmonyHubAPI_SetupHarmony($id);" },
				{ "type": "Label", "label": "Create scripts for Harmony hub activities:" },
				{ "type": "Button", "label": "Create activity scripts", "onClick": "HarmonyHubAPI_SetupActivityScripts($id);" }
			],';
		return  $form;
	}	
		
	protected function FormStatus()
	{
		$form = '"status":
            [
                {
                    "code": 101,
                    "icon": "inactive",
                    "caption": "Creating instance."
                },
				{
                    "code": 102,
                    "icon": "active",
                    "caption": "Harmony Hub accessible."
                },
                {
                    "code": 104,
                    "icon": "inactive",
                    "caption": "interface closed."
                },
                {
                    "code": 202,
                    "icon": "error",
                    "caption": "Harmony Hub IP adress must not empty."
                },
				{
                    "code": 203,
                    "icon": "error",
                    "caption": "No valid IP adress."
                },
                {
                    "code": 204,
                    "icon": "error",
                    "caption": "connection to the Harmony Hub lost."
                },
				{
                    "code": 205,
                    "icon": "error",
                    "caption": "field must not be empty."
                },
				{
                    "code": 206,
                    "icon": "error",
                    "caption": "select category for import."
                },
				{
                    "code": 207,
                    "icon": "error",
                    "caption": "HubID must not be empty."
                }
            ]';
		return $form;
	}
		
	protected function GetAlexaSmartHomeSkill()
	{
		$InstanzenListe = IPS_GetInstanceListByModuleID("{3F0154A4-AC42-464A-9E9A-6818D775EFC4}"); // IQL4SmartHome
		$IQL4SmartHomeID = @$InstanzenListe[0];
		if(!$IQL4SmartHomeID > 0)
		{
			$IQL4SmartHomeID = false;
		}
		return $IQL4SmartHomeID;
	}
	
	protected function CreateAlexaLinks()
		{
			$hubid = $this->ReadPropertyInteger("HubID");
			$IQL4SmartHomeID = $this->GetAlexaSmartHomeSkill();
			//Prüfen ob Kategorie schon existiert
			$AlexaCategoryID = @IPS_GetObjectIDByIdent("AlexaLogitechHarmonyAPI", $IQL4SmartHomeID);
			if ($AlexaCategoryID === false)
				{
					$AlexaCategoryID = IPS_CreateCategory();
					IPS_SetName($AlexaCategoryID, "Logitech Harmony Hub API");
					IPS_SetIdent($AlexaCategoryID, "AlexaLogitechHarmonyAPI");
					IPS_SetInfo($AlexaCategoryID, "Aktivitäten des Logitech Harmony Hubs über die Logitech API schalten");
					IPS_SetParent($AlexaCategoryID, $IQL4SmartHomeID);
				}
			//Prüfen ob Unterkategorie schon existiert
			$SubAlexaCategoryID = @IPS_GetObjectIDByIdent("AlexaLogitechHarmony_Hub_API_".$hubid, $AlexaCategoryID);
			if ($SubAlexaCategoryID === false)
				{
					$SubAlexaCategoryID = IPS_CreateCategory();
					IPS_SetName($SubAlexaCategoryID, "Logitech Harmony Hub (".$hubid.")");
					IPS_SetIdent($SubAlexaCategoryID, "AlexaLogitechHarmony_Hub_API_".$hubid);
					IPS_SetInfo($SubAlexaCategoryID, "Aktivitäten des Logitech Harmony Hubs (".$hubid.") schalten");
					IPS_SetParent($SubAlexaCategoryID, $AlexaCategoryID);
				}
			//Prüfen ob Link schon vorhanden
			$linkobjids = $this->GetLinkObjIDs();
			
			foreach ($linkobjids as $linkobjid)
			{
				$objectinfo = IPS_GetObject($linkobjid);
				$ident = $objectinfo["ObjectIdent"];
				$name = $objectinfo["ObjectName"];
				$alexalinkname = substr($name, 0, -7);
				$LinkID = @IPS_GetObjectIDByIdent("AlexaLink_".$ident, $SubAlexaCategoryID);
				if ($LinkID === false)
				{
					// Anlegen eines neuen Links für die Aktivität
					$LinkID = IPS_CreateLink();             // Link anlegen
					IPS_SetIdent($LinkID, "AlexaLink_".$ident); //ident
					IPS_SetLinkTargetID($LinkID, $linkobjid);    // Link verknüpfen
					IPS_SetInfo($LinkID, "Harmony Hub Aktivität ".$alexalinkname);
					IPS_SetParent($LinkID, $SubAlexaCategoryID); // Link einsortieren
					IPS_SetName($LinkID, $alexalinkname); // Link benennen					
				}	
			
			}
		}
			
	protected function DeleteAlexaLinks()
		{
			$hubid = $this->ReadPropertyInteger("HubID");
			$IQL4SmartHomeID = $this->GetAlexaSmartHomeSkill();
			$AlexaCategoryID = @IPS_GetObjectIDByIdent("AlexaLogitechHarmonyAPI", $IQL4SmartHomeID);
			$SubAlexaCategoryID = @IPS_GetObjectIDByIdent("AlexaLogitechHarmony_Hub_API_".$hubid, $AlexaCategoryID);
			$linkobjids = $this->GetLinkObjIDs();
			
			foreach ($linkobjids as $linkobjid)
			{
				$objectinfo = IPS_GetObject($linkobjid);
				$ident = $objectinfo["ObjectIdent"];
				$name = $objectinfo["ObjectName"];
				$LinkID = @IPS_GetObjectIDByIdent("AlexaLink_".$ident, $SubAlexaCategoryID);
				if($LinkID > 0)
				{
					IPS_DeleteLink($LinkID);
				}
			}
						
			
			if($SubAlexaCategoryID > 0)
			{
				$catempty = $this->ScreenCategory($SubAlexaCategoryID);
				if($catempty == true)
				{
					IPS_DeleteCategory($SubAlexaCategoryID);
				}
			}
			
			if($AlexaCategoryID > 0)
			{
				$catempty = $this->ScreenCategory($AlexaCategoryID);
				if($catempty == true)
				{
					IPS_DeleteCategory($AlexaCategoryID);
				}
			}
		}
	
	protected function GetLinkObjIDs()
	{
		$linkobjids = false;
		$hubid = $this->ReadPropertyInteger("HubID");
		$ConfiguratorID = $this->GetConfiguratorID();
		$ImportCategoryID = IPS_GetProperty($ConfiguratorID, "ImportCategoryID");
		$HubCategoryID = @IPS_GetObjectIDByIdent("CatLogitechHubAPI_".$hubid, $ImportCategoryID);
		$MainCatID = @IPS_GetObjectIDByIdent("LogitechAPIActivitiesScripts_".$hubid, $HubCategoryID);
		$linkobjids = IPS_GetChildrenIDs($MainCatID);
		$linktoogleids = array();
		foreach ($linkobjids as $linkobjid)
			{
				$objectinfo = IPS_GetObject($linkobjid);
				$ident = $objectinfo["ObjectIdent"];
				$name = $objectinfo["ObjectName"];
				$identtoggle = substr($ident, -6); 
				if($identtoggle == "Toggle")
				{
					$linktoogleids[] = $linkobjid;
				}
			}
		return $linktoogleids;
	}

	protected function ScreenCategory($CategoryID)
	{
		$catempty = IPS_GetChildrenIDs($CategoryID);
		if(empty($catempty))
		{
			$catempty = true;
		}
		else
		{
			$catempty = false;
		}	
		return $catempty;
	}		
	
	
}

?>