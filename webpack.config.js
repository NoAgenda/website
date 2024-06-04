const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
  .setOutputPath('public/build/')
  .setPublicPath('/build')

  .addEntry('app', './assets/app.js')
  .enableStimulusBridge('./assets/controllers.json')
  .splitEntryChunks()
  .enableSingleRuntimeChunk()

  .cleanupOutputBeforeBuild()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .enableSassLoader();

const appConfig = Encore.getWebpackConfig();
appConfig.name = 'app';

Encore.reset();

Encore
  .setOutputPath('public/console-build/')
  .setPublicPath('/console-build')

  .addEntry('console', './assets/admin/admin.js')
  .disableSingleRuntimeChunk()

  .cleanupOutputBeforeBuild()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .enableSassLoader();

const consoleConfig = Encore.getWebpackConfig();
consoleConfig.name = 'console';

module.exports = [appConfig, consoleConfig];
