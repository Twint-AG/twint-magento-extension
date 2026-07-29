define([
  'Magento_Ui/js/grid/columns/column',
], function (Column) {
  'use strict';

  return Column.extend({
    defaults: {
      bodyTmpl: 'Twint_Magento/grid/cell/cell'
    },
    getLabel: function (value) {
      let cell = ``;
      let actions = JSON.parse(value.soap_action);
      for (const action of actions) {
        cell += `<span class="twint-tag">${action}</span>`;
      }

      return cell;
    }
  });
});
