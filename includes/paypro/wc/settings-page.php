<?php

defined('ABSPATH') || exit;

/**
 * Class to render the PayPro plugin settings.
 */
class PayPro_WC_SettingsPage extends WC_Settings_Page {
    /**
     * Constuctor
     */
    public function __construct() {
        $this->id    = 'paypro_wc_settings';
        $this->label = __('PayPro', 'paypro-gateways-woocommerce');

        parent::__construct();
    }

    /**
     * Override method to render the ouput of the settings page.
     */
    public function output() {
        global $current_section, $hide_save_button;

        $settings = $this->get_settings($current_section);

        WC_Admin_Settings::output_fields($settings);

        if ('webhook' === $current_section) {
            $hide_save_button = true;

            $webhook_id = PayPro_WC_Settings::webhookId();

            if (empty($webhook_id)) {
                submit_button('Create a webhook', 'secondary', 'save');
            } else {
                try {
                    $webhook      = PayPro_WC_Plugin::$paypro_api->getWebhook($webhook_id);
                    $webhook_name = $webhook ? $webhook->name : null;

                    $html = '<table>
                        <tr>
                          <td><strong>Webhook ID</strong></td>
                          <td style="padding: 5px 15px"><code>' . $webhook_id . '</code></td>
                        </tr>
                        <tr>
                          <td><strong>Webhook Name</strong></td>
                          <td style="padding: 5px 15px">' . $webhook_name . '</td>
                        </tr>
                    </table>';

                    echo wp_kses(
                        $html,
                        [
                            'table'  => [],
                            'tr'     => [],
                            'td'     => [
                                'style' => [],
                            ],
                            'strong' => [],
                            'code'   => [],
                        ]
                    );
                } catch (\PayPro\Exception\ApiErrorException $e) {
                    $debug_message = sprintf(
                        'Failed to load the webhook %1$s - Message: %2$s',
                        $webhook_id,
                        $e->getMessage()
                    );

                    PayPro_WC_Logger::log($debug_message);

                    $message = sprintf(
                        /* translators: %s contains the webhook id of the PayPro webhook */
                        __('Failed to load the saved webhook (%s)', 'paypro-gateways-woocommerce'),
                        $webhook_id
                    );

                    PayPro_WC_Plugin::addAdminNotice('error', $message);
                }
            }
        }
    }

    /**
     * Do the relevant save action for a specific section.
     */
    public function save() {
        global $current_section;

        if ('webhook' === $current_section) {
            try {
                $webhook_url = WC()->api_request_url('paypro_webhook');

                $webhook = PayPro_WC_Plugin::$paypro_api->createWebhook(
                    'WooCommerce',
                    'Webhook for WooCommerce PayPro plugin',
                    $webhook_url
                );

                update_option($this->getSettingId('webhook-id'), $webhook->id);
                update_option($this->getSettingId('webhook-secret'), $webhook->secret);

                WC_Admin_Settings::add_message(__('PayPro webhook was created successfully!', 'paypro-gateways-woocommerce'));
            } catch (\PayPro\Exception\ApiErrorException $e) {
                $debug_message = sprintf(
                    'Failed to create the webhook - Message %s',
                    $e->getMessage()
                );

                PayPro_WC_Logger::log($debug_message);

                $message = __('Failed to create the webhook', 'paypro-gateways-woocommerce');
                PayPro_WC_Plugin::addAdminNotice('error', $message);
            }
        } else {
            $this->updateApiKey();

            $settings = $this->get_settings();
            WC_Admin_Settings::save_fields($settings);
        }
    }

    /**
     * Pass the PayPro setting sections to WC.
     */
    public function get_sections() {
        $sections = [
            ''        => __('Settings', 'paypro-gateways-woocommerce'),
            'webhook' => __('Webhook', 'paypro-gateways-woocommerce'),
        ];

        /**
         * Add the PayPro setting sections to the WC settings.
         *
         * @since 1.0.0
         */
        return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
    }

    /**
     * Get the settings for the current PayPro section.
     */
    public function get_settings() {
        global $current_section, $hide_save_button;

        switch ($current_section) {
            case 'webhook':
                $settings = $this->getPayproWebhookSection();
                break;
            default:
                $settings = $this->getPayproSettingsSection();
        }

        /**
         * Add the PayPro settings to the WC settings.
         *
         * @since 1.0.0
         */
        return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section);
    }

    /**
     * Returns the PayPro settings to be added to the WC settings.
     */
    private function getPayproSettingsSection() {
        $description = __('The following options are required to use the plugin and are used by all PayPro payment methods', 'paypro-gateways-woocommerce');
        $content     = "<p>{$description}</p>";

        if (PayPro_WC_Plugin::apiValid()) {
            $content = $this->checkApiConnectivity($content);
        }

        return [
            [
                'id'    => $this->getSettingId('title'),
                'type'  => 'title',
                'title' => __('PayPro settings', 'paypro-gateways-woocommerce'),
                'desc'  => $content,
            ],
            [
                'id'       => $this->getSettingId('api-key'),
                'title'    => __('PayPro API key', 'paypro-gateways-woocommerce'),
                'type'     => 'text',
                'desc_tip' => __('API key used by the PayPro API.', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'       => $this->getSettingId('product-id'),
                'title'    => __('PayPro Product ID', 'paypro-gateways-woocommerce'),
                'type'     => 'text',
                'desc_tip' => __('Product ID to connect a sale to a product. Not required.', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'       => $this->getSettingId('payment-description'),
                'title'    => __('Description', 'paypro-gateways-woocommerce'),
                'type'     => 'text',
                'desc_tip' => __('Payment description send to PayPro.', 'paypro-gateways-woocommerce'),
                'css'      => 'width: 350px',
            ],
            [
                'id'       => $this->getSettingId('payment-complete-status'),
                'title'    => __('Payment Complete Status', 'paypro-gateways-woocommerce'),
                'type'     => 'select',
                'default'  => 'wc-processing',
                'options'  => wc_get_order_statuses(),
                'desc_tip' => __('Set the status of the order after a completed payment. Default: Processing', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'       => $this->getSettingId('automatic-cancellation'),
                'title'    => __('Enable automatic cancellation', 'paypro-gateways-woocommerce'),
                'type'     => 'checkbox',
                'desc_tip' => __('If a payment is cancelled automatically set the order on cancelled too.', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'       => $this->getSettingId('debug-mode'),
                'title'    => __('Enable debug mode', 'paypro-gateways-woocommerce'),
                'type'     => 'checkbox',
                'desc_tip' => __('Enables the PayPro plugin to output debug information to the Woocommerce logs.', 'paypro-gateways-woocommerce'),
            ],
            [
                'id'   => $this->getSettingId('sectionend'),
                'type' => 'sectionend',
            ],
        ];
    }

    /**
     * Returns the PayPro webhook settings to be added to the WC settings.
     */
    private function getPayproWebhookSection() {
        $description = __('Webhook creation is required to use the plugin. If a webhook is created, you can see its info on this page.', 'paypro-gateways-woocommerce');
        $content     = "<p>{$description}</p>";

        if (PayPro_WC_Plugin::apiValid()) {
            $content = $this->checkApiConnectivity($content);
        }

        return [
            [
                'id'    => $this->getSettingId('title'),
                'type'  => 'title',
                'title' => __('PayPro webhook data', 'paypro-gateways-woocommerce'),
                'desc'  => $content,
            ],
            [
                'id'   => $this->getSettingId('sectionend'),
                'type' => 'sectionend',
            ],
        ];
    }

    /**
     * Checks if we can connect to the API to determine if all settings are correct.
     * Appends the content of the settings description if this is not the case.
     *
     * @param string $content Content of the settings description.
     */
    private function checkApiConnectivity($content) {
        try {
            PayPro_WC_Plugin::$paypro_api->getPayMethods();
        } catch (\PayPro\Exception\AuthenticationException $e) {
            /* translators: %s contains a link to the PayPro dashboard API keys page */
            $message  = sprintf(__('Could not connect with the API. Please, check the API key supplied. You can find your API keys at %s', 'paypro-gateways-woocommerce'), '<a href="https://app.paypro.nl/developers/api-keys">https://app.paypro.nl/developers/api-keys</a>');
            $content .= "<div class=\"notice notice-error\"><p><strong>PayPro</strong> - {$message} </p></div>";
        } catch (\PayPro\Exception\ApiErrorException $e) {
            $message  = __('Could not connect with the API. Check that your server can connect to https://api.paypro.nl', 'paypro-gateways-woocommerce');
            $content .= "<div class=\"notice notice-error\"><p><strong>PayPro</strong> - {$message} </p></div>";
        }

        return $content;
    }

    /**
     * Update the API key of the client to the newly submitted value. This ensures we use the correct
     * API key to check conectivity.
     */
    private function updateApiKey() {
        $nonce       = filter_input(INPUT_POST, '_wpnonce', FILTER_SANITIZE_SPECIAL_CHARS);
        $nonce_valid = $nonce && wp_verify_nonce($nonce, 'woocommerce-settings');

        if (!$nonce_valid) {
            return;
        }

        $api_key_name = 'paypro-gateways-woocommerce_api-key';
        $api_key      = isset($_POST[$api_key_name]) ? sanitize_text_field(wp_unslash($_POST[$api_key_name])) : nil;

        if ($api_key) {
            PayPro_WC_Logger::log('settings setApiKey - ' . $api_key);
            PayPro_WC_Plugin::$paypro_api->setApiKey($api_key);
        }
    }

    /**
     * Gets the setting ID used by the plugin for all settings.
     *
     * @param string $setting Name of the setting.
     */
    private function getSettingId($setting) {
        return PayPro_WC_Settings::getId($setting);
    }
}

return new PayPro_WC_Settingspage();
