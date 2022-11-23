# Changelog
All notable changes to this project will be documented in this file.

## [3.3.0] - 2022-11-23
### Changed
- Add compatibility with Magento 2.4.4


## [3.2.0] - 2022-03-11
### Changed
- Installation instructions in the README.md file were updated
- Color selector for Pay Button on the module Configuration page


## [3.1.1] - 2022-01-04
### Fixed
- Installation instructions in the README.md file were updated


## [3.1.0] - 2021-10-19
### Changed
- Add Embedded Payment Option
- Add configs for automated testing
- Add compatibility with Magento 2.4
- Branding Update

### Fixed
- Simplify JS is not included into csp_whitelist.xml file
- Shopping Cart is not emptied correctly after order is successfully created
- JS is broken after bundling


## [3.0.4] - 2021-03-18
### Changed
- Adding Magento 2.4 Support
- Removed dependency from Zend_Json

### Fixed
- Fixed CardTokenBuilder constructor php doc
- Fixing crypt issue


## [3.0.3] - 2020-09-10
### Changed
- Changing terminology for payment actions to Authorization and Payment

### Fixed
- Fix the loading with production mode


## [3.0.2] - 2020-06-02
### Changed
- Optimized JS component loading in checkout
- Composer metadata updates

### Fixed
- PHP autoloader issue when package not installed via composer (classmap missing)
- Private content not correctly invalidated when checkout success page is requested


## [3.0.1] - 2019-11-06
### Fixed
- Fixing broken links in the plugin


## [3.0.0] - 2019-09-10
### Changed
- This release makes the module compatible with Magento 2.2 and 2.3, we also added tokenization (Vault) support.
### Fixed


## [2.1.6] - 2017-02-23
### Changed
- Initial release of Simplify Commerce payment gateway for Magento 2



