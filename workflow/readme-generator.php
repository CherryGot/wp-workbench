<?php
/**
 * Generate readme.txt for WordPress plugins from Github's README.md and CHANGELOG.md.
 * Inspired from the fumikito/wp-readme project
 *
 * @see https://github.com/fumikito/wp-readme/
 *
 * @package cherrygot/genz-admin
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */

declare ( strict_types = 1 );

/**
 * Wraps up logic for searching for readme and changlog files in a directory and compiling them to
 * WordPress org compatible readme file.
 */
final class ReadmeGenerator {

  /**
   * Stores the path of the directory where readme and changelog files have to be searched, and
   * later readme.txt will have to be saved.
   */
  private string $target_dir;

  /**
   * Name of the file generated after compilation.
   */
  private static string $target_file = 'readme.txt';

  /**
   * Possible names of the readme file.
   *
   * @var string[]
   */
  private static array $readme_names = array( 'readme.md', 'README.md' );

  /**
   * Possible names of the changelog file.
   *
   * @var string[]
   */
  private static array $changelog_names = array( 'changelog.md', 'CHANGELOG.md' );

  /**
   * Inititializes the ReadmeGenerator instance.
   *
   * It takes an optional $target_dir argument that's later used to determine the location of the
   * files. Here it is trimed to remove the ending / before further usage.
   *
   * @param string $target_dir Path of the location where files are present.
   */
  public function __construct( string $target_dir = '.' ) {
    $this->target_dir = rtrim( $target_dir, DIRECTORY_SEPARATOR );
  }

  /**
   * Generates the readme.txt file.
   *
   * It's a publicly exposed function that locates the readme and changelog in the target directory
   * and generates the readme.txt file, while logging the status on the screen.
   */
  public function generate(): void {
    $readme_file    = $this->find_readme();
    $changelog_file = $this->find_changelog();

    echo 'Found README.md and CHANGELOG.md...' . PHP_EOL;
    $this->generate_readme( $readme_file, $changelog_file );
    echo 'readme.txt generated successfully!' . PHP_EOL;
  }

  /**
   * Locates the Github compatible readme file.
   *
   * @return string Path to readme file.
   * @throws Exception If the readme file is not found.
   */
  private function find_readme(): string {
    $path = $this->get_file_path( ...self::$readme_names );
    if ( empty( $path ) ) {
      throw new Exception( 'Can\'t find the README.md in the target directory.' );
    }

    return $path;
  }

  /**
   * Locates the Github compatible changelog file.
   *
   * @return string Path to ChangeLog file.
   * @throws Exception If the changelog file is not found.
   */
  private function find_changelog(): string {
    $path = $this->get_file_path( ...self::$changelog_names );
    if ( empty( $path ) ) {
      throw new Exception( 'Can\'t find the CHANGELOG.md in the target directory.' );
    }

    return $path;
  }

  /**
   * Determines the path of Github compatible files.
   *
   * It scans the target directory for the files using the list of names passed as arguments. If
   * the file with one of the said names is found, it returns its path.
   *
   * @param string ...$file_names List of file names to search.
   * @return string Path to the one of the files from the list of file names.
   */
  private function get_file_path( string ...$file_names ): string {
    if ( is_dir( $this->target_dir ) ) {
      foreach ( scandir( $this->target_dir ) as $file ) {
        if ( in_array( $file, $file_names, strict: true ) ) {
          return $this->target_dir . DIRECTORY_SEPARATOR . $file;
        }
      }
    }

    return '';
  }

  /**
   * Reads the contents of readme and changelog files and transforms them into .txt markdown.
   *
   * @param string $readme_file Path to the readme file.
   * @param string $changelog_file Path to the changelog file.
   *
   * @throws Exception If the target directory is not writable.
   * @throws Exception If the target file, readme.txt, is not writable.
   * @throws Exception If fails to save the generated file.
   */
  private function generate_readme( string $readme_file, string $changelog_file ): void {
    if ( ! is_writable( $this->target_dir ) ) {
      throw new Exception( "Target directory {$this->target_dir} is not writable." );
    }

    $target_file = $this->target_dir . DIRECTORY_SEPARATOR . self::$target_file;
    if ( file_exists( $target_file ) && ! is_writable( $target_file ) ) {
      throw new Exception( "Can't open the {$target_file} as it's not writable." );
    }

    $readme_content  = file_get_contents( $readme_file ) . PHP_EOL;
    $readme_content .= file_get_contents( $changelog_file );
    $this->transform_markdown( $readme_content ); // passed by reference.

    if ( ! file_put_contents( $target_file, $readme_content ) ) {
      throw new Exception( "Failed to save the file: {$target_file}." );
    }
  }

  /**
   * Transforms Github's markdown to WordPress org compatible markdown syntax.
   *
   * @param string $markdown_text The text that needs to be modified. Passed as reference.
   */
  private function transform_markdown( string &$markdown_text ): void {
    // All the below functions take $markdown_text as pass by reference.
    $this->compile_visibility_blocks( $markdown_text );
    $this->transform_headings( $markdown_text );
    $this->transform_code_blocks( $markdown_text );
  }

  /**
   * Filters and compiles the custom visibility blocks that let the content to either show only on
   * Github or WordPress markdown files. Comes handy to keep content targetted for different
   * platforms in one file.
   *
   * @param string $markdown_text The text that needs to be modified. Passed as reference.
   */
  private function compile_visibility_blocks( string &$markdown_text ): void {
    // Remove only:github visibility block.
    $regex         = '#(\n){0,2}<!-- only:github/ -->(.*?)<!-- /only:github -->(\n)?#us';
    $markdown_text = preg_replace( $regex, PHP_EOL, $markdown_text );

    // Display only:wp comment block.
    $regex         = '#<!-- only:wp>(.*?)</only:wp -->#us';
    $tidy_matched  = fn( array $matches ) => trim( $matches[1] );
    $markdown_text = preg_replace_callback( $regex, $tidy_matched, $markdown_text );
  }

  /**
   * Transforms Github's markdown headings to WordPress org compatible markdown headings.
   *
   * @param string $markdown_text The text that needs to be modified. Passed as reference.
   */
  private function transform_headings( string &$markdown_text ): void {
    $transform_matched = function ( $matches ) {
      $sep    = '';
      $length = strlen( $matches[1] );

      for ( $i = 1, $l = 3 - ( $length - 1 ); $i <= $l; $i += 1 ) {
        $sep .= '=';
      }

      return "{$sep} {$matches[2]} {$sep}";
    };

    $regex         = '/^(#+)\s+(.*)/mu';
    $markdown_text = preg_replace_callback( $regex, $transform_matched, $markdown_text );
  }

  /**
   * Transforms Github's markdown code blocks to WordPress org compatible <pre> tags.
   *
   * @param string $markdown_text The text that needs to be modified. Passed as reference.
   */
  private function transform_code_blocks( string &$markdown_text ): void {
    $regex         = '/```([^\n`]*?)\n(.*?)\n```/us';
    $markdown_text = preg_replace( $regex, '<pre>$2</pre>', $markdown_text );
  }

}

// This file is executed as main routine.
// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
if ( ! debug_backtrace() ) {
  $generator = new ReadmeGenerator( getenv( 'WP_README_DIR' ) ?: '.' );

  try {
    $generator->generate();
  }
  catch ( Exception $e ) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '[ERROR]' . $e->getMessage() . PHP_EOL;
    exit( 1 );
  }
}
