<?php
global $USER;

if (isloggedin() && !isguestuser()) {
    defined('MOODLE_INTERNAL') || die();

    require_once($CFG->libdir . '/behat/lib.php');
    require_once($CFG->dirroot . '/course/lib.php');
    require_once($CFG->dirroot . '/cohort/lib.php');


    // KTT CUSTOMIZATION, SEARCH FOR USER COHORTS
    $cohorts = new stdClass();

    if ($USER->id !== 0) {
        $cohortids = array();
        $userCohorts = cohort_get_user_cohorts($USER->id);

        foreach ($userCohorts as $cohort){
            $cohortIdentifier = $cohort->idnumber;
            $cohorts->$cohortIdentifier = $cohortIdentifier;
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
            'forceblockdraweropen' => $forceblockdraweropen,
            'regionmainsettingsmenu' => $regionmainsettingsmenu,
            'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
            'overflow' => $overflow,
            'headercontent' => $headercontent,
            'addblockbutton' => $addblockbutton,
            'logofooter' => $OUTPUT->image_url('FOSlogo-footer', 'theme_ddmood'),
            'slider1' => $OUTPUT->image_url('slider/slider1', 'theme_ddmood'),
            'slider2' => $OUTPUT->image_url('slider/slider2', 'theme_ddmood'),
            'slider3' => $OUTPUT->image_url('slider/slider3', 'theme_ddmood'),
            'i-our_FAT' => $OUTPUT->image_url('icons/fos_feminista_team', 'theme_ddmood'),
            'i-our_FA' => $OUTPUT->image_url('icons/our_Feminist_Alliance', 'theme_ddmood'),
            'i-ISEL' => $OUTPUT->image_url('icons/innovation_and_social_enterprise_lab', 'theme_ddmood'),
            'i-CI' => $OUTPUT->image_url('icons/Centro_de_Incidencia', 'theme_ddmood'),
            'i-CLR' => $OUTPUT->image_url('icons/center_for_legal_responses', 'theme_ddmood'),
            'i-CTN' => $OUTPUT->image_url('icons/center_for_transformative_and_narratives', 'theme_ddmood'),
            'i-C-CSE' => $OUTPUT->image_url('icons/center_for_CSE_and_youth-friendly_services', 'theme_ddmood')

    ];

    echo $OUTPUT->render_from_template('theme_ddmood/frontpage', $templatecontext);
} else {
    redirect(get_login_url());
}