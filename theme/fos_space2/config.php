<?php
/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     theme_fos_space2
 * @copyright   2024 digital Mood  <findme@digital-mood.eu>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$THEME->name = 'fos_space2';

$THEME->parents = array(
    'boost',
);

$THEME->sheets = array(
    'moodle',
);

$THEME->layouts = [
    // Main course page.
    'course' => array(
        'file' => 'drawers.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array('langmenu' => true),
    ),
    'coursecategory' => array(
        'file' => 'coursecategory.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    )
];

$THEME->haseditswitch = true;

$THEME->rendererfactory = 'theme_overridden_renderer_factory';

