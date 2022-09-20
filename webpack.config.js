const path = require('path');

module.exports = {
  mode: 'production',
  entry: {
    'main': path.resolve(__dirname, 'src', 'main'),
  },
  output: {
    path: path.resolve(__dirname, 'assets', 'compiled'),
    filename: '[name].js'
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: [
              [
                '@babel/preset-env',
                {
                  'targets': 'since 2016'
                }
              ]
            ]
          }
        }
      }
    ]
  }
};
