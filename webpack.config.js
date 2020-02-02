let encore = require('@symfony/webpack-encore');

encore
  .addEntry('app', './assets/app.js')

  .setOutputPath('public/build/')
  .setPublicPath('/build')

  .enableSassLoader()

  .cleanupOutputBeforeBuild()
  .disableSingleRuntimeChunk()
  .enableBuildNotifications()
  .enableSourceMaps(!encore.isProduction())
  // .enableVersioning()
;

module.exports = encore.getWebpackConfig();
