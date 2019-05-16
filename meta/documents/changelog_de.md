# heidelpay Payment Gateway Changelog

## [1.3.0][1.3.0]

### Added
- Für Rechnungskäufe können ab jetzt aus dem Backend heraus Finalisierungen durchgeführt werden. [Siehe Beschreibung im User Guide.](user_guide_de.md)

### Changed
- Der User Guide wurde aktualisiert.

## [1.2.3][1.2.3]

### Changed
- Der 'bezahlen' und der 'abbrechen' Button werden nun nach dem Klick deaktiviert, um zu verhindern, dass mehr als einmal geklickt wird.

### Fixed
- Es wurde ein Problem behoben, dass beim Rendern der Rechnungsinformationen dazu führte, dass ein Fehler auftrat, wenn die Zahlart nicht von heidelpay war.

## [1.2.2][1.2.2]

### Changed
- Dem Kunden wird nun mitgeteilt, wenn ihm eine gewählte Zahlart nicht angeboten werden kann.
- Die Kapitel ``Konfiguration > Daten Container`` und ``Beschreibung der Zahlungsabläufe > Gesicherter Rechnungskauf B2C`` im User-Guide wurden aktualisiert.
- Der Adressvergleich wurde optimiert um die Conversionrate für Rechnungszahlarten zu verbessern.

### Added
- Im Kundenbereich können nun zusätzliche Bezahlinformationen eingeblendet werden (Rechnungskauf). [Siehe Beschreibung im User Guide.](user_guide_de.md)
- Im Händlerbackend werden nun die Zahlinformationen für Rechnungszahlarten angezeigt.
- Es gibt nun eine Fehlermeldung, wenn ein Kunde unter 18 ist.
- Es gibt nun eine Fehlermeldung, wenn die Anrede des Kunden nicht gültig ist.

## [1.2.1][1.2.1]

### Fixed
- Einen Fehler, der auftrat wenn ein Rechnungsdokument für eine 'fremde' Zahlart erzeugt wurde.

## [1.2.0][1.2.0]

### Added
- Zahlart ``gesicherter Rechnungskauf B2C`` hinzugefügt.
- Daten-Container für zusätzliche Zahlungsinformationen ergänzt. [Siehe Beschreibung im User Guide.](user_guide_de.md)
- Weiteres Vorschaubild hinzugefügt.
- Warenkorbübertragung für gesicherte Zahlarten aktiviert.

### Changed
- User guides aktualisiert.

### Removed
- Veralteten code entfernt.

## [1.1.2][1.1.2]

### Fixed
- Ein Problem behoben, dass dazu geführt hat, dass Bestellungen im Preview-Modus nicht angelegt werden.

## [1.1.1][1.1.1]

### Change
- Logos ausgetauscht.
- Diverse Codingstyle-Fehler behoben.
- .gitignore aktualisiert
- Readme um Support Informationen ergänzt

### Fixed
- Support Telefonnummer korrigiert.
- MwSt wird nun bei Auslandsgeschäften nicht mit einbezogen

## [1.1.0][1.1.0]

### Removed
- Todo-Kommentare entfernt.
- Logging wurde aus den Eventhandlern entfernt, um zu verhindern, dass zu viel Logeinträge entstehen.

### Change
- Übersetzungen refactored, neue ergänzt und Fehler behoben.
- Das Erstellen der Zahlarten wurde in die Migration verschoben.
- Das Benutzerhandbuch wurde aktualisiert.

### Added
- IO wurde als Requirement in der plugin.json hinzugefügt.

## 1.0.0

### Added
- Erste Implementation.
- Zahlarten Kreditkarte, Debitkarte, Sofort und Lastschrift hinzugefügt.

[1.1.0]: https://github.com/heidelpay/plentymarkets-gateway/tree/1.1.0
[1.1.1]: https://github.com/heidelpay/plentymarkets-gateway/compare/1.1.0..1.1.1
[1.1.2]: https://github.com/heidelpay/plentymarkets-gateway/compare/1.1.1..1.1.2
[1.2.0]: https://github.com/heidelpay/plentymarkets-gateway/compare/1.1.2..1.2.0
[1.2.1]: https://github.com/heidelpay/plentymarkets-gateway/compare/1.2.0..1.2.1
[1.2.2]: https://github.com/heidelpay/plentymarkets-gateway/compare/1.2.1..1.2.2
[1.2.3]: https://github.com/heidelpay/plentymarkets-gateway/compare/1.2.2..1.2.3
[1.3.0]: https://github.com/heidelpay/plentymarkets-gateway/compare/1.2.3..1.3.0
