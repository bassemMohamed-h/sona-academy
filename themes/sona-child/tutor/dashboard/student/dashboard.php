<?php
/**
 * Student dashboard home — overrides Tutor's templates/dashboard/student/dashboard.php
 * with the "ملف الطالب" artboard of the السنة أكاديمي design.
 */

defined('ABSPATH') || exit;

use Tutor\Models\CourseModel;
use TUTOR\User;

$user_id = get_current_user_id();
$user    = get_userdata($user_id);

do_action('tutor_before_dashboard_content');
tutor_load_template('dashboard.components.profile-completion');

if (User::has_pending_instructor_application($user_id)) {
    tutor_load_template('dashboard.instructor.instructor-request-alert');
}

$enrolled = CourseModel::get_enrolled_courses_by_user($user_id, ['private', 'publish']);

if (!$enrolled || !$enrolled->post_count) {
    tutor_load_template('dashboard.student.dashboard-empty');
    return;
}

$subjects = [];
foreach ($enrolled->posts as $course) {
    $lesson_ids = tutor_utils()->get_course_content_ids_by(tutor()->lesson_post_type, tutor()->course_post_type, $course->ID);
    $pct        = (int) tutor_utils()->get_course_completed_percent($course->ID, $user_id);
    $subjects[] = [
        'name'    => get_the_title($course),
        'pct'     => $pct,
        'done'    => tutor_utils()->get_completed_lesson_count_by_course($course->ID, $user_id),
        'total'   => count($lesson_ids),
        'grade'   => sona_course_grade($course->ID, $user_id),
        'url'     => tutor_utils()->get_course_first_lesson($course->ID) ?: get_permalink($course),
        'order'   => $course->menu_order,
    ];
}
usort($subjects, fn ($a, $b) => $a['order'] <=> $b['order']);

$total_count = count($subjects);
$done_count  = count(array_filter($subjects, fn ($s) => $s['pct'] >= 100));
$overall     = (int) round(array_sum(array_column($subjects, 'pct')) / max(1, $total_count));
$lang        = get_user_meta($user_id, 'sona_translation_lang', true) ?: '—';
$initial     = mb_substr($user->display_name, 0, 1);
?>
<div class="sona-profile">
    <div class="sona-profile__head">
        <span class="sona-crumbs">الرئيسية ‹ ملفي</span>
        <h1 class="sona-profile__title">الفصل الأول</h1>
    </div>

    <div class="sona-profile__grid">

        <aside class="sona-profile__side">
            <section class="sona-card sona-idcard">
                <div class="sona-avatar sona-avatar--lg"><?php echo esc_html($initial); ?></div>
                <span class="sona-idcard__name"><?php echo esc_html($user->display_name); ?></span>
                <span class="sona-muted sona-small"><?php echo esc_html($user->user_email); ?></span>
                <dl class="sona-idcard__meta">
                    <div><dt>رقم الطالب</dt><dd><?php echo esc_html(sona_num($user_id)); ?></dd></div>
                    <div><dt>لغة الترجمة</dt><dd><?php echo esc_html($lang); ?></dd></div>
                    <div><dt>تاريخ التسجيل</dt><dd><?php echo esc_html(sona_num(wp_date('Y/m/d', strtotime($user->user_registered)))); ?></dd></div>
                </dl>
                <a class="sona-btn sona-btn--outline sona-btn--block" href="<?php echo esc_url(tutor_utils()->tutor_dashboard_url('settings')); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h4L19 9l-4-4L4 16v4z"/></svg>
                    تعديل الملف الشخصي
                </a>
            </section>

            <section class="sona-card">
                <h2 class="sona-card__title">درجات المواد</h2>
                <div class="sona-grades">
                    <?php foreach ($subjects as $s) : ?>
                        <div class="sona-grades__row">
                            <span><?php echo esc_html($s['name']); ?></span>
                            <span class="sona-grades__score">
                                <?php echo $s['grade'] === null ? '—' : esc_html(sona_num($s['grade'])); ?> / ١٠٠
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </aside>

        <section class="sona-profile__main">
            <div class="sona-welcome">
                <div class="sona-welcome__text">
                    <span class="sona-welcome__title">مرحبًا بك في الفصل الأول</span>
                    <span class="sona-welcome__sub">أكملت <?php echo esc_html(sona_num($done_count)); ?> من <?php echo esc_html(sona_num($total_count)); ?> مواد</span>
                </div>
                <div class="sona-progress sona-progress--light" role="progressbar" aria-valuenow="<?php echo esc_attr($overall); ?>" aria-valuemin="0" aria-valuemax="100">
                    <span style="width: <?php echo esc_attr($overall); ?>%"></span>
                </div>
            </div>

            <?php foreach ($subjects as $s) : ?>
                <article class="sona-card sona-course-row">
                    <div class="sona-course-row__body">
                        <div class="sona-course-row__head">
                            <span class="sona-course-row__name"><?php echo esc_html($s['name']); ?></span>
                            <span class="sona-muted sona-small"><?php echo esc_html(sona_num($s['done']) . ' من ' . sona_num($s['total']) . ' درس'); ?></span>
                        </div>
                        <div class="sona-progress" role="progressbar" aria-valuenow="<?php echo esc_attr($s['pct']); ?>" aria-valuemin="0" aria-valuemax="100">
                            <span style="width: <?php echo esc_attr($s['pct']); ?>%"></span>
                        </div>
                    </div>
                    <a class="sona-btn sona-btn--primary" href="<?php echo esc_url($s['url']); ?>">
                        <?php echo $s['pct'] >= 100 ? 'مراجعة الدروس' : 'متابعة الدروس'; ?>
                    </a>
                </article>
            <?php endforeach; ?>
        </section>

        <aside class="sona-profile__side">
            <section class="sona-card">
                <h2 class="sona-card__title">جدول البث المباشر</h2>
                <?php get_template_part('parts/schedule'); ?>
            </section>

            <section class="sona-card">
                <h2 class="sona-card__title">إفادة التسجيل</h2>
                <div class="sona-cert">
                    <img src="<?php echo esc_url(sona_logo_url()); ?>" alt="" width="80" height="55">
                    <span class="sona-cert__label">إفادة</span>
                </div>
                <p class="sona-small sona-body2">تأكد من صحة بياناتك في الملف الشخصي قبل طلب الإفادة.</p>
                <?php if ($done_count === $total_count) : ?>
                    <button type="button" class="sona-btn sona-btn--dark sona-btn--block">الحصول على إفادة التسجيل</button>
                <?php else : ?>
                    <button type="button" class="sona-btn sona-btn--dark sona-btn--block" disabled>الحصول على إفادة التسجيل</button>
                    <span class="sona-muted sona-small">تُتاح بعد إتمام جميع مواد الفصل.</span>
                <?php endif; ?>
            </section>
        </aside>
    </div>
</div>
<?php
do_action('tutor_after_continue_learning_section');
