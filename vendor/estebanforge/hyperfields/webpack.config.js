const path = require('path');

module.exports = (env, argv) => {
    const isProduction = argv.mode === 'production';

    return {
        entry: {
            'react-fields': './assets/js/src/index.jsx',
        },
        output: {
            path: path.resolve(__dirname, 'assets/js/dist'),
            filename: '[name].js',
            clean: true,
        },
        module: {
            rules: [
                {
                    test: /\.(js|jsx)$/,
                    exclude: /node_modules/,
                    use: {
                        loader: 'babel-loader',
                        options: {
                            presets: [
                                '@babel/preset-env',
                                ['@babel/preset-react', { runtime: 'automatic' }]
                            ],
                        },
                    },
                },
            ],
        },
        resolve: {
            extensions: ['.js', '.jsx'],
            alias: {
                '@wordpress/block-editor': path.resolve(__dirname, 'node_modules/@wordpress/block-editor'),
                '@wordpress/components': path.resolve(__dirname, 'node_modules/@wordpress/components'),
                '@wordpress/element': path.resolve(__dirname, 'node_modules/@wordpress/element'),
                '@wordpress/i18n': path.resolve(__dirname, 'node_modules/@wordpress/i18n'),
                '@wordpress/icons': path.resolve(__dirname, 'node_modules/@wordpress/icons'),
            },
        },
        externals: {
            '@wordpress/block-editor': 'wp.blockEditor',
            '@wordpress/components': 'wp.components',
            '@wordpress/element': 'wp.element',
            '@wordpress/i18n': 'wp.i18n',
            '@wordpress/icons': 'wp.icons',
            'react': 'React',
            'react-dom': 'ReactDOM',
        },
        devtool: isProduction ? 'source-map' : 'eval-source-map',
        optimization: {
            minimize: isProduction,
        },
    };
};
