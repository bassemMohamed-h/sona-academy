<?php
/**
 * Home page — implements the "الصفحة الرئيسية" artboard of the السنة أكاديمي design.
 */
defined('ABSPATH') || exit;

get_header();

$courses  = sona_subject_courses();
$subjects = $courses
    ? array_map(fn (WP_Post $c) => [
        'name' => get_the_title($c),
        'desc' => wp_strip_all_tags(get_the_excerpt($c)),
        'url'  => get_permalink($c),
    ], $courses)
    : array_map(fn (array $s) => $s + ['url' => sona_courses_url()], sona_subjects());
?>
<main id="primary" class="sona-home">

    <section class="sona-hero">
        <div class="sona-wrap sona-hero__inner">
            <div class="sona-hero__copy">
                <h1 class="sona-hero__title">تعلّم أساسيات دينك<br>خطوة بخطوة</h1>
                <p class="sona-hero__lead">دروس مسجّلة بالعربية على يد مختصين، مع ترجمة إلى لغات متعددة. ادرس في وقتك، واختبر فهمك، واحصل على إفادة عند إتمام الفصل.</p>
                <div class="sona-hero__cta">
                    <?php if (is_user_logged_in()) : ?>
                        <a class="sona-btn sona-btn--primary sona-btn--lg" href="<?php echo esc_url(sona_dashboard_url()); ?>">تابع دراستك</a>
                    <?php else : ?>
                        <a class="sona-btn sona-btn--primary sona-btn--lg" href="<?php echo esc_url(sona_register_url()); ?>">ابدأ التسجيل</a>
                    <?php endif; ?>
                    <a class="sona-btn sona-btn--outline sona-btn--lg" href="#subjects">تصفّح المواد</a>
                </div>
            </div>
            <img class="sona-hero__art" src="<?php echo esc_url(sona_logo_url()); ?>" alt="" aria-hidden="true">
        </div>
    </section>

    <section id="subjects" class="sona-subjects sona-wrap">
        <div class="sona-section-head">
            <div>
                <h2 class="sona-h2">المواد الدراسية</h2>
                <p class="sona-section-head__sub">سبع مواد تُدرَّس في كل فصل، تبدأ من الأساس.</p>
            </div>
            <a class="sona-link" href="<?php echo esc_url(sona_courses_url()); ?>">كل المواد</a>
        </div>
        <div class="sona-subjects__grid">
            <?php foreach ($subjects as $s) : ?>
                <a class="sona-subject-card" href="<?php echo esc_url($s['url']); ?>">
                    <span class="sona-subject-card__name"><?php echo esc_html($s['name']); ?></span>
                    <span class="sona-subject-card__desc"><?php echo esc_html($s['desc']); ?></span>
                </a>
            <?php endforeach; ?>
            <div class="sona-subject-card sona-subject-card--dark">
                <span class="sona-subject-card__promo">كل مادة لها اختبار ودرجة من ١٠٠</span>
                <a class="sona-link sona-link--light" href="<?php echo esc_url(sona_page_url('faq')); ?>">كيف تُحسب الدرجات</a>
            </div>
        </div>
    </section>

    <section class="sona-steps">
        <div class="sona-wrap">
            <h2 class="sona-h2 sona-h2--light">كيف تدرس معنا</h2>
            <div class="sona-steps__grid">
                <?php foreach (sona_steps() as $st) : ?>
                    <div class="sona-step">
                        <span class="sona-step__n"><?php echo esc_html($st['n']); ?></span>
                        <span class="sona-step__title"><?php echo esc_html($st['title']); ?></span>
                        <span class="sona-step__desc"><?php echo esc_html($st['desc']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

</main>
<?php
get_footer();
