<?php
defined('ABSPATH') || exit;

require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/inc/free-site.php';
require_once __DIR__ . '/inc/private-admin.php';

const SONA_VERSION = '1.1.0';

add_action('after_setup_theme', function () {
    register_nav_menus([
        'sona-primary' => 'القائمة الرئيسية (السنة أكاديمي)',
    ]);
});

// Load parent (Astra) styles, fonts, then the child styles
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('astra-parent', get_template_directory_uri() . '/style.css');
    wp_enqueue_style(
        'sona-fonts',
        'https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=IBM+Plex+Sans+Arabic:wght@400;500;600&display=swap',
        [],
        null
    );
    wp_enqueue_style('sona-child', get_stylesheet_uri(), ['astra-parent', 'sona-fonts'], SONA_VERSION);
    wp_enqueue_script('sona-child', get_stylesheet_directory_uri() . '/assets/js/sona.js', [], SONA_VERSION, true);
});

// Tutor enqueues its own stylesheets late; make sure our overrides win.
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('sona-child');
    wp_enqueue_style('sona-child');
}, 100);

// Replace Astra's header/footer builder output with the Sona header/footer.
add_action('wp', function () {
    remove_all_actions('astra_header');
    remove_all_actions('astra_footer');
    add_action('astra_header', fn () => get_template_part('parts/site-header'));
    add_action('astra_footer', fn () => get_template_part('parts/site-footer'));
});

add_filter('body_class', function (array $classes) {
    $classes[] = 'sona';
    if (is_front_page()) {
        $classes[] = 'sona-front';
    }
    return $classes;
});

// Let Tutor's dashboard shell find our header.
add_filter('tutor_theme_header_selector', fn () => '.sona-header');

add_action('customize_register', function (WP_Customize_Manager $wp_customize) {
    $wp_customize->add_section('sona_academy', ['title' => 'السنة أكاديمي', 'priority' => 30]);
    $fields = [
        'sona_footer_desc'   => 'وصف قصير للأكاديمية (التذييل)',
        'sona_support_email' => 'البريد الإلكتروني للدعم',
        'sona_youtube'       => 'رابط أو اسم قناة يوتيوب',
        'sona_timezone_note' => 'ملاحظة المنطقة الزمنية للجدول',
    ];
    foreach ($fields as $id => $label) {
        $wp_customize->add_setting($id, ['default' => '', 'sanitize_callback' => 'sanitize_text_field']);
        $wp_customize->add_control($id, ['label' => $label, 'section' => 'sona_academy', 'type' => 'text']);
    }
});

// [sona_schedule] — the weekly live-session table, used on the schedule page.
add_shortcode('sona_schedule', function () {
    ob_start();
    get_template_part('parts/schedule', null, ['variant' => 'table']);
    return ob_get_clean();
});

// Brand the WordPress login screen.
add_action('login_enqueue_scripts', function () {
    printf(
        '<style>body.login{background:#F5F8F3}#login h1 a{background-image:url(%s);background-size:contain;width:160px;height:110px}
        .login .button-primary{background:#2F7D32;border-color:#2F7D32}.login .button-primary:hover{background:#1C4A24;border-color:#1C4A24}
        .login #backtoblog a,.login #nav a{color:#1C4A24}</style>',
        esc_url(sona_logo_url())
    );
});
add_filter('login_headerurl', fn () => home_url('/'));
add_filter('login_headertext', fn () => get_bloginfo('name'));
