![Logo](http://dev.heidelpay.com/devHeidelpay_400_180.jpg)

# heidelpay plentymarkets-gateway plugin
This extension provides an integration of the heidelpay payment methods for your plentymarkets shop.

Currently supported payment methods are:
* Credit Card
* Debit Card
* Direct Debit
* Sofort.

## SYSTEM REQUIREMENTS
This plugin is designed fo Plentymarkets 7.

## LICENSE
For licensing information please refer to the attached LICENSE.txt.

## Release notes
This module is based on the heidelpay php-payment-api (https://github.com/heidelpay/php-payment-api) and heidelpay php-basket-api (https://github.com/heidelpay/php-basket-api).

## Installation
+ Please refer to [plentyKnowledge](https://knowledge.plentymarkets.com) in order to learn how to install plugins.
+ After performing the configuration steps described below you should be able to perform some tests in staging mode.
+ If everything is fine you can change the configuration to live mode and deploy the plugin for the productive area to enable it for your clients.

## Configuration
### Basic configuration
+ Select the Plugin-tab and then "Plugin overview"
+ Select the heidelpay plugin to switch to the configuration overview.
+ Now enter your credentials and channel configurations and activate the channels you want to use.

>*By default all configuration is set to work with the test-payment server.* (ref. https://dev.heidelpay.com/sandbox-environment/).

>*You will have to hit the save-button for each tab individually in order to save the entered data, otherwise Plenty will reset to the original data on tab change.*

>*In addition it takes plenty quite some time to show the updated Information on the tabs. You may have to reload the **Plugin overview**-tab in order to refresh the config-tabs and to make sure the entered data is indeed stored.*

### The configuration parameters
#### heidelpay settings
###### Test-/Livesystem
* Select parameter *'Test environment (CONNECTOR_TEST)'* to enable connection to the test environment, in which case any transactions will be transferred to the sandbox and will not be charged.  
Please make sure to use test credentials and channel-ids when this option is selected (ref. https://dev.heidelpay.com/sandbox-environment/).
* Select parameter *'Production environment (LIVE)'* to enable live mode which means that actual transactions will be executed and charged.
Please make sure to use your live credentials and channel-ids when this option is selected.

###### Sender-Id
The id necessary to connect to the heidelpay payment server.\
It should be given to you by your heidelpay contact person.

###### Login
The login necessary to connect to the heidelpay payment server.\
It should be given to you by your heidelpay contact person.

###### Password
The password necessary to connect to the heidelpay payment server.\
It should be given to you by your heidelpay contact person. 

##### Payment Method Parameters
###### Active
If checked the payment method will be selectable on the checkout page

###### Display Name
The name the payment method is shown under on the checkout page. \
A default name will be shown if left empty.

###### Channel-Id
This identifies the channel of the payment method. \
It should be given to you by your heidelpay contact person.

###### Min-/Max-Total
The payment method will only be available if the basket has a total between these values.
Setting one of those values to 0 will disable the corresponding limitation.

###### Transaction Mode
* Select option **Direct debit** to enable the payment method to debit the total immediately from the given account.
* Select option **Authorisation with capture** to enable pre-authorization and capture the total later.

> **Info:** Some payment methods allow for pre-authorization, which is basically a declaration of intent for the debit of the basket total.
The total is captured later, usually when the shipping takes place. This provides for the opportunity to only debit the totals of the items actually shipped, e.g. when the basket is divided into several shipments.

###### URL for custom-css in the iframe
Some payment methods are shown within an iframe rendering a form for the customer data.
Enter the url to a custom css-file in order to change the look of the form.
If left empty a default css is applied. \
Prerequisites for the url string:
* it must be reachable from the internet
* it must start with 'http://' or 'https://'
* it must end with '.css'

###### URL to payment icon
This defines an icon for the payment method which is shown within checkout in addition to the display name.
If left empty the default icon is used. \
Prerequisites for the url string:
* it must be reachable from the internet
* it must start with 'http://' or 'https://'
* it must end with '.jpg', '.png' or '.gif'

## Workflow description
### Credit Card and Debit Card
* If the payment method is configured to use *Transaction Mode* *'Direct debit'* the payment will be created immediately and referenced to the order.
There are no additional steps necessary to capture the amount.\
If the payment is successful, the order is immediately marked paid in your backend.\
If the payment fails, the order is not created and the customer will be redirected to the checkout page.
* If the payment method is configured to use *Transaction Mode* *'Authorisation with capture'* only the order will be created but the payment must be captured manually from the hip.
The capture transaction will then be pushed to the plenty shop (url given in the channel configuration of the hip).
This will create a payment in your plenty shop which will be referenced to the order.

### Sofort.
The payment will be created after the customer has been returned to the shop and as soon as the receipt transaction is being pushed to the shop. \
The order will be created immediately.

### Direct Debit
The payment will be created immediately and referenced to the order. \
There are no additional steps necessary to capture the amount.\
If the payment is successful, the order is immediately marked paid in your backend.\
If the payment fails, the order is not created and the customer will be redirected to the checkout page.

### All payment methods
* Payments contain the TxnId (which is the heidelpay orderId), the shortId (the id of the transaction which lead to the payment i.e. Receipt, Debit or Capture) and the origin (i.e. heidelpay).
* In case of an error resulting in the order not being created while the payment has been successful will lead to an unassigned plenty payment with the error message prepended to the booking text.
Unfortunately there is no way for us to re-create the order if the initial order creation fails, even if the payment has been successfully booked in our backend.
However you will be able to tell there has been an error when there are unassigned payments in your plenty backend showing an error in the booking text.

## Issues and Troubleshooting
If you run into any questions or issues with this plugin please do one of the following:
* Sent and email with the question or issue description to development@heidelpay.com
* Add a GitHub issue [here](https://github.com/heidelpay/plentymarkets-gateway/issues)