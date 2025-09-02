=== Payment Gateway for Cielo API on WooCommerce ===
Contributors: linknacional
Donate link: https://www.linknacional.com.br/wordpress/woocommerce/cielo/
Tags: woocommerce, payment, paymethod, card, credit
Requires at least: 5.7
Tested up to: 6.8
Stable tag: 1.23.0
Requires PHP: 7.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Payment Gateway for Cielo API on WooCommerce.

== Description ==

The **Cielo API Payment Gateway for WooCommerce** is a payment plugin for WooCommerce that allows you to integrate your online store with Cielo, one of Brazil's leading payment solutions. With this plugin, you can offer your customers the ability to pay with **credit** and **debit cards**, with **3DS support**, ensuring secure and fast transactions.

The plugin is easy to configure and enables the use of the main cards accepted by Cielo, integrating natively with WooCommerce and streamlining the checkout experience in your store.

**Main Features:**
- Support for **credit and debit cards**;
- Integration with **3DS** for secure transactions;
- Intuitive configuration directly in WooCommerce;
- Compatible with the main card brands accepted by Cielo.

**PRO Version**
In addition to the free version's features, the **Cielo API Payment Gateway for WooCommerce PRO** offers a range of advanced features to expand your store’s payment options and transaction management:

- **Full and partial refunds** directly from the WooCommerce dashboard;
- Support for **international currency payments**;
- **Recurring payments**, ideal for subscriptions and payment plans;
- Integration with **Pix**, an instant payment solution;
- The ability to offer **installments up to 18x**;
- Configuration of the **maximum number of installments** and **minimum installment amount**, providing greater flexibility in offering payment terms.

With the [PRO version](https://www.linknacional.com.br/wordpress/woocommerce/cielo/), you'll have access to a robust and complete solution to manage all types of transactions, providing a more convenient and personalized shopping experience for your customers.

[youtube https://www.youtube.com/watch?v=5mYIEC9V254]

**Dependencies**

Payment Gateway for Cielo API on WooCommerce plugin is dependent on WooCommerce plugin, please make sure WooCommerce is installed and properly configured before starting Payment Gateway for Cielo API on WooCommerce installation.

This plugin uses the Cielo API 3.0 to process payments. [terms of service](https://www.cielo.com.br/termos-condicoes-de-uso/) and [privacy policy](https://ri.cielo.com.br/estatuto-social-e-politicas-old/politica-de-privacidade-de-dados/).

JS Libraries used
This plugin uses the [React Credit Cards](https://github.com/amaroteam/react-credit-cards#readme) library to render an animated card in the checkout page.

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
= 1.24.0 =
** 02/09/2022**
* Addition of payment information in the

= 1.23.0 =
** 24/07/2025**
* Add PRO configuration to display the "Finalize and Generate PIX" button at checkout.

= 1.22.0 =
** 08/07/2025**
* New layout for credit and debit payment gateways.

= 1.21.5 =
** 23/06/2025**
* Fix Pix display issue.

= 1.21.4 =
** 20/06/2025**
* Fix payment processing logic.

= 1.21.3 =
** 18/06/2025**
* Fix final adjustments.

= 1.21.2 =
** 16/06/2025**
* Addition of new layout for the settings page(new version).

= 1.21.1 =
** 16/06/2025**
* Old version.

= 1.21.0 =
**30/05/2025**
* Addition of new layout for the settings page.

= 1.20.0 =
**23/05/2025**
* Add compatibility with PRO configuration to add discounts on installments.
* Add compatibility with PRO configuration to set the minimum value for interest-free installments.

= 1.19.2 =
**24/04/2025**
* Fix installment submission for payment methods.

= 1.19.1 =
**07/04/2025**
* Update descriptions of payment methods.

= 1.19.0 =
**20/03/2025**
* Add message to inform when credentials are incorrect;
* Duplicate scripts.

= 1.18.0 =
**17/03/2025**
* Payment method Pix.

= 1.17.2 =
**12/03/2025**
* Validation of the online card option at the time of payment;
* Fix currency display in installments.

= 1.17.1 =
**17/02/2025**
* Correction of reference for minimum value of installments;
* Update hooks reference to avoid collisions;
* Correction of nonce validation when configuration is disabled.

= 1.17.0 =
**06/02/2025**
* Added compatibility with retry settings for subscriptions.

= 1.16.0 =
**31/01/2025**
* Added the download notice for the plugin: fraud-detection-for-woocommerce.
* Added the plugin rating message in the footer.

= 1.15.0 =
**20/12/2024**
* Add function to renew token;
* Add configuration to display transaction logs in the order;
* Add animated card in the block editor checkout;
* Add configuration to show and hide animated card;
* Add automatic tab selection when the settings page is reloaded;
* Change credit payment method label;
* Change endpoint URLs.

= 1.14.1 =
**06/12/2024**
* Fix bug on show detailed logs on order.

= 1.14.0 =
**05/12/2024**
* Add compatibility with button to capture order;
* Add settings to include logs in the order;
* Fix layout script.

= 1.13.2 =
**02/12/2024**
* Fix for installment limitation hook;
* Fix for BIN query event in legacy form.

= 1.13.1 =
**29/11/2024**
* Fix order value formatting.

= 1.13.0 =
**25/11/2024**
* Add configuration to allow transactions without 3DS;
* Add filters to change card query URL;
* Change settings layout;
* Fix logger call.

= 1.12.1 =
**06/11/2024**
* Add fixes for 3DS 2.2 authentication.

= 1.12.0 =
**22/10/2024**
* Add compatibility with new WooCommerce Cielo PRO features.

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