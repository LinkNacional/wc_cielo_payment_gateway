=== Payment Gateway for Cielo API on WooCommerce ===
Contributors: linknacional
Donate link: https://www.linknacional.com.br/wordpress/woocommerce/cielo/
Tags: woocommerce, payment, paymethod, card, credit
Requires at least: 5.7
Tested up to: 6.5
Stable tag: 1.9.3
Requires PHP: 7.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Payment Gateway for Cielo API on WooCommerce.

== Description ==

**Dependencies**

Payment Gateway for Cielo API on WooCommerce plugin is dependent on WooCommerce plugin, please make sure WooCommerce is installed and properly configured before starting Payment Gateway for Cielo API on WooCommerce installation.

**User instructions**

1. Go to WooCommerce settings menu;

2. Select the 'Payments' option and search for 'Cielo credit card / Cielo debit card';

3. Enter all necessary credentials for each payment gateway;

4. Enter in the WooCommerce checkout page and select 'Cielo credit card / Cielo debit card' option;

5. Enter your card information;

6. Click in the finish payment button.

== Installation ==

1. Look in the sidebar for the WordPress plugins area;

2. In installed plugins look for the option 'add new';

3. Click on the 'send plugin' option in the page title and upload the lkn-wc-gateway-cielo.zip plugin;

4. Click on the 'install now' button and then activate the installed plugin;

5. Now go to WooCommerce settings menu;

6. Select the 'Payments' option and search for 'Cielo credit card / Cielo debit card';

7. Enter all necessary credentials for each payment gateway;

8. Click on save.

== Frequently Asked Questions ==

= What is the plugin license? =

* This plugin is released under a GPL license.

= What is needed to use this plugin? =

* WooCommerce version 5.0 or latter installed and active;
* Cielo API 3.0 credentials.

== Screenshots ==

1. Credit card configuration of payment method;

2. Debit card configuration of payment method;

3. Credit card front page with payment fields;

4. Debit card front page with payment fields.

== Changelog ==

= 1.9.3 =
**18/06/2024**
* Add compatibility with the Orders Auto-Complete functionality of the pro plugin;
* Fixed rendering issue for Cielo Debit 3DS card.

= 1.9.2 =
**05/06/2024**
* Correction of validation in the cardholder name field;

= 1.9.1 =
**23/05/2024**
* Correction of error in the cardholder field;
* Correction of error in validating fields in the new checkout template;
* Adjustments to installment recognition on invoices and non-standard WooCommerce pages.

= 1.9.0 =
**22/05/2024**
* Fixed error on form field inputs;
* Add error treatment for cardholder input field;
* Add cardholder input;
* Add be pro button.

= 1.8.1 =
**16/05/2024**
* Fixed error on the edit form page in blocks;

= 1.8.0 =
**10/05/2024**
* Added support for block-based checkout;
* Addition of hosting banner for Wordpress;
* Addition of mandatory fields for credit and debit card credentials;
* Improvement in error handling;

= 1.7.0 =
**25/03/2024**
* Fixed 3DS script loading bug in the debit card option;
* Added logic to handle API responses when the return code is "GF";
* Adjustment of the security code label;
* Addition of description in debit card settings;
* Addition of validation in the invoice description field;

= 1.6.0 =
**27/11/2023**
* 3DS script loading bug fix;
* Added display of additional card information in order details;
* Correction of mask bug in debit card fields;
* Removal of deprecated functions from JQuery;
* Added 'view logs' button in plugin settings.

= 1.5.0 =
* Implemented loading of global attributes to installment script.

= 1.4.0 =
* Validation compatibility mode implementation;
* Tweaks to settings descriptions;
* Update notice links;
* Storage of the number of installments in the order metadata;
* Added amount of installments in the thank you page and in the new order email.

= 1.3.2 =
* Fixed duplicate validation messages;
* Options loading tweaks;
* Admin area scripts directory change.

= 1.3.1 =
* Script load optimization;
* Removed unnecessary script loading.

= 1.3.0 =
* Field mask library refactor;
* Implemented preparation for interest recognition;
* Implemented warnings area on the front-end.

= 1.2.0 =
* Implemented BIN filter;
* Fixed input bugs on checkout page;
* Optimized script loading;
* Fixed debit card payment bug on checkout page.

= 1.1.0 =
* Installments implementation;
* Documentation update;
* Fixed validation error for expiration date input;
* Fixed 3DS initialization.

= 1.0.0 =
* Plugin launch.

== Upgrade Notice ==

= 1.0.0 =
* Plugin launch.
