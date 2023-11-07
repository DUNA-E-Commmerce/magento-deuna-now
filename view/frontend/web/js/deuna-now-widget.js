/**
 * Required components and environment variable declaration.
 * @type {Array} components - An array of required module names (e.g., 'jquery', 'mage/url').
 * @type {string} environment - Represents the current environment determined based on the hostname.
 */
var components = ['jquery'];
var environment;

/**
 * Gets the current environment based on the hostname and updates the components accordingly.
 * @function getEnvironment
 * @returns {void}
 */

var hostname = document.location.hostname;

function getEnvironment() {
  if (hostname.includes('dev.') || hostname.includes('local.')) {
    environment = 'develop';
    components.push('deuna-cdl-dev');
    components.push('deuna-now-dev');
  } else if (hostname.includes('stg.') || hostname.includes('mcstaging.')) {
    environment = 'staging';
    console.log(environment);
    components.push('deuna-cdl-stg');
    components.push('deuna-now-stg');
  } else {
    environment = 'production';
    components.push('deuna-cdl-prod');
    components.push('deuna-now-prod');
  }
}

getEnvironment();

function onEventDispatch(eventData) {
  // Handle the different events that can be dispatched.
  switch (eventData.eventName) {
    case 'close-modal':
      // Handle close modal event
      console.log('Modal closed');
      break;
    case 'purchase':
      // Handle purchase event
      console.log('Purchase event', eventData.payload);

      fetchJson('POST', hostname + '/rest/V1/deuna/clear-car')
        .then(function (clearCarResponse) {
          if (clearCarResponse) {
            console.log('Success');
            window.location.href = '/checkout/onepage/success/';
          } else {
            console.error('Error while clearing cart.');
          }
        })
        .catch(function (error) {
          console.error('Error while clearing cart:', error);
        });
      break;
    // Add more cases as needed for other events
  }
}

/**
 * Initializes components and handles the click event for the "deuna-button".
 * @function
 * @param {Array} components - An array of required modules (jQuery, DeunaCDL, DeunaNow).
 */
require(components, function ($, DeunaCDL, DeunaNow) {
  'use strict';

  console.log("loading");
  console.log($, DeunaCDL, DeunaNow);

  // $ = jQuery

  var interval = setInterval(checkAndReload, 1000);

  $(document).ready(function () {
    window.DeunaCDL = DeunaCDL;
    window.DeunaPay = DeunaNow;

    var hostname = document.location.origin;

    fetchJson('GET', hostname + '/rest/V1/deuna/public-key')
      .then(function (DEUNA_PUBLIC_KEY) {
        if (!DEUNA_PUBLIC_KEY) {
          alert('Error Getting Keys');
          return;
        }

        console.log('Public Key: ' + DEUNA_PUBLIC_KEY);

        $(document).on('click', '#deuna-button', function (e) {
          e.preventDefault();

          fetchJson('GET', hostname + '/rest/V1/Deuna/token')
            .then(function (tokenResponse) {
              var tokenResponseObject = JSON.parse(tokenResponse);

              if (!tokenResponseObject.orderToken) {
                console.error('Error Generating Order Token:', tokenResponseObject);
                alert('Error Generating Order Token');
                return;
              }

              var orderToken = tokenResponseObject.orderToken;
              var orderTokenString = orderToken.toString();

              console.log('Order Token: ' + orderTokenString);
              console.log('Environment: ' + environment);

              var pay = new window.DeunaPay();

              var configs = {
                orderToken: orderTokenString,
                apiKey: DEUNA_PUBLIC_KEY,
                env: environment,
                onEventDispatch: onEventDispatch
              };

              pay.configure(configs).then(function () {
                pay.show({
                  // ... additional configurations for pay.show if needed
                });
              }).catch(function (error) {
                console.error('Error configuring DeunaPay:', error);
              });

            })
            .catch(function (error) {
              console.error('Error Getting Order Token:', error);
              alert('Error Getting Order Token');
            });
        });

      })
      .catch(function (error) {
        console.error('Error Getting Public Key:', error);
        alert('Error Getting Public Key');
      });
  });


});

/**
 * Fetches JSON data from the specified URL using the GET method and headers.
 * @async
 * @function fetchJson
 * @param {string} method -The Method of request.
 * @param {string} urlRequest - The URL to fetch JSON data from.
 * @returns {Promise} A Promise that resolves with the JSON data retrieved from the URL.
 */
async function fetchJson(method, urlRequest) {
  const response = await fetch(urlRequest, {
    method: method,
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    }
  });

  return await response.json();
}

/**
 * Checks for the existence of an HTML element with the ID 'deuna' and adds a click event listener to it.
 * When the element is clicked, it reloads the current page.
 * @function checkAndReload
 * @returns {void}
 */
function checkAndReload() {
  var radioElement = document.getElementById('deuna');

  if (radioElement) {
    radioElement.addEventListener('click', function () {
      location.reload();
    });
  }
}