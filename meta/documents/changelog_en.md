# heidelpay Payment Gateway Changelog

## [1.2.2][1.2.2]

### Changed
- If a payment method cannot be used by the customer a specific error message is shown.
- The sections ``Configuration > Data Container`` and ``Workflow description > Invoice secured B2C`` within the user guide have been updated.
- The address comparison has been optimized to raise the conversion rate of invoice payments.

### Added
- Additional payment information can now be shown within the customer backend (Invoice payment).  [See User Guide for details.](user_guide_en.md)
- Payment information for invoice payment types are now shown within the merchant backend.
- An error message when the customer is under 18.
- An error message when the salutation is invalid.

## [1.2.1][1.2.1]

### Fixed
- A bug resulting in an error when generating invoices for unknown payment methods.

## [1.2.0][1.2.0]

### Added
- Added payment type ``secured Invoice B2C``.
- Data-Container for additional payment information. [See User Guide for details.](user_guide_en.md)
- Additional preview image.
- Enabled basket transmission for secured payments.

### Changed
- Updated the User guides.

### Removed
- Removed obsolete code.

## [1.1.2][1.1.2]

### Fixed
- A bug resulting in a problem creating orders in preview mode.

## [1.1.1][1.1.1]

### Change
- Replace images.
- Resolve codingstyle violations.
- Update .gitignore
- Add support information to readme

### Fixed
- Fix support phone number.
- Fix incorrect VAT for internaational customer

## [1.1.0][1.1.0]

### Removed
- Todo-comments.
- Logging from event handler to avoid extensive logging.

### Change
- Refactored translations, added new ones and fixed mixup.
- Moved payment method generation to migration.
- Updated the user-guide.

### Added
- IO as requirement to plugin.json.

## 1.0.0

### Added
- Initial implementation.
- Added payment methods credit card, debit card, Sofort and direct debit.

[1.1.0]: https://github.com/heidelpay/plentymarkets-gateway/tree/1.1.0
[1.1.1]: https://github.com/heidelpay/plentymarkets-gateway/compare/1.1.0..1.1.1
[1.1.2]: https://github.com/heidelpay/plentymarkets-gateway/compare/1.1.1..1.1.2
[1.2.0]: https://github.com/heidelpay/plentymarkets-gateway/compare/1.1.2..1.2.0
[1.2.1]: https://github.com/heidelpay/plentymarkets-gateway/compare/1.2.0..1.2.1
[1.2.2]: https://github.com/heidelpay/plentymarkets-gateway/compare/1.2.1..1.2.2
