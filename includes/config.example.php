<?php
/**
 * Copy this file to config.php and fill in your GoDaddy MySQL details.
 * cPanel → MySQL Databases → note host (usually localhost), database, username, password.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

/**
 * Leave empty for domain root (public_html).
 * If the site lives in a folder, e.g. https://example.com/portfolio, set '/portfolio'.
 * Set to 'auto' to detect from the script path.
 */
define('BASE_URL', 'auto');
