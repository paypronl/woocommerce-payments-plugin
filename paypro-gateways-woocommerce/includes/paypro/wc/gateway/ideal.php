<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly

class PayPro_WC_Gateway_Ideal extends PayPro_WC_Gateway_Abstract
{
	public function __construct()
	{
		$this->supports = array(
			'products',
		);

		$this->has_fields = TRUE;

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
}