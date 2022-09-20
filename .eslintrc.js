module.exports = {
  'env': {
    'browser': true,
    'es2021': true,
  },
  'extends': 'google',
  'overrides': [],
  'parserOptions': {
    'ecmaVersion': 'latest',
    'sourceType': 'module',
  },
  'rules': {
    'object-curly-spacing': [
      'error',
      'always',
    ],
    'padded-blocks': [
      'error',
      {
        'classes': 'always',
      },
    ],
    'max-len': [
      'error',
      {
        'code': 120,
      },
    ],
    'brace-style': [
      'error',
      'stroustrup',
    ],
    'space-in-parens': [
      'error',
      'always',
    ],
  },
};
