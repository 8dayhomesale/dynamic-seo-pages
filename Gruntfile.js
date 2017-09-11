module.exports = function(grunt) {
	require('load-grunt-tasks')(grunt);

	grunt.initConfig({
		makepot: {
			main: {
				options: {
					domainPath: 'lang',
					mainFile: 'dynamic-seo-child-pages.php',
					potFilename: 'dynamic-seo-child-pages.pot',
					type: 'wp-plugin',
					potHeaders: true,
					exclude: ['vendor', 'node_modules', 'tests']
				}
			}
		}
	});
};
