# Simplify Commerce Payment Gateway for Magento 2

## Overview
Simplify Commerce Payment Gateway module is a free Magento 2 module that adds Simplify Commerce as payment method on your Magento 2 checkout page. With simple configuration steps described below you can quickly start using Simplify Commerce's secure payment form for receiving payments in your online store. 

The module allows payments using Simplify Commerce Hosted Payments. Simplify Commerce Hosted Payments handle card data in safe and secure way, with full compliance with legal requirements. We use state-of-the-art encryption and tokenization to securely get payment information from your customer to our database. We monitor every transaction and manage sensitive payment data on our Level 1 PCI certified servers, which makes PCI compliance easier for you.


## Prerequisites
### Magento 2
This module is compatible with:
- Magento 2.2
- Magento 2.3
- Magento 2.4

Community and Commerce Edition are both supported.
 
The Standard payment form in some of the previous releases is now deprecated and out of support.

## Support

For any issues or enhancement requests you have with this module, please raise them with the bank's support team. Please make sure you also provide your module & magento version as well as your merchant ID where applicable. This will help to speed up the troubleshooting of any issues you are having.

### Composer
To download and install the components you need Composer, a PHP package manager. If it's not yet there on your Magento 2 server, install it first. Please follow the instructions specific for your operating system: [https://getcomposer.org/doc/00-intro.md](https://getcomposer.org/doc/00-intro.md)

### Simplify Commerce Account
Before you can use the module, you must have a Simplify Commerce account [https://www.simplify.com/commerce/](https://www.simplify.com/commerce/)

We recommend that you configure the module using Simplify Commerce Sandbox configuration. This way you can make sure everything works well, before you start receiving live payments. For testing module configuration you can use test card numbers provided by Simplify Commerce at [https://simplify.com/commerce/docs/testing/test-card-numbers](https://simplify.com/commerce/docs/testing/test-card-numbers). You will find more details about the configuration in the following chapters.     

## Installation
Before installing the module, make a full backup of your site.

### Installation using PHP Composer
The preferred method to install the module is by using PHP Composer:

Log on to your Magento 2 server and navigate to Magento installation folder. The exact location can vary, but you can identify it by content. Inside this folder, amongst others, you should see the following files and folders: 

    index.php
    composer.json
    /bin
    /var
    /vendor

Run the following commands to download and install the module:

    composer config repositories.mastercard-module-simplifycommerce git https://github.com/simplifycom/simplify-magento-module.git
    composer require mastercard/module-simplifycommerce
    ./bin/magento module:enable MasterCard_SimplifyCommerce
    ./bin/magento setup:upgrade
    ./bin/magento cache:clean

When asked by Composer for user name and password for Magento repository at [https://repo.magento.com](https://repo.magento.com), enter the Magento Marketplace public key as user name, and private key as password.

Verify whether the module has been successfully installed. Log in to Magento Admin dashboard and go to *Stores* > *Configuration* > *Sales* > *Payment Methods*. Settings of the Simplify Commerce module should be there under the name "Mastercard Payment Gateway Services - Simplify".

#### Deinstallation
If you want to uninstall the previously installed Simplify Commerce Payment Gateway module, please run the following commands:

    ./bin/magento module:uninstall MasterCard_SimplifyCommerce
    ./bin/magento setup:upgrade
    ./bin/magento cache:clean

### Manual installation 
If you prefer to deploy and install the module without Composer, proceed with the following steps:

Download module from [https://github.com/simplifycom/simplify-magento-module/releases](https://github.com/simplifycom/simplify-magento-module/releases).

Create folder structure inside the Magento main folder:

    ./app/code/MasterCard/SimplifyCommerce

Extract module files into that folder and execute the following commands:

    ./bin/magento module:enable MasterCard_SimplifyCommerce
    ./bin/magento setup:upgrade
    ./bin/magento cache:clean

Verify whether the module has been successfully installed. Log in to Magento Admin dashboard and go to *Stores* > *Configuration* > *Sales* > *Payment Methods*. Settings of the Simplify Commerce module should be there under the name "Mastercard Payment Gateway Services - Simplify".

## Configuration

### Configuration steps
Please follow these steps to configure the module:

1. Login to Magento Admin dashboard 
2. Go to *Stores* > *Configuration* > *Sales* > *Payment Methods*
3. Expand *OTHER PAYMENT METHODS*, then *Mastercard Payment Gateway Services - Simplify*
4. Fill in configuration details as described below
5. Click Save Config to store the configuration.
6. Follow Magento instructions and clean application cache, to make sure that the new payment method is immediately available in your online store.  

### Configuration details
A few Payment Options are available under the Simplify Commerce Payment Gateway options group: the Modal Payment Option and the Embedded Payment Option. Both options can be configured separately. Each of these Payment Options has the following settings:

* *Enabled*: should be set to YES to make Simplify Commerce available as a payment method on the checkout page
* *Title*: Name of the payment method displayed on the checkout page
* *Display Order*: position, at which this payment method should be listed on the checkout page
* *Public API Key*: secret key from your Simplify Commerce Merchant Dashboard. For testing the module, please use the Sandbox key. Once you see that payments from Magento 2 with test card numbers are visible in your Simplify Commerce Merchant Dashboard, you should come back here and enter the Live key. From this moment, you will be able to receive payments from real credit cards.
* *Private API Key*: the second secret key from Simplify Commerce Merchant Dashboard. The same rules as above apply.
* *Debug*: Set the option to YES if you want all communication between Magento and the Gateway to be logged into the var/log/mastercard.log file. It usually uses for the Test Mode to troubleshoot problems if the Payment Method doesn't work.
* *Payment Action*: determines when the buyer's card will be charged. If you select *Payment*, the card will be charged immediately. If you select *Authorization*, the payment will be verified and authorized, but no money will be charged yet. Only when you issue an invoice for the received order will the card be charged.
* *New Order Status*: status assigned to a newly created order before the payment has been received
* *Payment from Applicable Countries*: countries from which customers are allowed in your online store
* *Payment from Specific Countries*: This option is available if *Payment from Applicable Countries* option is set to the *Specific Countries* mode. It's possible to choose a few countries for whose the Payment Method will be available. The billing address is used to identify the country on the Checkout Page.
* *Vault*: if enabled, provides store customers with the ability to use the previously saved card information for checkout and the Magento Instant Purchase feature

## License
This software is Open Source, released under the Apache License 2.0. See [LICENSE.md](LICENSE.md) for more info.
