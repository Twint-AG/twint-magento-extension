require([
  'jquery',
  'Magento_Customer/js/customer-data'
], function ($, customerData) {
  'use strict';

  $(document).ready(function () {
    const sections = ['cart'];
    customerData.invalidate(sections);
    customerData.reload(sections, true);
  });
});
