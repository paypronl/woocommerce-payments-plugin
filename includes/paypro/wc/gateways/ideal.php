<?php

defined('ABSPATH') || exit;

/**
 * Gateway to handle iDEAL on the checkout.
 */
class PayPro_WC_Gateway_Ideal extends PayPro_WC_Gateway_Abstract {
    /**
     * Constructor
     */
    public function __construct() {
        $this->supports = [
           'products',
        ];

        $this->issuer                 = 'ideal';
        $this->subscription_method    = 'directdebit';
        $this->supports_subscriptions = true;
        $this->has_fields             = true;

        parent::__construct();
    }

    /**
     * Returns the title of the gateway.
     *
     * @return string Title of the gateway
     */
    public function getTitle() {
        return __('iDEAL', 'paypro-gateways-woocommerce');
    }

    /**
     * Returns the description of the gateway.
     *
     * @return string Description of the gateway
     */
    public function getDescription() {
        return __('Select your bank', 'paypro-gateways-woocommerce');
    }

    /**
     * Returns the description of the gateway.
     *
     * @return array Additonal information for the gateway.
     */
    public function getAdditionalPaymentData() {
        return [ 'issuer' => $this->getIssuer() ];
    }

    /**
     * Renders the iDEAL issuer select for the old checkout.
     */
    public function payment_fields() {
        parent::payment_fields();

        $ideal_issuers   = PayPro_WC_Plugin::$ideal_issuers;
        $selected_issuer = $this->getIssuer();
        $html            = '<select name="' . PayPro_WC_Plugin::PLUGIN_ID . '_issuer_' . $this->id . '">';

        $html .= '<option value=""></option>';

        foreach ($ideal_issuers as $issuer) {
            $selected = $selected_issuer === $issuer['code'] ? ' selected="selected"' : '';
            $html    .= '<option value="' . esc_attr($issuer['code']) . '"' . $selected . '>' . esc_html($issuer['name']) . '</option>';
        }

        $html .= '</select>';

        echo wp_kses(
            $html,
            [
                'select' => [
                    'name'  => [],
                    'id'    => [],
                    'class' => [],
                ],
                'option' => [
                    'value'    => [],
                    'selected' => [],
                ],
            ]
        );
    }

    /**
     * Returns the selected issuer.
     *
     * It checks in the following order:
     *  - Old checkout iDEAL issuer
     *  - Block based checkout iDEAL issuer
     */
    private function getIssuer() {
        $issuer_id = PayPro_WC_Plugin::PLUGIN_ID . '_issuer_' . $this->id;

        //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        return wp_unslash($_POST[$issuer_id] ?? null) ?? wp_unslash($_POST['selected_issuer'] ?? null);
    }
}
