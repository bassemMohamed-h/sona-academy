<?php
/**
 * The academy is completely free: strip every billing / earnings surface Tutor
 * still shows when monetization is disabled (monetize_by = free).
 */

defined('ABSPATH') || exit;

// Account area: no Billing (order history, invoices) and no Withdrawals.
add_filter('tutor_dashboard_account_pages', function (array $pages) {
    unset($pages['billing'], $pages['withdrawals']);
    return $pages;
});

// Unlisting isn't enough — Tutor falls back to loading the templates directly — so send those URLs away.
add_action('template_redirect', function () {
    if (get_query_var('tutor_dashboard_page') === 'account'
        && in_array(get_query_var('tutor_dashboard_sub_page'), ['billing', 'withdrawals'], true)) {
        wp_safe_redirect(\TUTOR\Dashboard::get_account_page_url());
        exit;
    }
});

// Account → Settings: no Withdraw tab.
add_filter('tutor_dashboard/nav_items/settings/nav_items', function (array $tabs) {
    unset($tabs['withdraw']);
    return $tabs;
});

// Instructor home ranks "Top Performing Courses" by revenue unless told otherwise.
add_action('template_redirect', function () {
    if (function_exists('tutor_utils') && tutor_utils()->is_tutor_frontend_dashboard()
        && (!isset($_GET['type']) || $_GET['type'] === 'revenue')) {
        $_GET['type'] = 'student';
    }
});
