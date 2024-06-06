<?php

defined('ABSPATH') || exit;

class PayPro_WC_Settingspage extends WC_Settings_Page
{
    public function __construct() {
        $this->id = 'paypro_wc_settings';
        $this->label = __('PayPro', 'paypro-gateways-woocommerce');

        parent::__construct();
    }

    public function output() {
        global $current_section, $hide_save_button;

        $settings = $this->get_settings($current_section);

        WC_Admin_Settings::output_fields($settings);

        if ($current_section == 'webhook') {
            $hide_save_button = true;

            try {
                $webhook_id = get_option('paypro-webhook-id', true);

                $webhook = PayPro_WC_Plugin::$paypro_api->getWebhook($webhook_id);

                echo '<table>
                        <tr>
                          <td><strong>Webhook ID</strong></td>
                          <td style="padding: 5px 15px"><code>' . $webhook?->id . '</code></td>
                        </tr>
                        <tr>
                          <td><strong>Webhook Name</strong></td>
                          <td style="padding: 5px 15px">' . $webhook?->name . '</td>
                        </tr>
                      </table>';
            }
            catch(\PayPro\Exception\ApiErrorException $e) {
                echo sprintf(
                    '<div class="error"><p><strong>PayPro</strong> - %s</p></div>',
                    __($e->getMessage(), 'paypro-gateways-woocommerce')
                );

                submit_button('Create a webhook', 'secondary', 'save');
            }
        }
    }

    public function save() {
        global $current_section;

        if ($current_section == 'webhook') {
            try {
                $webhook_id = PayPro_WC_Plugin::$paypro_api->createWebhook()->id;

                update_option('paypro-webhook-id', $webhook_id);

                WC_Admin_Settings::add_message(__('PayPro webhook was created successfully!', 'paypro-gateways-woocommerce'));
            }
            catch(\PayPro\Exception\ApiErrorException $e) {
                echo sprintf(
                    '<div class="error"><p><strong>PayPro</strong> - %s</p></div>',
                    __($e->getMessage(), 'paypro-gateways-woocommerce')
                );
            }
        } else {
            $settings = $this->get_settings();

            WC_Admin_Settings::save_fields($settings);
        }
    }

    public function get_sections() {
        $sections = array(
            '' => __( 'Settings', 'paypro' ),
            'webhook' => __( 'Webhook', 'paypro' )
        );

        return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
    }

    public function get_settings() {
        global $current_section, $hide_save_button;

        switch ($current_section) {
            case 'webhook':
                $settings = $this->getPayproWebhook();;
                break;
            default:
                $settings = $this->getPayproSettings();
        }

        return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section);
    }

    private function getPayproSettings() {
        return [
            [
                'id'         => $this->getSettingId('title'),
                'type'       => 'title',
                'title'      => __('PayPro settings', 'paypro-gateways-woocommerce'),
                'desc'       => __('The following options are required to use the plugin and are used by all PayPro payment methods', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'         => $this->getSettingId('api-key'),
                'title'      => __('PayPro API key', 'paypro-gateways-woocommerce'),
                'type'       => 'text',
                'desc_tip'   => __('API key used by the PayPro API.', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'         => $this->getSettingId('product-id'),
                'title'      => __('PayPro Product ID', 'paypro-gateways-woocommerce'),
                'type'       => 'text',
                'desc_tip'   => __('Product ID to connect a sale to a product. Not required.', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'         => $this->getSettingId('payment-description'),
                'title'      => __('Description', 'paypro-gateways-woocommerce'),
                'type'       => 'text',
                'desc_tip'   => __('Payment description send to PayPro.', 'paypro-gateways-woocommerce'),
                'css'        => 'width: 350px',
            ],
            [
                'id'         => $this->getSettingId('payment-complete-status'),
                'title'      => __('Payment Complete Status', 'paypro-gateways-woocommerce'),
                'type'       => 'select',
                'default'    => 'wc-processing',
                'options'    => wc_get_order_statuses(),
                'desc_tip'   => __('Set the status of the order after a completed payment. Default: Processing', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'         => $this->getSettingId('automatic-cancellation'),
                'title'      => __('Enable automatic cancellation', 'paypro-gateways-woocommerce'),
                'type'       => 'checkbox',
                'desc_tip'   => __('If a payment is cancelled automatically set the order on cancelled too.', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'         => $this->getSettingId('debug-mode'),
                'title'      => __('Enable debug mode', 'paypro-gateways-woocommerce'),
                'type'       => 'checkbox',
                'desc_tip'   => __('Enables the PayPro plugin to output debug information to the Woocommerce logs.', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'         => $this->getSettingId('sectionend'),
                'type'       => 'sectionend',
            ]
        ];
    }

    private function getPayproWebhook() {
        return [
            [
                'id'         => $this->getSettingId('title'),
                'type'       => 'title',
                'title'      => __('PayPro webhook data', 'paypro-gateways-woocommerce'),
                'desc'       => __('Webhook creation is required to use the plugin. If a webhook is created, you can see its info on this page.', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'         => $this->getSettingId('sectionend'),
                'type'       => 'sectionend',
            ]
        ];
    }

    private function getSettingId($setting) {
        return PayPro_WC_Settings::getId($setting);
    }
}
