/**
 * Global Require
 * ****************************************************************************
 */
var gulp = require('gulp');
var plugins = require('gulp-load-plugins')();
var path = require('path');
var del = require('del');
var fs = require('fs');
var Promise = require('promise');
var merge = require('merge');
var mergeStream = require('merge-stream');
var gulpSequence = require('gulp-sequence');

/**
 * Configuration
 * ****************************************************************************
 */
var config = {
    assetsDir: 'app/Resources/assets',
    themesDir: 'app/Resources/themes',
    lessPattern: '**/*.less',
    production: !!plugins.util.env.prod,
    sourceMaps: !plugins.util.env.prod,
    bowerDir: 'vendor/bower_components',
    revManifestDir: 'app/Resources/assets/manifests/'
};

/**
 * Application
 * ****************************************************************************
 */
var app = {};

app.getThemes = function() {
    var list = [];

    if (!fs.existsSync(config.themesDir)) {
        return;
    }

    fs.readdirSync(config.themesDir).forEach(function(theme) {
        var path = config.themesDir + '/' + theme;

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
    return gulp.src(paths)
        .pipe(plugins.if(!config.production, plugins.plumber()))
        .pipe(plugins.if(config.sourceMaps, plugins.sourcemaps.init()))
        .pipe(plugins.less({
            paths: [
                config.bowerDir + '/uikit/less',
                config.assetsDir + '/uikit-commsy'
            ]
        }))
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
        config.bowerDir + '/jstree/dist/themes/default/style.css',
        config.assetsDir + '/uikit-commsy/commsy.less'
    ], 'commsy.css');
});

gulp.task('js', function() {
    return app.addScript([
        config.bowerDir + '/jquery/dist/jquery.js',

        config.bowerDir + '/jstree/dist/jstree.js',

        config.bowerDir + '/uikit/js/uikit.js',
        config.bowerDir + '/uikit/js/components/autocomplete.js',
        config.bowerDir + '/uikit/js/components/search.js',
        config.bowerDir + '/uikit/js/components/nestable.js',
        config.bowerDir + '/uikit/js/components/tooltip.js',
        config.bowerDir + '/uikit/js/components/grid.js',
        config.bowerDir + '/uikit/js/components/accordion.js',
        config.bowerDir + '/uikit/js/components/upload.js',
        config.bowerDir + '/uikit/js/components/sticky.js',
        config.bowerDir + '/uikit/js/components/slider.js',
        config.bowerDir + '/uikit/js/components/lightbox.js',
        config.bowerDir + '/uikit/js/components/sortable.js',
        config.bowerDir + '/uikit/js/components/notify.js',
        config.bowerDir + '/uikit/js/components/parallax.js',
        config.bowerDir + '/uikit/js/components/datepicker.js',

        config.assetsDir + '/js/**/*.js'
    ], 'commsy.js');
});

gulp.task('fonts', function() {
    return app.copy(
        config.bowerDir + '/uikit/fonts/*',
        'web/fonts'
    );
});

gulp.task('images', function() {
    var assetImages = app.copy(
        config.assetsDir + '/img/*',
        'web/img'
    );

    var bowerImages = app.copy([
        config.bowerDir + '/jstree/dist/themes/default/*.png',
        config.bowerDir + '/jstree/dist/themes/default/*.gif'
    ], 'web/css/build');

    return mergeStream(assetImages, bowerImages);
});

gulp.task('staticThemes', function(done) {
    var promises = [];

    app.getThemes().forEach(function(theme) {
        promises.push(new Promise(function(resolve) {
            app.addStyle([
                config.bowerDir + '/jstree/dist/themes/default/style.css',
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
            var result = {};

            Object.keys(data).forEach(function(key) {
                var value = data[key];
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