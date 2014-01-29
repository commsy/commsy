# Anbindung einer LimeSurvey-Installation

## Voraussetzungen
- mindestens CommSy 8.0.6
- aktuelle LimeSurvey-Installation

## Installation

### LimeSurvey
Da LimeSurvey in der aktuellen Version leider nicht die Möglichkeit bietet über die RPC-Schnittstelle Umfragen zu exportieren, ist es notwendig die Datei **application/controllers/admin/remotecontrol.php** um die fehlende Methode zu erweitern.

In CommSy finden Sie im Ordner **docs/** eine entsprechende Patch-File, welche die notwendigen Änderungen vornimmt.

### CommSy
Kopieren Sie die Datei **etc/commsy/limesurvey.php-dist** in den selben Ordner unter den Namen **limsurvey.php** und setzten Sie den Wert **$c_limesurvey** auf **true**.

Optional kann CommSy die Verbindung zu LimeSurvey auch über einen Proxy-Server herstellen. Kopieren Sie dazu - sofern nicht schon geschehen - die Datei **etc/commsy/settings.php-dist** in den selben Ordner unter den Namen **settings.php**, entfernen Sie die Kommentare vor den entsprechenden Zeilen und nehmen Sie die notwendigen Einstellungen für den Proxy-Server vor:

	// proxy settings
	// if you use commsy behind a proxy, just set the proxy information here
	// $c_proxy_ip   = '100.200.300.400';
	// $c_proxy_port = '3128';

## Konfiguration

### LimeSurvey
Aktivieren Sie im Backend von LimeSurvey die Benutzung der JSON-RPC Schnittstelle.

### CommSy
Aktivieren Sie auf Portalebene die Anbindung an eine Limesurvey-Installation und tragen Sie die notwendigen Daten für die Verbindung ein.