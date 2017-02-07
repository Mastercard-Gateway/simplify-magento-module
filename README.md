Simplify Commerce Magento 2 Module
==================================

Overview
--------
```
WARNING! WORK IN PROGRESS!
```


Installation
------------

Installation using Composer
---------------------------

1.	Under the Magento root folder run the following command to setup the repository:
```
composer config repositories.simplifycommerce git https://github.com/ nnnnn .git
```
2.	Then run the following command to add the module:
```
composer require mastercard/module-simplifycommerce
```
3.	Following this the dependencies will be fetched or updated where required – this may take a few minutes.
4.	Once all dependencies have installed the module needs to be enabled using the following commands:
```
php bin/magento module:enable MasterCard_SimplifyCommerce --clear-static-content && php bin/magento setup:upgrade
```
5.	Once the setup:upgrade command completes the module will be available within the Store Admin to configure.


Manual Installation
-------------------

1.	Download the latest archive of the module from https://github.com/ nnnnn .zip
2.	Copy the archive to the server and extract – the files should be extracted into the Magento /app/code folder.
3.	Run enable the module by running the following commands:
```
php bin/magento module:enable MasterCard_SimplifyCommerce --clear-static-content && php bin/magento setup:upgrade
```
4.	Once the setup:upgrade command completed the module will be available within the Store Admin to configure


Configuration
-------------
To configure the module the following steps should be taken:

1.	Login to the Magento Admin area 
2.	From the menu on the left hand side select Stores and then Configuration
3.  Under the Configuration menu select Sales and then Payment Methods
4.	Under the Simplify Commerce payment method set the configuration details as required
5.	Once the configuration has been entered click Save Config – this will commit the changes to the database. The payment method can now be tested.

