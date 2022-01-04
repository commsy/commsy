const Encore = require('@symfony/webpack-encore');
let webpack = require('webpack');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or sub-directory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */

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

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    // .enableStimulusBridge('./assets/controllers.json')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    .copyFiles([
        { from: './assets/uikit2/img', includeSubdirectories: false },
        { from: './node_modules/mathjax/es5', pattern: /tex-chtml\.js/, includeSubdirectories: false },
        { from: './node_modules/mathjax/es5/output/chtml/fonts/woff-v2', to: 'mathjax/fonts/[path][name].[ext]', includeSubdirectories: false },
    ])

    .addPlugin(new webpack.IgnorePlugin({
        resourceRegExp: /^\.\/locale$/,
        contextRegExp: /uikit\/dist\/js\/components$/
    }))

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    .enableLessLoader(function(options) {
        options.lessOptions = {
            paths: [
                'node_modules/uikit/src/less',
                'node_modules/uikit3/src/less',
                'assets/uikit2/css'
            ]
        };
    })

    // enables Sass/SCSS support
    //.enableSassLoader()

    // uncomment if you use TypeScript
    .enableTypeScriptLoader()

    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    .autoProvidejQuery()

    .autoProvideVariables({
        UIkit: 'uikit',
    })
;

module.exports = Encore.getWebpackConfig();
