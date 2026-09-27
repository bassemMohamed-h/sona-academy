<?php
/**
 * Weekly live-session schedule. $args['variant']: 'list' (dashboard card) or 'table' (schedule page).
 */
defined('ABSPATH') || exit;

$variant = $args['variant'] ?? 'list';
?>
<?php if ($variant === 'table') : ?>
    <div class="sona-schedule-table">
        <?php foreach (sona_schedule() as $day) : ?>
            <div class="sona-schedule-table__day">
                <h3><?php echo esc_html($day['day']); ?></h3>
                <?php foreach ($day['items'] as $item) : ?>
                    <div class="sona-schedule__row">
                        <span><?php echo esc_html($item['subject']); ?></span>
                        <span class="sona-muted"><?php echo esc_html($item['time']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="sona-muted sona-small"><?php echo esc_html(sona_setting('sona_timezone_note')); ?></p>
<?php else : ?>
    <div class="sona-schedule">
        <?php foreach (sona_schedule() as $day) : ?>
            <div class="sona-schedule__day">
                <span class="sona-schedule__dayname"><?php echo esc_html($day['day']); ?></span>
                <?php foreach ($day['items'] as $item) : ?>
                    <div class="sona-schedule__row">
                        <span><?php echo esc_html($item['subject']); ?></span>
                        <span class="sona-muted"><?php echo esc_html($item['time']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <span class="sona-schedule__note"><?php echo esc_html(sona_setting('sona_timezone_note')); ?></span>
<?php endif; ?>
