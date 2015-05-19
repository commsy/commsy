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

Optional kann CommSy die Verbindung zu LimeSurvey auch über einen Proxy-Server herstellen. Konfigurieren Sie dafür in der Datei app/config/commsy.yml die Proxy-Einstellungen oder setzen entsprechende Einträge in der Datei parameters.yml.

## Konfiguration

### LimeSurvey
Aktivieren Sie im Backend von LimeSurvey die Benutzung der JSON-RPC Schnittstelle.

### CommSy
Aktivieren Sie auf Portalebene die Anbindung an eine Limesurvey-Installation und tragen Sie die notwendigen Daten für die Verbindung ein.