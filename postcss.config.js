const postcssPlugins = require( '@wordpress/postcss-plugins-preset' );

module.exports = ( ctx ) => {
	const isDevelopment = ( 'development' === ctx.env );
	const isSass = ( '.scss' === ctx.file.extname );

	return {
		map: {
			inline: isDevelopment,
			annotation: true
		},
		parser: isSass ? 'postcss-scss' : false,
		plugins: [
			...( isSass ? [ require( '@csstools/postcss-sass' ) ] : [] ),
			...postcssPlugins,
			// Additional plugins here.
		]
	};
};
