define(['jquery', 'mage/url'], function ($, urlBuilder) {
  'use strict';

  return {
    /**
     * Perform asynchronous GET request to server.
     * @param {String} url
     * @param {Boolean} global
     * @param {String} contentType
     * @param {Object} headers
     * @returns {Deferred}
     */
    get: function (url, global, contentType, headers) {
      headers = headers || {};
      global = global === undefined ? true : global;
      contentType = contentType || 'application/json';

      return $.ajax({
        url: urlBuilder.build(url),
        type: 'GET',
        global: global,
        contentType: contentType,
        headers: headers,
        timeout:3000
      });
    }
  };
});
