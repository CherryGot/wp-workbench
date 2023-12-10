
# Release Checklist

To make a release for one of the packages, there are a couple of things to do. I'll list them out here as a short list. This can later be extended for more understanding if it got complicated for some reason.

## Run Linters and Tests
Obviously, we want to ship the code that's properly formatted and tested before making a release. So run those to check if all good. They have to be run for both JS and PHP codebases.

Commands
```sh
$ bunx lint
```
```sh
$ bunx test
```
```sh
$ composer run lint
```
```sh
$ composer run test
```


## Build Static Assets
After the tests, we need to build assets like the compiled CSS and JS files so that they are optimized for loading on the frontend.

Commands
``` sh
$ bunx build-styles
```

``` sh
$ bunx build-scripts
```

## Doing a version bump
As we're getting closer to the release, we now have to do a version bump (no idea why they call it this way). For that, you need to first figure out what type of release are you going to make.

Releases are done for packages in the codebase. The packages are there in the `packages/` directory of the codebase. Each of the release is associated with a version bump. I'm following the semantic version schema, where we have MAJOR, MINOR and PATCH releases. The version string, therefore, looks something like this `<package-name>/MAJOR.MINOR.PATCH`.

PATCH release is done when we have fixed a bunch of problems with an existing MINOR release. These fixes should be related to that particular package only or else, the release script will not consider it. If you're following the correct commit messages, this will never be a problem.

MINOR releases are done when we implement a new feature to the package. This doesn't break any existing features though. We need to be careful with all our testing suite that implementing a feature doesn't break the existing one. Because if it does, then we gotta do a ...

MAJOR release. This is basically what happens when you have no other options than breaking the existing functionality, either it is visual or in API.

The release for a package is done via following command:
``` sh
$ composer run release -- <package-name> <major|minor|patch>
```
As you can see, it takes two parameters, one of them is the package you want to make a release of and the type of the release.

Now, there are some convention for git branches before you can make a release. Each package has a separate sort of "master" branch for major releases. For example, all the releases of package `package-name` of version `0.x.x` will have their master branch as `package-name/v0`. Similarly, all the releases of version `1.x.x` will have their master branch as `package-name/v1`, and so on. This is necessary or else the above command will fail.

The above rule works well when you have to make a MINOR or a PATCH release. However, when we do a MAJOR release, we can not use the existing "master" branch of that package. For example, we are on `package-name/v0` branch and we try to make a MAJOR release, it'll fail saying that it can not do a major release on the current branch. You have to manually checkout to the new major release branch `package-name/v1` to do so.

I know that was a mouthful, but hopefully clear. If not, let me know, and I'll try to simplify it a little more later.

This command will do couple of things for you
- It'll first increase the version string in the `composer.json` and `plugin.php` file of that package.
- Then, it'll generate a changelog file for the end user listing all the features and fixes that were done during this release. The location of the changelog file will be `package-name/docs/changelog.md`
- Then it'll make a commit of the these updated files and git tag with the release tag.

Disclaimer: This feature, as of now, is still not tested, so things may change here in future.


## Generating autoloads and product documentation
After this is done, we need to generate the autoload files for the packages so that there are no resolution errors from PHP. To do so, run the command
``` sh
$ composer run build-autoload
```

This is then followed by the following command that builds the documentation for the end user.
``` sh
$ composer run build-docs
```

## Building the ZIP file
Okay, one last step before it is finally done, building the zip. The zip file for a package is run via the following command
``` sh
$ composer run build-zip -- <package-name>
```

This will go into that directory of that package and then selects all the files and folders of the directory that are not mentioned in the `.zipignore` file, along with the LICENSE and builds the ZIP in the `dist/` directory of the project root.
