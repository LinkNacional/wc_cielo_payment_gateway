<?php
/**
 * Cielo Debit Card Payment Fields - Default Layout Template
 * 
 * This template contains the original/default layout for debit card payment fields.
 * Used when checkout_layout option is set to 'default'.
 * 
 * @since 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

?>

<fieldset
    id="wc-<?php echo esc_attr($gateway_id); ?>-cc-form"
    class="wc-credit-card-form wc-payment-form"
    style="background:transparent;">

    <p class="debit-card-description">
        <?php echo esc_html($description); ?>
    </p>
    
    <div class="cielo-debit-fields-wrapper">
        <?php if ('yes' === $show_card_animation) { ?>
        <div
            id="cielo-debit-card-animation"
            class="card-wrapper card-animation"></div>
        <?php } ?>
        <div class="wc-payment-cielo-form-fields">
    <input
        type="hidden"
        id="lkn_cielo_3ds_installment_show"
        value="no" />
    <input
        type="hidden"
        name="nonce_lkn_cielo_debit"
        class="nonce_lkn_cielo_debit"
        value="<?php echo esc_attr($nonce); ?>" />
    <input
        type="hidden"
        name="lkn_auth_enabled"
        class="bpmpi_auth"
        value="true" />
    <input
        type="hidden"
        name="lkn_auth_enabled_notifyonly"
        class="bpmpi_auth_notifyonly"
        value="true" />
    <input
        type="hidden"
        name="lkn_auth_suppresschallenge"
        className="bpmpi_auth_suppresschallenge"
        value="false" />
    <input
        type="hidden"
        name="lkn_access_token"
        class="bpmpi_accesstoken"
        value="<?php echo esc_attr($access_token['access_token']); ?>" />
    <input
        type="hidden"
        name="lkn_expires_in"
        id="expires_in"
        value="<?php echo esc_attr($access_token['expires_in']); ?>" />
    <input
        type="hidden"
        size="50"
        name="lkn_order_number"
        class="bpmpi_ordernumber"
        value="<?php echo esc_attr(uniqid()); ?>" />
    <input
        type="hidden"
        name="lkn_currency"
        class="bpmpi_currency"
        value="BRL" />
    <input
        type="hidden"
        size="50"
        id="lkn_cielo_3ds_value"
        name="lkn_amount"
        class="bpmpi_totalamount"
        value="<?php echo esc_attr($total_cart_3ds); ?>" />
    <input
        type="hidden"
        size="2"
        name="lkn_installments"
        class="bpmpi_installments"
        value="1" />
    <input
        type="hidden"
        name="lkn_payment_method"
        class="bpmpi_paymentmethod"
        value="Debit" />
    <input
        type="hidden"
        id="lkn_bpmpi_cardnumber"
        class="bpmpi_cardnumber" />
    <input
        type="hidden"
        id="lkn_bpmpi_expmonth"
        maxlength="2"
        name="lkn_card_expiry_month"
        class="bpmpi_cardexpirationmonth" />
    <input
        type="hidden"
        id="lkn_bpmpi_expyear"
        maxlength="4"
        name="lkn_card_expiry_year"
        class="bpmpi_cardexpirationyear" />
    <input
        type="hidden"
        id="lkn_bpmpi_default_card"
        name="lkn_default_card"
        className="bpmpi_default_card"
        value="false" />
    <input
        type="hidden"
        id="lkn_bpmpi_order_recurrence"
        name="lkn_order_recurrence"
        className="bpmpi_order_recurrence"
        value="false" />
    <input
        type="hidden"
        size="50"
        class="bpmpi_order_productcode"
        value="PHY" />
    <input
        type="hidden"
        size="50"
        className="bpmpi_transaction_mode"
        value="S" />
    <input
        type="hidden"
        size="50"
        class="bpmpi_merchant_url"
        value="<?php echo esc_attr($url); ?>" />
    <input
        type="hidden"
        size="14"
        id="lkn_bpmpi_billto_customerid"
        name="lkn_card_customerid"
        className="bpmpi_billto_customerid"
        value="<?php echo esc_attr($billing_document); ?>" />
    <input
        type="hidden"
        size="120"
        id="lkn_bpmpi_billto_contactname"
        name="lkn_card_contactname"
        className="bpmpi_billto_contactname"
        value="<?php echo esc_attr($name); ?>" />
    <input
        type="hidden"
        size="15"
        id="lkn_bpmpi_billto_phonenumber"
        name="lkn_card_phonenumber"
        className="bpmpi_billto_phonenumber"
        value="<?php echo esc_attr($billing_phone); ?>" />
    <input
        type="hidden"
        size="255"
        id="lkn_bpmpi_billto_email"
        name="lkn_card_email"
        className="bpmpi_billto_email"
        value="<?php echo esc_attr($email); ?>" />
    <input
        type="hidden"
        size="60"
        id="lkn_bpmpi_billto_street1"
        name="lkn_card_billto_street1"
        className="bpmpi_billto_street1"
        value="<?php echo esc_attr($billing_address_1); ?>" />
    <input
        type="hidden"
        size="60"
        id="lkn_bpmpi_billto_street2"
        name="lkn_card_billto_street2"
        className="bpmpi_billto_street2"
        value="<?php echo esc_attr($billing_address_2); ?>" />
    <input
        type="hidden"
        size="50"
        id="lkn_bpmpi_billto_city"
        name="lkn_card_billto_city"
        className="bpmpi_billto_city"
        value="<?php echo esc_attr($billing_city); ?>" />
    <input
        type="hidden"
        size="2"
        id="lkn_bpmpi_billto_state"
        name="lkn_card_billto_state"
        className="bpmpi_billto_state"
        value="<?php echo esc_attr($billing_state); ?>" />
    <input
        type="hidden"
        size="8"
        id="lkn_bpmpi_billto_zipcode"
        name="lkn_card_billto_zipcode"
        className="bpmpi_billto_zipcode"
        value="<?php echo esc_attr($billing_postcode); ?>" />
    <input
        type="hidden"
        size="2"
        id="lkn_bpmpi_billto_country"
        name="lkn_card_billto_country"
        className="bpmpi_billto_country"
        value="<?php echo esc_attr($billing_country); ?>" />
    <input
        type="hidden"
        id="lkn_bpmpi_shipto_sameasbillto"
        name="lkn_card_shipto_sameasbillto"
        className="bpmpi_shipto_sameasbillto"
        value="true" />
    <input
        type="hidden"
        id="lkn_bpmpi_useraccount_guest"
        name="lkn_card_useraccount_guest"
        className="bpmpi_useraccount_guest"
        value="<?php echo esc_attr($user_guest); ?>" />
    <input
        type="hidden"
        id="lkn_bpmpi_useraccount_authenticationmethod"
        name="lkn_card_useraccount_authenticationmethod"
        className="bpmpi_useraccount_authenticationmethod"
        value="<?php echo esc_attr($authentication_method); ?>" />
    <input
        type="hidden"
        size="45"
        id="lkn_bpmpi_device_ipaddress"
        name="lkn_card_device_ipaddress"
        className="bpmpi_device_ipaddress"
        value="<?php echo esc_attr($client_ip); ?>" />
    <input
        type="hidden"
        size="7"
        id="lkn_bpmpi_device_channel"
        name="lkn_card_device_channel"
        className="bpmpi_device_channel"
        value="Browser" />
    <input
        type="hidden"
        size="10"
        id="lkn_bpmpi_brand_establishment_code"
        name="lkn_card_brand_establishment_code"
        className="bpmpi_brand_establishment_code"
        value="<?php echo esc_attr($bec); ?>" />
    <input
        type="hidden"
        id="lkn_cavv"
        name="lkn_cielo_3ds_cavv"
        value="true" />
    <input
        type="hidden"
        id="lkn_eci"
        name="lkn_cielo_3ds_eci"
        value="true" />
    <input
        type="hidden"
        id="lkn_ref_id"
        name="lkn_cielo_3ds_ref_id"
        value="true" />
    <input
        type="hidden"
        id="lkn_version"
        name="lkn_cielo_3ds_version"
        value="true" />
    <input
        type="hidden"
        id="lkn_xid"
        name="lkn_cielo_3ds_xid"
        value="true" />

    <?php do_action('woocommerce_credit_card_form_start', $gateway_id); ?>

    <div class="form-row form-row-wide">
        <label
            for="lkn_dc_cardholder_name"><?php esc_html_e('Card Holder Name', 'lkn-wc-gateway-cielo'); ?>
            <span class="required">*</span></label>
        <input
            id="lkn_dc_cardholder_name"
            name="lkn_dc_cardholder_name"
            type="text"
            autocomplete="cc-name"
            required
            placeholder="<?php echo $placeholder_enabled ? esc_attr('John Doe') : ''; ?>"
            data-placeholder="<?php echo $placeholder_enabled ? esc_attr('John Doe') : ''; ?>"
            class="lkn-wc-gateway-cielo-input">
    </div>

    <div class="form-row form-row-wide">
        <label
            for="lkn_dcno"><?php esc_html_e('Card Number', 'lkn-wc-gateway-cielo'); ?>
            <span class="required">*</span></label>
        <input
            id="lkn_dcno"
            name="lkn_dcno"
            type="tel"
            inputmode="numeric"
            class="lkn-card-num lkn-wc-gateway-cielo-input"
            maxlength="24"
            required
            placeholder="<?php echo $placeholder_enabled ? esc_attr('XXXX XXXX XXXX XXXX') : ''; ?>"
            data-placeholder="<?php echo $placeholder_enabled ? esc_attr('XXXX XXXX XXXX XXXX') : ''; ?>">
    </div>
    <div class="form-row form-row-wide">
        <label
            for="lkn_dc_expdate"><?php esc_html_e('Expiry Date', 'lkn-wc-gateway-cielo'); ?>
            <span class="required">*</span></label>
        <input
            id="lkn_dc_expdate"
            name="lkn_dc_expdate"
            type="tel"
            inputmode="numeric"
            class="lkn-card-exp lkn-wc-gateway-cielo-input"
            maxlength="7"
            required
            placeholder="<?php echo $placeholder_enabled ? esc_attr('MM/YY') : ''; ?>"
            data-placeholder="<?php echo $placeholder_enabled ? esc_attr('MM/YY') : ''; ?>">
    </div>
    <div class="form-row form-row-wide">
        <label
            for="lkn_dc_cvc"><?php esc_html_e('Security Code', 'lkn-wc-gateway-cielo'); ?>
            <span class="required">*</span></label>
        <input
            id="lkn_dc_cvc"
            name="lkn_dc_cvc"
            type="tel"
            inputmode="numeric"
            autocomplete="off"
            class="lkn-cvv lkn-wc-gateway-cielo-input"
            maxlength="4"
            required
            placeholder="<?php echo $placeholder_enabled ? esc_attr('CVV') : ''; ?>"
            data-placeholder="<?php echo $placeholder_enabled ? esc_attr('CVV') : ''; ?>">
    </div>
    <div class="form-row form-row-wide">
        <label
            for="lkn_cc_type"><?php esc_html_e('Card type', 'lkn-wc-gateway-cielo'); ?>
            <span class="required">*</span>
        </label>
        <select
            id="lkn_cc_type"
            name="lkn_cc_type"
            class="input-select wc-credit-card-form-card-cvc">
            <option
                value="Credit"><?php esc_html_e('Credit card', 'lkn-wc-gateway-cielo'); ?>
            </option>
            <option value="Debit">
                <?php esc_html_e('Debit card', 'lkn-wc-gateway-cielo'); ?>
            </option>
        </select>
    </div>

    <?php if ('yes' === $active_installment) { ?>
        <input
            id="lkn_cc_dc_installment_total"
            type="hidden"
            value="<?php echo esc_attr($total_cart); ?>">
        <input
            id="lkn_cc_dc_no_login_checkout"
            type="hidden"
            value="<?php echo esc_attr($no_login_checkout); ?>">
        <input
            id="lkn_cc_dc_installment_limit"
            type="hidden"
            value="<?php echo esc_attr($installment_limit); ?>">
        <input
            id="lkn_cc_dc_installment_min"
            type="hidden"
            value="<?php echo esc_attr($installment_min); ?>">
        <input
            id="lkn_cc_dc_installment_interest"
            type="hidden"
            value="<?php echo esc_attr(wp_json_encode($installments)); ?>">
        <input
            id="lkn_cc_dc_fees_total"
            type="hidden"
            value="<?php echo esc_attr($fees_total); ?>">
        <input
            id="lkn_cc_dc_taxes_total"
            type="hidden"
            value="<?php echo esc_attr($taxes_total); ?>">
        <input
            id="lkn_cc_dc_discounts_total"
            type="hidden"
            value="<?php echo esc_attr($discounts_total); ?>">

        <div
            id="lkn-cc-dc-installment-row"
            class="form-row form-row-wide">
            <label
                for="lkn_cc_dc_installments"><?php esc_html_e('Installments', 'lkn-wc-gateway-cielo'); ?>
                <span class="required">*</span>
            </label>
            <select
                id="lkn_cc_dc_installments"
                name="lkn_cc_dc_installments"
                class="input-select wc-credit-card-form-card-cvc">
                <option
                    value="1"
                    selected="1">1 x R$0,00 sem juros</option>
            </select>
        </div>
    <?php } ?>

    <div class="clear"></div>

    <?php do_action('woocommerce_credit_card_form_end', $gateway_id); ?>

    <div class="clear"></div>
        </div>
    </div>

</fieldset>