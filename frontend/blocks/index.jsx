import { sprintf, __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

import { PAYMENT_METHODS } from './constants';

import './index.scss';

const IdealContent = (props) => {
  const { eventRegistration, emitResponse, issuers } = props;
  const { onPaymentProcessing } = eventRegistration;

  const [issuer, setIssuer] = useState('');

  const options = issuers.map((issuer) => <option value={issuer.id} key={issuer.id}>{issuer.name}</option>);

  const updateSelect = (event) => {
    setIssuer(event.target.value)
  };

  useEffect(() => {
    const unsubscribe = onPaymentProcessing(async () => {
      if (issuer === '') {
        return {
          type: emitResponse.responseTypes.ERROR,
          message: __('Please select your bank', 'paypro-gateways-woocommerce')
        }
      }

      return {
        type: emitResponse.responseTypes.SUCCESS,
        meta: {
          paymentMethodData: {
            selected_issuer: issuer
          }
        }
      };
    });

    return () => {
      unsubscribe();
    };
  },
  [
      emitResponse.responseTypes.ERROR,
      emitResponse.responseTypes.SUCCESS,
      onPaymentProcessing,
      issuer
  ]);

  return (
    <select onChange={updateSelect}>
      <option value=""></option>
      { options }
    </select>
  );
};

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

  let content;
  if (name == 'paypro_wc_gateway_ideal') {
    content = <IdealContent issuers={settings.issuers}/>
  } else {
    content = <Content />
  }

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
