# IPSymconHarmonyAPI

Module for IP Symcon from version 4.2. Allows you to communicate with a Logitech Harmony Hub through the Logitech API and perform actions via the Logitech Harmony Hub.

The module uses the Logitech API to control the Logitech hub. For this purpose, a link is created between the Logitech account and IP Symcon to authorise IP Symcon for the
Control of the Harmony Hub. You can then start and stop actions via IP-Symcon on the Logitech Harmony Hub.
The Logitech API will probably be extended in the future by further functions, these will be supplemented with the release of Logitech suksessiv in the module.


## Documentation

**Table of Contents**

1. [Features] (# 1 function)
2. [Requirements] (# 2 requirements)
3. [Installation] (# 3-installation)
4. [Function Reference] (# 4 function reference)
5. [Notes] (# 5-note)
6. [Appendix] (# 6-appendix)

## 1. Functional scope

With the help of the Logitech Harmony Hub you can remote control devices which can be controlled via IR remote controls or even newer devices like FireTV and AppleTV.
For more information about controllable devices, go to [Harmony Ultimate Hub](http://www.logitech.com/de-de/product/harmony-ultimate-hub "Harmony Ultimate Hub").

Using the module, Harmony Hubs associated with a Logitech account can be imported into IP Symcon. You can then start and stop actions from IP-Symcon on the Logitech hub.
   

## 2. Prerequisites

 - IPS 4.2
 - enabled IP Symcon Connect service
 - Logitech Harmony Hub is linked to the same Logitech account, which is also used to authenticate IP Symcon

## 3. Installation

### a. Download and installation of the module

Open the 'Modules' instance below core instances in the object tree of IP-Symcon (> = ver. 4.2) and press the _add_ button. Enter the following URL in the field and confirm with _OK_:

    `git://github.com/Wolbolar/IPSymconLogitechHarmony.git` 


### b. Installation in IP-Symcon

Then add an instance (_**CTRL+1**_) to IP-Symcon 4.2 under _Configurator Instances_.

Now enter _Logitech_ as manufacturer and select _Logitech Harmony API Konfigurator_.

![Modulauswahl](img/ModulInstallationAuswahl.png?raw=true "Modulauswahl")

In the opening window, proceed as described below:

![Configuratorform1](img/KonfiguratorForm1.png?raw=true "Configuratorform 1")
![Configuratorform2](img/KonfiguratorForm2.png?raw=true "Configuratorform 2")

####1. step

Create a category in the IP-Symcon object tree (_**CTRL+0**_) under which the activities are to be created. Select the created category in the configuration form, which is displayed in the Webfront and press _**Apply**_.

####2. step

Press the _** Register **_ button in the action section of the configuration form. This opens a browser window that automatically forwards Logitech's authentication site to authenticate IP-Symcon to use the Logitech Harmony Hub.
Here you have the choice to log on with your Facebook user name, Google user name or _MyHarmony user name_ (email).
In the example, we continue with the _MyHarmony user name_ and enter the _email address_ and _password_, which we used to login to _MyHarmony_.

![Logitech Anmeldebildschirm](img/logitechformLogin.png?raw=true "Logitech Anmeldebildschirm")

Log on to the Logitech website with _Harmony Username_ and _Harmony Password_.

![Logitech Anmeldescreen 3](img/logitechemailform.png?raw=true "Logitech Anmeldescreen")

If the input was correct and a link to IP-Symcon could be created, the message _**Logitech MyHarmony successfully connected!**_ appears.

![OAuth2 Confirmation](img/logitechOAuthConnected.png?raw=true "OAuth2 Confirmation")

Now we close the browser window and switch back to the configuration form in IP-Symcon.

####3. step Retrieve information

Now press the _**Get Harmony Hub info**_ and wait a short moment until the variables _**Harmony Discover**_ and _**Harmony Activities**_ have been described. The variables _**Harmony Discover**_ and _**Harmony Activities**_ 
are located below the _Logitech Harmony API Configurator_. If the variables _**Harmony Discover**_ and _**Harmony Activities**_ have been updated, continue with step 4.


####4. step

After all settings have been set press _**Create Harmony hubs**_ . A Logitech I/O instance is now created by the configurator for all Logitech Harmony Hubs connected to the MyHarmony account in IP Symcon.
The Logitech API currently supports only switching actions. There is automatically created a link for the Webfront, which can then be moved to the visualization to switch action from the Webfront.
Optionally, a script can be created for each action. To do this, you need to press the button _**Create activity scripts**_ in the module. A script is then created below the selected category for each existing Harmony Hub action.


In the Webfront of IP-Symcon it looks like:

![Webfront](img/activitywebfront.png?raw=true "Webfront")

You can then start and stop actions via the Webfront or the scripts.

## 4. Functional Reference

### Logitech Harmony Hub:
 
### Harmony Hub
Logitech Harmony Hub activities can be performed.
The current activity of the Logitech Harmony Hub is displayed in the Harmony Activity variable and can be displayed in the Webfront.
 
If the activity is to be updated via functions or via a script, the following functions are to be used:
 
#### Reads all available information from the Logitech Harmony Hubs associated with the Logitech account.
```php
HarmonyHubAPI_Discover(int $InstanceID) 
```  
Parameters _$InstanceID_ Object ID of the Harmony Hub I/O

#### Reads all available information from a specific Logitech Harmony Hub.
```php
HarmonyHubAPI_DiscoverHub(int $InstanceID, int $hubId) 
``` 
Parameters _$InstanceID_ Object ID of the Harmony Hub I/O

Parameters _$hubId_ Identity number of the Logitech Harmony Hub, this is found in the instance of the Logitech hub

#### Reads all available activities of the Logitech Harmony Hubs associated with the Logitech account.
```php
HarmonyHubAPI_GetActivities(integer $InstanceID) 
```  
Parameters _$InstanceID_ Object ID of the Harmony Hub I/O

#### Reads all available activities of a specific Logitech Harmony Hub.
```php
HarmonyHubAPI_GetHubActivities(integer $InstanceID, int $hubId) 
```  
Parameters _$InstanceID_ Object ID of the Harmony Hub I/O
  
Parameters _$hubId_ Identity number of the Logitech Harmony Hub, this is found in the instance of the Logitech hub

#### Reads the status of Logitech Harmony Hubs associated with the Logitech account.
```php
HarmonyHubAPI_GetStateDigest(integer $InstanceID) 
``` 
Parameters _$InstanceID_ Object ID of the Harmony Hub I/O

#### Reads the status of a specific Logitech Harmony Hub.
```php
HarmonyHubAPI_GetHubStateDigest(integer $InstanceID, int $hubId) 
```
Parameters _$InstanceID_ Object ID of the Harmony Hub I/O
  
Parameters _$hubId_ Identity number of the Logitech Harmony Hub, this is found in the instance of the Logitech hub

#### Ends all current AV actions and sets all AV devices to be controlled by the Logitech Harmony Hubs associated with the Logitech account.
```php
HarmonyHubAPI_PowerOffAV(integer $InstanceID) 
``` 
Parameters _$InstanceID_ Object ID of the Harmony Hub I/O

#### Ends all current AV actions and turns off all AV devices controlled by the specific Logitech Harmony Hub.
```php
HarmonyHubAPI_PowerOffHubAV(integer $InstanceID, int $hubId) 
```  
Parameters _$InstanceID_ Object ID of the Harmony Hub I/O

Parameters _$hubId_ Identity number of the Logitech Harmony Hub, this is found in the instance of the Logitech hub

#### Enables the activity of a specific Logitech Harmony Hub. If it is an AV activity, the current AV activity is terminated. If a non-AV activity is started, this does not affect the running AV activity.
```php
HarmonyHubAPI_StartActivityAPI(integer $InstanceID, int $hubId, int $activityId) 
``` 
Parameters _$InstanceID_ Object ID of the Harmony Hub I/O
  
Parameters _$hubId_ Identity number of the Logitech Harmony Hub, this is found in the instance of the Logitech hub

Parameters _$activityId_ Identity number of the Logitech Harmony Hub activity, this can be found in the instance of the Logitech hub

#### Turns off the activity of a specific Logitech Harmony Hub.
```php
HarmonyHubAPI_EndActivity(integer $InstanceID, int $hubId, int $activityId) 
``` 
Parameters _$InstanceID_ Object ID of the Harmony Hub I/O
  
Parameters _$hubId_ Identity number of the Logitech Harmony Hub, this is found in the instance of the Logitech hub

Parameters _$activityId_ Identity number of the Logitech Harmony Hub activity, this can be found in the instance of the Logitech hub

## 5. Configuration:

### Logitech Harmony Hub Configurator:


| Property         | Type    | Default value | function                                            |
| :--------------: | :-----: | :-----------: | :-------------------------------------------------: |
| ImportCategoryID | integer |               | Object ID of the import category                    |


### Logitech Harmony Hub:

| Property         | Type    | Default value | function                                            |
| :--------------: | :-----: | :-----------: | :-------------------------------------------------: |
| Name             | string  |               | Name of the Logitech Harmony Hub                    |
| Firmware         | string  |               | Firmware version of the Logitech Harmony Hub        |
| HarmonyUser      | string  |               | Email address to log in to MyHarmony                |
| HubID            | integer |               | Hub ID of the Logitech Harmony Hub                  |


## 6. Appendix

### GUIDs and data exchange:

#### Logitech Harmony Hub Configurator:

GUID: `{37D1B484-B5A5-4C0D-AE53-0DD022923248}` 


#### Logitech Harmony Hub:

GUID: `{32803D90-824E-4CDE-987E-107CEB48D441}` 