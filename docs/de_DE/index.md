Konfiguration 
=============

Mit dem SMS-Plugin können Sie über Jeedom interagieren
Mit SMS kann Jeedom Ihnen im Falle einer Warnung auch eine SMS senden
(Alarm Plugin, Szenario…)

> **Important**
>
> Um mit Jeedom zu interagieren, müssen Sie Interaktionen konfiguriert haben.

Plugin Konfiguration 
-----------------------

Nach dem Herunterladen des Plugins müssen Sie es nur noch aktivieren und
Port konfigurieren. Nach dem Speichern sollte der Daemon gestartet werden. Die
Das Plugin ist bereits standardmäßig konfiguriert. Sie haben also nichts damit zu tun
mehr. Sie können diese Konfiguration jedoch ändern. Hier ist die
Detail (einige Parameter sind möglicherweise nur im Expertenmodus sichtbar) :

-   *SMS-Port* : der USB-Anschluss, an dem sich Ihr GSM-Schlüssel befindet
        ist verbunden.

> **Tip**
>
> Wenn Sie nicht wissen, welcher USB-Anschluss verwendet wird, können Sie dies einfach tun
> Geben Sie "Auto" an". Achtung, der Auto-Modus funktioniert nur mit den Tasten
> Huawai E220.

> **Important**
>
> Bitte beachten Sie, dass einige 3G-Schlüssel standardmäßig im Modemmodus und nicht in GSM verwendet werden.
> Mit der Software Ihres Schlüsselherstellers müssen Sie die ändern
> Tastenmodus auf GSM (oder Text oder seriell). 

-   **PIN-Code** : Hier können Sie den PIN-Code der SIM-Karte angeben und leer lassen, wenn Sie keinen haben. 
-   **Textmodus** : Erweiterter Kompatibilitätsmodus, der nur verwendet wird, wenn das Senden und / oder Empfangen von Nachrichten nicht funktioniert.
-   **Schneiden Sie Nachrichten nach Zeichenpaket** : Gibt die maximale Anzahl von Zeichen pro Text an.
-   **SMS / SMS Gateway (im Fehlerfall ändern : CMS 330 SMSC-Nummer nicht festgelegt)** : Beachten Sie, dass Sie in diesem Fall die SMS-Gateway-Nummer Ihres Telefonisten angeben müssen, wenn der Fehler "CMS 330 SMSC-Nummer nicht festgelegt" angezeigt wird. 
-   **Signalstärke** : Signalempfangsstärke Ihres GSM-Schlüssels.
-   **Netzwerk** : Telefonnetz Ihres GSM-Schlüssels (kann "Keine" sein, wenn Jeedom ihn nicht wiederherstellen kann). 
-   **interne Steckdose (gefährliche Änderung)** : Ermöglicht das Ändern des internen Kommunikationsports des Dämons.
-   **Zyklus (e)** : Daemon-Scan-Zyklus zum Senden und Empfangen von SMS. Eine zu niedrige Zahl kann zu Instabilität führen

Gerätekonfiguration 
-----------------------------

Die Konfiguration der SMS-Geräte ist über das Menü zugänglich
Plugin dann Kommunikation

Hier finden Sie die gesamte Konfiguration Ihrer Geräte :

-   **Name des SMS-Geräts** : Name Ihres SMS-Geräts,

-   **Activer** : macht Ihre Ausrüstung aktiv,

-   **Visible** : macht Ihre Ausrüstung auf dem Armaturenbrett sichtbar,

-   **Übergeordnetes Objekt** : gibt das übergeordnete Objekt an, zu dem
    gehört Ausrüstung.

Nachfolgend finden Sie die Liste der Bestellungen :

-   **Nom** : Der im Dashboard angezeigte Name,

-   **Utilisateur** : entsprechender Benutzer in Jeedom (erlaubt
    bestimmte Interaktionen auf bestimmte Benutzer beschränken),

-   **Anzahl** : Telefonnummer, an die Nachrichten gesendet werden sollen. Sie
    kann mehrere Zahlen setzen, indem man sie mit trennt; (z
    : 0612345678; 0698765432).

    > **Important**
    >
    > Nur in Geräten angegebene Telefonnummern können
    > Verwenden Sie Interaktionen, da nur diese zulässig sind.

-   **Afficher** : ermöglicht die Anzeige der Daten im Dashboard,

-   **Erweiterte Konfiguration** (kleine gekerbte Räder) : permet
    Zeigen Sie die erweiterte Konfiguration des Befehls (Methode) an
    Geschichte, Widget…),

-   **Tester** : Wird zum Testen des Befehls verwendet,

-   **Supprimer** (Zeichen -) : ermöglicht das Löschen des Befehls.

Mit dem Plugin 
---------------------

Dies ist auf der Seite Allgemein ziemlich normal
→ Plugin dann durch Auswahl des SMS-Plugins :

-   Der Port (Pfad) zu dem Gerät, das beispielsweise als Modem dient
    Beispiel: Es kann / dev / ttyUSB0 sein, um zu sehen, wie es gerade gestartet wird
    “dmesg” puis de brancher le modem)

-   Der PIN-Code der SIM-Karte

    Sie müssen also neue Geräte hinzufügen und diese dann konfigurieren :

-   Der Name davon,

-   Ob es aktiv ist oder nicht,

    Fügen Sie dann die Befehle hinzu, die aus einem Namen und bestehen
    eine Zahl, nur die in der Befehlsliste aufgeführten Zahlen
    kann eine Antwort von Jeedom erhalten (dies sichert,
    Vermeiden Sie es, zu Beginn jeder SMS ein Passwort einzugeben
    nach Jeedom geschickt). Sie können auch angeben, welcher Benutzer ist
    mit dieser Nummer verknüpft (für die Rechteverwaltung auf der Ebene
    Wechselwirkungen).

Um mit Jeedom zu kommunizieren, reicht es dann aus, ihm eine zu schicken
Nachricht von einer autorisierten Nummer, alle Interaktionen kommen von der
Interaktionssystem.

Kleines Beispiel für Interaktion : Frage : “Was ist die Temperatur von
das Zimmer ?” Antwort : “16.3 C.”

Liste der kompatiblen Schlüssel 
---------------------------

-   HUAWEI E220
-   Alcatel One Touch X220L
-   HSDPA 7.2MBPS 3G Wireless
-   HUAWEI E3372


Faq 
===

> **Ich bekomme nichts mit einem huwaei e160 Schlüssel.**
>
>Sie müssen minicom installieren (sudo apt-get install -y minicom), starten
>dieses und verbinden Sie sich mit dem Modem und dann tun :
>
>`` `{.bash}
>AT ^ CURC = 0
>AT ^ u2diag = 0
>`` ''
>
>Und auf dem Plugin tun :
>
>-   Wählen Sie den ersten USB-Anschluss und nicht den zweiten
>
>-   Geschwindigkeit : 9600
>
>-   Textmodus aus

> **Ich kann den USB-Anschluss meines Schlüssels nicht sehen**
>
>Stellen Sie sicher, dass Sie nicht brltty installieren müssen (`sudo apt-get remove brltty`, um es zu entfernen)

> **Nach einigen Stunden / Tagen erhalte ich keine SMS mehr und kann keine mehr senden, korrigiert eine Erinnerung des Dämons**
>
>Überprüfen Sie Ihr USB-Kabel (ein schlechtes USB-Kabel führt häufig dazu
>Art von Sorgen, es sollte auch nicht zu lang sein),
>Überprüfen Sie auch Ihr Netzteil, ein USB-Hub ist stark
>conseillé

> **Ich habe einen CME-Fehler XX**
>
>Sie können finden [hier](:http://www.micromedia-int.com/fr/gsm-2/669-cme-error-gsm-equipment-related-errors) Beschreibung der verschiedenen CME-Fehler

> **Konfiguration der Alcatel One-Touch-X220L-Taste**
>
>Wenn wir den Schlüssel einstecken, haben wir diesen :
>
>`` `{.bash}
>root @ jeedom:# lsusb
>Bus 002 Gerät 003: ID 1bbb:f000 T & A-Handys
>`` ''
>
>Seien Sie vorsichtig, wenn Sie nicht 1bbb haben:f000 es ist wichtig, das nicht zu tun
>Fortsetzung dieser Dokumentation
>
>Fügen Sie die folgenden Zeilen am Ende der Datei hinzu
>/etc/usb\_modeswitch.conf :
>
>`` `{.bash}
>########################################################
># Alcatel X220L
>DefaultVendor = 0x1bbb
>DefaultProduct = 0xf000
>MessageContent = "55534243123456788000000080000606f50402527000000000000000000000"
>########################################################
>`` ''
>
>Führen Sie dann den folgenden Befehl zum Testen aus :
>
>`` `{.bash}
>/ usr / sbin / usb_modeswitch -c
>/etc/usb_modeswitch.conf
>`` ''
>
>Wir bekommen das :
>
>`` `{.bash}
>root @ jeedom:~ # lsusb
>Bus 002 Gerät 003: ID 1bbb:0017 T & A-Handys
>`` ''
>
>und die Links unter / dev werden hinzugefügt :
>
>`` `{.bash}
>root @ jeedom:~ # ls / dev / ttyUSB*
>/ dev / ttyUSB0 / dev / ttyUSB1 / dev / ttyUSB2 / dev / ttyUSB3 / dev / ttyUSB4
>`` ''
>
>Jetzt müssen Sie den Start des vorherigen Befehls automatisieren
>über udev :
>
>`` `{.bash}
>root @ jeedom:# vi /etc/udev/rules.d99-usb_modeswitch.rules
>SUBSYSTEM == "usb", ATTRS {idVendor} == "1bbb", ATTRS {idProduct} == "f000", RUN + = "/ usr / sbin / usb_modeswitch -c /etc/usb_modeswitch.conf"
>`` ''
>
>Unter jeedom im SMS-Plugin müssen Sie (in meinem Fall) den "Port" verwenden
>SMS "weiter : / dev / ttyUSB3. Grundsätzlich muss man jeden Port ausprobieren
>finde den richtigen ...

> **Der SMS-Daemon wird gestartet, Sie erhalten jedoch keine SMS**
>
>Eine der wahrscheinlichen Ursachen ist die falsche Netzwerkkonfiguration. In
>"Allgemein "->" Konfiguration "->" Administration" ->
>"Netzwerkkonfiguration ", überprüfen Sie den Inhalt des Felds" URL oder IP-Adresse "".
>
>Letzteres darf nicht localhost oder 127.0.0 sein.1 aber die IP-Adresse von
>Ihr Jeedom- oder DNS-Name.

> **Im Debug-Modus wird der Fehler "Timeout" angezeigt**
>
>Dieser Fehler tritt auf, wenn der Schlüssel nicht innerhalb von 10 Sekunden antwortet
>die einer Anfrage folgen. Bekannte Ursachen können sein :
>
>-   Inkompatibilität des GSM-Schlüssels,
>
>-   Problem mit der Firmware-Version des Sticks.

> **Beim Starten im Debug-Modus habe ich : "Steckdose bereits in Gebrauch"**
>
>Es bedeutet, dass der Dämon gestartet wird, aber Jeedom nicht ankommt
>um es zu stoppen. Sie können entweder das gesamte System neu starten oder über
>SSH do "killall -9 refxcmd.py".

> **Der Dämon weigert sich zu starten**
>
>Versuchen Sie, es im Debug-Modus zu starten, um den Fehler zu sehen.

> **Ich habe mehrere USB-Anschlüsse für meinen GSM-Schlüssel, während ich nur einen habe**
>
>Dies ist normal, aus einem unbekannten Grund erzeugen GSM-Schlüssel 2 (und
>noch mehr) USB-Anschlüsse auf Systemebene. Wählen Sie einfach eine,
>wer auch immer.

> **Jeedom sendet oder empfängt keine SMS mehr**
>
>Dies geschieht normalerweise, wenn der GSM-Schlüssel keine Verbindung mehr herstellen kann
>zum Netzwerk. Bewegen Sie es und sehen Sie, ob es wieder zu Ende ist
>ein paar Minuten.

>**Ich habe Empfangsbedenken, die für ein paar Stunden dann nichts arbeiten**
>
>Legen Sie die SIM-Karte in ein Mobiltelefon ein und leeren Sie alle gesendeten und empfangenen SMS von der Karte.
