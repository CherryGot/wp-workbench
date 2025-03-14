module.exports = {
  root: true,
  env: {
    jest: true,
  },
  plugins: [ 'align-assignments' ],
  extends: [ 'plugin:@wordpress/eslint-plugin/recommended-with-formatting' ],
  rules: {
    'brace-style': [ 'error', 'stroustrup' ],
    'space-in-parens': [ 'error', 'always' ],
    'max-len': [ 'error', {
      'code': 100,
    } ],
    'indent': [ 'error', 2 ],
    'no-multi-spaces': [ 'error', {
      exceptions: {
        'AssignmentExpression': true,
        'VariableDeclarator': true,
        'ImportDeclaration': true,
        'Property': true,
      }
    } ],
    'align-assignments/align-assignments': [ 'warn', {
      'AssignmentExpression': true,
      'VariableDeclarator': true,
    } ],
    'import/no-extraneous-dependencies': [ 'error', {
      'packageDir': '.'
    } ],
    "key-spacing": [ "error", {
      "align": "colon"
    } ],
  },
}
