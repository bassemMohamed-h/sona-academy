<?php
defined('ABSPATH') || exit;

$is_logged_in = is_user_logged_in();
$user         = wp_get_current_user();
?>
<header class="sona-header site-header" role="banner">
    <div class="sona-header__inner">
        <a class="sona-brand" href="<?php echo esc_url(home_url('/')); ?>">
            <img class="sona-brand__logo" src="<?php echo esc_url(sona_logo_url()); ?>" alt="<?php echo esc_attr('شعار ' . get_bloginfo('name')); ?>" width="84" height="58">
            <span class="sona-brand__text">
                <span class="sona-brand__name"><?php bloginfo('name'); ?></span>
                <?php if (get_bloginfo('description')) : ?>
                    <span class="sona-brand__tag"><?php bloginfo('description'); ?></span>
                <?php endif; ?>
            </span>
        </a>

        <button class="sona-nav-toggle" type="button" aria-controls="sona-nav" aria-expanded="false" aria-label="القائمة">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>

        <div class="sona-header__menu" id="sona-nav">
            <nav class="sona-nav" aria-label="القائمة الرئيسية">
                <?php
                if (has_nav_menu('sona-primary')) {
                    wp_nav_menu(['theme_location' => 'sona-primary', 'container' => false, 'menu_class' => 'sona-nav__list', 'depth' => 1]);
                } else {
                    ?>
                    <ul class="sona-nav__list">
                        <li><a href="<?php echo esc_url(home_url('/')); ?>">الرئيسية</a></li>
                        <li><a href="<?php echo esc_url(sona_courses_url()); ?>">المواد الدراسية</a></li>
                        <li><a href="<?php echo esc_url(sona_page_url('schedule')); ?>">الجدول</a></li>
                        <li><a href="<?php echo esc_url(sona_page_url('library')); ?>">المكتبة</a></li>
                        <li><a href="<?php echo esc_url(sona_page_url('contact')); ?>">تواصل معنا</a></li>
                    </ul>
                    <?php
                }
                ?>
            </nav>

            <div class="sona-header__actions">
                <button type="button" class="sona-lang" aria-label="تغيير اللغة">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.5 3 14.5 0 18M12 3c-3 3.5-3 14.5 0 18"/></svg>
                    العربية
                </button>
                <?php if ($is_logged_in) : ?>
                    <a class="sona-user-pill" href="<?php echo esc_url(sona_dashboard_url()); ?>">
                        <span class="sona-avatar sona-avatar--sm"><?php echo esc_html(mb_substr($user->display_name, 0, 1)); ?></span>
                        <?php echo esc_html($user->display_name); ?>
                    </a>
                <?php else : ?>
                    <a class="sona-btn sona-btn--ghost" href="<?php echo esc_url(sona_dashboard_url()); ?>">دخول</a>
                    <a class="sona-btn sona-btn--primary" href="<?php echo esc_url(sona_register_url()); ?>">سجّل مجانًا</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
