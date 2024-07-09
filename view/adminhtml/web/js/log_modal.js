define([
  'jquery',
  'Magento_Ui/js/modal/modal',
  'mage/translate'
], function ($, modal, $t) {
  'use strict';

  return function (config) {
    var logModal = $('#log-modal');

    modal(config, logModal);

    $(document).on('click', '[data-action="log-view"]', function () {
      var logId = $(this).data('log-id');
      $.ajax({
        url: config.url,
        data: { id: logId },
        type: 'GET',
        dataType: 'json',
        success: function (response) {
          logModal.html($t('Log Content: ') + response.log_content);
          logModal.modal('openModal');
        }
      });
    });

    return logModal;
  };
});
