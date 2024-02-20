<?php
/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     theme_ddmood2
 * @copyright   2024 digital Mood  <findme@digital-mood.eu>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$THEME->name = 'ddmood2';

$THEME->parents = array(
    'boost',
);

$THEME->sheets = array(
    'moodle',
);

$THEME->haseditswitch = true;

$THEME->rendererfactory = 'theme_overridden_renderer_factory';