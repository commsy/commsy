// webpack.config.js
let Encore = require('@symfony/webpack-encore');
let webpack = require('webpack');
let HardSourceWebpackPlugin = require('hard-source-webpack-plugin');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // the project directory where all compiled assets will be stored
    .setOutputPath('public/build/')

    // the public path used by the web server to access the previous directory
    .setPublicPath('/build')

    // entry points UiKit2
    .addEntry('app_default', './assets/uikit2/js/app_default.js')
    .addEntry('app_confetti', './assets/uikit2/js/app_confetti.js')
    .addEntry('app_darkblue', './assets/uikit2/js/app_darkblue.js')
    .addEntry('app_football', './assets/uikit2/js/app_football.js')
    .addEntry('app_grey', './assets/uikit2/js/app_grey.js')
    .addEntry('app_ocean', './assets/uikit2/js/app_ocean.js')
    .addEntry('app_red', './assets/uikit2/js/app_red.js')
    .addEntry('app_redgrey', './assets/uikit2/js/app_redgrey.js')
    .addEntry('app_schulcommsyhh', './assets/uikit2/js/app_schulcommsyhh.js')
    .addEntry('app_stars', './assets/uikit2/js/app_stars.js')
    .addEntry('app_sun', './assets/uikit2/js/app_sun.js')
    .addEntry('app_uhh', './assets/uikit2/js/app_uhh.js')

    // entry points UiKit3
    .addEntry('app_portal', './assets/uikit3/js/app_portal.js')

    .enableTypeScriptLoader()

    // allow less files to be processed
    .enableLessLoader(function(options) {
        options.paths = [
            'node_modules/uikit/src/less',
            'node_modules/uikit3/src/less',
            'assets/uikit2/css'
        ]
    })

    .copyFiles([
        { from: './assets/uikit2/img', includeSubdirectories: false },
        { from: './node_modules/mathjax/es5', pattern: /tex-chtml\.js/, includeSubdirectories: false },
        { from: './node_modules/mathjax/es5/output/chtml/fonts/woff-v2', to: 'mathjax/fonts/[path][name].[ext]', includeSubdirectories: false },
    ])

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
    .enableVersioning(Encore.isProduction())

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })
;

// export the final configuration
module.exports = Encore.getWebpackConfig();