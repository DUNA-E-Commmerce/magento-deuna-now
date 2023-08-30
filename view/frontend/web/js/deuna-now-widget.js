/**
 * Required components and environment variable declaration.
 * @type {Array} components - An array of required module names (e.g., 'jquery', 'mage/url').
 * @type {string} environment - Represents the current environment determined based on the hostname.
 */
var components = [ 'jquery'];
var environment;

/**
 * Gets the current environment based on the hostname and updates the components accordingly.
 * @function getEnvironment
 * @returns {void}
 */
function getEnvironment() {
  let hostname = document.location.hostname;

  if ( hostname.includes('dev.') || hostname.includes('local.')){
    environment = 'develop';
    components.push('deuna-cdl-dev');
    components.push('deuna-now-dev');
  } else if (hostname.includes('stg.') || hostname.includes('mcstaging.')){
    environment = 'staging';
    components.push('deuna-cdl-stg');
    components.push('deuna-now-stg');
  } else {
    environment = 'production';
    components.push('deuna-cdl-prod');
    components.push('deuna-now-prod');
  }
}
getEnvironment();

/**
 * Initializes components and handles the click event for the "deuna-button".
 * @function
 * @param {Array} components - An array of required modules (jQuery, DeunaCDL, DeunaNow).
 */
require(components, function ($, DeunaCDL, DeunaNow) {
  'use strict';

  $(document).ready(async function () {

    window.DeunaCDL = DeunaCDL;
    window.DeunaPay = DeunaNow;

    var hostname = document.location.origin;

    const DEUNA_PUBLIC_KEY = await fetchJson(hostname + '/rest/V1/deuna/public-key');

    if (!DEUNA_PUBLIC_KEY){
      alert('Error Getting Keys');
      return;
    }

    console.log('Public Key: ' + DEUNA_PUBLIC_KEY);

    $(document).on('click', '.deuna-button', async function (e) {

      var tokenResponse = await fetchJson(hostname + '/rest/V1/Deuna/token');

      var tokenResponseObject = JSON.parse(tokenResponse);

      if (!tokenResponseObject.orderToken){
        console.log(tokenResponseObject);
        alert('Error Generating Order Token');
        return;
      }
      var orderToken = tokenResponseObject.orderToken;
      var orderTokenString = orderToken.toString();

      console.log('Order Token: ' + orderTokenString);
      console.log('Environment: ' + environment);

      var pay = new window.DeunaPay();

      const configs = {
        orderToken: orderTokenString,
        apiKey: DEUNA_PUBLIC_KEY,
        env: environment,
      }

      pay.configure(configs);
      const params = {
        callbacks: {
          onPaymentSuccess: () => {
            console.log('Success');
            window.location.href = '/checkout/onepage/success/';
          },
          onClose: () => {
            console.log('Error');
          }
        }
      }

      pay.show(params)

      e.preventDefault();
    });

  });

});

/**
 * Fetches JSON data from the specified URL using the GET method and headers.
 * @async
 * @function fetchJson
 * @param {string} urlRequest - The URL to fetch JSON data from.
 * @returns {Promise} A Promise that resolves with the JSON data retrieved from the URL.
 */
async function fetchJson(urlRequest) {

  const response = await fetch(urlRequest, {
    method: "GET",
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    }
  });

  return await response.json();
}
