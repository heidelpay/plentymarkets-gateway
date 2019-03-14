# heidelpay Payment Gateway Changelog

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