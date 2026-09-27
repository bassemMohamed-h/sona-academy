<?php
/**
 * Static demo content and small helpers shared by the templates.
 *
 * The subject list doubles as the seed for scripts/seed-demo.php and as the
 * home-page fallback when no Tutor courses exist yet.
 */

defined('ABSPATH') || exit;

const SONA_SUBJECT_CATEGORY = 'subjects';

function sona_subjects(): array
{
    return [
        ['slug' => 'aqeedah',  'name' => 'العقيدة',            'desc' => 'معنى العقيدة وأهميتها، ومصادر التلقي، وأركان الإيمان.'],
        ['slug' => 'tafsir',   'name' => 'التفسير',            'desc' => 'مدخل إلى علم التفسير، وتفسير سور مختارة.'],
        ['slug' => 'hadith',   'name' => 'الحديث',             'desc' => 'أحاديث صحيحة في أصول الدين والأخلاق مع شرحها.'],
        ['slug' => 'seerah',   'name' => 'السيرة',             'desc' => 'السيرة النبوية كاملة بشكل مختصر.'],
        ['slug' => 'fiqh',     'name' => 'الفقه',              'desc' => 'أحكام الطهارة والصلاة بأسلوب مبسّط.'],
        ['slug' => 'tarbiyah', 'name' => 'التربية الإسلامية',  'desc' => 'الحقوق والآداب في حياة المسلم اليومية.'],
        ['slug' => 'arabic',   'name' => 'اللغة العربية',      'desc' => 'مدخل إلى اللغة العربية وأقسام الكلام.'],
    ];
}

function sona_steps(): array
{
    return [
        ['n' => '١', 'title' => 'أنشئ حسابك',      'desc' => 'سجّل مجانًا ببريدك الإلكتروني.'],
        ['n' => '٢', 'title' => 'شاهد الدروس',     'desc' => 'دروس مسجّلة بالعربية مع ترجمة.'],
        ['n' => '٣', 'title' => 'اختبر فهمك',      'desc' => 'اختبار في نهاية كل مادة.'],
        ['n' => '٤', 'title' => 'احصل على إفادة',  'desc' => 'عند إتمام الفصل بنجاح.'],
    ];
}

function sona_schedule(): array
{
    return [
        ['day' => 'الأحد',     'items' => [['subject' => 'السيرة', 'time' => '٧:٠٠ م'], ['subject' => 'الحديث', 'time' => '٨:٣٠ م']]],
        ['day' => 'الإثنين',   'items' => [['subject' => 'اللغة العربية', 'time' => '٧:٠٠ م'], ['subject' => 'التفسير', 'time' => '٨:٣٠ م']]],
        ['day' => 'الثلاثاء',  'items' => [['subject' => 'التربية الإسلامية', 'time' => '٧:٠٠ م'], ['subject' => 'الفقه', 'time' => '٨:٣٠ م']]],
        ['day' => 'الأربعاء',  'items' => [['subject' => 'العقيدة', 'time' => '٧:٠٠ م']]],
    ];
}

/** Western digits → Arabic-Indic digits, as used throughout the design. */
function sona_num($value): string
{
    return strtr((string) $value, ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']);
}

function sona_logo_url(): string
{
    $logo_id = get_theme_mod('custom_logo');
    $url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';
    return $url ?: get_stylesheet_directory_uri() . '/assets/img/logo.png';
}

function sona_page_url(string $slug, string $fallback = '#'): string
{
    $page = get_page_by_path($slug);
    return $page ? get_permalink($page) : $fallback;
}

function sona_dashboard_url(): string
{
    return function_exists('tutor_utils') ? tutor_utils()->tutor_dashboard_url() : wp_login_url();
}

function sona_register_url(): string
{
    if (function_exists('tutor_utils')) {
        $page_id = (int) tutor_utils()->get_option('student_register_page');
        if ($page_id) {
            return get_permalink($page_id);
        }
    }
    return wp_registration_url();
}

function sona_courses_url(): string
{
    return get_post_type_archive_link('courses') ?: home_url('/');
}

/** Customizer-editable text with the design's bracketed placeholder as default. */
function sona_setting(string $key): string
{
    $defaults = [
        'sona_footer_desc'    => '[وصف قصير للأكاديمية]',
        'sona_support_email'  => '[البريد الإلكتروني للدعم]',
        'sona_youtube'        => '[قناة يوتيوب]',
        'sona_timezone_note'  => '[المنطقة الزمنية للمواعيد]',
    ];
    $value = get_theme_mod($key, '');
    return $value !== '' ? $value : ($defaults[$key] ?? '');
}

/** Subject courses (Tutor) in display order; empty array when none are set up. */
function sona_subject_courses(): array
{
    if (!post_type_exists('courses')) {
        return [];
    }
    return get_posts([
        'post_type'   => 'courses',
        'post_status' => 'publish',
        'numberposts' => 12,
        'orderby'     => 'menu_order',
        'order'       => 'ASC',
        'tax_query'   => [['taxonomy' => 'course-category', 'field' => 'slug', 'terms' => SONA_SUBJECT_CATEGORY]],
    ]);
}

/** Best finished quiz score for a course, 0–100, or null when no attempt exists. */
function sona_course_grade(int $course_id, int $user_id): ?int
{
    global $wpdb;
    $score = $wpdb->get_var($wpdb->prepare(
        "SELECT MAX(earned_marks / NULLIF(total_marks, 0) * 100)
           FROM {$wpdb->prefix}tutor_quiz_attempts
          WHERE course_id = %d AND user_id = %d AND attempt_status = 'attempt_ended'",
        $course_id,
        $user_id
    ));
    return $score === null ? null : (int) round((float) $score);
}
