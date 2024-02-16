#!/usr/bin/env zx

import colors from 'colors';
import archiver from 'archiver';
import fs from 'fs';

const pluginSlug = 'paypro-gateways-woocommerce';

const releaseFolder = 'release';
const targetFolder = `${releaseFolder}/${pluginSlug}`;

const directoriesToCopy = [
  'assets',
  'build',
  'includes',
  'languages',
  'vendor'
];

// Cleanup folders
await $`rm -rf ${releaseFolder}`;

// Create folders
await $`mkdir ${releaseFolder}`;
await $`mkdir ${targetFolder}`;

// Install PHP prod dependencies
await $`composer install --no-dev`

// Move folders
for(const directory of directoriesToCopy) {
  await $`cp -R ${directory}/ ${targetFolder}`;
}

// Move files
await $`cp resources/readme.txt ${targetFolder}`;
await $`cp LICENSE.md ${targetFolder}/license.txt`;
await $`cp paypro-gateways-woocommerce.php ${targetFolder}`;

// Create ZIP file
const output = fs.createWriteStream(`${releaseFolder}/${pluginSlug}.zip`);
const archive = archiver('zip', { zlib: { level: 9 } });

output.on('close', () => {
  console.log(colors.green(`Done: Check the built in the ${releaseFolder} folder.`))
});

archive.on('error', (error) => {
  console.error(colors.red(`Error: Could not create the zip: ${error}`));
});

archive.pipe(output);
archive.directory(targetFolder, pluginSlug);
archive.finalize();
