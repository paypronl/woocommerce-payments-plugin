const path = require('path');
const webpack = require('webpack');

const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const DependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');

const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  ...defaultConfig,
  plugins: [
    new DependencyExtractionWebpackPlugin({
      injectPolyfill: true
    }),
    new MiniCssExtractPlugin()
  ],

  resolve: {
    extensions: ['.json', '.js', '.jsx', '.mjs'],
    modules: [path.join(__dirname, 'frontend'), 'node_modules']
  },

  entry: {
    index: './frontend/blocks/index.js'
  }
};
