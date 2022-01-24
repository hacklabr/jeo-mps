const path = require( 'path' );
const defaultConfig = require( './node_modules/@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		partnersPosts: './src/js/src/partners-posts/index.js',
	},
	output: {
		path: path.resolve( __dirname, './src/js/build/' ),
		publicPath: './src/js/build/',
		filename: '[name].js',
	},
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.css$/,
				use: [ 'style-loader', 'css-loader' ],
			},

			{
				test: /\.s[ac]ss$/i,
				use: [
				  // Creates `style` nodes from JS strings
				  'style-loader',
				  // Translates CSS into CommonJS
				  'css-loader',
				  // Compiles Sass to CSS
				  'sass-loader',
				],
			  },
		],
	},
};
