const path = require('path');
const TerserPlugin = require("terser-webpack-plugin");
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const BrowserSyncPlugin = require( 'browser-sync-webpack-plugin' );
const { VueLoaderPlugin } = require('vue-loader');

const config = require( './config.json' );

const ENV_TYPES = {
    DEVELOPMENT: 'development',
    PRODUCTION: 'production'
}

// Enviroment flag
const plugins = [];
const env = process.env.WEBPACK_ENV;

// Naming and path settings
let appName = '[name].js';
const entryPoint = {    
    admin: './assets/src/admin/main.js',    
    style: './assets/stylus/index.styl',    
};

const exportPath = path.resolve(__dirname, './assets/js');

const vueLoaderPlugin = new VueLoaderPlugin();

// extract css into its own file
const extractCss = new MiniCssExtractPlugin({
    filename: "../css/[name].css",
});

const browserSyncPlugin = new BrowserSyncPlugin({
    proxy: {
        target: config.proxyURL
    },
    files: [
        '**/*.php'
    ],
    cors: true,
    reloadDelay: 0
});

// Differ settings based on production flag
if (env === ENV_TYPES.PRODUCTION) {
    appName = '[name].min.js';
}

// Query all plugins instantiated
plugins.push(vueLoaderPlugin);
plugins.push(extractCss);
plugins.push(browserSyncPlugin);

module.exports = {
    mode: env,    
    entry: entryPoint,    
    output: {
        path: exportPath,
        filename: appName,        
    },
    optimization: {
        minimize: true,
        minimizer: [
            new TerserPlugin({
                test: /\.js(\?.*)?$/i
            }),
            new CssMinimizerPlugin()
        ],
    },
    resolve: {
        alias: {
            'vue': 'vue/dist/vue.js',
            '@': path.resolve('./assets/src/'),
            'frontend': path.resolve('./assets/src/frontend/'),
            'admin': path.resolve('./assets/src/admin/'),    
            '@images': path.resolve('./assets/images/'),
            'me': path.resolve('./assets/stylus/me-bootstrap')
        },
        modules: [
            path.resolve('./node_modules'),
            path.resolve(path.join(__dirname, 'assets/src/index.styl')),
        ],
        fallback: {
            path: require.resolve("path-browserify")
        }
    },
    plugins,
    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue-loader',
                options: {
                    extractCSS: true
                }
            },
            {
                test: /\.(png|svg|jpg|jpeg|gif)$/i,
                type: 'asset/resource',
            },
            {
                test: /\.js$/,
                exclude: /(node_modules|bower_components)/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: [
                          ['@babel/preset-env', { targets: "defaults" }]
                        ],
                        plugins: ['@babel/plugin-proposal-object-rest-spread']
                    }
                },
            },            
            {
                test: /\.styl$/,
                use: [
                    MiniCssExtractPlugin.loader, 
                    'css-loader', 
                    {
                        loader: 'stylus-loader',
                        options: {
                            stylusOptions: {
                                use: ['jeet', 'rupture'],                                
                                include: [path.join(__dirname, "assets/stylus")]
                            }
                        }
                    }
                ],                
            },
            {
                test: /\.css$/,
                use: [MiniCssExtractPlugin.loader, 'css-loader']
            },
        ]
    },
}
