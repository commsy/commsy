// webpack.config.js
let Encore = require('@symfony/webpack-encore');
let webpack = require('webpack');
let HardSourceWebpackPlugin = require('hard-source-webpack-plugin');

Encore
    // the project directory where all compiled assets will be stored
    .setOutputPath('public/build/')

    // the public path used by the web server to access the previous directory
    .setPublicPath('/build')

    // entry points
    .addStyleEntry('app_default', './assets/js/app_default.js')
    .addStyleEntry('app_confetti', './assets/js/app_confetti.js')
    .addStyleEntry('app_darkblue', './assets/js/app_darkblue.js')
    .addStyleEntry('app_football', './assets/js/app_football.js')
    .addStyleEntry('app_grey', './assets/js/app_grey.js')
    .addStyleEntry('app_ocean', './assets/js/app_ocean.js')
    .addStyleEntry('app_red', './assets/js/app_red.js')
    .addStyleEntry('app_redgrey', './assets/js/app_redgrey.js')
    .addStyleEntry('app_schulcommsyhh', './assets/js/app_schulcommsyhh.js')
    .addStyleEntry('app_stars', './assets/js/app_stars.js')
    .addStyleEntry('app_sun', './assets/js/app_sun.js')
    .addStyleEntry('app_uhh', './assets/js/app_uhh.js')

    .addEntry('app_default', './assets/js/app_default.js')

    .enableTypeScriptLoader()

    // allow less files to be processed
    .enableLessLoader(function(options) {
        options.paths = [
            'node_modules/uikit/src/less',
            'assets/css'
        ]
    })

    .copyFiles({
        from: './assets/img',
        includeSubdirectories: false
    })

    .addPlugin(new webpack.IgnorePlugin(/^\.\/locale$/, /uikit\/dist\/js\/components$/))
    .addPlugin(new HardSourceWebpackPlugin())

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    // allow legacy applications to use $/jQuery as a global variable
    .autoProvidejQuery()

    .enableSourceMaps(!Encore.isProduction())

    // empty the outputPath dir before each build
    .cleanupOutputBeforeBuild()

    // show OS notifications when builds finish/fail
    .enableBuildNotifications()

    // create hashed filenames (e.g. app.abc123.css)
    .enableVersioning()
;

// export the final configuration
module.exports = Encore.getWebpackConfig();