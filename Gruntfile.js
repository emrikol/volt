module.exports = function( grunt ) {
	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		version: {
			readme: {
				options: {
					prefix: 'Stable tag:\\s*'
				},
				src: ['readme.txt']
			},
			scss: {
				options: {
					prefix: 'Version:\\s*'
				},
				src: ['scss/style.scss']
			},
			package: {
				src: ['package.json']
			}
		},

		sass: {
			all: {
				options: {
					unixNewlines: true
				},
				files: {
					'style.css': 'scss/style.scss',
					'css/menu.css': 'scss/menu.scss',
					'css/jetpack-sharing.css': 'scss/jetpack-sharing.scss',
					'css/jetpack-relatedposts.css': 'scss/jetpack-relatedposts.scss'
				}
			}
		},

		watch: {
			styles: {
				files: [ 'scss/**/*.scss' ],
				tasks: [ 'version::patch', 'sass' ],
				options: {
					debounceDelay: 500
				}
			}
		},

		clean: {
			main: ['release/<%= pkg.version %>']
		},

		copy: {
			main: {
				src:  [
					'**',
					'!**/.*',
					'!**/style.css.map',
					'!**/readme.md',
					'!node_modules/**',
					'!release/**',
					'!scss/**',
					'!fonts/**',
					'!images/src/**',
					'!composer.json',
					'!composer.lock',
					'!Gruntfile.js',
					'!package.json',
				],
				dest: 'release/<%= pkg.version %>/'
			}
		},

		compress: {
			main: {
				options: {
					mode: 'zip',
					archive: './release/<%= pkg.name %>.<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'release/<%= pkg.version %>/',
				src: ['**/*'],
				dest: '<%= pkg.name %>/'
			}
		},

	} );

	require( 'load-grunt-tasks' )(grunt);

	grunt.registerTask( 'default', ['concat', 'uglify', 'sass', 'cssmin' ] );
	grunt.registerTask( 'css', [ 'version::patch', 'sass' ] );
	grunt.registerTask( 'js', ['concat', 'uglify'] );
	grunt.registerTask( 'build', ['default', 'clean', 'copy', 'compress'] );

	grunt.util.linefeed = '\n';
};
