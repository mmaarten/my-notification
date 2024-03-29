<?php
/**
 * Plugin Name:       My Notification
 * Plugin URI:
 * Description:
 * Version:           1.0
 * Requires at least: 5.5
 * Requires PHP:      5.6
 * Author:            Maarten Menten
 * Author URI:        https://profiles.wordpress.org/maartenm/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-notification
 * Domain Path:       /languages
 */

if (version_compare(PHP_VERSION, '5.6.0', '<')) {
    return;
}

define('MY_NOTIFICATION_PLUGIN_FILE', __FILE__);

$autoloader = __DIR__ . '/vendor/autoload.php';
if (! is_readable($autoloader)) {
    return;
}

require $autoloader;

add_action('plugins_loaded', ['\My\Notification\App', 'init']);
