<?php
global $USER, $DB;

if (isloggedin() && !isguestuser()) {
    defined('MOODLE_INTERNAL') || die();

    require_once($CFG->libdir . '/behat/lib.php');
    require_once($CFG->dirroot . '/course/lib.php');

    // KTT CUSTOMIZATION, SEARCH FOR USER COHORTS
    require_once($CFG->dirroot . '/cohort/lib.php');

    $cohorts = new stdClass();
    $categories = new stdClass();

    if ($USER->id !== 0) {
        $cohortids = array();
        $userCohorts = cohort_get_user_cohorts($USER->id);

        foreach ($userCohorts as $cohort){
            $cohortIdentifier = $cohort->idnumber;
            $cohorts->$cohortIdentifier = $cohortIdentifier;
        }

        $systemCategories = $DB->get_records('course_categories');
        
        foreach ($systemCategories as $category){
            $categoryIdentifier = $category->idnumber;
            $categories->$categoryIdentifier = $category->id;
        }
    }

    // Add block button in editing mode.
    $addblockbutton = $OUTPUT->addblockbutton();

    user_preference_allow_ajax_update('drawer-open-index', PARAM_BOOL);
    user_preference_allow_ajax_update('drawer-open-block', PARAM_BOOL);

    if (isloggedin()) {
        $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
        $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
    } else {
        $courseindexopen = false;
        $blockdraweropen = false;
    }

    if (defined('BEHAT_SITE_RUNNING')) {
        $blockdraweropen = true;
    }

    $extraclasses = ['uses-drawers'];
    if ($courseindexopen) {
        $extraclasses[] = 'drawer-open-index';
    }

    $blockshtml = $OUTPUT->blocks('side-pre');
    $hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
    if (!$hasblocks) {
        $blockdraweropen = false;
    }
    $courseindex = core_course_drawer();
    if (!$courseindex) {
        $courseindexopen = false;
    }

    $bodyattributes = $OUTPUT->body_attributes($extraclasses);
    $forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

    $secondarynavigation = false;
    $overflow = '';
    if ($PAGE->has_secondary_navigation()) {
        $tablistnav = $PAGE->has_tablist_secondary_navigation();
        $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
        $secondarynavigation = $moremenu->export_for_template($OUTPUT);
        $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
        if (!is_null($overflowdata)) {
            $overflow = $overflowdata->export_for_template($OUTPUT);
        }
    }

    $primary = new core\navigation\output\primary($PAGE);
    $renderer = $PAGE->get_renderer('core');
    $primarymenu = $primary->export_for_template($renderer);
    $buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
    // If the settings menu will be included in the header then don't add it here.
    $regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

    $header = $PAGE->activityheader;
    $headercontent = $header->export_for_template($renderer);

    $templatecontext = [
            'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
            'output' => $OUTPUT,
            'sidepreblocks' => $blockshtml,
            'hasblocks' => $hasblocks,
            'bodyattributes' => $bodyattributes,
            'courseindexopen' => $courseindexopen,
            'blockdraweropen' => $blockdraweropen,
            'courseindex' => $courseindex,
            'primarymoremenu' => $primarymenu['moremenu'],
            'secondarymoremenu' => $secondarynavigation ?: false,
            'mobileprimarynav' => $primarymenu['mobileprimarynav'],
            'usermenu' => $primarymenu['user'],
            'langmenu' => $primarymenu['lang'],
            'cohorts' => $cohorts,
            'categories' => $categories,
            'forceblockdraweropen' => $forceblockdraweropen,
            'regionmainsettingsmenu' => $regionmainsettingsmenu,
            'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
            'overflow' => $overflow,
            'headercontent' => $headercontent,
            'addblockbutton' => $addblockbutton,
            'logofooter' => $OUTPUT->image_url('FOSlogo-footer', 'theme_fos_space1'),
            'slider1' => $OUTPUT->image_url('slider/slider1', 'theme_fos_space1'),
            'slider2' => $OUTPUT->image_url('slider/slider2', 'theme_fos_space1'),
            'slider3' => $OUTPUT->image_url('slider/slider3', 'theme_fos_space1'),
            'i-our_FAT' => $OUTPUT->image_url('icons/fos_feminista_team', 'theme_fos_space1'),
            'i-our_FA' => $OUTPUT->image_url('icons/others', 'theme_fos_space1'),
            'i-ISEL' => $OUTPUT->image_url('icons/others', 'theme_fos_space1'),
            'i-CI' => $OUTPUT->image_url('icons/others', 'theme_fos_space1'),
            'i-CLR' => $OUTPUT->image_url('icons/others', 'theme_fos_space1'),
            'i-CTN' => $OUTPUT->image_url('icons/others', 'theme_fos_space1'),
            'i-C-CSE' => $OUTPUT->image_url('icons/others', 'theme_fos_space1')

    ];

    echo $OUTPUT->render_from_template('theme_fos_space1/frontpage', $templatecontext);
} else {
    redirect(get_login_url());
}