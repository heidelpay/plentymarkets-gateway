![Logo](https://dev.heidelpay.com/devHeidelpay_400_180.jpg)

# heidelpay plentymarkets-gateway plugin
Diese Erweiterung integriert die heidelpay Zahlarten in Ihren Plentymarkets Shop. 

Aktuell werden folgende Zahlarten unterstützt:
* Kreditkarte
* Debitkarte
* Lastschrift (ungesichert)
* Sofort.
* Gesicherter Rechnungskauf B2C

## Anforderungen
* Dieses Plugin ist für Plentymarkets 7 entwickelt worden.
* Im heidelpay Backend müssen Push-Benachrichtigungen aktiviert sein, damit das Plugin ordnungsgemäß funktioniert. \
Die Push-URL besteht aus der Shop-Domain gefolgt von **'/payment/heidelpay/pushNotification'** \
z. B. https://my-demo-shop.plentymarkets-cloud01.com/payment/heidelpay/pushNotification \
oder https://www.my-demo-shop.com/payment/heidelpay/pushNotification

## LIZENZ
Lizensierungsinformationen finden Sie in der Datei LICENSE.txt.

## Release notes
Dieses Plugin basiert auf heidelpay php-payment-api (https://github.com/heidelpay/php-payment-api) und heidelpay php-basket-api (https://github.com/heidelpay/php-basket-api).

## Installation
+ Bitte lesen Sie unter [plentyKnowledge](https://knowledge.plentymarkets.com) nach, wie Plugins aus dem Market installiert werden.
+ Nachdem Sie die unten beschriebenen Konfigurationsschritte durchgeführt haben sollten Sie in der Lage sein ein paar Testbuchungen im Staging-Mode durchzuführen.
+ Wenn alles klappt können Sie die Live-Konfiguration durchführen und das Modul in Ihrer Live-Umgebung veröffentlichen um es Ihren Kunden zugänglich zu machen.

## Konfiguration
### Grundkonfiguration
+ Wählen Sie den "Plugins-Tab" und danach "Plugin Übersicht"
+ Wählen Sie dann das heidelpay Plugin um die Konfigurationsumgebung zu gelangen.
+ Nun tragen Sie Ihre Zugangsdaten und Kanalkonfigurationen ein und aktivieren Sie die Zahlarten, die beim Checkout auswählbar sein sollen.

>*Standardmäßig ist die Konfiguration auf unsere Testumgebung eingestellt.* (siehe https://dev.heidelpay.de/sandbox-environment/).

>*Klicken Sie den speichern-Button für jeden Tab einzeln um die eingetragenen Einstellungen zu sichern, andernfalls setzt Plenty die Daten ungespeicherter Tabs zurück auf die Standardwerte, wenn der Tab gewechselt wird.*

>*Bitte beachten Sie, dass Plenty etwas Zeit braucht um die aktualisierten Daten auf den Konfigurationstabs anzuzeigen. Wenn Sie den **Plugin Übersicht**-Tab nachladen sollten aber die aktuellen Daten angezeigt werden.*

### Die Konfigurationsparameter
#### heidelpay Einstellungen
###### Test-/Livesystem
* Wählen Sie die Option *'Testumgebung (CONNECTOR_TEST)'* um die Verbindung zu unserem Testserver herzustellen, was dazu führt, dass alle Transaktionen zur Sandbox gesendet werden und keine Kosten entstehen.  
Bitte stellen Sie hierfür sicher, dass die Konfiguration test-Zugangsdaten und Kanäle enthält (siehe https://dev.heidelpay.de/sandbox-environment/).
* Wählen Sie die Option *'Liveumgebung (LIVE)'* um den Produktivitätsmodus einzuschalten. Das bedeutet, das tatsächliche, kostenpflichtige Transaktionen durchgeführt werden.
Bitte stellen Sie hierfür Ihre Live-Zugangsdaten und Kanal-Ids ein.

###### Sender-Id
Die Id, die benötigt wird, um eine Verbindung mit dem Payment-Server aufzubauen.\
Diese Information erhalten Sie von ihrem heidelpay-Ansprechpartner. 

###### Login
Der Benutzername, der benötigt wird, um eine Verbindung mit dem Payment-Server aufzubauen.\
Diese Information erhalten Sie von ihrem heidelpay-Ansprechpartner. 

###### Kennwort
Das Passwort, das benötigt wird, um eine Verbindung mit dem Payment-Server aufzubauen.\
Diese Information erhalten Sie von ihrem heidelpay-Ansprechpartner. 

###### Geheimer Schlüssel
Der Schlüssel ist erforderlich, um einen Sicherheitshash zu generieren, der verwendet wird um zu verifizieren, dass alle Transaktionen tatsächlich vom heidelpay backend kommen.\
Dieser Parameter ist erforderlich und darf nicht leer gelassen werden. Sie können hier eine beliebige Zeichenfolge eintragen.

##### Parameter für die Zahlarten
###### Aktiv
Wenn diese Option angehakt ist, wird die entsprechende Zahlart auf der Checkout-Seite auswählbar.

###### Anzeigename
Der Name unter dem die Zahlart auf der Checkout-Seite angezeigt wird.\
Wenn hier nichts eingetragen wird, wird der Standardname für die Zahlart angezeigt.

###### Channel-Id
Die Id des Kanals auf dem für Sie die entsprechende Zahlart aufgeschaltet wurde.\
Diese Information erhalten Sie von ihrem heidelpay-Ansprechpartner.

###### Mindest-/Höchstbetrag
Die Zahlart ist für Ihre Kunden nur auswählbar, wenn die Bestellsumme zwischen diesen beiden Werten liegt.\
Um die Limitierung zu deaktivieren muss der entsprechende Wert auf 0 gesetzt werden.

###### Buchungsmodus
* Wählen Sie die Option **Direkte Buchung** aus, damit der Gesamtbetrag sofort und komplett von der angegebenen Quelle abzubuchen. 
* Wählen Sie die Option **Reservierung mit Erfassung bei Rechnungserstellung** aus, um Abbuchungen zunächst nur Autorisieren zu lassen und erst später tatsächlich durchzuführen.

> **Info:** Einige Zahlarten ermöglichen die Reservierung von Beträgen, was grundsätzlich nur die Absichtserklärung darüber ist, dass der Betrag abgebucht werden wird.\
Der Betrag wird später abgebucht, z. B. zum Versandzeitpunkt. Dies gibt Ihnen die Möglichkeit nur die Beträge der Waren tatsächlich zu buchen, die auch tatsächlich versandt werden, z. B. dann wenn eine Bestellung auf mehrere Einzellieferungen aufgeteilt wird.

###### URL für Custom-CSS im IFrame
Einige Zahlarten rendern ein Formular für Kundeneingaben innerhalb eines iFrames.
Fügen Sie hier die URL zu einer eigenen Stylsheetdatei ein um das Aussehen des Formulars anzupassen.
Wenn Sie den Parameter leer lassen werden die Standard Stylesheets angewendet.\
Anforderungen an die URL:
* sie muss aus dem Internet erreichbar sein
* sie muss mit 'http://' oder 'https://' beginnen
* sie muss mit '.css' enden

###### URL zum Icon der Zahlart
Hiermit können Sie festlegen, welches Icon für die entsprechende Zahlart auf der Checkout-Seite neben dem Anzeigenamen dargestellt werden soll.
Lassen Sie die URL leer um das Standard Icon zu verwenden.\
Anforderungen an die URL:
* sie muss aus dem Internet erreichbar sein
* sie muss mit 'http://' oder 'https://' beginnen
* sie muss mit '.jpg', '.png' oder '.gif' enden

## Beschreibung der Zahlungsabläufe 
### Kreditkarte und Debitkarte
* Wenn für die Zahlart der **Buchungsmodus 'Direkte Buchung'** ausgewählt ist, wird die Zahlung sofort erzeugt und mit der Bestellung verknüpft.
Es sind in diesem Fall keine weiteren Schritte notwendig um den Betrag zu buchen. Ist die Zahlung erfolgreich, wird die Bestellung sofort erzeugt und im Backend als bezahlt markiert.
Schlägt die Zahlung fehl, wird die Bestellung nicht erzeugt und der Kunde wird zur Checkout-Seite umgeleitet.
* Wenn für die Zahlart der **Buchungsmodus 'Reservierung mit Erfassung bei Rechnungserstellung'** ausgewählt ist, wird die Bestellung erzeugt aber die Buchung muss manuell im hIP ausgelöst werden.
Die Buchungstransaktion (Capture) wird dann in Ihren Plenty-Markets shop gepusht (hierfür die Push-URL im bei heidelpay eingetragen sein s. Anforderungen).
Dies führt dazu, dass eine Zahlung im Plenty-Backend angelegt wird und mit der entsprechenden Buchung verknüpft wird.

### Sofort.
Buchung und Zahlung werden erzeugt, sobald der Kunde von der Sofort. Seite zurück auf die Erfolgsseite des Shops geleitet wird.

### Lastschrift
Die Zahlung wird sofort erzeugt und mit der Bestellung verknüpft.\
Es sind keine weiteren Schritte notwendig, um den Betrag zu buchen.\
Wenn die Zahlung erfolgreich ist, wird die Bestellung im Backend direkt als bezahlt markiert.\
Wenn die Zahlung fehlschlägt, wird die Bestellung nicht erzeugt und der Kunde wird wieder auf die Checkout-Seite des Shops geleitet.

### Gesicherter Rechnungskauf B2C
Um die Sicherung zu aktivieren müssen Sie im hIP eine Finalisierung (FIN) ausführen.\
Ab diesem Zeitpunkt startet der vertraglich festgelegte Versicherungzeitraum innerhalb dessen die Zahlung durch den Kunden erwartet wird.\
Wenn der Kunde die Überweisung tätigt erscheint diese im hIP als Receipt (REC) und wird an die Push-URL ihres Shops gesendet.\
Hier wird daraufhin eine Zahlung angelegt und mit der Buchung verknüpft.

### Alle Zahlarten
* Zahlungen im Plenty-Backend enthalten die txnId (heidelpay Bestellnummer), die shortId (die eindeutige id der Transaktion d. h. Receipt, Debit oder Capture) und den Hinweis, dass es sich um eine durch heidelpay angelegte Zahlung handelt.
* Im Falle eines Fehlers, der dazu führt, dass die Bestellung im Plenty-Backend nicht angelegt werden kann, während die Zahlung erfolgreich im heidelpay backend erzeugt wird, wird der Buchungstext der Zahlung um eine entsprechende Fehlermeldung erweitert.

## Technische Besonderheiten
* Leider ist es nicht möglich die Bestellung im Nachhinein zu erzeugen, d. h. wenn zum Beispiel die Rückleitung in den Shop nach dem Bezahlen schief geht. Auch wenn die Zahlung erfolgreich im heidelpay backend gespeichert worden ist.\
Durch die Fehlermeldung im Buchungstext von Zahlungen, die nicht zugeordnet werden konnten, ist es möglich diese Fehlerfälle zu erkennen und zu behandeln.