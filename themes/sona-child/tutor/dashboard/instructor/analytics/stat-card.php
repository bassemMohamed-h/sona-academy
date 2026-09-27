<?php
/**
 * Free academy: drop the "Total Earnings" card, render every other stat card as usual.
 */

defined('ABSPATH') || exit;

if (($icon ?? '') === \TUTOR\Icon::EARNING) {
    return;
}

include tutor()->path . 'templates/dashboard/instructor/analytics/stat-card.php';
