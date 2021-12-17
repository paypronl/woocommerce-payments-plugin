#!/bin/bash

woocommerce_link="https://downloads.wordpress.org/plugin/woocommerce.6.0.0.zip"

wordpress_plugins_folder="/var/www/html/wp-content/plugins"
temp_folder_woocommerce="/var/tmp/woocommerce"

# check if tmp folder exists if not create folder
[ ! -d "/var/tmp" ] && mkdir "/var/tmp"

# check if tmp folder for woocommerce exists if not create folder
[ ! -d $temp_folder_woocommerce ] && mkdir $temp_folder_woocommerce

# download woocommerce<version>.zip and save as woocommerce.zip in tmp folder
curl -L $woocommerce_link > $temp_folder_woocommerce/woocommerce.zip

# unzip downloaded woocommerce.zip
unzip -q $temp_folder_woocommerce/woocommerce.zip -d $temp_folder_woocommerce

# move zip content to plugins folder
mv $temp_folder_woocommerce/woocommerce $wordpress_plugins_folder

# remove tmp zip
rm $temp_folder_woocommerce/woocommerce.zip