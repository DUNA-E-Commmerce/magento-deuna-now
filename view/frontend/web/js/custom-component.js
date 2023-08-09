
console.log('Custom button here!');

function isDev() {
  var hostname = document.location.hostname;

  return hostname.includes('dev.') || hostname.includes('local.');
}

function isStaging() {
  var hostname = document.location.hostname;

  return hostname.includes('stg.') || hostname.includes('mcstaging.');
}

if (isDev()) {
  env = 'Develop';
} else if (isStaging()) {
  env = 'Staging';
} else {
  env = 'Prod';
}

let deuna_widget_version = 'v0.1';

console.log(`Env: ${env} ${deuna_widget_version}`);

let components = [
  'jquery',
  'uiComponent',
  'ko',
  'mage/url'
];

let deunaEnv;

if (isDev()) {
  deunaEnv = 'develop';
  components.push('https://cdn.dev.deuna.io/cdl/index.js');
  components.push(`https://cdn.stg.deuna.io/now-widget/${deuna_widget_version}/index.js`);
} else if (isStaging()) {
  deunaEnv = 'staging';
  components.push('https://cdn.stg.deuna.io/cdl/index.js');
  components.push(`https://cdn.stg.deuna.io/now-widget/${deuna_widget_version}/index.js`);
} else {
  deunaEnv = 'production';
  components.push('https://cdn.getduna.com/cdl/index.js');
  components.push(`https://cdn.stg.deuna.io/now-widget/${deuna_widget_version}/index.js`);
}

const data = {
  order_type: "DEUNA_NOW",
  order: {
    order_id: "kpjh0zVDzfwHVGB8GXmsqVN1PyhuXfhjzQTcBF5",
    store_code: "13006",
    currency: "MXN",
    tax_amount: 0,
    display_tax_amount: "MXN 0.00",
    shipping_amount: 0,
    display_shipping_amount: "MXN 0.00",
    items_total_amount: 8534,
    display_items_total_amount: "MXN 85.34",
    sub_total: 9900,
    total_tax_amount: 5000,
    display_sub_total: "MXN 99.00",
    total_amount: 14900,
    display_total_amount: "MXN 149.00",
    items: [
      {
        id: "MEDPIZ",
        name: "Original",
        description: "Masa fresca hecha a mano al momento.",
        options: "",
        total_amount: {
          amount: 9900,
          original_amount: 0,
          display_amount: "MXN 99.00",
          display_original_amount: "MXN 0.00",
          currency: "MXN",
          currency_symbol: "$",
          total_discount: 0,
          display_total_discount: "MXN 0.00",
        },
        unit_price: {
          amount: 9900,
          display_amount: "MXN 99.00",
          currency: "MXN",
          currency_symbol: "$",
        },
        tax_amount: {
          amount: 0,
          display_amount: " 0.00",
          currency: "",
          currency_symbol: "",
        },
        quantity: 1,
        uom: "",
        upc: "",
        sku: "",
        isbn: "",
        brand: "",
        manufacturer: "",
        category: "",
        color: "",
        size: "",
        weight: {
          weight: 0,
          unit: "",
        },
        image_url:
          "https://olodominos.blob.core.windows.net/dev/webOptimized/flavors/buildPizza.png",
        details_url: "",
        type: "",
        taxable: false,
        discounts: [],
      },
    ],
    billing_address: {
      first_name: "Juan",
      last_name: "Duarte",
      phone: "+51992248719",
      identity_document: "1111111111",
      lat: -0.1602236,
      lng: -78.49664,
      address1: "Av. del Parque, Quito 130152, Ecuador",
      address2: "20",
      city: "Quito",
      zipcode: "130152",
      state_name: "Pichincha",
      state_code: "PICHINCHA",
      country: "EC",
      additional_description: "12",
      address_type: "home",
      email: "test@gmail.com"
    },
    discounts: [
      {
        amount: 50,
        display_amount: "",
        code: "MF99O",
        reference: "",
        description: "Mediana Favorita 1 ing",
        details_url: "",
        free_shipping: {
          is_free_shipping: false,
          maximum_cost_allowed: 0,
        },
        discount_category: "coupon",
        target_type: "",
        type: "",
      },
    ],
    shipping_address: {
      id: 0,
      user_id: "",
      first_name: "Roberto",
      last_name: "Rosales",
      phone: "3222222229",
      identity_document: "",
      lat: 0,
      lng: 0,
      address1: "Piramide del Sol",
      address2: "boom",
      city: "New Mexico",
      zipcode: "1456667",
      state_name: "Hola",
      country_code: "MX",
      country: "MX",
      additional_description: "",
      address_type: "other",
      is_default: true,
      created_at: "0001-01-01T00:00:00Z",
      updated_at: "0001-01-01T00:00:00Z",
    },
    shipping_options: {
      type: "delivery",
      details: {
        store_name: "VS001 - 13006",
        address: "Piramide del Sol 1, San Juan Teotihuacan, MX, C.P. 55800",
        address_coordinates: {
          lat: 19.692435,
          lng: -98.843596,
        },
        contact: {
          name: "",
          phone: "5552417171",
        },
        additional_details: {
          pickup_time: "2022-08-08T14:58:20.721Z",
          stock_location: "",
        },
      },
    },
    user_instructions: "",
    status: "pending",
    payment: {
      data: {
        amount: {
          amount: 0,
          currency: "",
        },
        metadata: {},
        from_card: {
          card_brand: "",
          first_six: "",
          last_four: "",
        },
        updated_at: "0001-01-01 00:00:00 +0000 UTC",
        method_type: "",
        merchant: {
          store_code: "",
          id: "",
        },
        created_at: "0001-01-01 00:00:00 +0000 UTC",
        id: "",
        processor: "",
        customer: {
          email: "",
          id: "",
        },
        status: "",
        reason: "",
      },
    },
    gift_card: [],
    redirect_url: "",
    webhook_urls: {
      notify_order: "",
      apply_coupon: "https://railway-nodejs.onrender.com/applyCoupons/{orderId}",
      remove_coupon: "https://railway-nodejs.onrender.com/removeCoupons/{orderId}/code/{couponCode}",
      get_shipping_methods: "https://railway-nodejs.onrender.com/{orderId}",
      update_shipping_method: "https://railway-nodejs.onrender.com/{orderId}/{codeMethod}",
      shipping_rate: "",
      sync_notify_order: ""
    },
    total_discount: 2000,
    display_total_discount: "MXN 0.00",
    shipping: {
      original_amount: 0,
      total_discount: 0,
      discounts: [],
    },
    cash_change: 0,
    shipping_method: {
      code: "pickup",
      name: "",
      min_delivery_date: "",
      max_delivery_date: "",
      cost: 0,
      display_cost: "",
      tax_amount: 0,
      display_tax_amount: "",
      scheduler: [],
    },
    shipping_methods: [
      {
        code: "pickup",
        name: "En mostrador",
        min_delivery_date: "",
        max_delivery_date: "",
        cost: 0,
        display_cost: "",
        tax_amount: 1366,
        display_tax_amount: "",
        scheduler: [],
      },
    ],
  },
};

console.log(`Components: ${components}`);

const DEUNA_PUBLIC_API_KEY = 'ab88c4b4866150ebbce7599c827d00f9f238c34e42baa095c9b0b6233e812ba54ef13d1b5ce512e7929eb4804b0218365c1071a35a85311ff3053c5e23a6';


async function getOrderToken(Url) {


  const tokenUrl = Url.build('rest/V1/DUna/token');

  // $.ajax({
  //   method: 'GET',
  //   url: tokenUrl
  // })
  // .done(async function (data) {
  //     // Configure Modal based on data returned from token endpoint
  //     await self.configure(data);
  //     // Trigger DEUNA Checkout Modal
  //     await self.dunaCheckout.show();
  // })
  // .error(function (error, status, message) {
  //     alert(`Error DEUNA (${status}): Falló la petición para obtener el token de pago.`);

  //     window.location.reload();
  // });


  const response = await fetch(`https://api.stg.deuna.io/merchants/orders`, {
    method: "POST",
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'x-api-key': DEUNA_PUBLIC_API_KEY
    },
    body: JSON.stringify(data),
  });
  const newResponse = await response.json();

  return newResponse.token;

}


require([
  'jquery',
  'uiComponent',
  'ko',
  'mage/url',
  'deuna-cdl',
  'deuna-now',
], function ($, Component, ko, Url, DeunaCDL, DeunaNow) {
  'use strict';



  $(document).ready(function () {

    // function addCdnScript(url) {
    //   var scriptElement = document.createElement("script");
    //   scriptElement.src = url;
    //   document.head.appendChild(scriptElement);
    // }

    // addCdnScript("https://cdn.stg.deuna.io/cdl/index.js");
    // addCdnScript("https://cdn.dev.deuna.io/checkout-widget/v1.0.0/index.js");

    $(document).on('click', '.deuna-button', async function (e) {

      window.DeunaCDL = DeunaCDL;
      window.DeunaPay = DeunaNow;
      
      console.log('Deuna Now');
      var orderToken = await getOrderToken(Url);
      console.log('Order Token: ' + orderToken);

      const configs = {
        orderToken: orderToken,
        apiKey: DEUNA_PUBLIC_API_KEY,
        env: 'staging',
      }

      var pay = new window.DeunaPay();
      pay.configure(configs);

      const params = {
        callbacks: {
          onPaymentSuccess: () => {
            // Código para el manejo de éxito de pago
          },
          onClose: () => {
            // Código para el manejo de cierre
          }
        }
      }

      pay.show(params)

      console.log('DeunaCheckout');

      e.preventDefault();
      console.log('Custom button clicked!');
    });

  });



});
