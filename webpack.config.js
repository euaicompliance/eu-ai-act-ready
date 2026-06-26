/**
 * Webpack configuration for EU AI Act Ready plugin.
 *
 * @package EUAIACTREADY
 */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path          = require( 'path' );

// Use the modern Sass API to suppress legacy JS API deprecation warnings.
const sassRule = defaultConfig.module.rules.find(
	( rule ) => rule.test && rule.test.toString().includes( 'scss' )
);
if ( sassRule ) {
	sassRule.use = sassRule.use.map( ( loader ) => {
		if ( loader.loader && loader.loader.includes( 'sass-loader' ) ) {
			return { ...loader, options: { ...loader.options, api: 'modern' } };
		}
		return loader;
	} );
}

module.exports = {
	...defaultConfig,
	entry: {
		'admin/admin': [
			path.resolve( process.cwd(), 'src/admin', 'admin.js' ),
			path.resolve( process.cwd(), 'src/admin', 'admin.scss' ),
		],
		'admin/settings-preview': path.resolve( process.cwd(), 'src/admin', 'settings-preview.js' ),
		'admin/media-recheck': path.resolve( process.cwd(), 'src/admin', 'media-recheck.js' ),
		'assets/eu-ai-act-ready': [
			path.resolve( process.cwd(), 'src/assets', 'eu-ai-act-ready.js' ),
			path.resolve( process.cwd(), 'src/assets', 'eu-ai-act-ready.scss' ),
		],
		'assets/chatbot-transparency': path.resolve( process.cwd(), 'src/assets', 'chatbot-transparency.js' ),
		'admin/ai-tools': path.resolve( process.cwd(), 'src/admin', 'ai-tools.js' ),
		'assets/ai-tools-notice': path.resolve( process.cwd(), 'src/assets', 'ai-tools-notice.js' ),
	},
	output: {
		filename: '[name].js',
		path: path.resolve( process.cwd(), 'build' ),
	},
};
