const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

// Remove SASS rule from the default config so we can define our own.
const defaultRules = defaultConfig.module.rules.filter((rule) => {
    return String(rule.test) !== String(/\.(sc|sa)ss$/);
});

module.exports = {
    ...defaultConfig,
    entry: {
        index: path.resolve(process.cwd(), 'src', 'index.js'),
        'cart/cart-order-total/index': path.resolve(process.cwd(), 'src', 'cart-order-total', 'index.js'),
        'cart/cart-order-total/frontend': path.resolve( process.cwd(), 'src', 'cart-order-total', 'frontend.js' ),
        'cart/index': path.resolve( process.cwd(), 'src', 'cart', 'index.js' ),
        'cart/frontend': path.resolve( process.cwd(), 'src', 'cart', 'frontend.js' ),

        'checkout/checkout-order-total/index': path.resolve(process.cwd(), 'src', 'checkout-order-total', 'index.js'),
        'checkout/checkout-order-total/frontend': path.resolve( process.cwd(), 'src', 'checkout-order-total', 'frontend.js' ),
        'checkout/index': path.resolve( process.cwd(), 'src', 'checkout', 'index.js' ),
        'checkout/frontend': path.resolve( process.cwd(), 'src', 'checkout', 'frontend.js' ),



    },
    output: {
        path: path.resolve(process.cwd(), 'build'),
        filename: '[name].js',
    },
    module: {
        ...defaultConfig.module,
        rules: [
            ...defaultRules,
            {
                test: /\.(sc|sa)ss$/,
                exclude: /node_modules/,
                use: [
                    MiniCssExtractPlugin.loader,
                    { loader: 'css-loader', options: { importLoaders: 1 } },
                    'sass-loader',
                ],
            },
            {
                test: /\.css$/,
                exclude: /node_modules/,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                ],
            },
        ],
    },
    plugins: [
        ...defaultConfig.plugins.filter(
            (plugin) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
        ),
        new WooCommerceDependencyExtractionWebpackPlugin(),
        new MiniCssExtractPlugin({
            filename: '[name].css',
        }),
    ],
};
