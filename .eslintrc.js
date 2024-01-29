module.exports = {
  root: true,
  env: {
    jest: true,
  },
  extends: [ 'plugin:@wordpress/eslint-plugin/recommended-with-formatting' ],
  rules: {
    'brace-style': [
      'error',
      'stroustrup',
    ],
    'space-in-parens': [
      'error',
      'always',
    ],
    'max-len': [
      'error',
      {
        'code': 100,
      }
    ],
    'indent': [ 'error', 2 ],
  },
}
