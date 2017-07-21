/**
 * Global Require
 * ****************************************************************************
 */
let gulp = require('gulp');
let plugins = require('gulp-load-plugins')();
let path = require('path');
let del = require('del');
let fs = require('fs');
let Promise = require('promise');
let merge = require('merge');
let mergeStream = require('merge-stream');
let gulpSequence = require('gulp-sequence');

/**
 * Configuration
 * ****************************************************************************
 */
let config = {
    assetsDir: 'app/Resources/assets',
    themesDir: 'app/Resources/themes',
    lessPattern: '**/*.less',
    production: !!plugins.util.env.prod,
    sourceMaps: !plugins.util.env.prod,
    nodeDir: 'node_modules/',
    revManifestDir: 'app/Resources/assets/manifests/'
};

/**
 * Application
 * ****************************************************************************
 */
let app = {};

app.getThemes = function() {
    let list = [];

    if (!fs.existsSync(config.themesDir)) {
        return;
    }

    fs.readdirSync(config.themesDir).forEach(function(theme) {
        let path = config.themesDir + '/' + theme;

        if (!fs.lstatSync(path).isDirectory()) {
            return;
        }

        list.push({
            'name': theme,
            'path': path
        });
    });

    return list;
};

app.addStyle = function(paths, outputFilename) {
    let lessFilter = plugins.filter(['**/*.less'], {
        restore: true
    });

    return gulp.src(paths)
        .pipe(plugins.if(!config.production, plugins.plumber()))
        .pipe(plugins.if(config.sourceMaps, plugins.sourcemaps.init()))

        .pipe(lessFilter)
        .pipe(plugins.less({
            paths: [
                config.nodeDir + '/uikit/dist/less',
                config.assetsDir + '/uikit-commsy'
            ]
        }))
        .pipe(lessFilter.restore)

        .pipe(plugins.concat('css/build/' + outputFilename))
        .pipe(plugins.if(config.production, plugins.cssnano()))
        .pipe(plugins.rev())
        .pipe(plugins.if(config.sourceMaps, plugins.sourcemaps.write('.')))
        .pipe(gulp.dest('web'))

        // write the rev-manifest.json file for gulp-rev
        .pipe(plugins.rev.manifest(config.revManifestDir + '/' + outputFilename + '-manifest.json', {
            merge: true
        }))
        .pipe(gulp.dest('.'));
};

app.addScript = function(paths, outputFilename) {
    return gulp.src(paths)
        .pipe(plugins.if(!config.production, plugins.plumber()))
        .pipe(plugins.if(config.sourceMaps, plugins.sourcemaps.init()))
        .pipe(plugins.babel({
            presets: ['es2015'],
            only: [
                config.assetsDir
            ]
        }))
        .pipe(plugins.concat('js/build/' + outputFilename))
        .pipe(plugins.if(config.production, plugins.uglify()))
        .pipe(plugins.rev())
        .pipe(plugins.if(config.sourceMaps, plugins.sourcemaps.write('.')))
        .pipe(gulp.dest('web'))

        // write the rev-manifest.json file for gulp-rev
        .pipe(plugins.rev.manifest(config.revManifestDir + '/' + outputFilename + '-manifest.json', {
            merge: true
        }))
        .pipe(gulp.dest('.'));
};

app.copy = function(srcFiles, outputDir) {
    return gulp.src(srcFiles)
        .pipe(gulp.dest(outputDir));
};  

/**
 * Tasks
 * ****************************************************************************
 */
gulp.task('clean', function(done) {
    del.sync(config.revManifestDir + '*');
    del.sync('web/css/build/*');
    del.sync('web/js/build/*');
    del.sync('web/fonts/*');

    done();
});

gulp.task('less', function() {
    return app.addStyle([
        config.nodeDir + '/jstree/dist/themes/default/style.css',
        config.nodeDir + '/nprogress/nprogress.css',
        config.nodeDir + '/fullcalendar/dist/fullcalendar.css',
        config.nodeDir + '/tooltipster/dist/css/tooltipster.bundle.min.css',
        
        config.nodeDir + '/video.js/dist/video-js.css',
        
        config.assetsDir + '/uikit-commsy/commsy.less'
    ], 'commsy.css');
});

gulp.task('js', function() {
    return app.addScript([
        config.nodeDir + '/jquery/dist/jquery.js',

        config.nodeDir + '/jstree/dist/jstree.js',

        config.nodeDir + '/nprogress/nprogress.js',

        config.nodeDir + '/moment/moment.js',
        config.nodeDir + '/fullcalendar/dist/fullcalendar.js',
        config.nodeDir + '/fullcalendar/dist/locale-all.js',
        
        config.nodeDir + '/tooltipster/dist/js/tooltipster.bundle.min.js',

        config.nodeDir + '/urijs/src/URI.js',

        config.nodeDir + '/uikit/dist/js/uikit.js',
        config.nodeDir + '/uikit/dist/js/components/autocomplete.js',
        config.nodeDir + '/uikit/dist/js/components/search.js',
        config.nodeDir + '/uikit/dist/js/components/nestable.js',
        config.nodeDir + '/uikit/dist/js/components/tooltip.js',
        config.nodeDir + '/uikit/dist/js/components/grid.js',
        config.nodeDir + '/uikit/dist/js/components/accordion.js',
        config.nodeDir + '/uikit/dist/js/components/upload.js',
        config.nodeDir + '/uikit/dist/js/components/sticky.js',
        config.nodeDir + '/uikit/dist/js/components/slider.js',
        config.nodeDir + '/uikit/dist/js/components/lightbox.js',
        config.nodeDir + '/uikit/dist/js/components/sortable.js',
        config.nodeDir + '/uikit/dist/js/components/notify.js',
        config.nodeDir + '/uikit/dist/js/components/parallax.js',
        config.nodeDir + '/uikit/dist/js/components/datepicker.js',
        config.nodeDir + '/uikit/dist/js/components/timepicker.js',
        config.nodeDir + '/uikit/dist/js/components/form-select.js',
        
        config.nodeDir + '/video.js/dist/video.js',

        config.assetsDir + '/js/**/*.js'
    ], 'commsy.js');
});

gulp.task('fonts', function() {
    return app.copy([
            config.assetsDir + '/fonts/*',
            config.nodeDir + '/uikit/dist/fonts/*'
    ],'web/fonts');
});

gulp.task('images', function() {
    let assetImages = app.copy(
        config.assetsDir + '/img/*',
        'web/img'
    );

    let bowerImages = app.copy([
        config.nodeDir + '/jstree/dist/themes/default/*.png',
        config.nodeDir + '/jstree/dist/themes/default/*.gif'
    ], 'web/css/build');

    return mergeStream(assetImages, bowerImages);
});

gulp.task('staticThemes', function(done) {
    let promises = [];

    app.getThemes().forEach(function(theme) {
        promises.push(new Promise(function(resolve) {
            app.addStyle([
                config.nodeDir + '/jstree/dist/themes/default/style.css',
                config.nodeDir + '/nprogress/nprogress.css',
                config.nodeDir + '/fullcalendar/dist/fullcalendar.css',
                config.nodeDir + '/tooltipster/dist/css/tooltipster.bundle.min.css',
                
                config.nodeDir + '/video.js/dist/video-js.css',
                
                config.assetsDir + '/uikit-commsy/commsy.less',
                theme.path + '/theme.less'
            ], 'commsy_' + theme.name + '.css').on('end', function() {
                resolve();
            });
        }));
    });

    Promise.all(promises).then(function() {
        done();
    });
});

gulp.task('manifest', function() {
    return gulp.src(config.revManifestDir + '*.json')
        .pipe(plugins.jsoncombine('rev-manifest.json', function(data) {
            let result = {};

            Object.keys(data).forEach(function(key) {
                let value = data[key];
                result = merge(result, value);
            });

            return new Buffer(JSON.stringify(result));
        }))
        .pipe(gulp.dest(config.revManifestDir));
});

gulp.task('postClean', function(done) {
    del.sync(config.revManifestDir + 'commsy*');

    done();
});

/**
 * Main Tasks
 * ****************************************************************************
 */
gulp.task('watch', function() {
    gulp.watch(config.assetsDir + '/uikit-commsy/' + config.lessPattern, ['basic']);
    gulp.watch(config.assetsDir + '/js/**/*.js', ['basic']);
    gulp.watch(config.themesDir + '/' + config.lessPattern, ['default']);
});

gulp.task('default', function(done) {
    gulpSequence(
        'clean',
        ['less', 'js', 'fonts', 'images'],
        'staticThemes',
        'manifest',
        'postClean'
    )(done);
});

gulp.task('basic', function(done) {
    gulpSequence(
        'clean',
        ['less', 'js', 'fonts', 'images'],
        'manifest',
        'postClean'
    )(done);
});
