<?php
defined('ABSPATH') || exit;

$youtube = sona_setting('sona_youtube');
?>
<footer class="sona-footer" role="contentinfo">
    <div class="sona-footer__top">
        <div class="sona-footer__brand">
            <span class="sona-footer__name"><?php bloginfo('name'); ?></span>
            <span class="sona-footer__desc"><?php echo esc_html(sona_setting('sona_footer_desc')); ?></span>
        </div>
        <div class="sona-footer__cols">
            <div class="sona-footer__col">
                <span class="sona-footer__title">الأكاديمية</span>
                <a href="<?php echo esc_url(sona_page_url('about')); ?>">عن الأكاديمية</a>
                <a href="<?php echo esc_url(sona_courses_url()); ?>">المواد الدراسية</a>
                <a href="<?php echo esc_url(sona_page_url('faq')); ?>">الأسئلة الشائعة</a>
            </div>
            <div class="sona-footer__col">
                <span class="sona-footer__title">تواصل معنا</span>
                <span><?php echo esc_html(sona_setting('sona_support_email')); ?></span>
                <?php if (wp_http_validate_url($youtube)) : ?>
                    <a href="<?php echo esc_url($youtube); ?>" rel="noopener">قناة يوتيوب</a>
                <?php else : ?>
                    <span><?php echo esc_html($youtube); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="sona-footer__bottom">
        <span>© <?php echo esc_html(sona_num(wp_date('Y')) . ' ' . get_bloginfo('name')); ?>. جميع الحقوق محفوظة.</span>
        <?php if (get_privacy_policy_url()) : ?>
            <a href="<?php echo esc_url(get_privacy_policy_url()); ?>">سياسة الخصوصية</a>
        <?php endif; ?>
    </div>
</footer>
