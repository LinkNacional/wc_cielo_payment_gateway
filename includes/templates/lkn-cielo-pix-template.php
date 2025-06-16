<?php
if (! defined('ABSPATH')) {
    exit();
}
$telegramIconPath = LKN_WC_GATEWAY_CIELO_DIR . 'resources/img/telegram.svg';
$telegramIconContent = file_get_contents($telegramIconPath);

?>


<div class="lknCieloApiProPixFieldsWrapper">
    <div>
        <img
            src="data:image/jpeg;base64,<?= $base64Image ?>"
            alt=""
            width="320px"
        >
    </div>
</div>
<div id="lknCieloShareModal">
    <div id="lknCieloShareModalContent">
        <div id="lknCieloApiProCloseModalDiv">
            <button
                id="lknCieloApiProCloseModal"
                onclick="changeModalVisibility()"
            >×</button>
        </div>
        <div id="lknCieloApiProTitleButtonsDiv">
            <p id="lknCieloApiProShareTitle">
                <?php esc_attr_e('Share with', 'lkn-wc-gateway-cielo') ?>
            </p>
            <div id="lknCieloApiProShareButtons">
                <a
                    id="lknCieloShareButtonIconWhatsapp"
                    class="lknCieloApiProShareButtonLink"
                >
                    <button class="button alt wp-element-button lknCieloApiProShareButton">
                        <div class="lknCieloShareButtonIcon dashicons dashicons-whatsapp"></div>
                    </button>
                </a>
                <a
                    id="lknCieloShareButtonIconEmail"
                    class="lknCieloApiProShareButtonLink"
                >
                    <button
                        id="lknCieloShareButtonIconEmail"
                        class="button alt wp-element-button lknCieloApiProShareButton"
                    >
                        <div class="lknCieloShareButtonIcon dashicons dashicons-email"></div>
                    </button>
                </a>
                <a
                    id="lknCieloShareButtonIconTelegram"
                    class="lknCieloApiProShareButtonLink"
                >
                    <button
                        id="lknCieloShareButtonIconTelegram"
                        class="button alt wp-element-button lknCieloApiProShareButton"
                    >
                        <div class="lknCieloShareButtonIcon dashicons">
                            <?php echo $telegramIconContent; ?>
                        </div>
                    </button>
                </a>
            </div>
        </div>
    </div>
</div>
<div class="lknCieloApiProSharePixCodeDiv">
    <button
        class="button alt wp-element-button"
        id="lknCieloSharePixCodeButton"
    >
        <p>
            <?php esc_attr_e('Share', 'lkn-wc-gateway-cielo') ?>
        </p>
    </button>
</div>
<div class="lknCieloApiProPixCodeDiv">
    <span
        class="woocommerce-input-wrapper"
        id="lknCieloPixCodeSpan"
    >
        <input
            type="text"
            class="input-text"
            id="lknCieloPixCodeInput"
            readonly
            value="<?= $pixString ?>"
        >
        <button
            class="button alt wp-element-button"
            id="lknCieloPixCodeButton"
        >
            <?php esc_attr_e('Copy', 'lkn-wc-gateway-cielo') ?>
        </button>
    </span>
</div>