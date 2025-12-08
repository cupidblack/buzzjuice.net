const https = require('https');

const options = {
  hostname: 'buzzjuice.net', // or 'localhost'
  port: 3002,                // your SSL port
  path: '/streams/nodejs',   // or '/' depending on your app
  method: 'GET',
  timeout: 5000
};

const req = https.request(options, (res) => {
  console.log(`STATUS: ${res.statusCode}`);
  if (res.statusCode === 200) {
    console.log('SSL Server is UP and responding.');
    process.exit(0);
  } else {
    console.log('SSL Server responded, but not OK:', res.statusCode);
    process.exit(1);
  }
});

req.on('error', (e) => {
  console.error(`Problem with request: ${e.message}`);
  process.exit(2);
});

req.on('timeout', () => {
  console.error('Request timed out.');
  req.destroy();
  process.exit(3);
});

req.end();