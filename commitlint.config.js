const fs = require( 'fs' );
const path = require( 'path' );

// Function to get package names from the "packages" directory
function getPackageNames() {
  const packagesDir = 'packages'; // Change this to your actual directory

  // Read the names of all subdirectories in the packagesDir
  const allFolders = fs.readdirSync( packagesDir );

  // Filter out only those folders that contain a composer.json file
  const packageNames = allFolders.filter( ( folder ) => {
    const composerJsonPath = path.join( packagesDir, folder, 'composer.json' );
    return fs.existsSync( composerJsonPath ) && fs.statSync( composerJsonPath ).isFile();
  } );

  return packageNames;
}

module.exports = {
  extends: ['@commitlint/config-conventional'],
  rules: {
    'scope-enum': [ 2, 'always', getPackageNames() ],
  },
};
