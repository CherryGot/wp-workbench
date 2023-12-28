#!/bin/bash

# Check if a directory name is provided as an argument
if [ -z "$1" ]; then
  echo "Usage: $0 <directory_name>"
  exit 1
fi

# Assign the provided directory name to a variable
directory_name="$1"

# Read the version number from composer.json using sed
version=$(sed -n 's/.*"version": "\([^"]*\)".*/\1/p' packages/${directory_name}/composer.json)

# Check if sed was successful in extracting the version
if [ $? -ne 0 ]; then
  echo "Failed to read version from composer.json."
  exit 1
fi

# Cleanup of tmp directory before using it.
rm -rf /tmp/genz-admin/*

# Run rsync to copy files to a temporary directory
rsync -a --exclude-from=packages/${directory_name}/.zipignore --exclude=packages/${directory_name}/.zipignore --delete-excluded packages/${directory_name} /tmp/genz-admin

# Check if rsync was successful
if [ $? -eq 0 ]; then
  # Copy the license.
  cp LICENSE /tmp/genz-admin/${directory_name}

  # Storing the project directory in a variable.
  pwd=$( pwd )

  # Preparing for zip the directory.
  cd /tmp/genz-admin/
  mv ${directory_name} genz_admin-${directory_name}

  # Create a zip archive with the same name as the directory
  zip -r genz_admin-${directory_name} genz_admin-${directory_name}/.

  # Move the zip file to the dist folder.
  cd $pwd
  mkdir -p dist
  mv /tmp/genz-admin/genz_admin-${directory_name}.zip dist/genz_admin-${directory_name}-${version}.zip

  # Inform the user about the success
  echo "Zip archive created: genz_admin-${directory_name}-${version}.zip"
else
  # Inform the user about the failure
  echo "Failed to create the zip archive."
fi
