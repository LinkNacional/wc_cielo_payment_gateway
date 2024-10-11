=== Payment Gateway for Cielo API on WooCommerce ===
Contributors: linknacional
Donate link: https://www.linknacional.com.br/wordpress/woocommerce/cielo/
Tags: woocommerce, payment, paymethod, card, credit
Requires at least: 5.7
Tested up to: 6.6
Stable tag: 1.11.5
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

1. Payment methods credit and debit card.
2. Debit card configuration page.
3. Debit card configuration page.
4. Credit card configuration page.
5. Credit card configuration page.
6. Credit card front page with payment fields.
7. Debit card front page with payment fields.

== Changelog ==
= 1.11.5 =
**10/10/2024**
* Fix nonce validation;
* Change placeholders.

= 1.11.4 =
**04/10/2024**
* Added compatibility mode for nonce validation;
* Fixed error on invalid credit card submission.

= 1.11.3 =
**02/10/2024**
* Added improvements to field validation;
* Adjustments to redirection in case of payment failure;
* Fixed order with installments with interest has a different total value of the product.

= 1.11.2 =
**27/09/2024**
* Fix placeholders on WooCommerce classic checkout;
* Fix of calculation of installment amounts for customers with active Cielo PRO.

= 1.11.1 =
**25/09/2024**
* Fix installment values when changes are made to the cart.

= 1.11.0 =
**02/09/2024**
* Add translations for CPF field for Pix payments.
* Add compatibility with configuration to change status when the order is paid.

= 1.10.0 =
**16/08/2024**
* Add credit payments with 3DS validation;
* Add BIN validation to automatically select card type;
* Fix bug on debit 3DS render;
* Better compatibility with WooCommerce Cielo PRO;
* Add plugin dependence on WooCommerce.

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
