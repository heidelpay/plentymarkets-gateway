# heidelpay Payment Gateway Changelog

## [1.1.1][1.1.1]

### Change
- Logos ausgetauscht.
- diverse Codingstyle-Fehler behoben.
- .gitignore aktualisiert

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