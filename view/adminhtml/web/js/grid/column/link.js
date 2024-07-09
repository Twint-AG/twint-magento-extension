define([
  'Magento_Ui/js/grid/columns/column',
], function (Column) {
  'use strict';

  return Column.extend({
    defaults: {
      bodyTmpl: 'Twint_Magento/grid/cell/link'
    },
    getLabel: function (value) {
      const getLink = (href, label) => {
        return `<a href="${href}" class="twint-request-log">${label}</a>`;
      };

      const href = value.actions.view.href;
      const label = value.actions.view.label;

      return getLink(href, label);
    }
  });
});
