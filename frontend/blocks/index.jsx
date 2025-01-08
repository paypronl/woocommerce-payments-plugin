import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

import { PAYMENT_METHODS } from './constants';

import './index.scss';

const Content = () => {
  return '';
};

const Label = (props) => {
  return (
    <span
      className="pp-woocommerce-label"
    >
      { props.title }
      <img
        className="pp-woocommerce-icon"
        src={ props.iconUrl }
        alt={ props.title }
      />
    </span>
  );
};

PAYMENT_METHODS.forEach((name) => {
  const settings = getSetting(`${name}_data`, {} );
  const title = decodeEntities(settings.title);
  const content = <Content />;

  const PaymentMethod = {
    name: name,
    label: <Label title={title} iconUrl={settings.iconUrl} />,
    content: content,
    edit: content,
    canMakePayment: () => true,
    ariaLabel: title,
    supports: {
      features: settings.supports,
    }
  };

  registerPaymentMethod(PaymentMethod);
});
