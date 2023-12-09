#!/bin/bash

# Check if a package name is provided as an argument
if [ -z "$1" ] || [ ! -d "packages/$1" ]; then
  echo "Error: Invalid or no package specified for the release."
  echo "Usage: $0 <package_name> <release_type>"
  exit 1
fi

# Check if the current git branch is a release branch for that package.
package_name="$1"
current_branch=$(git rev-parse --abbrev-ref HEAD)
release_branch_pattern="^${package_name}/v([0-9]+)$"

if ! [[ "$current_branch" =~ $release_branch_pattern ]]; then
  echo "Invalid branch. It should be a release branch for the package '${package_name}'."
  exit 2
fi

# Check if a release type provided as an argument
if [ -z "$2" ] || [[ "$2" != "major" && "$2" != "minor" && "$2" != "patch" ]]; then
  echo "Error: Invalid or no release type specified. Expected values: major, minor or patch."
  echo "Usage: $0 <package_name> <release_type>"
  exit 3
fi

# Determining the current release version of the package from composer.json
version=$( sed -n 's/.*"version": "\([^"]*\)".*/\1/p' packages/${package_name}/composer.json )
IFS='.' read -ra version <<< "$version"
unset IFS

# Check if the current branch is ready for a major release, if specified.
release_type="$2"
if [[ $release_type == 'major' && ${BASH_REMATCH[1]} != $(( version[0] + 1 )) ]]; then
  echo "Can not do a major release since the branch is not '${package_name}/v$(( version[0] + 1 ))'."
  exit 4
fi

# Determine the new version.
case $release_type in
  patch)
    new_version=( "${version[0]}" "${version[1]}" "$(( version[2] + 1 ))" )
    ;;
  major)
    new_version=( "$(( version[0] + 1 ))" "0" "0" )
    ;;
  minor|*)
    new_version=( "${version[0]}" "$(( version[1] + 1 ))" "0" )
esac

version=$( IFS="."; echo "${version[*]}"; unset IFS )
new_version=$( IFS="."; echo "${new_version[*]}"; unset IFS )

# Latest tag for the package on the release branch.
if [ "$version" = "0.0.0" ]; then
  latest_tag="0.0.0"
else
  latest_tag="$package_name/$version"
fi

# Check if there are feature/fix commits since the latest tag.
if ! git log --oneline "$latest_tag"...HEAD -- "packages/$package_name" | grep -qE "^(feat|fix)\($package_name\)"; then
  echo "No relevant commits for the release were found."
  exit 5
fi

# Run version replacement in the files.
sed -i "s/\"version\": \".*\"/\"version\": \"$new_version\"/" packages/${package_name}/composer.json
sed -i "s/\(Version:[[:space:]]*\).*$/\1$new_version/" packages/${package_name}/plugin.php

# Generate changelog. Check if awks output can be processed before prepending
awk -v prepend="$( date -u +"%d %B %Y" )" \
  "NR==3{print \"### $new_version\n\n> \" prepend \"\n\n\"} {print} NR!=3 && FNR==NR {totalLines=FNR} FNR!=NR {print}" \
  "packages/$package_name/docs/changelog.md" \
  <(git log --pretty=format:"%s" $latest_tag...HEAD -- "packages/$package_name" | \
    grep -iE "^(feat|fix)\($package_name\):" | \
    sed -E "s/feat\($package_name\)/Feature/i; s/fix\($package_name\)/Fix/i" | \
    awk '{print "- " $0}') \
  > packages/$package_name/docs/changelog.md.tmp

mv "packages/$package_name/docs/changelog.md.tmp" "packages/$package_name/docs/changelog.md"

# Add these changes to git history, make a release commit and tag it.
git add packages/$package_name/plugin.php \
  packages/$package_name/composer.json \
  packages/$package_name/docs/changelog.md

git commit -m "build($package_name): Release of version - $new_version"
git tag "$package_name/$new_version"
