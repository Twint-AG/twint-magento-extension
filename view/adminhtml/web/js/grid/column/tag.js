define([
  'Magento_Ui/js/grid/columns/column',
], function (Column) {
  'use strict';

  return Column.extend({
    defaults: {
      bodyTmpl: 'Twint_Magento/grid/cell/cell'
    },
    getLabel: function (value) {
      return `<span class="twint-method">${value.method}</span>`;
    }
  });
});
