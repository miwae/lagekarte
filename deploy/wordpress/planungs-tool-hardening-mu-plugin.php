<?php
/**
 * Plugin Name: Planungs-Tool Hardening
 * Description: Reduces public WordPress attack surface for planungs-tool.de.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Hide WordPress version in HTML and feeds.
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');

// Disable XML-RPC unless explicitly needed by integrations.
add_filter('xmlrpc_enabled', '__return_false');

// Block anonymous access to REST user endpoints.
add_filter('rest_authentication_errors', function ($result) {
    if ($result !== null) {
        return $result;
    }

    $route = isset($_SERVER['REQUEST_URI']) ? wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
    $route = is_string($route) ? $route : '';

    if (!is_user_logged_in() && preg_match('#/wp-json/wp/v2/users(?:/|$)#', $route)) {
        return new WP_Error(
            'rest_forbidden',
            'User endpoint disabled for anonymous access.',
            array('status' => 401)
        );
    }

    return $result;
});

// Redirect classic author enumeration and public author archives to home.
add_action('template_redirect', function () {
    if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    $has_author_query = isset($_GET['author']) && is_numeric($_GET['author']);

    if ($has_author_query || is_author()) {
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }
});
