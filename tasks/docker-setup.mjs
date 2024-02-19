#!/usr/bin/env zx

const WP_CONTAINER = process.env.WP_CONTAINER || 'paypro_woocommerce_wordpress';

const WP_ADMIN = process.env.WP_ADMIN || 'admin';
const WP_ADMIN_EMAIL = process.env.WP_ADMIN_EMAIL || 'admin@example.org';
const WP_ADMIN_PASSWORD = process.env.WP_ADMIN_PASSWORD || 'admin';

const SITE_URL = process.env.WP_URL || 'localhost:8082';
const SITE_TITLE = process.env.SITE_TITLE || 'WooCommerce PayPro Dev';

let result;

const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

function dr(command) {
  const args = Array.isArray(command) ? command : command.split(' ');
  return $`docker run -it --env-file default.env --rm --user xfs --volumes-from ${WP_CONTAINER} --network container:${WP_CONTAINER} wordpress:cli ${args}`;
}

result = await dr('wp db check --path=/var/www/html').exitCode;

while(result != 0) {
  console.log('Waiting until the service is ready...');
  await sleep(5000);
  result = await dr('wp db check --path=/var/www/html').exitCode;
}

result = await dr('wp plugin is-active paypro-gateways-woocommerce').exitCode;

if(result == 0) {
  console.log('');
  console.log('PayPro WooCommerce gateways plugin is installed and active');
  console.log(`SUCCESS! You should be able to access http://${SITE_URL}/wp-admin/`);
  console.log("You can login by using the username and password both as 'admin'");
  process.exit(0);
}

console.log('');
console.log('Setting up environment...');
console.log('');

console.log('Pulling the WordPress CLI docker image...');
await $`docker pull wordpress:cli`;

console.log('Setting up WordPress...');
await dr([
  'wp',
  'core',
  'install',
  '--path=/var/www/html',
  `--url=${SITE_URL}`,
  `--title=${SITE_TITLE}`,
  `--admin_name=${WP_ADMIN}`,
  `--admin_password=${WP_ADMIN_PASSWORD}`,
  `--admin_email=${WP_ADMIN_EMAIL}`,
  '--skip-email'
]);

console.log('Updating WordPress to the latest version...');
await dr('wp core update --quiet');

console.log('Updating the WordPress database...');
await dr('wp core update-db --quiet');

console.log('Enable WordPress debug flags...');
await dr('config set WP_DEBUG true --raw');
await dr('config set WP_DEBUG_DISPLAY true --raw');
await dr('config set WP_DEBUG_LOG true --raw');
await dr('config set SCRIPT_DEBUG true --raw');

console.log('Enabling WordPress development environment...');
await dr('config set WP_ENVIRONMENT_TYPE development');

console.log('Installing and activating WooCommerce...');
await dr('wp plugin install woocommerce --activate');

console.log('Installing and activating Storefront theme...');
await dr('wp theme install storefront --activate');

console.log('Adding basic WooCommerce settings...');
await dr(['wp', 'option', 'set', 'woocommerce_store_addres', '"Emmaplein 1"']);
await dr(['wp', 'option', 'set', 'woocommerce_store_city', '"Groningen"']);
await dr(['wp', 'option', 'set', 'woocommerce_default_country', '"NL"']);
await dr(['wp', 'option', 'set', 'woocommerce_store_postcode', '"9711AP"']);
await dr(['wp', 'option', 'set', 'woocommerce_currency', '"EUR"']);
await dr(['wp', 'option', 'set', 'woocommerce_product_type', '"both"']);
await dr(['wp', 'option', 'set', 'woocommerce_allow_tracking', '"no"']);

console.log('Importing WooCommerce shop pages...');
await dr('wp wc --user=admin tool run install_pages');

console.log('Installing and activating the WordPress Importer plugin...');
await dr('wp plugin install wordpress-importer --activate');

console.log('Importing some sample data...');
await dr('wp import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=skip');

console.log('Activating the PayPro WooCommerce payments plugin...');
await dr('wp plugin activate paypro-gateways-woocommerce');

console.log();
console.log(`SUCCESS! You should now be able to access http://${SITE_URL}/wp-admin/`);
console.log("You can login by using the username and password both as 'admin'");
