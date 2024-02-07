export default {
  name: 'Bancontact',
  content: <div>Content</div>,
  edit: <div>Edit</div>,
  canMakePayment: () => true,
  paymentMethodId: 'paypro_wc_gateway_bancontact',
  supports: {
    features: []
  },
  label: <div>Label</div>,
  ariaLabel: <div>AriaLabel</div>
};
