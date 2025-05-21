const path = require('path');
const glob = require('glob');
const CopyPlugin = require('copy-webpack-plugin');

// Create entries for all block's index.js
const entryPoints = {};

// Dynamically grab each block's index.js and set as entries
glob.sync('./blocks/*/src/index.js').forEach((file) => {
    const blockName = path.basename(path.dirname(path.dirname(file)));
    entryPoints[blockName] = file;
});

module.exports = {
    entry: entryPoints,
    externals: {
        '@wordpress/blocks': 'wp.blocks',
        '@wordpress/block-editor': 'wp.blockEditor',
        '@wordpress/components': 'wp.components',
        '@wordpress/element': 'wp.element',
        '@wordpress/api-fetch': 'wp.apiFetch',
        '@wordpress/url': 'wp.url',
        'react': 'React',
        'react-dom': 'ReactDOM'
    },
    output: {
        path: path.resolve(__dirname),
        filename: (chunkData) => {
            return `blocks/${chunkData.chunk.name}/build/index.js`;
        },
        clean: false
    },
    resolve: {
        extensions: ['.js', '.jsx'],
    },
    plugins: [
        // Directly copy CSS files from src to the block root directory, we could obviously minify/mangle this
        new CopyPlugin({
            patterns: [
                {
                    from: './blocks/*/src/style.css',
                    to: ({ absoluteFilename }) => {
                        const blockName = path.basename(path.dirname(path.dirname(absoluteFilename)));
                        return `blocks/${blockName}/style.css`;
                    },
                    noErrorOnMissing: true, // Don't error if some blocks don't have style.css, as our test 2nd block doesn't
                }
            ],
        }),
    ],
    module: {
        rules: [
            {
                test: /\.jsx?$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                },
            }
        ],
    },
};