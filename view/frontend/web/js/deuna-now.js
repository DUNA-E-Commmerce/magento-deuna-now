console.log('Deuna Now');

const pay = window.DeunaPay();

const configs = {
    orderToken: 'aaaa',
    apiKey: 'aaaa',
    env: 'staging',
    target: 'deunanow'
}

pay.configure(configs)
