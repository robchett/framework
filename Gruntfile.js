module.exports = function(grunt) {
    require('grunt-task-loader')(grunt, {
        mapping: {
            sass_globbing: 'grunt-sass-globbing'
        }
    })
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        watch: {
            scss: {
                files: ['module/cms/scss/**/*.scss', '!module/cms/scss/includes/*.scss'],
                tasks: ['sass_globbing', 'sass'],
            },
            scripts: {
                files: ['.core/**/*.js', 'js/src/*.js'],
                tasks: ['concat','uglify'],
                spawn: false,
                interrupt: true
            }
        },
        uglify: {
            build: {
                src: 'js/script.js',
                dest: 'js/script.min.js'
            }
        },
        concat: {
            options: {
                separator: ';',
            },
            dist: {
                src: ['./.core/js/jquery.js', './.core/js/_ajax.js', './.core/js/_default.js', './.core/js/jscrollpane.min.js', './.core/js/page_handler.js', './.core/js/table-sorter.js', 'js/src/*.js'],
                dest: 'js/script.js',
                nonull: true,
            },
        },
        sass: {
            cms: {
                options: {
                },
                files: {
                    'module/cms/css/styles.css': 'module/cms/scss/styles.scss'
                }
            }
        },
        sass_globbing: {
            cms: {
                files: {
                    'module/cms/scss/imports/partials.scss': 'module/cms/scss/partials/*.scss',
                },
                options: {
                }
            }
        }
    });
    grunt.registerTask('default', ['watch']);
    grunt.registerTask('compile', ['watch']);
    grunt.registerTask('compile.css', ['sass_globbing', 'sass']);
    grunt.registerTask('compile.js', ['concat', 'uglify']);
}
