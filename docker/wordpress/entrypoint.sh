#!/bin/bash

TARGET_DIR=./wp-content/plugins
SOURCE_DIR=./wp-content/packages

find -L $TARGET_DIR -maxdepth 1 -type l -delete

for entry in $SOURCE_DIR/*
do
  # If there is no entry in dir, it'll return $TARGET_DIR/*. So just check if entry ends with *
  if [[ "$entry" =~ .*\*$ ]]
  then
    continue
  fi

  name=$(basename $entry)
  hashedName=$(echo $name | base64)

  if [[ $name == *.php ]]
  then
    hashedName+=".php"
  fi

  symlinkEntry=$TARGET_DIR"/"$hashedName

  if [[ ! -e $symlinkEntry ]]
  then
    relativePath=$(realpath --relative-to="$TARGET_DIR" "$SOURCE_DIR")
    target=$relativePath"/"$name
    ln -s $target $symlinkEntry
  fi
done
