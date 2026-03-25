<?php
/**
 * Weekly Menu Template
 *
 * @package DevChefPress
 */

get_header();

// Output the weekly menu HTML
include DEVCHEFPRESS_PATH . 'app/Frontend/WeeklyMenu/WeeklyMenu.php';

get_footer();