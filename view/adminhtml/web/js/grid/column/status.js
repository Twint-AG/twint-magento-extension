define([
  'Magento_Ui/js/grid/columns/column',
  'mage/translate'
], function (Column, $t) {
  'use strict';

  return Column.extend({
    defaults: {
      bodyTmpl: 'Twint_Magento/grid/cell/cell'
    },
    getLabel: function (value) {
      let statusLabel = $t('Status');
      let pairingLabel = $t('Pairing status');
      let transactionLabel = $t('Transaction status');

      let status = value.status ? `<span class="twint-status">${statusLabel}: ${value.status} </span></br>` : '';
      let pairingStatus = value.pairing_status ? `<span class="twint-status">${pairingLabel}: ${value.pairing_status}</span> </br>` : '';
      let transactionStatus = value.transaction_status ? `<span class="twint-status">${transactionLabel}: ${value.transaction_status}</span>` : '';

      return `<div>
        ${status}
        ${pairingStatus}
        ${transactionStatus}
      </div>`;
    }
  });
});
