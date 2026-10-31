<?php

/**
 * Plugin Name: WP Rocket | Cache Dynamic Cookie
 * Description: Adds a dedicated dynamic cache for a defined cookie ID.
 * Plugin URI:  https://github.com/wp-media/wp-rocket-helpers/tree/master/cache/wp-rocket-cache-dynamic-cookie/
 * Author:      WP Rocket Support Team
 * Author URI:  http://wp-rocket.me/
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright SAS WP MEDIA 2018
 */


/*

HOST light-speed cache


RewriteCond %{REQUEST_URI} !(\/wp-admin\/|\/xmlrpc.php|\/wp-(app|cron|login|register|mail)\.php|wp-.*\.php) [NC]
RewriteCond %{HTTP_COOKIE} !(comment_author|wp-postpass|wordpress_\[a-f0-9\]\+|wordpress_logged_in) [NC]
RewriteCond %{REQUEST_URI} !\.(jpg|gif|jpeg|png|css|ico|js|swf|zip|rar|mp3|exe|ttf|woff|tff) [NC]
RewriteRule ^$ - [L,E=Cache-Control:max-age=240]

RewriteCond %{REQUEST_URI} !(\/wp-admin\/|\/xmlrpc.php|\/wp-(app|cron|login|register|mail)\.php|wp-.*\.php) [NC]
RewriteCond %{HTTP_COOKIE} !(comment_author|wp-postpass|wordpress_\[a-f0-9\]\+|wordpress_logged_in) [NC]
RewriteCond %{REQUEST_URI} !\.(jpg|gif|jpeg|png|css|ico|js|swf|zip|rar|mp3|exe|ttf|woff|tff) [NC]
RewriteRule ^/$ - [L,E=Cache-Control:max-age=240]

RewriteCond %{REQUEST_URI} !(\/wp-admin\/|\/xmlrpc.php|\/wp-(app|cron|login|register|mail)\.php|wp-.*\.php) [NC]
RewriteCond %{HTTP_COOKIE} !(comment_author|wp-postpass|wordpress_\[a-f0-9\]\+|wordpress_logged_in) [NC]
RewriteCond %{REQUEST_URI} !\.(jpg|gif|jpeg|png|css|ico|js|swf|zip|rar|mp3|exe|ttf|woff|tff) [NC]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !(order|checkout|cart)
RewriteRule . /index.php [L,E=Cache-Control:max-age=240]

 */

namespace WP_Rocket\Helpers\cache\dynamic_cookie;

// Standard plugin security, keep this line in place.
defined('ABSPATH') or die();

// EDIT THIS:

define('WPROCKETHELPERS_CACHE_DYNAMIC_COOKIE', 'using_dollar');

// STOP EDITING

add_action('wp_before_admin_bar_render', function () {
	global $wp_admin_bar;

	if (!is_admin_bar_showing() || !is_object($wp_admin_bar)) { return; }

	$rocket_id = 'wp-rocket';
	$rocket_node = $wp_admin_bar->get_node($rocket_id);

	if (!$rocket_node) { return; }

	// حذف آیتم WP Rocket فعلی
	$wp_admin_bar->remove_node($rocket_id);

	// دریافت تمام آیتم‌ها
	$all_nodes = $wp_admin_bar->get_nodes();

	// ایجاد آرایه جدید با راکت در پوزیشن سوم
	$new_nodes = []; $count = 0;
	foreach ($all_nodes as $id => $node) {
		// اضافه کردن آیتم WP Rocket در پوزیشن سوم
		if ($count === 80) {
			$new_nodes[$rocket_id] = $rocket_node;
		}
		if (!in_array($id, ['elementor_notes', 'wp-mail-smtp-menu'])) {
			$new_nodes[$id] = $node;
			$count++;
		}
	}

	// پاکسازی همه نودهای قبلی
	foreach (array_keys($all_nodes) as $id) {
		$wp_admin_bar->remove_node($id);
	}

	// بازنویسی نودها با ترتیب جدید
	foreach ($new_nodes as $id => $node) {
		if ($id == "wp-rocket") {
			$wp_admin_bar->add_node([
				'id'       => $id,
				'title'    => __("Cache"),
				'href'     => $node->href,
				'meta'     => $node->meta,
			]);
		}else{
			$wp_admin_bar->add_node($node);
		}
	}
}, 100);


/**
 * Define cookie ID for dynamic caches.
 *
 * @author Caspar Hübinger
 */
function cache_dynamic_cookie(array $cookies) {
	$cookies[] = WPROCKETHELPERS_CACHE_DYNAMIC_COOKIE;
	return $cookies;
}

// Add cookie ID to cookkies for dynamic caches.
add_filter('rocket_cache_dynamic_cookies', __NAMESPACE__ . '\cache_dynamic_cookie');
add_filter('rocket_cache_mandatory_cookies', __NAMESPACE__ . '\cache_dynamic_cookie');
// Remove .htaccess-based rewrites, since we need to detect the cookie,
// which happens in inc/front/process.php.
add_filter('rocket_htaccess_mod_rewrite', '__return_false');


/**
 * Updates .htaccess, regenerates WP Rocket config file.
 *
 * @author Caspar Hübinger
 */
function flush_wp_rocket() {

	if (
		! function_exists('flush_rocket_htaccess')
		|| ! function_exists('rocket_generate_config_file')
	) {
		return false;
	}

	// Update WP Rocket .htaccess rules.
	flush_rocket_htaccess();

	// Regenerate WP Rocket config file.
	rocket_generate_config_file();
}

/**
 * Add customizations, updates .htaccess, regenerates config file.
 *
 * @author Caspar Hübinger
 */
function activate() {

	// Add customizations upon activation.
	add_filter('rocket_htaccess_mod_rewrite', '__return_false');
	add_filter('rocket_cache_dynamic_cookies', __NAMESPACE__ . '\cache_dynamic_cookie');
	add_filter('rocket_cache_mandatory_cookies', __NAMESPACE__ . '\cache_dynamic_cookie');

	// Flush .htaccess rules, and regenerate WP Rocket config file.
	flush_wp_rocket();
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\activate');

/**
 * Removes customizations, updates .htaccess, regenerates config file.
 *
 * @author Caspar Hübinger
 */
function deactivate() {

	// Remove customizations upon deactivation.
	remove_filter('rocket_htaccess_mod_rewrite', '__return_false');
	remove_filter('rocket_cache_dynamic_cookies', __NAMESPACE__ . '\cache_dynamic_cookie');
	remove_filter('rocket_cache_mandatory_cookies', __NAMESPACE__ . '\cache_dynamic_cookie');

	// Flush .htaccess rules, and regenerate WP Rocket config file.
	flush_wp_rocket();
}
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\deactivate');
