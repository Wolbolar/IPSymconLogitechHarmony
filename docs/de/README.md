# IPSymconHarmonyAPI

Modul für IP-Symcon ab Version 4.2. Ermöglicht die Kommunikation mit einem Logitech Harmony Hub über die Logitech API und das Ausführen von Aktionen über den Logitech Harmony Hub.

Das Modul nutzt die Logitech API zur Ansteuerung des Logitech Hub. Hierzu wird zunächst eine Verknüpfung zwischen dem Logitech Konto und IP-Symcon hergestellt um IP-Symcon für die 
Steuerung zu authorisieren. Anschließend kann über IP-Symcon auf dem Logitech Harmony Hub Aktionen gestartet und gestoppt werden.
Die Logitech API soll wohl in Zukunft um weitere Funktionen erweitert werden, diese werden dann mit der Freigabe von Logitech suksessiv im Modul ergänzt werden.


## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)  
5. [Anhang](#5-anhang)  

## 1. Funktionsumfang

Mit Hilfe des Logitech Harmony Hub sind Geräte bedienbar, die sonst über IR-Fernbedienungen steuerbar sind oder auch neuere Geräte wie FireTV und AppleTV.
Nähere Informationen zu ansteuerbaren Geräten über den Logitech Harmony Hub unter [Harmony Ultimate Hub](http://www.logitech.com/de-de/product/harmony-ultimate-hub "Harmony Ultimate Hub")

Mit Hilfe des Moduls können Harmony Hubs die mit einem Logitech Konto verknüpft sind in IP-Symcon importiert werden. Es lassen sich dann Aktionen von IP-Symcon auf dem Logitech Hub starten und beenden.
   

## 2. Voraussetzungen

 - IPS 4.2
 - aktivierter IP-Symcon Connect Dienst
 - Logitech Harmony Hub ist mit dem gleichen Logitech Account verknüpft der auch für die Authentizifierung von IP-Symcon verwendet wird

## 3. Installation

### a. Laden des Moduls

Die Instanz 'Modules' unterhalb von Kerninstanzen im Objektbaum von IP-Symcon (>=Ver. 4.2) öffnen und den Button _Hinzufügen_ drücken. Im Feld die folgende URL eintragen und mit _OK_ bestätigen:
	
    `git://github.com/Wolbolar/IPSymconLogitechHarmony.git`  

### b. Einrichtung in IPS

Danach IP-Symcon 4.2 unter _Konfigurator Instanzen_ eine Instanz hinzufügen (_**CTRL+1**_).

Hier nun als Hersteller _Logitech_ eingeben und _Logitech Harmony API Konfigurator_ auswählen.

![Modulauswahl](img/ModulInstallationAuswahl.png?raw=true "Modulauswahl")

Im dem sich öffnenden Fenster zunächst bei Erstinstallation wie folgt vorgehen:

![Configuratorform1](img/KonfiguratorForm1.png?raw=true "Configuratorform 1")
![Configuratorform2](img/KonfiguratorForm2.png?raw=true "Configuratorform 2")

#### 1. Kategorie anlegen

Eine Kategorie im IP-Symcon Objektbaum anlegen (_**CTRL+0**_) unter der die Aktivitäten angelegt werden sollen. Die erstellte Kategorie im Konfigurationsformular auswählen, diese dient der Anzeige im Webfront und _**Übernehmen**_ drücken.

#### 2. Logitech Registrierung

Den Button _**Registrieren**_ in der Aktionsektion des Konfigurationsformulares drücken. Es öffnet sich ein Browserfester das automatisch zur Authentifizierungs Website von Logitech weiterleitet um IP-Symcon zur Nutzung des Logitech Harmony Hubs zu authentifizieren. 
Hier hat man die Auswahl sich mit seinem Facebook Benutzernamen, Google Benutzernamen oder dem _MyHarmony Benutzernamen_ (Email) anzumelden.
In dem Beispiel fahren wir mit dem _MyHarmony Benutzernamen_ fort und geben hier die _Email Adresse_ und das _Passwort_ an, was wir zur Anmeldung bei _MyHarmony_ benutzten.

![Logitech Anmeldebildschirm](img/logitechformLogin.png?raw=true "Logitech Anmeldebildschirm")

Auf der Webseite von Logitech nun mit dem _Harmony Benutzernamen_ und dem _Harmony Passwort_ anmelden.

![Logitech Anmeldescreen 3](img/logitechemailform.png?raw=true "Logitech Anmeldescreen")

Wenn die Eingabe korrekt war und eine Verknüpfung zu IP-Symcon erstellt werden konnte erscheint die Meldung _**Logitech MyHarmony successfully connected!**_.

![OAuth2 Confirmation](img/logitechOAuthConnected.png?raw=true "OAuth2 Confirmation")

Nun schließen wir das Browser Fenster und wechseln zurück zum Konfigurationsformular in IP-Symcon.

#### 3. Information abholen

Jetzt drücken wir die Taste _**Harmony Hub Info holen**_ und warten einen kurzen Moment bis die Variablen _**Harmony Discover**_ und _**Harmony Activities**_ beschrieben worden sind. Die Variablen _**Harmony Discover**_ und _**Harmony Activities**_
befinden sich unterhalb des _Logitech Harmony API Konfigurator_. Wenn die Variablen _**Harmony Discover**_ und _**Harmony Activities**_ aktualisiert wurden kann mit Schritt 4 fortgefahren werden.

#### 4. Setup Logitech Harmony Hubs

Nachdem alle Einstellungen gesetzt wurden _**Harmony Hubs anlegen**_ drücken. Es werden nun vom Konfigurator jeweils eine Logitech I/O Instanz für alle mit dem MyHarmony Account verknüpften Logitech Harmony Hubs in IP-Symcon angelegt.
Die Logitech API unterstützt zur Zeit nur das schalten von Aktionen. Es wird für den Webfront automatisch ein Link erstellt dieser kann dann in die Visulalisierung verschoben werden um Aktion aus dem Webfront zu schalten.
Optional kann noch für jede Aktion ein Skript angelegt werden, dazu ist der Button _**Aktivitäten Scripte anlegen**_ im Modul zu drücken. Es werden dann unterhalb der gewählten Kategorie für jede existiernde Aktion des Harmony Hub ein Skript angelegt. 


Im Webfront von IP-Symcon sieht das z.B. dann so aus:

![Webfront](img/activitywebfront.png?raw=true "Webfront")

Es lassen sich über das Webfront oder die Skripte dann Aktionen starten und beenden.

## 4. Funktionsreferenz

### Logitech Harmony Hub:
 
### Harmony Hub
Es können Aktivitäten des Logitech Harmony Hub ausgeführt werden.
Die aktuelle Akivität des Logitech Harmony Hub wird in der Variable Harmony Activity angezeigt und kann im Webfront geschaltet werden.
 
Wenn die Aktivität über Funktionen aktualisiert werden soll oder über ein Skript geschaltet sind die folgenden Funktionen zu benutzten:
 
#### Liest alle verfügbaren Informationen der mit dem Logitech Account verknüpften Logitech Harmony Hubs aus.
```php
HarmonyHubAPI_Discover(int $InstanceID) 
```   
Parameter _$InstanceID_ ObjektID des Harmony Hub I/O

#### Liest alle verfügbaren Informationen eines spezifischen Logitech Harmony Hubs aus.  
```php
HarmonyHubAPI_DiscoverHub(int $InstanceID, int $hubId) 
```   
Parameter _$InstanceID_ ObjektID des Harmony Hub I/O

Parameter _$hubId_ Indentifikations Nummer des Logitech Harmony Hub, diese ist in der Instanz des Logitech Hub zu finden 

#### Liest alle verfügbaren Aktivitäten der mit dem Logitech Account verknüpften Logitech Harmony Hubs aus.  
```php
HarmonyHubAPI_GetActivities(integer $InstanceID) 
```   
Parameter _$InstanceID_ ObjektID des Harmony Hub I/O

#### Liest alle verfügbaren Aktivitäten eines spezifischen Logitech Harmony Hubs aus.    
```php
HarmonyHubAPI_GetHubActivities(integer $InstanceID, int $hubId) 
```   
Parameter _$InstanceID_ ObjektID des Harmony Hub I/O
  
Parameter _$hubId_ Indentifikations Nummer des Logitech Harmony Hub, diese ist in der Instanz des Logitech Hub zu finden 

#### Liest den Status der mit dem Logitech Account verknüpften Logitech Harmony Hubs aus.  
```php
HarmonyHubAPI_GetStateDigest(integer $InstanceID) 
```   
Parameter _$InstanceID_ ObjektID des Harmony Hub I/O  

#### Liest den Status eines spezifischen Logitech Harmony Hubs aus.   
```php
HarmonyHubAPI_GetHubStateDigest(integer $InstanceID, int $hubId) 
```   
Parameter _$InstanceID_ ObjektID des Harmony Hub I/O  
  
Parameter _$hubId_ Indentifikations Nummer des Logitech Harmony Hub, diese ist in der Instanz des Logitech Hub zu finden  

#### Beendet alle aktuellen AV Aktionen und setzt alle AV Geräte auf aus, die mit dem Logitech Account verknüpften Logitech Harmony Hubs kontrolliert werden.
```php
HarmonyHubAPI_PowerOffAV(integer $InstanceID) 
```   
Parameter _$InstanceID_ ObjektID des Harmony Hub I/O   

#### Beendet alle aktuellen AV Aktionen und setzt alle AV Geräte auf aus, die mit dem spezifischen Logitech Harmony Hub kontrolliert werden.
```php
HarmonyHubAPI_PowerOffHubAV(integer $InstanceID, int $hubId) 
```   
Parameter _$InstanceID_ ObjektID des Harmony Hub I/O  

Parameter _$hubId_ Indentifikations Nummer des Logitech Harmony Hub, diese ist in der Instanz des Logitech Hub zu finden   

#### Schaltet die Aktivität eines spezifischen Logitech Harmony Hubs ein. Falls es sich um eine AV Aktivität handelt wird die aktuelle AV Aktivität beendet. Wenn eine nicht-AV Aktivität gestartet wird beeinflusst dies nicht die laufende AV Aktivität.
```php
HarmonyHubAPI_StartActivityAPI(integer $InstanceID, int $hubId, int $activityId) 
```   
Parameter _$InstanceID_ ObjektID des Harmony Hub I/O 
  
Parameter _$hubId_ Indentifikations Nummer des Logitech Harmony Hub, diese ist in der Instanz des Logitech Hub zu finden 

Parameter _$activityId_ Indentifikations Nummer der Logitech Harmony Hub Aktivität, diese kann in der Instanz des Logitech Hub gefunden werden 

#### Schaltet die Aktivität eines spezifischen Logitech Harmony Hubs aus. 
```php
HarmonyHubAPI_EndActivity(integer $InstanceID, int $hubId, int $activityId) 
```   
Parameter _$InstanceID_ ObjektID des Harmony Hub I/O 
  
Parameter _$hubId_ Indentifikations Nummer des Logitech Harmony Hub, diese ist in der Instanz des Logitech Hub zu finden 

Parameter _$activityId_ Indentifikations Nummer der Logitech Harmony Hub Aktivität, diese kann in der Instanz des Logitech Hub gefunden werden 

## 5. Konfiguration:

### Logitech Harmony Hub Konfigurator:

| Eigenschaft      | Typ     | Standardwert | Funktion                                            |
| :--------------: | :-----: | :----------: | :-------------------------------------------------: |
| ImportCategoryID | integer |              | ObjektID der Import Kategorie                       |


### Logitech Harmony Hub:

| Eigenschaft      | Typ     | Standardwert | Funktion                                            |
| :--------------: | :-----: | :----------: | :-------------------------------------------------: |
| Name             | string  |              | Name des Logitech Harmony Hub                       |
| Firmware         | string  |              | Firmwareversion des Logitech Harmony Hub            |
| HarmonyUser      | string  |              | Email Adresse zur Anmeldung bei MyHarmony           |
| HubID            | integer |              | Hub ID des Logitech Harmony Hub                     |


## 6. Anhang

###  GUIDs und Datenaustausch:

#### Logitech Harmony Hub Konfigurator:

GUID: `{37D1B484-B5A5-4C0D-AE53-0DD022923248}` 


#### Logitech Harmony Hub:

GUID: `{32803D90-824E-4CDE-987E-107CEB48D441}` 


