import { sprintf, __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

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

['paypro_wc_gateway_ideal', 'paypro_wc_gateway_mistercash'].forEach((name) => {
  const settings = getSetting(`${name}_data`, {} );
  const label = decodeEntities(settings.title);

  let content;
  if (name == 'paypro_wc_gateway_ideal') {
    content = <IdealContent issuers={settings.issuers}/>
  } else {
    content = <Content />
  }

  /**
   * Label component
   *
   * @param {*} props Props from payment API.
   */
  const Label = (props) => {
    const { PaymentMethodLabel } = props.components;
    return <PaymentMethodLabel text={ label } />;
  };

  const PaymentMethod = {
    name: name,
    label: (
      <span
        className="pp-woocommerce-label"
      >
        { settings.title }
        <img
          className="pp-woocommerce-icon"
          src={ settings.iconUrl }
          alt={ decodeEntities(settings.title) }
        />
      </span>
    ),
    content: content,
    edit: content,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
      features: settings.supports,
    }
  };

  registerPaymentMethod(PaymentMethod);
});
