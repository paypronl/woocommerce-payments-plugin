<?php

defined('ABSPATH') || exit;

class PayPro_WC_Gateway_Ideal extends PayPro_WC_Gateway_Abstract
{
    public function __construct()
    {
        $this->supports = array(
            'products',
        );

        $this->issuer = 'ideal';
        $this->has_fields = true;

        parent::__construct();
    }

    public function getTitle()
    {
        return __('iDEAL', 'paypro-gateways-woocommerce');
    }

    public function getDescription()
    {
        return __('Select your bank', 'paypro-gateways-woocommerce');
    }

    public function getAdditionalPaymentData()
    {
        return ['issuer' => $this->getIssuer()];
    }

    public function payment_fields()
    {
        parent::payment_fields();

        $ideal_issuers = PayPro_WC_Plugin::$paypro_api->getIdealIssuers();

        $selected_issuer = $this->getSelectedIssuer();

        $html = '<select name="' . PayPro_WC_Plugin::PLUGIN_ID . '_issuer_' . $this->id . '">';
        $html .= '<option value=""></option>';

        foreach($ideal_issuers['issuers'] as $issuer)
        {
            $html .= '<option value="' . esc_attr($issuer['id']) . '"' . ($selected_issuer == $issuer['id'] ? ' selected="selected"' : '') .  '>' . esc_html($issuer['name']) . '</option>';
        }

        $html .= '</select>';

        echo wpautop(wptexturize($html));
    }

    /**
     * Returns the selected issuer.
     *
     * It checks in the following order:
     *  - Old checkout iDEAL issuer
     *  - Block based checkout iDEAL issuer
     */
    private function getIssuer()
    {
        $issuer_id = PayPro_WC_Plugin::PLUGIN_ID . '_issuer_' . $this->id;

        if(!empty($_POST[$issuer_id]))
           return strval($_POST[$issuer_id]);
        elseif(!empty($_POST['selected_issuer']))
            return strval($_POST['selected_issuer']);
        else
            return null;
    }
}
