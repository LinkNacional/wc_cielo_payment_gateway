const path = require('path');

module.exports = {
  mode: 'production',
  entry: {
    lknCieloDebit: './resources/js/debitCard/lknCieloDebit.js',
    lknCieloCredit: './resources/js/creditCard/lknCieloCredit.js',
  },
  output: {
    filename: (pathData) => {
      const name = pathData.chunk.name;
      const entryPath = module.exports.entry[name];
      const baseName = path.basename(entryPath, '.js');
      return entryPath.replace(baseName, `${baseName}Compiled`);
    },
    path: path.resolve(__dirname)
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env', '@babel/preset-react']
          }
        }
      },
      {
        test: /\.css$/,
        use: ['style-loader', 'css-loader']
      }
    ]
  }
};