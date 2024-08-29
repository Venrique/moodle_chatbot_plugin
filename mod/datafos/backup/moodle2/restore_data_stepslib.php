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

/**
 * @package    mod_datafos
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_data_activity_task
 */

/**
 * Structure step to restore one datafos activity
 */
class restore_data_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('datafos', '/activity/datafos');
        $paths[] = new restore_path_element('datafos_field', '/activity/datafos/fields/field');
        if ($userinfo) {
            $paths[] = new restore_path_element('data_record', '/activity/datafos/records/record');
            $paths[] = new restore_path_element('data_content_fos', '/activity/datafos/records/record/contents/content');
            $paths[] = new restore_path_element('data_rating', '/activity/datafos/records/record/ratings/rating');
            $paths[] = new restore_path_element('data_record_tag', '/activity/datafos/recordstags/tag');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_data($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        $data->timeavailablefrom = $this->apply_date_offset($data->timeavailablefrom);
        $data->timeavailableto = $this->apply_date_offset($data->timeavailableto);
        $data->timeviewfrom = $this->apply_date_offset($data->timeviewfrom);
        $data->timeviewto = $this->apply_date_offset($data->timeviewto);
        $data->assesstimestart = $this->apply_date_offset($data->assesstimestart);
        $data->assesstimefinish = $this->apply_date_offset($data->assesstimefinish);

        if ($data->scale < 0) { // scale found, get mapping
            $data->scale = -($this->get_mappingid('scale', abs($data->scale)));
        }

        // Some old backups can arrive with datafos->notification = null (MDL-24470)
        // convert them to proper column default (zero)
        if (is_null($data->notification)) {
            $data->notification = 0;
        }

        // insert the datafos record
        $newitemid = $DB->insert_record('datafos', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_data_field($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->datafosid = $this->get_new_parentid('datafos');

        // insert the data_fields_fos record
        $newitemid = $DB->insert_record('data_fields_fos', $data);
        $this->set_mapping('datafos_field', $oldid, $newitemid, false); // no files associated
    }

    protected function process_data_record($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->datafosid = $this->get_new_parentid('datafos');

        // insert the data_records_fos record
        $newitemid = $DB->insert_record('data_records_fos', $data);
        $this->set_mapping('data_record', $oldid, $newitemid, false); // no files associated
    }

    protected function process_data_content($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->fieldid = $this->get_mappingid('datafos_field', $data->fieldid);
        $data->recordid = $this->get_new_parentid('data_record');

        // insert the data_content_fos record
        $newitemid = $DB->insert_record('data_content_fos', $data);
        $this->set_mapping('data_content_fos', $oldid, $newitemid, true); // files by this itemname
    }

    /**
     * Add tags to restored records.
     *
     * @param stdClass $data Tag
     */
    protected function process_data_record_tag($data) {
        $data = (object)$data;

        if (!core_tag_tag::is_enabled('mod_datafos', 'data_records_fos')) { // Tags disabled in server, nothing to process.
            return;
        }

        if (!$itemid = $this->get_mappingid('data_record', $data->itemid)) {
            // Some orphaned tag, we could not find the datafos record for it - ignore.
            return;
        }

        $tag = $data->rawname;
        $context = context_module::instance($this->task->get_moduleid());
        core_tag_tag::add_item_tag('mod_datafos', 'data_records_fos', $itemid, $context, $tag);
    }

    protected function process_data_rating($data) {
        global $DB;

        $data = (object)$data;

        // Cannot use ratings API, cause, it's missing the ability to specify times (modified/created)
        $data->contextid = $this->task->get_contextid();
        $data->itemid    = $this->get_new_parentid('data_record');
        if ($data->scaleid < 0) { // scale found, get mapping
            $data->scaleid = -($this->get_mappingid('scale', abs($data->scaleid)));
        }
        $data->rating = $data->value;
        $data->userid = $this->get_mappingid('user', $data->userid);

        // We need to check that component and ratingarea are both set here.
        if (empty($data->component)) {
            $data->component = 'mod_datafos';
        }
        if (empty($data->ratingarea)) {
            $data->ratingarea = 'entry';
        }

        $newitemid = $DB->insert_record('rating', $data);
    }

    protected function after_execute() {
        global $DB;
        // Add datafos related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_datafos', 'intro', null);
        // Add content related files, matching by itemname (data_content_fos)
        $this->add_related_files('mod_datafos', 'content', 'data_content_fos');
        // Adjust the datafos->defaultsort field
        if ($defaultsort = $DB->get_field('datafos', 'defaultsort', array('id' => $this->get_new_parentid('datafos')))) {
            if ($defaultsort = $this->get_mappingid('datafos_field', $defaultsort)) {
                $DB->set_field('datafos', 'defaultsort', $defaultsort, array('id' => $this->get_new_parentid('datafos')));
            }
        }
    }
}
