const Encore = require('@symfony/webpack-encore');
const webpack = require('webpack');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/bootstrap.js')
    .addEntry('auth', './assets/styles/pages/auth.css')
    .splitEntryChunks()
    .enableStimulusBridge('./assets/controllers.json')
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.38';
    })
    .enableSassLoader()
;

const config = Encore.getWebpackConfig();
config.resolve = config.resolve || {};
config.resolve.fallback = { ...(config.resolve.fallback || {}), crypto: false, path: false, vm: false };
config.plugins = config.plugins || [];
config.plugins.push(
    new webpack.NormalModuleReplacementPlugin(/^node:/, (resource) => {
        resource.request = resource.request.replace(/^node:/, '');
    })
);

module.exports = config;
