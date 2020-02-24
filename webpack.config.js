let encore = require('@symfony/webpack-encore');

encore
  .addEntry('app', './assets/app.js')

  .setOutputPath('public/build/')
  .setPublicPath('/build')

  .enableSassLoader()
  .addLoader({
    test: /\.(png|jpe?g|gif)$/i,
    loader: 'file-loader',
    options: {
      name: '[name].[ext]',
    }
  })

  .cleanupOutputBeforeBuild()
  .disableSingleRuntimeChunk()
  .enableBuildNotifications()
  .enableSourceMaps(!encore.isProduction())
  // .enableVersioning()
;

module.exports = encore.getWebpackConfig();
