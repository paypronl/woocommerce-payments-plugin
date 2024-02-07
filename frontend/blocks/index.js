import { registerPaymentMethod } from '@woocommerce/blocks-registry';

import bancontactPaymentMethod from './bancontact';

registerPaymentMethod(bancontactPaymentMethod);
