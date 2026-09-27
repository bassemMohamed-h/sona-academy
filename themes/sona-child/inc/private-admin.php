<?php
/**
 * Students only ever see the academy: no admin bar, no wp-admin, no WordPress
 * login/registration screens. Staff (anyone who can edit posts — admins,
 * editors, Tutor instructors) keep full access.
 */

defined('ABSPATH') || exit;

function sona_is_staff(): bool
{
    return current_user_can('edit_posts');
}

add_filter('show_admin_bar', fn ($show) => $show && sona_is_staff());

// wp-admin → academy dashboard. admin-ajax / admin-post / uploads stay open: Tutor's front end uses them.
add_action('admin_init', function () {
    global $pagenow;
    if (sona_is_staff() || wp_doing_ajax() || in_array($pagenow, ['admin-post.php', 'async-upload.php'], true)) {
        return;
    }
    wp_safe_redirect(sona_dashboard_url());
    exit;
});

// After logging in, students go to their dashboard rather than wp-admin.
add_filter('login_redirect', function ($redirect_to, $requested, $user) {
    if ($user instanceof WP_User && !user_can($user, 'edit_posts')
        && (!$requested || str_starts_with($redirect_to, admin_url()))) {
        return sona_dashboard_url();
    }
    return $redirect_to;
}, 10, 3);

add_filter('logout_redirect', fn () => home_url('/'));

// Registration and password recovery happen on the academy's own pages.
add_filter('register_url', fn () => sona_register_url());
add_filter('lostpassword_url', fn ($url) => function_exists('tutor_utils') ? tutor_utils()->tutor_dashboard_url('retrieve-password') : $url);
add_action('login_init', function () {
    $action = $_REQUEST['action'] ?? '';
    if ($action === 'register') {
        wp_safe_redirect(sona_register_url());
        exit;
    }
    if (in_array($action, ['lostpassword', 'retrievepassword'], true) && $_SERVER['REQUEST_METHOD'] === 'GET' && function_exists('tutor_utils')) {
        wp_safe_redirect(wp_lostpassword_url());
        exit;
    }
});
add_filter('login_display_language_dropdown', '__return_false');

// Emails come from the academy, not "WordPress".
add_filter('wp_mail_from_name', fn () => get_bloginfo('name'));

// Don't advertise the WordPress version in page source / feeds.
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');
