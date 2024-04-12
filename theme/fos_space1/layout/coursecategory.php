<?php
global $USER, $DB;

if (isloggedin() && !isguestuser()) {
    defined('MOODLE_INTERNAL') || die();

    require_once($CFG->libdir . '/behat/lib.php');
    require_once($CFG->dirroot . '/course/lib.php');

    require_once($CFG->dirroot . '/lib/modinfolib.php');

    $modules = new stdClass();

    try{
        $course = $DB->get_record('course', array('shortname' => 'FF'));
        
        $courseModulesObject = get_fast_modinfo($course->id);
        $courseModules = $courseModulesObject->get_cms();
    
        foreach ($courseModules as $module){
            if (($module->modname == 'forum' || $module->modname == 'data') && $module->deletioninprogress == 0){
                $moduleIdentifier = $module->modname;
                $modules->$moduleIdentifier = $module->id;
            }
        }

        $supportCourse = $course = $DB->get_record('course', array('shortname' => 'SD'));
        $modules->support = $supportCourse->id;
    }catch(Exception $e){
        echo $e->getMessage();
    }

    // Add block button in editing mode.
    $addblockbutton = $OUTPUT->addblockbutton();


    $extraclasses = ['uses-drawers'];

    $blockshtml = $OUTPUT->blocks('side-pre');
    $bodyattributes = $OUTPUT->body_attributes($extraclasses);

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
        'bodyattributes' => $bodyattributes,
        'primarymoremenu' => $primarymenu['moremenu'],
        'secondarymoremenu' => $secondarynavigation ?: false,
        'mobileprimarynav' => $primarymenu['mobileprimarynav'],
        'usermenu' => $primarymenu['user'],
        'langmenu' => $primarymenu['lang'],
        'regionmainsettingsmenu' => $regionmainsettingsmenu,
        'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
        'overflow' => $overflow,
        'headercontent' => $headercontent,
        'addblockbutton' => $addblockbutton,
        'contentcategory' => $OUTPUT->main_content(),
        'logofooter' => $OUTPUT->image_url('FOSlogo-footer', 'theme_fos_space1'),
        'modules' => $modules

    ];
    echo $OUTPUT->render_from_template('theme_fos_space1/coursecategory', $templatecontext);
} else {
    redirect(get_login_url());
}