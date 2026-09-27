<?php
// Load parent (Astra) styles, then the child styles
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('astra-parent', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('sona-child', get_stylesheet_uri(), ['astra-parent'], '1.0.0');
});
