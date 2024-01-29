#!/bin/bash

# Check if a package name is provided as an argument
if [ -z "$2" ] || [ ! -d "packages/$2" ]; then
  echo "Error: Invalid or no package specified for compiling /(J|T)SX?/"
  exit 1
fi

# Renaming the arguments
command=$1
package_name=$2

# Run wp-scripts for the package
case "$command" in
  "lint")
    npx wp-scripts lint-js ./packages/$package_name/
    npx wp-scripts lint-md-docs ./packages/$package_name/**/*.md
    npx wp-scripts lint-style ./packages/$package_name/**/*.scss
    ;;

  "build" | "start")
    npx wp-scripts $command \
      --config ./packages/$package_name/webpack.config.js \
      --webpack-src-dir=./packages/$package_name/src \
      --output-path=./packages/$package_name/dist
    ;;

  *)
    npx wp-scripts $command ./packages/$package_name/
    ;;
esac
