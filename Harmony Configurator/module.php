<?

/**
 * HarmonyConfigurator Klasse für die einfache Erstellung von IPS-Instanzen in IPS.
 * Erweitert IPSModule.
 */
class HarmonyAPIConfigurator extends IPSModule
{
	//This one needs to be available on our OAuth client backend.
	//Please contact us to register for an identifier: https://www.symcon.de/kontakt/#OAuth
	private $oauthIdentifer = "logitech";
	
    /**
     * Interne Funktion des SDK.
     *
     * @access public
     */
    public function Create()
    {
        //Never delete this line!
		parent::Create();
		$this->RegisterPropertyInteger("ImportCategoryID", 0);
		$this->RegisterPropertyString("Token", "");
    }

    /**
     * Interne Funktion des SDK.
     * 
     * @access public
     */
    public function ApplyChanges()
    {
        //Never delete this line!
		parent::ApplyChanges();
		$this->RegisterOAuth($this->oauthIdentifer);
		$this->RegisterVariableString("HarmonyDiscover", "Harmony Discover", "", 1);
		IPS_SetHidden($this->GetIDForIdent('HarmonyDiscover'), true);
		$this->RegisterVariableString("HarmonyActivities", "Harmony Activities", "", 2);
		IPS_SetHidden($this->GetIDForIdent('HarmonyActivities'), true);
		$this->ValidateConfiguration();
    }
	
	private function ValidateConfiguration()
	{		
		$discoverjson = GetValue($this->GetIDForIdent("HarmonyDiscover"));
		
		$counthubs = 0;
		if(!$discoverjson == "")
		{
			$discover = json_decode($discoverjson, true);
			$counthubs = count($discover);
		}
					
		//Import Kategorie für HarmonyHub Geräte
		$ImportCategoryID = $this->ReadPropertyInteger('ImportCategoryID');
		if ( $ImportCategoryID === 0)
			{
				// Status Error Kategorie zum Import auswählen
				$this->SetStatus(206);
			}
		elseif ( $ImportCategoryID != 0)	
			{
				// Status Aktiv
				$this->SetStatus(102);
			}
	}
   
    /**
     * Liefert die ID des Logitech IO.
     * 
     * @access private
     * @return bool|int FALSE wenn kein Splitter vorhanden, sonst die ID des IO.
     */
    private function GetHarmonyIO($hubid)
    {
        $HarmonyIOID = false;
		$HarmonyIOID = @IPS_GetObjectIDByIdent($hubid."_Logitech_API", 0);
        return $HarmonyIOID;
    }

    /**
     * Erzeugt einen Logitech Hub IO anhand der übergebenen Daten.
     * 
     * @access public
     * @param string $ModuleID GUID der zu erzeugenden Instanz.
     */
	protected function CreateIOInstance($hubid, $user, $firmware, $name, $ImportCategoryID)
    {
        $HarmonyHubIOID = $this->GetHarmonyIO($hubid);
		
        if($HarmonyHubIOID === false)
		{
			$HarmonyHubIOID = IPS_CreateInstance("{32803D90-824E-4CDE-987E-107CEB48D441}");
			IPS_SetName($HarmonyHubIOID, "Logitech ".$name); // Instanz benennen
			IPS_SetProperty($HarmonyHubIOID, "Open", true); //Aktiv setzten.
			IPS_SetProperty($HarmonyHubIOID, "Name", $name); //Name setzten.
			IPS_SetProperty($HarmonyHubIOID, "Firmware", $firmware); //HarmonyFirmware setzten.
			IPS_SetProperty($HarmonyHubIOID, "HarmonyUser", $user); //HarmonyUser setzten.
			IPS_SetProperty($HarmonyHubIOID, "HubID", $hubid); //Harmony HubID setzten.
			IPS_SetIdent($HarmonyHubIOID, $hubid."_Logitech_API");
			IPS_SetInfo($HarmonyHubIOID, $hubid);
				
			$this->SendDebug("Logitech Hub","Instanz erstellt: Logitech".$name." (".$HarmonyHubIOID.")",0);
			IPS_ApplyChanges($HarmonyHubIOID); //Neue Konfiguration übernehmen
		}

        return $HarmonyHubIOID;
    }
		
	private function RegisterOAuth($WebOAuth)
	{
		$ids = IPS_GetInstanceListByModuleID("{F99BF07D-CECA-438B-A497-E4B55F139D37}");
		if(sizeof($ids) > 0)
			{
			$clientIDs = json_decode(IPS_GetProperty($ids[0], "ClientIDs"), true);
			$found = false;
			foreach($clientIDs as $index => $clientID)
				{
					if($clientID['ClientID'] == $WebOAuth)
						{
						if($clientID['TargetID'] == $this->InstanceID)
							return;
						$clientIDs[$index]['TargetID'] = $this->InstanceID;
						$found = true;
						}
				}
				if(!$found)
					{
					$clientIDs[] = Array("ClientID" => $WebOAuth, "TargetID" => $this->InstanceID);
					}
				IPS_SetProperty($ids[0], "ClientIDs", json_encode($clientIDs));
				IPS_ApplyChanges($ids[0]);
			}
	}
	
	/**
	* This function will be called by the register button on the property page!
	*/
	public function Register()
	{
			
		//Return everything which will open the browser
		return "https://oauth.ipmagic.de/authorize/".$this->oauthIdentifer."?username=".urlencode(IPS_GetLicensee());
			
	}
	
	private function FetchBearerToken($code)
	{
		//Exchange our Authentication Code for a permanent Baerer Token
		$options = array(
			'http' => array(
				'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
				'method'  => "POST",
				'content' => http_build_query(Array("code" => $code))
				)
			);
		$context = stream_context_create($options);
		$result = file_get_contents("https://oauth.ipmagic.de/access_token/".$this->oauthIdentifer, false, $context);
		$data = json_decode($result);
			
		if(!isset($data->token_type) || $data->token_type != "Bearer")
			{
				die("Bearer Token expected");
			}
			
		return $data->access_token;
	}
	
	/**
	* This function will be called by the OAuth control. Visibility should be protected!
	*/
	protected function ProcessOAuthData()
	{

			if(!isset($_GET['code'])) {
				die("Authorization Code expected");
			}
			
			$token = $this->FetchBearerToken($_GET['code']);
			
			IPS_SetProperty($this->InstanceID, "Token", $token);
			IPS_ApplyChanges($this->InstanceID);

	}
		
	private function FetchData($url, $type)
	{
			
			if($this->ReadPropertyString("Token") == "") {
				die("No token found. Please register for a token first.");
			}
			
			if($type == "POST")
			{
				$postdata = http_build_query(
				array(
					'var1' => 'some content',
					'var2' => 'doh'
					)
				);

			$opts = array('http' =>
				array(
					'method'  => 'POST',
					'header'  => 'Content-type: application/x-www-form-urlencoded',
					'content' => $postdata
					)
				);
			}	
			elseif($type == "GET")
			{
				$opts = array(
				  'http'=>array(
					'method'=>"GET",
					'header'=>"Authorization: Bearer " . $this->ReadPropertyString("Token") . "\r\n"
				  )
				);
			}
			
			$context = stream_context_create($opts);
			
			return file_get_contents($url, false, $context);
			
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
		$discover = json_decode($discoverjson, true);
		$hubs = $discover["hubs"];
		$discoverresponse = array();
		foreach ($hubs as $key => $hub)
		{
			IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub ID: ".$key); // ID Harmony Hub
			$status = $hubs[$key]["status"];
			if ($status == 504)
			{
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is offline"); 
				$message = $hubs[$key]["message"];
				$name = $hubs[$key]["name"];
				IPS_LogMessage("Logitech Harmony Hub:", "message from ".$name.": ".$message);
				$response[] = array("status" => $status, "message" => $message, "name" => $name);
			}
			elseif ($status == 200) //success
			{
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is online"); 
				$message = $hubs[$key]["message"];
				$response = $hubs[$key]["response"];
				$code = $response["code"];
				$msg = $response["msg"];
				$data = $response["data"];
				$firmware = $data["fw"];
				$name = $data["name"];
				$user = $data["user"];
				$discoverresponse[] = array("key" => $key, "status" => $status, "message" => $message, "code" => $code, "msg" => $msg, "firmware" => $firmware, "name" => $name, "user" => $user);
			}
		}
		$discoverresponsejson = json_encode($discoverresponse);
		SetValue($this->GetIDForIdent("HarmonyDiscover"), $discoverresponsejson);
		return $discoverresponse;
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
		$discoverhub = json_decode($discoverhubjson);
		$code = $discoverhub->code;
		$msg = $discoverhub->msg;
		$data = $discoverhub->data;
		$firmware = $data->fw;
		$name = $data->name;
		$user = $data->user;
		$discoverhubresponse = array("code" => $code, "msg" => $msg, "firmware" => $firmware, "name" => $name, "user" => $user);
		return $discoverhubresponse;
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
		SetValue($this->GetIDForIdent("HarmonyActivities"), $getactivitiesjson);
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
		/*
		$gethubactivities = json_decode($gethubactivitiesjson);
		$code = $gethubactivities->code;
		$msg =  $gethubactivities->msg;
		if ($code == 504)
			{
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is offline. Code ".$code.", Message: ".$msg);
				$response = array("code" => $code, "msg" => $msg);
			}
			elseif ($code == 200) //success
			{
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is online. Code ".$code.", Message: ".$msg);
				$data =  $gethubactivities->data;
				$activities = $data->activities;
				$hubactivities = array();
				foreach ($activities as $key => $activity)
						{
								$activityID = $key;
								$type = $activities->$activityID->type;
								$name = $activities->$activityID->name;
								$hubactivities[] = array("activityid" => $activityID, "name" => $name, "type" => $type);
						}
				$response = array("code" => $code, "msg" => $msg, "activities" => $hubactivities);		
			 }
		return $response; */
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
	public function  GetHubStateDigest(int $hubId)
	{
		$statehubdigestcommand = "/hub/".$hubId."/state";
		$type = "GET";
		$statehubdigestjson =  $this->SendHarmonyAPI($statehubdigestcommand, $type);
		/*
		$statehubdigest = json_decode($statehubdigestjson);
		$code = $statehubdigest->code;
		$msg = $statehubdigest->msg;
			if ($code == 504)
			{
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is offline. Message: ".$msg);
				$response = array("status" => $code, "message" => $msg);	 
			}
			elseif ($code == 200) //success
			{
				IPS_LogMessage("Logitech Harmony Hub:", "Harmony Hub is online. Message ".$msg." mit Status ".$code);
				$data = $statehubdigest->data;
				$version = $data->version;
				$configVersion = $data->configVersion;
				$currentAvActivity = $data->currentAvActivity;
				$currentActivities = $data->currentActivities;
				$syncStatus = $data->syncStatus;
				$activityStatus = $data->activityStatus;
				$response = array("status" => $code, "message" => $msg, "version" => $version, "configversion" => $configVersion, "currentAvActivity" => $currentAvActivity, "currentActivities" => $currentActivities, "syncStatus" => $syncStatus, "activityStatus" => $activityStatus );	
			}
		return $response; */
		return $statehubdigestjson;
	}
	
	/* Send to Logitech API
	* 
	*/
	public function SendHarmonyAPI(string $command, string $type)
	{
		$authorization = "Authorization: Bearer ".$this->ReadPropertyString("Token");
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
		curl_close ($ch);
		return $result;
	}
	
	/* Setup Harmony
	* Create Logitech Harmony Hub I/O Instances.
	*
	*/
	public function SetupHarmony()
	{
		$this->RefreshHarmonyHubIO();
		$ImportCategoryID = $this->ReadPropertyInteger('ImportCategoryID');
		$discoverjson = GetValue($this->GetIDForIdent("HarmonyDiscover"));
		if(!$discoverjson == "")
		{
			$discover = json_decode($discoverjson, true);
			$i = 0;
			foreach($discover as $key => $hub) 
			{
				$i++;
				$name = $hub["name"];
				$firmware = $hub["firmware"];
				$user = $hub["user"];
				$hubid = $hub["key"];			
				$this->CreateIOInstance($hubid, $user, $firmware, $name, $ImportCategoryID);
			}	
		}
	}
	
	/* Setup Harmony Activity Scripts
	* Create Logitech Harmony Hub Activity Scripts.
	*
	*/
	public function SetupHarmonyScripts()
	{
		$hubobjids = $this->RefreshHarmonyHubIO();
		foreach ($hubobjids as $HarmonyHubIOID)
			{
				HarmonyHubAPI_SetupActivityScripts($HarmonyHubIOID);
			}
	}
	
	
	/* Get Info Harmony Hub
	* Create Logitech Harmony Hub Instances and devices.
	*
	*/
	public function GetInfo()
	{
		$this->Discover();
		$this->GetActivities();
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
	
	/* RefreshHarmonyHubIO
	* Set ident and name for existing Logitech Harmony Hub IO and update name and firmware variables.
	*
	*/
	protected function RefreshHarmonyHubIO()
	{
		$discover = $this->Discover(); 
		$InstanzenListe = @IPS_GetInstanceListByModuleID("{32803D90-824E-4CDE-987E-107CEB48D441}"); // HarmonyAPIIO
		if($InstanzenListe)
			{
				foreach ($InstanzenListe as $HarmonyHubIOID)
				{
					$hubname = IPS_GetProperty($HarmonyHubIOID, "Name");
					$firmware = IPS_GetProperty($HarmonyHubIOID, "Firmware");
					foreach($discover as $key => $hub)
					{
						
						$hubid = @IPS_GetObjectIDByIdent($key."_Logitech_API", 0);
						if($hubid > 0)
						{
							IPS_SetInfo($HarmonyHubIOID, $key);
							IPS_SetProperty($HarmonyHubIOID, "Name", $hub["name"]);
							IPS_SetProperty($HarmonyHubIOID, "Firmware", $hub["firmware"]);
							IPS_ApplyChanges($HarmonyHubIOID);
							IPS_SetName($HarmonyHubIOID, "Logitech ".$hub["name"]);
						}
					}
				}
			}
		return $InstanzenListe;
	}
	
	protected function GetParent($objid)
    {
        $instance = IPS_GetInstance($objid);
        return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;
    }
	
    //Configuration Form
		public function GetConfigurationForm()
		{
			$formhead = $this->FormHead();
			$formselection = $this->FormSelection();
			$formstatus = $this->FormStatus();
			$formactions = $this->FormActions();
			$formelementsend = '{ "type": "Label", "label": "__________________________________________________________________________________________________" }';
			
			return	'{ '.$formhead.$formselection.$formelementsend.'],'.$formactions.$formstatus.' }';
		}
		
		protected function FormSelection()
		{			 
			$form = '';
			return $form;
		}
		
		protected function FormHubs()
		{
			$discoverjson = GetValue($this->GetIDForIdent("HarmonyDiscover"));
			$discover = json_decode($discoverjson, true);
			$count = count($discover);
			$form = '';
			if($discover == "")
			{
				$form = '';
			}
			else
			{
				$i = 0;
				foreach($discover as $key => $hub)
				{
					$name = $hub["name"];
					$hubid = $key;
					$i++;
					$form .= '{ "type": "Label", "label": "IP adress from the Harmony Hub" },
					{ "type": "Label", "label": "'.$name.'" },
					{
						"name": "Host'.$i.'",
						"type": "ValidationTextBox",
						"caption": "IP adress"
					},';
				}
			}
			return $form;
		}
		
		protected function FormHead()
		{
			$form = '"elements":
            [
				{ "type": "Label", "label": "Logitech Harmony Hub Configurator:" },
				{ "type": "Label", "label": "1. Step:" },
				{ "type": "Label", "label": "Select a category (see below) for the creation of a link for the Webfront for the Logitech Harmony Hub activities and then push \"Apply\"" },
				{ "type": "Label", "label": "category for Logitech Harmony Hub activities" },
				{ "type": "SelectCategory", "name": "ImportCategoryID", "caption": "Harmony Hub category" },
				{ "type": "Label", "label": "2. Step:" },
				{ "type": "Label", "label": "Push \"Register\" in the action part of this configuration form." },
				{ "type": "Label", "label": "At the webpage from Logitech log in with your Harmony username and your Harmony password." },
				{ "type": "Label", "label": "If the connection to IP-Symcon was successfull you get the message: \"Logitech MyHarmony successfully connected!\". Close the browser window." },
				{ "type": "Label", "label": "Return to this configuration form." },
				{ "type": "Label", "label": "3. Step:" },
				{ "type": "Label", "label": "Push \"Get Harmony Hub Info\"" },
				{ "type": "Label", "label": "When the Variables \"Harmony Discover\" and \"Harmony Activities\" are updated continue with step 4."},
				{ "type": "Label", "label": "4. Step:" },
				{ "type": "Label", "label": "After all settings in the configuration form are set push \"Create Harmony Hubs\"" },
				{ "type": "Label", "label": "If you need scripts for the Harmony activities press optional \"Create activity scripts\"" },
				';
			
			return $form;
		}
		
		protected function FormActions()
		{
			$form = '"actions":
			[
				{ "type": "Label", "label": "1. Register with your Logitech Harmony username und Logitech Harmony password:" },
				{ "type": "Button", "label": "Register", "onClick": "echo HarmonyAPICONF_Register($id);" },
				{ "type": "Label", "label": "2. Get information about the Logitech Harmony hubs:" },
				{ "type": "Button", "label": "Get Harmony Hub info", "onClick": "HarmonyAPICONF_GetInfo($id);" },
				{ "type": "Label", "label": "3. Create Logitech Harmony hubs:" },
				{ "type": "Button", "label": "Create Harmony hubs", "onClick": "HarmonyAPICONF_SetupHarmony($id);" },
				{ "type": "Label", "label": "4. Create Logitech Harmony hubs activity scripts (optional):" },
				{ "type": "Button", "label": "Create activity scripts", "onClick": "HarmonyAPICONF_SetupHarmonyScripts($id);" }
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
                    "caption": "Harmony Hub Configurator ready."
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
                }
            ]';
			return $form;
		}
		
		protected function GetIPSVersion ()
		{
			$ipsversion = IPS_GetKernelVersion ( );
			$ipsversion = explode( ".", $ipsversion);
			$ipsmajor = intval($ipsversion[0]);
			$ipsminor = intval($ipsversion[1]);
			if($ipsminor < 10)
			{
			$ipsversion = 1;
			}
			else
			{
			$ipsversion = 2;
			}
			return $ipsversion;
		}

################## DUMMYS / WORKAROUNDS - protected

    /**
     * Prüft den Parent auf vorhandensein und Status.
     * 
     * @return bool True wenn Parent vorhanden und in Status 102, sonst false.
     */
    protected function HasActiveParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);
        if ($instance['ConnectionID'] > 0)
        {
            $parent = IPS_GetInstance($instance['ConnectionID']);
            if ($parent['InstanceStatus'] == 102)
                return true;
        }
        return false;
    }

}

?>