(function($) {
    $(document).ready(function() {
        var vat_percentage_fixed_container = $('input#paypro-gateways-woocommerce_vat-percentage-fixed').parents('tr');
        var vat_percentage_setting_select = $('select#paypro-gateways-woocommerce_vat-percentage-setting');

        vat_percentage_setting_select.change(function() {
            if ($(this).val() == 'fixed') {
                vat_percentage_fixed_container.show();
            } else {
                vat_percentage_fixed_container.hide();
            }
        });

        vat_percentage_setting_select.change();
    });
})(jQuery);
