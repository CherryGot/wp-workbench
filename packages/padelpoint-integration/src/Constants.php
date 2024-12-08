<?php
/**
 * This file here contiains the reusable constants used throughout the codebase.
 *
 * @package arpadel
 */

declare ( strict_types = 1 );

namespace PadelPoint;

/**
 * Wraps up constants to follow PSR-4 standard.
 */
class Constants {

  public const SETTING_FIELD_LOGIN = 'padelpoint-login';

  public const SETTING_FIELD_PASSWORD = 'padelpoint-password';

  public const SETTING_FIELD_IMPORT_WEEKDAY = 'padelpoint-import-weekday';

  public const SETTING_FIELD_CATEGORY_MAP = 'padelpoint-category_map';

  public const SETTING_FIELD_IMPORT_STATS = 'padelpoint-import_stats';

  public const ACTION_SLUG_MANUAL_IMPORT = 'init-catalog-fetch';

}
