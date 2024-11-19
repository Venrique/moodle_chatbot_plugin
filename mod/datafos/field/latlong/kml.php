<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

// A lot of this initial stuff is copied from mod/datafos/view.php

require_once('../../../../config.php');
require_once('../../lib.php');

// Optional params: row id "rid" - if set then export just one, otherwise export all

$d       = required_param('d', PARAM_INT);   // database id
$fieldid = required_param('fieldid', PARAM_INT);   // field id
$rid     = optional_param('rid', 0, PARAM_INT);    //record id

$url = new moodle_url('/mod/datafos/field/latlong/kml.php', array('d'=>$d, 'fieldid'=>$fieldid));
if ($rid !== 0) {
    $url->param('rid', $rid);
}
$PAGE->set_url($url);

if ($rid) {
    if (! $record = $DB->get_record('data_records_fos', array('id'=>$rid))) {
        throw new \moodle_exception('invalidrecord', 'datafos');
    }
    if (! $data = $DB->get_record('datafos', array('id'=>$record->dataid))) {
        throw new \moodle_exception('invalidid', 'datafos');
    }
    if (! $course = $DB->get_record('course', array('id'=>$data->course))) {
        throw new \moodle_exception('coursemisconf');
    }
    if (! $cm = get_coursemodule_from_instance('datafos', $data->id, $course->id)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
    if (! $field = $DB->get_record('data_fields_fos', array('id'=>$fieldid))) {
        throw new \moodle_exception('invalidfieldid', 'datafos');
    }
    if (! $field->type == 'latlong') { // Make sure we're looking at a latlong datafos type!
        throw new \moodle_exception('invalidfieldtype', 'datafos');
    }
    if (! $content = $DB->get_record('data_content_fos', array('fieldid'=>$fieldid, 'recordid'=>$rid))) {
        throw new \moodle_exception('nofieldcontent', 'datafos');
    }
} else {   // We must have $d
    if (! $data = $DB->get_record('datafos', array('id'=>$d))) {
        throw new \moodle_exception('invalidid', 'datafos');
    }
    if (! $course = $DB->get_record('course', array('id'=>$data->course))) {
        throw new \moodle_exception('coursemisconf');
    }
    if (! $cm = get_coursemodule_from_instance('datafos', $data->id, $course->id)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
    if (! $field = $DB->get_record('data_fields_fos', array('id'=>$fieldid))) {
        throw new \moodle_exception('invalidfieldid', 'datafos');
    }
    if (! $field->type == 'latlong') { // Make sure we're looking at a latlong datafos type!
        throw new \moodle_exception('invalidfieldtype', 'datafos');
    }
    $record = NULL;
}

require_course_login($course, true, $cm);

$context = context_module::instance($cm->id);
// If we have an empty Database then redirect because this page is useless without datafos.
if (has_capability('mod/datafos:managetemplates', $context)) {
    if (!$DB->record_exists('data_fields_fos', array('dataid'=>$data->id))) {      // Brand new database!
        redirect($CFG->wwwroot.'/mod/datafos/field.php?d='.$data->id);  // Redirect to field entry
    }
}




//header('Content-type: text/plain'); // This is handy for debug purposes to look at the KML in the browser
header('Content-type: application/vnd.google-earth.kml+xml kml');
header('Content-Disposition: attachment; filename="moodleearth-'.$d.'-'.$rid.'-'.$fieldid.'.kml"');


echo data_latlong_kml_top();

if($rid) { // List one single item
    $pm = new stdClass();
    $pm->name = data_latlong_kml_get_item_name($content, $field);
    $pm->description = "&lt;a href='$CFG->wwwroot/mod/datafos/view.php?d=$d&amp;rid=$rid'&gt;Item #$rid&lt;/a&gt; in Moodle datafos activity";
    $pm->long = $content->content1;
    $pm->lat = $content->content;
    echo data_latlong_kml_placemark($pm);
} else {   // List all items in turn

    $contents = $DB->get_records('data_content_fos', array('fieldid'=>$fieldid));

    echo '<Document>';

    foreach($contents as $content) {
        $pm->name = data_latlong_kml_get_item_name($content, $field);
        $pm->description = "&lt;a href='$CFG->wwwroot/mod/datafos/view.php?d=$d&amp;rid=$content->recordid'&gt;Item #$content->recordid&lt;/a&gt; in Moodle datafos activity";
        $pm->long = $content->content1;
        $pm->lat = $content->content;
        echo data_latlong_kml_placemark($pm);
    }

    echo '</Document>';

}

echo data_latlong_kml_bottom();




function data_latlong_kml_top() {
    return '<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://earth.google.com/kml/2.0">

';
}

function data_latlong_kml_placemark($pm) {
    return '<Placemark>
  <description>'.$pm->description.'</description>
  <name>'.$pm->name.'</name>
  <LookAt>
    <longitude>'.$pm->long.'</longitude>
    <latitude>'.$pm->lat.'</latitude>
    <range>30500.8880792294568</range>
    <tilt>46.72425699662645</tilt>
    <heading>0.0</heading>
  </LookAt>
  <visibility>0</visibility>
  <Point>
    <extrude>1</extrude>
    <altitudeMode>relativeToGround</altitudeMode>
    <coordinates>'.$pm->long.','.$pm->lat.',50</coordinates>
  </Point>
</Placemark>
';
}

function data_latlong_kml_bottom() {
    return '</kml>';
}

function data_latlong_kml_get_item_name($content, $field) {
    global $DB;

    // $field->param2 contains the user-specified labelling method

    $name = '';

    if($field->param2 > 0) {
        $name = htmlspecialchars($DB->get_field('data_content_fos', 'content', array('fieldid'=>$field->param2, 'recordid'=>$content->recordid)), ENT_COMPAT);
    }elseif($field->param2 == -2) {
        $name = $content->content . ', ' . $content->content1;
    }
    if($name=='') { // Done this way so that "item #" is the default that catches any problems
        $name = get_string('entry', 'data') . " #$content->recordid";
    }


    return $name;
}
