<?php
/**
 * Free academy: courses can only be ranked by students, not revenue.
 */

defined('ABSPATH') || exit;

unset($options['revenue']);
$selected = isset($options[$selected]) ? $selected : 'student';

include tutor()->path . 'templates/dashboard/instructor/home/top-performing-course-filter.php';
