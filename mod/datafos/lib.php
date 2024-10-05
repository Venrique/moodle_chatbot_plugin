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
 * @package   mod_datafos
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_datafos\manager;
use mod_datafos\preset;

defined('MOODLE_INTERNAL') || die();

// Some constants
define ('DATAFOS_MAX_ENTRIES', 50);
define ('DATAFOS_PERPAGE_SINGLE', 1);

define ('DATAFOS_FIRSTNAME', -1);
define ('DATAFOS_LASTNAME', -2);
define ('DATAFOS_APPROVED', -3);
define ('DATAFOS_TIMEADDED', 0);
define ('DATAFOS_TIMEMODIFIED', -4);
define ('DATAFOS_TAGS', -5);

define ('DATAFOS_CAP_EXPORT', 'mod/datafos:viewalluserpresets');
// Users having assigned the default role "Non-editing teacher" can export database records
// Using the mod/datafos capability "viewalluserpresets" existing in Moodle 1.9.x.
// In Moodle >= 2, new roles may be introduced and used instead.

define('DATAFOS_PRESET_COMPONENT', 'mod_datafos');
define('DATAFOS_PRESET_FILEAREA', 'site_presets');
define('DATAFOS_PRESET_CONTEXT', SYSCONTEXTID);

define('DATAFOS_EVENT_TYPE_OPEN', 'open');
define('DATAFOS_EVENT_TYPE_CLOSE', 'close');

require_once(__DIR__ . '/deprecatedlib.php');

/**
 * @package   mod_datafos
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class datafos_field_base {     // Base class for Database Field Types (see field/*/field.class.php)

    /** @var string Subclasses must override the type with their name */
    var $type = 'unknown';
    /** @var object The database object that this field belongs to */
    var $data = NULL;
    /** @var object The field object itself, if we know it */
    var $field = NULL;
    /** @var int Width of the icon for this fieldtype */
    var $iconwidth = 16;
    /** @var int Width of the icon for this fieldtype */
    var $iconheight = 16;
    /** @var object course module or cmifno */
    var $cm;
    /** @var object activity context */
    var $context;
    /** @var priority for globalsearch indexing */
    protected static $priority = self::NO_PRIORITY;
    /** priority value for invalid fields regarding indexing */
    const NO_PRIORITY = 0;
    /** priority value for minimum priority */
    const MIN_PRIORITY = 1;
    /** priority value for low priority */
    const LOW_PRIORITY = 2;
    /** priority value for high priority */
    const HIGH_PRIORITY = 3;
    /** priority value for maximum priority */
    const MAX_PRIORITY = 4;

    /** @var bool whether the field is used in preview mode. */
    protected $preview = false;

    /**
     * Constructor function
     *
     * @global object
     * @uses CONTEXT_MODULE
     * @param int $field
     * @param int $data
     * @param int $cm
     */
    function __construct($field=0, $data=0, $cm=0) {   // Field or datafos or both, each can be id or object
        global $DB;

        if (empty($field) && empty($data)) {
            throw new \moodle_exception('missingfield', 'datafos');
        }

        if (!empty($field)) {
            if (is_object($field)) {
                $this->field = $field;  // Programmer knows what they are doing, we hope
            } else if (!$this->field = $DB->get_record('data_fields_fos', array('id'=>$field))) {
                throw new \moodle_exception('invalidfieldid', 'datafos');
            }
            if (empty($data)) {
                if (!$this->datafos = $DB->get_record('datafos', array('id'=>$this->field->dataid))) {
                    throw new \moodle_exception('invalidid', 'datafos');
                }
            }
        }

        if (empty($this->datafos)) {         // We need to define this properly
            if (!empty($data)) {
                if (is_object($data)) {
                    $this->datafos = $data;  // Programmer knows what they are doing, we hope
                } else if (!$this->datafos = $DB->get_record('datafos', array('id'=>$data))) {
                    throw new \moodle_exception('invalidid', 'datafos');
                }
            } else {                      // No way to define it!
                throw new \moodle_exception('missingdata', 'datafos');
            }
        }

        if ($cm) {
            $this->cm = $cm;
        } else {
            $this->cm = get_coursemodule_from_instance('datafos', $this->datafos->id);
        }

        if (empty($this->field)) {         // We need to define some default values
            $this->define_default_field();
        }

        $this->context = context_module::instance($this->cm->id);
    }

    /**
     * Return the field type name.
     *
     * @return string the filed type.
     */
    public function get_name(): string {
        return $this->field->name;
    }

    /**
     * Return if the field type supports preview.
     *
     * Fields without a preview cannot be displayed in the preset preview.
     *
     * @return bool if the plugin supports preview.
     */
    public function supports_preview(): bool {
        return false;
    }

    /**
     * Generate a fake data_content_fos for this field to be used in preset previews.
     *
     * Data plugins must override this method and support_preview in order to enable
     * preset preview for this field.
     *
     * @param int $recordid the fake record id
     * @return stdClass the fake record
     */
    public function get_data_content_preview(int $recordid): stdClass {
        $message = get_string('nopreviewavailable', 'mod_datafos', $this->field->name);
        return (object)[
            'id' => 0,
            'fieldid' => $this->field->id,
            'recordid' => $recordid,
            'content' => "<span class=\"nopreview\">$message</span>",
            'content1' => null,
            'content2' => null,
            'content3' => null,
            'content4' => null,
        ];
    }

    /**
     * Set the field to preview mode.
     *
     * @param bool $preview the new preview value
     */
    public function set_preview(bool $preview) {
        $this->preview = $preview;
    }

    /**
     * Get the field preview value.
     *
     * @return bool
     */
    public function get_preview(): bool {
        return $this->preview;
    }


    /**
     * This field just sets up a default field object
     *
     * @return bool
     */
    function define_default_field() {
        global $OUTPUT;
        if (empty($this->datafos->id)) {
            echo $OUTPUT->notification('Programmer error: dataid not defined in field class');
        }
        $this->field = new stdClass();
        $this->field->id = 0;
        $this->field->dataid = $this->datafos->id;
        $this->field->type   = $this->type;
        $this->field->param1 = '';
        $this->field->param2 = '';
        $this->field->param3 = '';
        $this->field->name = '';
        $this->field->description = '';
        $this->field->required = false;

        return true;
    }

    /**
     * Set up the field object according to datafos in an object.  Now is the time to clean it!
     *
     * @return bool
     */
    function define_field($data) {
        $this->field->type        = $this->type;
        $this->field->dataid      = $this->datafos->id;

        $this->field->name        = trim($data->name);
        $this->field->description = trim($data->description);
        $this->field->required    = !empty($data->required) ? 1 : 0;

        if (isset($data->param1)) {
            $this->field->param1 = trim($data->param1);
        }
        if (isset($data->param2)) {
            $this->field->param2 = trim($data->param2);
        }
        if (isset($data->param3)) {
            $this->field->param3 = trim($data->param3);
        }
        if (isset($data->param4)) {
            $this->field->param4 = trim($data->param4);
        }
        if (isset($data->param5)) {
            $this->field->param5 = trim($data->param5);
        }

        return true;
    }

    /**
     * Insert a new field in the database
     * We assume the field object is already defined as $this->field
     *
     * @global object
     * @return bool
     */
    function insert_field() {
        global $DB, $OUTPUT;

        if (empty($this->field)) {
            echo $OUTPUT->notification('Programmer error: Field has not been defined yet!  See define_field()');
            return false;
        }

        $this->field->id = $DB->insert_record('data_fields_fos',$this->field);

        // Trigger an event for creating this field.
        $event = \mod_datafos\event\field_created::create(array(
            'objectid' => $this->field->id,
            'context' => $this->context,
            'other' => array(
                'fieldname' => $this->field->name,
                'dataid' => $this->datafos->id
            )
        ));
        $event->trigger();

        return true;
    }


    /**
     * Update a field in the database
     *
     * @global object
     * @return bool
     */
    function update_field() {
        global $DB;

        $DB->update_record('data_fields_fos', $this->field);

        // Trigger an event for updating this field.
        $event = \mod_datafos\event\field_updated::create(array(
            'objectid' => $this->field->id,
            'context' => $this->context,
            'other' => array(
                'fieldname' => $this->field->name,
                'dataid' => $this->datafos->id
            )
        ));
        $event->trigger();

        return true;
    }

    /**
     * Delete a field completely
     *
     * @global object
     * @return bool
     */
    function delete_field() {
        global $DB;

        if (!empty($this->field->id)) {
            $manager = manager::create_from_instance($this->datafos);

            // Get the field before we delete it.
            $field = $DB->get_record('data_fields_fos', array('id' => $this->field->id));

            $this->delete_content();
            $DB->delete_records('data_fields_fos', array('id'=>$this->field->id));

            // Trigger an event for deleting this field.
            $event = \mod_datafos\event\field_deleted::create(array(
                'objectid' => $this->field->id,
                'context' => $this->context,
                'other' => array(
                    'fieldname' => $this->field->name,
                    'dataid' => $this->datafos->id
                 )
            ));

            if (!$manager->has_fields() && $manager->has_records()) {
                $DB->delete_records('data_records_fos', ['dataid' => $this->datafos->id]);
            }

            $event->add_record_snapshot('data_fields_fos', $field);
            $event->trigger();
        }

        return true;
    }

    /**
     * Print the relevant form element in the ADD template for this field
     *
     * @global object
     * @param int $recordid
     * @return string
     */
    function display_add_field($recordid=0, $formdata=null) {
        global $DB, $OUTPUT;

        if ($formdata) {
            $fieldname = 'field_' . $this->field->id;
            $content = $formdata->$fieldname;
        } else if ($recordid) {
            $content = $DB->get_field('data_content_fos', 'content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid));
        } else {
            $content = '';
        }

        // beware get_field returns false for new, empty records MDL-18567
        if ($content===false) {
            $content='';
        }

        $str = '<div title="' . s($this->field->description) . '">';
        $str .= '<label for="field_'.$this->field->id.'"><span class="accesshide">'.$this->field->name.'</span>';
        if ($this->field->required) {
            $image = $OUTPUT->pix_icon('req', get_string('requiredelement', 'form'));
            $str .= html_writer::div($image, 'inline-req');
        }
        $str .= '</label><input class="basefieldinput form-control d-inline mod-datafos-input" ' .
                'type="text" name="field_' . $this->field->id . '" ' .
                'id="field_' . $this->field->id . '" value="' . s($content) . '" />';
        $str .= '</div>';

        return $str;
    }

    /**
     * Print the relevant form element to define the attributes for this field
     * viewable by teachers only.
     *
     * @global object
     * @global object
     * @return void Output is echo'd
     */
    function display_edit_field() {
        global $CFG, $DB, $OUTPUT;

        if (empty($this->field)) {   // No field has been defined yet, try and make one
            $this->define_default_field();
        }

        // Throw an exception if field type doen't exist. Anyway user should never access to edit a field with an unknown fieldtype.
        if ($this->type === 'unknown') {
            throw new \moodle_exception(get_string('missingfieldtype', 'datafos', (object)['name' => $this->field->name]));
        }

        echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');

        echo '<form id="editfield" action="'.$CFG->wwwroot.'/mod/datafos/field.php" method="post">'."\n";
        echo '<input type="hidden" name="d" value="'.$this->datafos->id.'" />'."\n";
        if (empty($this->field->id)) {
            echo '<input type="hidden" name="mode" value="add" />'."\n";
        } else {
            echo '<input type="hidden" name="fid" value="'.$this->field->id.'" />'."\n";
            echo '<input type="hidden" name="mode" value="update" />'."\n";
        }
        echo '<input type="hidden" name="type" value="'.$this->type.'" />'."\n";
        echo '<input name="sesskey" value="'.sesskey().'" type="hidden" />'."\n";

        echo $OUTPUT->heading($this->name(), 3);

        $filepath = $CFG->dirroot.'/mod/datafos/field/'.$this->type.'/mod.html';

        if (!file_exists($filepath)) {
            throw new \moodle_exception(get_string('missingfieldtype', 'datafos', (object)['name' => $this->field->name]));
        } else {
            require_once($filepath);
        }

        $actionbuttons = html_writer::start_div();
        $actionbuttons .= html_writer::tag('input', null, [
            'type' => 'submit',
            'name' => 'cancel',
            'value' => get_string('cancel'),
            'class' => 'btn btn-secondary mx-1'
        ]);
        $actionbuttons .= html_writer::tag('input', null, [
            'type' => 'submit',
            'value' => get_string('save'),
            'class' => 'btn btn-primary mx-1'
        ]);
        $actionbuttons .= html_writer::end_div();

        $stickyfooter = new core\output\sticky_footer($actionbuttons);
        echo $OUTPUT->render($stickyfooter);

        echo '</form>';

        echo $OUTPUT->box_end();
    }

    /**
     * Validates params of fieldinput datafos. Overwrite to validate fieldtype specific datafos.
     *
     * You are expected to return an array like ['paramname' => 'Error message for paramname param'] if there is an error,
     * return an empty array if everything is fine.
     *
     * @param stdClass $fieldinput The field input datafos to check
     * @return array $errors if empty validation was fine, otherwise contains one or more error messages
     */
    public function validate(stdClass $fieldinput): array {
        return [];
    }

    /**
     * Return the data_content_fos of the field, or generate it if it is in preview mode.
     *
     * @param int $recordid the record id
     * @return stdClass|bool the record datafos or false if none
     */
    protected function get_data_content(int $recordid) {
        global $DB;
        if ($this->preview) {
            return $this->get_data_content_preview($recordid);
        }
        return $DB->get_record(
            'data_content_fos',
            ['fieldid' => $this->field->id, 'recordid' => $recordid]
        );
    }

    /**
     * Display the content of the field in browse mode
     *
     * @global object
     * @param int $recordid
     * @param object $template
     * @return bool|string
     */
    function display_browse_field($recordid, $template) {
        global $DB, $SESSION, $USER;
        $content = $this->get_data_content($recordid);
        if (!$content || !isset($content->content)) {
            return '';
        }
        $options = new stdClass();
        if ($this->field->param1 == '1') {
            // We are autolinking this field, so disable linking within us.
            $options->filter = false;
        }
        $options->para = false;
        if (!property_exists($SESSION,'lang')){
            $SESSION->lang = $USER->lang;
        }

       /* //KTT CUSTOMIZATION
        if ($this->field->name === "Category"){
            switch ($content->content) {
                case "General tool":
                    $content->content = get_string('generaltoolcategory', 'data');
                    break;
                case "Quality of care tool":
                    $content->content = get_string('qualityofcaretoolcategory', 'data');
                    break;
                case "Research":
                    $content->content = get_string('researchcategory', 'data');
                    break;
                case "Narrative":
                    $content->content = get_string('narrativecategory', 'data');
                    break;
            }
        }*/

        $str = format_text($content->content, $content->content1, $options);
        return $str;
    }

    /**
     * Update the content of one datafos field in the data_content_fos table
     * @global object
     * @param int $recordid
     * @param mixed $value
     * @param string $name
     * @return bool
     */
    function update_content($recordid, $value, $name=''){
        global $DB;

        $content = new stdClass();
        $content->fieldid = $this->field->id;
        $content->recordid = $recordid;
        $content->content = clean_param($value, PARAM_NOTAGS);

        if ($oldcontent = $DB->get_record('data_content_fos', array('fieldid'=>$this->field->id, 'recordid'=>$recordid))) {
            $content->id = $oldcontent->id;
            return $DB->update_record('data_content_fos', $content);
        } else {
            return $DB->insert_record('data_content_fos', $content);
        }
    }

    /**
     * Delete all content associated with the field
     *
     * @global object
     * @param int $recordid
     * @return bool
     */
    function delete_content($recordid=0) {
        global $DB;

        if ($recordid) {
            $conditions = array('fieldid'=>$this->field->id, 'recordid'=>$recordid);
        } else {
            $conditions = array('fieldid'=>$this->field->id);
        }

        $rs = $DB->get_recordset('data_content_fos', $conditions);
        if ($rs->valid()) {
            $fs = get_file_storage();
            foreach ($rs as $content) {
                $fs->delete_area_files($this->context->id, 'mod_datafos', 'content', $content->id);
            }
        }
        $rs->close();

        return $DB->delete_records('data_content_fos', $conditions);
    }

    /**
     * Check if a field from an add form is empty
     *
     * @param mixed $value
     * @param mixed $name
     * @return bool
     */
    function notemptyfield($value, $name) {
        return !empty($value);
    }

    /**
     * Just in case a field needs to print something before the whole form
     */
    function print_before_form() {
    }

    /**
     * Just in case a field needs to print something after the whole form
     */
    function print_after_form() {
    }


    /**
     * Returns the sortable field for the content. By default, it's just content
     * but for some plugins, it could be content 1 - content4
     *
     * @return string
     */
    function get_sort_field() {
        return 'content';
    }

    /**
     * Returns the SQL needed to refer to the column.  Some fields may need to CAST() etc.
     *
     * @param string $fieldname
     * @return string $fieldname
     */
    function get_sort_sql($fieldname) {
        return $fieldname;
    }

    /**
     * Returns the name/type of the field
     *
     * @return string
     */
    function name() {
        return get_string('fieldtypelabel', "datafield_$this->type");
    }

    /**
     * Prints the respective type icon
     *
     * @global object
     * @return string
     */
    function image() {
        global $OUTPUT;

        return $OUTPUT->pix_icon('field/' . $this->type, $this->type, 'datafos');
    }

    /**
     * Per default, it is assumed that fields support text exporting.
     * Override this (return false) on fields not supporting text exporting.
     *
     * @return bool true
     */
    function text_export_supported() {
        return true;
    }

    /**
     * Per default, it is assumed that fields do not support file exporting. Override this (return true)
     * on fields supporting file export. You will also have to implement export_file_value().
     *
     * @return bool true if field will export a file, false otherwise
     */
    public function file_export_supported(): bool {
        return false;
    }

    /**
     * Per default, does not return a file (just null).
     * Override this in fields class, if you want your field to export a file content.
     * In case you are exporting a file value, export_text_value() should return the corresponding file name.
     *
     * @param stdClass $record
     * @return null|string the file content as string or null, if no file content is being provided
     */
    public function export_file_value(stdClass $record): null|string {
        return null;
    }

    /**
     * Per default, a field does not support the import of files.
     *
     * A field type can overwrite this function and return true. In this case it also has to implement the function
     * import_file_value().
     *
     * @return false means file imports are not supported
     */
    public function file_import_supported(): bool {
        return false;
    }

    /**
     * Returns a stored_file object for exporting a file of a given record.
     *
     * @param int $contentid content id
     * @param string $filecontent the content of the file as string
     * @param string $filename the filename the file should have
     */
    public function import_file_value(int $contentid, string $filecontent, string $filename): void {
        return;
    }

    /**
     * Per default, return the record's text value only from the "content" field.
     * Override this in fields class if necessary.
     *
     * @param stdClass $record
     * @return string
     */
    public function export_text_value(stdClass $record) {
        if ($this->text_export_supported()) {
            return $record->content;
        }
        return '';
    }

    /**
     * @param string $relativepath
     * @return bool false
     */
    function file_ok($relativepath) {
        return false;
    }

    /**
     * Returns the priority for being indexed by globalsearch
     *
     * @return int
     */
    public static function get_priority() {
        return static::$priority;
    }

    /**
     * Returns the presentable string value for a field content.
     *
     * The returned string should be plain text.
     *
     * @param stdClass $content
     * @return string
     */
    public static function get_content_value($content) {
        return trim($content->content, "\r\n ");
    }

    /**
     * Return the plugin configs for external functions,
     * in some cases the configs will need formatting or be returned only if the current user has some capabilities enabled.
     *
     * @return array the list of config parameters
     * @since Moodle 3.3
     */
    public function get_config_for_external() {
        // Return all the field configs to null (maybe there is a private key for a service or something similar there).
        $configs = [];
        for ($i = 1; $i <= 10; $i++) {
            $configs["param$i"] = null;
        }
        return $configs;
    }
}


/**
 * Given a template and a dataid, generate a default case template
 *
 * @param stdClass $data the mod_datafos record.
 * @param string $template the template name
 * @param int $recordid the entry record
 * @param bool $form print a form instead of datafos
 * @param bool $update if the function update the $data object or not
 * @return string the template content or an empty string if no content is available (for instance, when database has no fields).
 */
function datafos_generate_default_template(&$data, $template, $recordid = 0, $form = false, $update = true) {
    global $DB;

    if (!$data || !$template) {
        return '';
    }

    // These templates are empty by default (they have no content).
    $emptytemplates = [
        'csstemplate',
        'jstemplate',
        'listtemplateheader',
        'listtemplatefooter',
        'rsstitletemplate',
    ];
    if (in_array($template, $emptytemplates)) {
        return '';
    }

    $manager = manager::create_from_instance($data);
    if (empty($manager->get_fields())) {
        // No template will be returned if there are no fields.
        return '';
    }

    $templateclass = \mod_datafos\template::create_default_template($manager, $template, $form);
    $templatecontent = $templateclass->get_template_content();

    if ($update) {
        // Update the database instance.
        $newdata = new stdClass();
        $newdata->id = $data->id;
        $newdata->{$template} = $templatecontent;
        $DB->update_record('datafos', $newdata);
        $data->{$template} = $templatecontent;
    }

    return $templatecontent;
}

/**
 * Build the form elements to manage tags for a record.
 *
 * @param int|bool $recordid
 * @param string[] $selected raw tag names
 * @return string
 */
function datafos_generate_tag_form($recordid = false, $selected = []) {
    global $CFG, $DB, $OUTPUT, $PAGE;

    $tagtypestoshow = \core_tag_area::get_showstandard('mod_datafos', 'data_records_fos');
    $showstandard = ($tagtypestoshow != core_tag_tag::HIDE_STANDARD);
    $typenewtags = ($tagtypestoshow != core_tag_tag::STANDARD_ONLY);

    $str = html_writer::start_tag('div', array('class' => 'datatagcontrol'));

    $namefield = empty($CFG->keeptagnamecase) ? 'name' : 'rawname';

    $tagcollid = \core_tag_area::get_collection('mod_datafos', 'data_records_fos');
    $tags = [];
    $selectedtags = [];

    if ($showstandard) {
        $tags += $DB->get_records_menu('tag', array('isstandard' => 1, 'tagcollid' => $tagcollid),
            $namefield, 'id,' . $namefield . ' as fieldname');
    }

    if ($recordid) {
        $selectedtags += core_tag_tag::get_item_tags_array('mod_datafos', 'data_records_fos', $recordid);
    }

    if (!empty($selected)) {
        list($sql, $params) = $DB->get_in_or_equal($selected, SQL_PARAMS_NAMED);
        $params['tagcollid'] = $tagcollid;
        $sql = "SELECT id, $namefield FROM {tag} WHERE tagcollid = :tagcollid AND rawname $sql";
        $selectedtags += $DB->get_records_sql_menu($sql, $params);
    }

    $tags += $selectedtags;

    $str .= '<select class="custom-select" name="tags[]" id="tags" multiple>';
    foreach ($tags as $tagid => $tag) {
        $selected = key_exists($tagid, $selectedtags) ? 'selected' : '';
        $str .= "<option value='$tag' $selected>$tag</option>";
    }
    $str .= '</select>';

    if (has_capability('moodle/tag:manage', context_system::instance()) && $showstandard) {
        $url = new moodle_url('/tag/manage.php', array('tc' => core_tag_area::get_collection('mod_datafos',
            'data_records_fos')));
        $str .= ' ' . $OUTPUT->action_link($url, get_string('managestandardtags', 'tag'));
    }

    $PAGE->requires->js_call_amd('core/form-autocomplete', 'enhance', $params = array(
            '#tags',
            $typenewtags,
            '',
            get_string('entertags', 'tag'),
            false,
            $showstandard,
            get_string('noselection', 'form')
        )
    );

    $str .= html_writer::end_tag('div');

    return $str;
}


/**
 * Search for a field name and replaces it with another one in all the
 * form templates. Set $newfieldname as '' if you want to delete the
 * field from the form.
 *
 * @global object
 * @param object $data
 * @param string $searchfieldname
 * @param string $newfieldname
 * @return bool
 */
function datafos_replace_field_in_templates($data, $searchfieldname, $newfieldname) {
    global $DB;

    $newdata = (object)['id' => $data->id];
    $update = false;
    $templates = ['listtemplate', 'singletemplate', 'asearchtemplate', 'addtemplate', 'rsstemplate'];
    foreach ($templates as $templatename) {
        if (empty($data->$templatename)) {
            continue;
        }
        $search = [
            '[[' . $searchfieldname . ']]',
            '[[' . $searchfieldname . '#id]]',
            '[[' . $searchfieldname . '#name]]',
            '[[' . $searchfieldname . '#description]]',
        ];
        if (empty($newfieldname)) {
            $replace = ['', '', '', ''];
        } else {
            $replace = [
                '[[' . $newfieldname . ']]',
                '[[' . $newfieldname . '#id]]',
                '[[' . $newfieldname . '#name]]',
                '[[' . $newfieldname . '#description]]',
            ];
        }
        $newdata->{$templatename} = str_ireplace($search, $replace, $data->{$templatename} ?? '');
        $update = true;
    }
    if (!$update) {
        return true;
    }
    return $DB->update_record('datafos', $newdata);
}


/**
 * Appends a new field at the end of the form template.
 *
 * @global object
 * @param object $data
 * @param string $newfieldname
 * @return bool if the field has been added or not
 */
function datafos_append_new_field_to_templates($data, $newfieldname): bool {
    global $DB, $OUTPUT;

    $newdata = (object)['id' => $data->id];
    $update = false;
    $templates = ['singletemplate', 'addtemplate', 'rsstemplate'];
    foreach ($templates as $templatename) {
        if (empty($data->$templatename)
            || strpos($data->$templatename, "[[$newfieldname]]") !== false
            || strpos($data->$templatename, "##otherfields##") !== false
        ) {
            continue;
        }
        $newdata->$templatename = $data->$templatename;
        $fields = [[
            'fieldname' => '[[' . $newfieldname . '#name]]',
            'fieldcontent' => '[[' . $newfieldname . ']]',
        ]];
        $newdata->$templatename .= $OUTPUT->render_from_template(
            'mod_datafos/fields_otherfields',
            ['fields' => $fields, 'classes' => 'added_field']
        );
        $update = true;
    }
    if (!$update) {
        return false;
    }
    return $DB->update_record('datafos', $newdata);
}


/**
 * given a field name
 * this function creates an instance of the particular subfield class
 *
 * @global object
 * @param string $name
 * @param object $data
 * @return object|bool
 */
function datafos_get_field_from_name($name, $data){
    global $DB;

    $field = $DB->get_record('data_fields_fos', array('name'=>$name, 'dataid'=>$data->id));

    if ($field) {
        return datafos_get_field($field, $data);
    } else {
        return false;
    }
}

/**
 * given a field id
 * this function creates an instance of the particular subfield class
 *
 * @global object
 * @param int $fieldid
 * @param object $data
 * @return bool|object
 */
function datafos_get_field_from_id($fieldid, $data){
    global $DB;

    $field = $DB->get_record('data_fields_fos', array('id'=>$fieldid, 'dataid'=>$data->id));

    if ($field) {
        return datafos_get_field($field, $data);
    } else {
        return false;
    }
}

/**
 * given a field id
 * this function creates an instance of the particular subfield class
 *
 * @global object
 * @param string $type
 * @param object $data
 * @return object
 */
function datafos_get_field_new($type, $data) {
    global $CFG;

    // Construye la ruta al archivo de la clase del campo
    $filepath = $CFG->dirroot.'/mod/datafos/field/'.$type.'/field.class.php';

    // Verifica si el archivo de la clase del campo existe
    if (!file_exists($filepath)) {
        throw new \moodle_exception('invalidfieldtype', 'datafos'); // Podrías considerar personalizar este mensaje de error
    }

    // Incluye el archivo de la clase del campo
    require_once($filepath);

    // Construye el nombre de la clase del campo basado en $type
    $newfieldclass = 'datafos_field_'.$type;

    // Crea una nueva instancia del campo y la devuelve
    $newfield = new $newfieldclass(0, $data);
    return $newfield;
}


/**
 * returns a subclass field object given a record of the field, used to
 * invoke plugin methods
 * input: $param $field - record from db
 *
 * @param stdClass $field the field record
 * @param stdClass $data the datafos instance
 * @param stdClass|null $cm optional course module datafos
 * @return datafos_field_base the field object instance or data_field_base if unkown type
 *@global object
 */
function datafos_get_field(stdClass $field, stdClass $data, ?stdClass $cm=null): datafos_field_base {
    global $CFG;
    if (!isset($field->type)) {
        return new datafos_field_base($field);
    }
    $filepath = $CFG->dirroot.'/mod/datafos/field/'.$field->type.'/field.class.php';
    if (!file_exists($filepath)) {
        return new datafos_field_base($field);
    }
    require_once($filepath);
    $newfield = 'datafos_field_'.$field->type;
    $newfield = new $newfield($field, $data, $cm);
    return $newfield;
}



/**
 * Given record object (or id), returns true if the record belongs to the current user
 *
 * @global object
 * @global object
 * @param mixed $record record object or id
 * @return bool
 */
function datafos_isowner($record) {
    global $USER, $DB;

    if (!isloggedin()) { // perf shortcut
        return false;
    }

    if (!is_object($record)) {
        if (!$record = $DB->get_record('data_records_fos', array('id'=>$record))) {
            return false;
        }
    }

    return ($record->userid == $USER->id);
}

/**
 * has a user reached the max number of entries?
 *
 * @param object $data
 * @return bool
 */
function datafos_atmaxentries($data){
    if (!$data->maxentries){
        return false;

    } else {
        return (datafos_numentries($data) >= $data->maxentries);
    }
}

/**
 * returns the number of entries already made by this user
 *
 * @global object
 * @global object
 * @param object $data
 * @return int
 */
function datafos_numentries($data, $userid=null) {
    global $USER, $DB;
    if ($userid === null) {
        $userid = $USER->id;
    }
    $sql = 'SELECT COUNT(*) FROM {data_records_fos} WHERE dataid=? AND userid=?';
    return $DB->count_records_sql($sql, array($data->id, $userid));
}

/**
 * function that takes in a dataid and adds a record
 * this is used everytime an add template is submitted
 *
 * @global object
 * @global object
 * @param object $data
 * @param int $groupid
 * @param int $userid
 * @return bool
 */
function datafos_add_record($data, $groupid = 0, $userid = null) {
    global $USER, $DB;

    $cm = get_coursemodule_from_instance('datafos', $data->id);
    $context = context_module::instance($cm->id);

    $record = new stdClass();
    $record->userid = $userid ?? $USER->id;
    $record->dataid = $data->id;
    $record->groupid = $groupid;
    $record->timecreated = $record->timemodified = time();
    if (has_capability('mod/datafos:approve', $context)) {
        $record->approved = 1;
    } else {
        $record->approved = 0;
    }
    $record->id = $DB->insert_record('data_records_fos', $record);

    // Trigger an event for creating this record.
    $event = \mod_datafos\event\record_created::create(array(
        'objectid' => $record->id,
        'context' => $context,
        'other' => array(
            'dataid' => $data->id
        )
    ));
    $event->trigger();

    $course = get_course($cm->course);
    datafos_update_completion_state($data, $course, $cm);

    return $record->id;
}

/**
 * check the multple existence any tag in a template
 *
 * check to see if there are 2 or more of the same tag being used.
 *
 * @global object
 * @param int $dataid,
 * @param string $template
 * @return bool
 */
function datafos_tags_check($dataid, $template) {
    global $DB, $OUTPUT;

    // first get all the possible tags
    $fields = $DB->get_records('data_fields_fos', array('dataid'=>$dataid));
    // then we generate strings to replace
    $tagsok = true; // let's be optimistic
    foreach ($fields as $field){
        $pattern="/\[\[" . preg_quote($field->name, '/') . "\]\]/i";
        if (preg_match_all($pattern, $template, $dummy)>1){
            $tagsok = false;
            echo $OUTPUT->notification('[['.$field->name.']] - '.get_string('multipletags', 'data'));
        }
    }
    // else return true
    return $tagsok;
}

/**
 * Adds an instance of a datafos
 *
 * @param stdClass $data
 * @param mod_datafos_mod_form $mform
 * @return int intance id
 */
function datafos_add_instance($data, $mform = null) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/mod/datafos/locallib.php');

    // Asegurarse de que $data->assessed tenga un valor predeterminado si está vacío
    if (empty($data->assessed)) {
        $data->assessed = 0;
    }

    // Asegurarse de que $data->assesstimestart y $data->assesstimefinish tengan valores predeterminados si $data->ratingtime o $data->assessed están vacíos
    if (empty($data->ratingtime) || empty($data->assessed)) {
        $data->assesstimestart = 0;
        $data->assesstimefinish = 0;
    }

    // Actualizar la marca de tiempo de modificación
    $data->timemodified = time();

    // Insertar el registro en la tabla 'datafos'
    try {
        $data->id = $DB->insert_record('datafos', $data);
    } catch (Exception $e) {
        // Capturar cualquier excepción y manejarla según sea necesario
        // Por ejemplo, puedes registrar el error o lanzar una excepción personalizada
        error_log('Error al insertar registro en datafos: ' . $e->getMessage());
        throw new moodle_exception('Error al insertar registro en datafos', 'datafos_insert_error', '', $e->getMessage());
    }

    // Agregar eventos de calendario si es necesario.
    datafos_set_events($data);

    // Actualizar la fecha de completitud esperada si está configurada
    if (!empty($data->completionexpected)) {
        \core_completion\api::update_completion_date_event($data->coursemodule, 'datafos', $data->id, $data->completionexpected);
    }

    // Actualizar el ítem de calificación de datafos
    datafos_grade_item_update($data);

    return $data->id;
}


/**
 * updates an instance of a datafos
 *
 * @global object
 * @param object $data
 * @return bool
 */
function datafos_update_instance($data) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/datafos/locallib.php');

    $data->timemodified = time();
    if (!empty($data->instance)) {
        $data->id = $data->instance;
    }

    if (empty($data->assessed)) {
        $data->assessed = 0;
    }

    if (empty($data->ratingtime) or empty($data->assessed)) {
        $data->assesstimestart  = 0;
        $data->assesstimefinish = 0;
    }

    if (empty($data->notification)) {
        $data->notification = 0;
    }

    $DB->update_record('datafos', $data);

    // Add calendar events if necessary.
    datafos_set_events($data);
    $completionexpected = (!empty($data->completionexpected)) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'datafos', $data->id, $completionexpected);

    datafos_grade_item_update($data);

    return true;

}

/**
 * deletes an instance of a datafos
 *
 * @global object
 * @param int $id
 * @return bool
 */
function datafos_delete_instance($id) {    // takes the dataid
    global $DB, $CFG;

    if (!$data = $DB->get_record('datafos', array('id'=>$id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('datafos', $data->id);
    $context = context_module::instance($cm->id);

    // Delete all information related to fields.
    $fields = $DB->get_records('data_fields_fos', ['dataid' => $id]);
    foreach ($fields as $field) {
        $todelete = datafos_get_field($field, $data, $cm);
        $todelete->delete_field();
    }

    // Remove old calendar events.
    $events = $DB->get_records('event', array('modulename' => 'datafos', 'instance' => $id));
    foreach ($events as $event) {
        $event = calendar_event::load($event);
        $event->delete();
    }

    // cleanup gradebook
    datafos_grade_item_delete($data);

    // Delete the instance itself
    // We must delete the module record after we delete the grade item.
    $result = $DB->delete_records('datafos', array('id'=>$id));

    return $result;
}

/**
 * returns a summary of datafos activity of this user
 *
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $data
 * @return object|null
 */
function datafos_user_outline($course, $user, $mod, $data) {
    global $DB, $CFG;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'datafos', $data->id, $user->id);
    if (empty($grades->items[0]->grades)) {
        $grade = false;
    } else {
        $grade = reset($grades->items[0]->grades);
    }


    if ($countrecords = $DB->count_records('data_records_fos', array('dataid'=>$data->id, 'userid'=>$user->id))) {
        $result = new stdClass();
        $result->info = get_string('numrecords', 'datafos', $countrecords);
        $lastrecord   = $DB->get_record_sql('SELECT id,timemodified FROM {data_records_fos}
                                              WHERE dataid = ? AND userid = ?
                                           ORDER BY timemodified DESC', array($data->id, $user->id), true);
        $result->time = $lastrecord->timemodified;
        if ($grade) {
            if (!$grade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
                $result->info .= ', ' . get_string('gradenoun') . ': ' . $grade->str_long_grade;
            } else {
                $result->info = get_string('gradenoun') . ': ' . get_string('hidden', 'grades');
            }
        }
        return $result;
    } else if ($grade) {
        $result = (object) [
            'time' => grade_get_date_for_user_grade($grade, $user),
        ];
        if (!$grade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
            $result->info = get_string('gradenoun') . ': ' . $grade->str_long_grade;
        } else {
            $result->info = get_string('gradenoun') . ': ' . get_string('hidden', 'grades');
        }

        return $result;
    }
    return NULL;
}

/**
 * Prints all the records uploaded by this user
 *
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $data
 */
function datafos_user_complete($course, $user, $mod, $data) {
    global $DB, $CFG, $OUTPUT;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'datafos', $data->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        if (!$grade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
            echo $OUTPUT->container(get_string('gradenoun') . ': ' . $grade->str_long_grade);
            if ($grade->str_feedback) {
                echo $OUTPUT->container(get_string('feedback').': '.$grade->str_feedback);
            }
        } else {
            echo $OUTPUT->container(get_string('gradenoun') . ': ' . get_string('hidden', 'grades'));
        }
    }
    $records = $DB->get_records(
        'data_records_fos',
        ['dataid' => $data->id, 'userid' => $user->id],
        'timemodified DESC'
    );
    if ($records) {
        $manager = manager::create_from_instance($data);
        $parser = $manager->get_template('singletemplate');
        echo $parser->parse_entries($records);
    }
}

/**
 * Return grade for given user or all users.
 *
 * @global object
 * @param object $data
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function datafos_get_user_grades($data, $userid=0) {
    global $CFG;

    require_once($CFG->dirroot.'/rating/lib.php');

    $ratingoptions = new stdClass;
    $ratingoptions->component = 'mod_datafos';
    $ratingoptions->ratingarea = 'entry';
    $ratingoptions->modulename = 'datafos';
    $ratingoptions->moduleid   = $data->id;

    $ratingoptions->userid = $userid;
    $ratingoptions->aggregationmethod = $data->assessed;
    $ratingoptions->scaleid = $data->scale;
    $ratingoptions->itemtable = 'data_records_fos';
    $ratingoptions->itemtableusercolumn = 'userid';

    $rm = new rating_manager();
    return $rm->get_user_grades($ratingoptions);
}

/**
 * Update activity grades
 *
 * @category grade
 * @param object $data
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone
 */
function datafos_update_grades($data, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if (!$data->assessed) {
        datafos_grade_item_update($data);

    } else if ($grades = datafos_get_user_grades($data, $userid)) {
        datafos_grade_item_update($data, $grades);

    } else if ($userid and $nullifnone) {
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = NULL;
        datafos_grade_item_update($data, $grade);

    } else {
        datafos_grade_item_update($data);
    }
}

/**
 * Update/create grade item for given datafos
 *
 * @category grade
 * @param stdClass $data A database instance with extra cmidnumber property
 * @param mixed $grades Optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return object grade_item
 */
function datafos_grade_item_update($data, $grades=NULL) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $params = array('itemname'=>$data->name, 'idnumber'=>$data->cmidnumber);

    if (!$data->assessed or $data->scale == 0) {
        $params['gradetype'] = GRADE_TYPE_NONE;

    } else if ($data->scale > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $data->scale;
        $params['grademin']  = 0;

    } else if ($data->scale < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$data->scale;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }

    return grade_update('mod/datafos', $data->course, 'mod', 'datafos', $data->id, 0, $grades, $params);
}

/**
 * Delete grade item for given datafos
 *
 * @category grade
 * @param object $data object
 * @return object grade_item
 */
function datafos_grade_item_delete($data) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/datafos', $data->course, 'mod', 'datafos', $data->id, 0, NULL, array('deleted'=>1));
}

// junk functions
/**
 * takes a list of records, the current datafos, a search string,
 * and mode to display prints the translated template
 *
 * @deprecated since Moodle 4.1 MDL-75146 - please do not use this function any more.
 * @todo MDL-75189 Final deprecation in Moodle 4.5.
 * @param string $templatename the template name
 * @param array $records the entries records
 * @param stdClass $data the database instance object
 * @param string $search the current search term
 * @param int $page page number for pagination
 * @param bool $return if the result should be returned (true) or printed (false)
 * @param moodle_url|null $jumpurl a moodle_url by which to jump back to the record list (can be null)
 * @return mixed string with all parsed entries or nothing if $return is false
 */
function datafos_print_template($templatename, $records, $data, $search='', $page=0, $return=false, moodle_url $jumpurl=null) {
    debugging(
        'data_print_template is deprecated. Use mod_datafos\\manager::get_template and mod_datafos\\template::parse_entries instead',
        DEBUG_DEVELOPER
    );

    $options = [
        'search' => $search,
        'page' => $page,
    ];
    if ($jumpurl) {
        $options['baseurl'] = $jumpurl;
    }
    $manager = manager::create_from_instance($data);
    $parser = $manager->get_template($templatename, $options);
    $content = $parser->parse_entries($records);
    if ($return) {
        return $content;
    }
    echo $content;
}

/**
 * Return rating related permissions
 *
 * @param string $contextid the context id
 * @param string $component the component to get rating permissions for
 * @param string $ratingarea the rating area to get permissions for
 * @return array an associative array of the user's rating permissions
 */
function datafos_rating_permissions($contextid, $component, $ratingarea) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
    if ($component != 'mod_datafos' || $ratingarea != 'entry') {
        return null;
    }
    return array(
        'view'    => has_capability('mod/datafos:viewrating',$context),
        'viewany' => has_capability('mod/datafos:viewanyrating',$context),
        'viewall' => has_capability('mod/datafos:viewallratings',$context),
        'rate'    => has_capability('mod/datafos:rate',$context)
    );
}

/**
 * Validates a submitted rating
 * @param array $params submitted datafos
 *            context => object the context in which the rated items exists [required]
 *            itemid => int the ID of the object being rated
 *            scaleid => int the scale from which the user can select a rating. Used for bounds checking. [required]
 *            rating => int the submitted rating
 *            rateduserid => int the id of the user whose items have been rated. NOT the user who submitted the ratings. 0 to update all. [required]
 *            aggregation => int the aggregation method to apply when calculating grades ie RATING_AGGREGATE_AVERAGE [required]
 * @return boolean true if the rating is valid. Will throw rating_exception if not
 */
function datafos_rating_validate($params) {
    global $DB, $USER;

    // Check the component is mod_datafos
    if ($params['component'] != 'mod_datafos') {
        throw new rating_exception('invalidcomponent');
    }

    // Check the ratingarea is entry (the only rating area in datafos module)
    if ($params['ratingarea'] != 'entry') {
        throw new rating_exception('invalidratingarea');
    }

    // Check the rateduserid is not the current user .. you can't rate your own entries
    if ($params['rateduserid'] == $USER->id) {
        throw new rating_exception('nopermissiontorate');
    }

    $datasql = "SELECT d.id as dataid, d.scale, d.course, r.userid as userid, d.approval, r.approved, r.timecreated, d.assesstimestart, d.assesstimefinish, r.groupid
                  FROM {data_records_fos} r
                  JOIN {datafos} d ON r.dataid = d.id
                 WHERE r.id = :itemid";
    $dataparams = array('itemid'=>$params['itemid']);
    if (!$info = $DB->get_record_sql($datasql, $dataparams)) {
        //item doesn't exist
        throw new rating_exception('invaliditemid');
    }

    if ($info->scale != $params['scaleid']) {
        //the scale being submitted doesnt match the one in the database
        throw new rating_exception('invalidscaleid');
    }

    //check that the submitted rating is valid for the scale

    // lower limit
    if ($params['rating'] < 0  && $params['rating'] != RATING_UNSET_RATING) {
        throw new rating_exception('invalidnum');
    }

    // upper limit
    if ($info->scale < 0) {
        //its a custom scale
        $scalerecord = $DB->get_record('scale', array('id' => -$info->scale));
        if ($scalerecord) {
            $scalearray = explode(',', $scalerecord->scale);
            if ($params['rating'] > count($scalearray)) {
                throw new rating_exception('invalidnum');
            }
        } else {
            throw new rating_exception('invalidscaleid');
        }
    } else if ($params['rating'] > $info->scale) {
        //if its numeric and submitted rating is above maximum
        throw new rating_exception('invalidnum');
    }

    if ($info->approval && !$info->approved) {
        //database requires approval but this item isnt approved
        throw new rating_exception('nopermissiontorate');
    }

    // check the item we're rating was created in the assessable time window
    if (!empty($info->assesstimestart) && !empty($info->assesstimefinish)) {
        if ($info->timecreated < $info->assesstimestart || $info->timecreated > $info->assesstimefinish) {
            throw new rating_exception('notavailable');
        }
    }

    $course = $DB->get_record('course', array('id'=>$info->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('datafos', $info->dataid, $course->id, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    // if the supplied context doesnt match the item's context
    if ($context->id != $params['context']->id) {
        throw new rating_exception('invalidcontext');
    }

    // Make sure groups allow this user to see the item they're rating
    $groupid = $info->groupid;
    if ($groupid > 0 and $groupmode = groups_get_activity_groupmode($cm, $course)) {   // Groups are being used
        if (!groups_group_exists($groupid)) { // Can't find group
            throw new rating_exception('cannotfindgroup');//something is wrong
        }

        if (!groups_is_member($groupid) and !has_capability('moodle/site:accessallgroups', $context)) {
            // do not allow rating of posts from other groups when in SEPARATEGROUPS or VISIBLEGROUPS
            throw new rating_exception('notmemberofgroup');
        }
    }

    return true;
}

/**
 * Can the current user see ratings for a given itemid?
 *
 * @param array $params submitted datafos
 *            contextid => int contextid [required]
 *            component => The component for this module - should always be mod_datafos [required]
 *            ratingarea => object the context in which the rated items exists [required]
 *            itemid => int the ID of the object being rated [required]
 *            scaleid => int scale id [optional]
 * @return bool
 * @throws coding_exception
 * @throws rating_exception
 */
function mod_datafos_rating_can_see_item_ratings($params) {
    global $DB;

    // Check the component is mod_datafos.
    if (!isset($params['component']) || $params['component'] != 'mod_datafos') {
        throw new rating_exception('invalidcomponent');
    }

    // Check the ratingarea is entry (the only rating area in datafos).
    if (!isset($params['ratingarea']) || $params['ratingarea'] != 'entry') {
        throw new rating_exception('invalidratingarea');
    }

    if (!isset($params['itemid'])) {
        throw new rating_exception('invaliditemid');
    }

    $datasql = "SELECT d.id as dataid, d.course, r.groupid
                  FROM {data_records_fos} r
                  JOIN {datafos} d ON r.dataid = d.id
                 WHERE r.id = :itemid";
    $dataparams = array('itemid' => $params['itemid']);
    if (!$info = $DB->get_record_sql($datasql, $dataparams)) {
        // Item doesn't exist.
        throw new rating_exception('invaliditemid');
    }

    // User can see ratings of all participants.
    if ($info->groupid == 0) {
        return true;
    }

    $course = $DB->get_record('course', array('id' => $info->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('datafos', $info->dataid, $course->id, false, MUST_EXIST);

    // Make sure groups allow this user to see the item they're rating.
    return groups_group_visible($info->groupid, $course, $cm);
}


/**
 * function that takes in the current datafos, number of items per page,
 * a search string and prints a preference box in view.php
 *
 * This preference box prints a searchable advanced search template if
 *     a) A template is defined
 *  b) The advanced search checkbox is checked.
 *
 * @global object
 * @global object
 * @param object $data
 * @param int $perpage
 * @param string $search
 * @param string $sort
 * @param string $order
 * @param array $search_array
 * @param int $advanced
 * @param string $mode
 * @return void
 */
function datafos_print_preference_form($data, $perpage, $search, $sort='', $order='ASC', $search_array = '', $advanced = 0, $mode= ''){
    global $DB, $PAGE, $OUTPUT, $SESSION;

    $cm = get_coursemodule_from_instance('datafos', $data->id);
    $context = context_module::instance($cm->id);
    echo '<div class="datapreferences my-5">';
    echo '<form id="options" action="view.php" method="get">';
    echo '<div class="d-flex">';
    echo '<div>';
    echo '<input type="hidden" name="d" value="'.$data->id.'" />';
    if ($mode =='asearch') {
        $advanced = 1;
        echo '<input type="hidden" name="mode" value="list" />';
    }
    echo '<label for="pref_perpage">'.get_string('pagesize', 'data').'</label> ';
    $pagesizes = array(10=>10,20=>20,30=>30,40=>40,50=>50,100=>100,200=>200,300=>300,400=>400,500=>500,1000=>1000);
    echo html_writer::select($pagesizes, 'perpage', $perpage, false, array('id' => 'pref_perpage', 'class' => 'custom-select'));

    if ($advanced) {
        $regsearchclass = 'search_none';
        $advancedsearchclass = 'search_inline';
    } else {
        $regsearchclass = 'search_inline';
        $advancedsearchclass = 'search_none';
    }
    echo '<div id="reg_search" class="' . $regsearchclass . ' form-inline" >&nbsp;&nbsp;&nbsp;';
    echo '<label for="pref_search">' . get_string('search') . '</label> <input type="text" ' .
         'class="form-control" size="16" name="search" id= "pref_search" value="' . s($search) . '" /></div>';
    echo '&nbsp;&nbsp;&nbsp;<label for="pref_sortby">'.get_string('sortby').'</label> ';
    // foreach field, print the option
    echo '<select name="sort" id="pref_sortby" class="custom-select mr-1">';
    if ($fields = $DB->get_records('data_fields_fos', array('dataid'=>$data->id), 'name')) {
        echo '<optgroup label="'.get_string('fields', 'data').'">';
        foreach ($fields as $field) {
            if ($field->id == $sort) {

                if ($field->name === "Category"){
                    echo '<option value="'.$field->id.'" selected="selected">'.get_string('categoryfield', 'datafos').'</option>';
                }else{
                    echo '<option value="'.$field->id.'" selected="selected">'.$field->name.'</option>';
                }

            } else {
                switch ($field->name) {
                    case "Author":
                        echo '<option value="' . $field->id . '">' . get_string('authorfield', 'datafos'). '</option>';
                        break;
                    case "Category":
                        echo '<option value="' . $field->id . '">' . get_string('categoryfield', 'datafos'). '</option>';
                        break;
                    case "Needs":
                        echo '<option value="' . $field->id . '">' . get_string('needsfield', 'datafos'). '</option>';
                        break;
                    case "Organization":
                        echo '<option value="' . $field->id . '">' . get_string('organizationfield', 'datafos'). '</option>';
                        break;
                    case "Upload Date":
                        echo '<option value="' . $field->id . '">' . get_string('uploaddatefield', 'datafos'). '</option>';
                        break;
                    case "Year of Completion":
                        echo '<option value="' . $field->id . '">' . get_string('yearofcompletionfield', 'datafos'). '</option>';
                        break;
                    default:
                        //echo '<option value="' . $field->id . '">' . $field->name . '</option>';
                        break;
                }
                // echo '<option value="'.$field->id.'">'.$field->name.'</option>';
            }
        }
        echo '</optgroup>';
    }
    $options = array();
    $options[DATAFOS_TIMEADDED]    = get_string('timeadded', 'data');
    $options[DATAFOS_TIMEMODIFIED] = get_string('timemodified', 'data');
    //$options[DATAFOS_FIRSTNAME]    = get_string('authorfirstname', 'datafos');
    //$options[DATAFOS_LASTNAME]     = get_string('authorlastname', 'datafos');
    if ($data->approval and has_capability('mod/datafos:approve', $context)) {
        $options[DATAFOS_APPROVED] = get_string('approved', 'datafos');
    }
    echo '<optgroup label="'.get_string('other', 'data').'">';
    foreach ($options as $key => $name) {
        if ($key == $sort) {
            echo '<option value="'.$key.'" selected="selected">'.$name.'</option>';
        } else {
            echo '<option value="'.$key.'">'.$name.'</option>';
        }
    }
    echo '</optgroup>';
    echo '</select>';
    echo '<label for="pref_order" class="accesshide">'.get_string('order').'</label>';
    echo '<select id="pref_order" name="order" class="custom-select mr-1">';
    if ($order == 'ASC') {
        echo '<option value="ASC" selected="selected">'.get_string('ascending', 'data').'</option>';
    } else {
        echo '<option value="ASC">'.get_string('ascending', 'data').'</option>';
    }
    if ($order == 'DESC') {
        echo '<option value="DESC" selected="selected">'.get_string('descending', 'data').'</option>';
    } else {
        echo '<option value="DESC">'.get_string('descending', 'data').'</option>';
    }
    echo '</select>';

    if ($advanced) {
        $checked = ' checked="checked" ';
    }
    else {
        $checked = '';
    }
    $PAGE->requires->js('/mod/datafos/datafos.js');
    echo '&nbsp;<input type="hidden" name="advanced" value="0" />';
    echo '&nbsp;<input type="hidden" name="filter" value="1" />';
    echo '&nbsp;<input type="checkbox" id="advancedcheckbox" name="advanced" value="1" ' . $checked . ' ' .
         'onchange="showHideAdvSearch(this.checked);" class="mx-1" />' .
         '<label for="advancedcheckbox">' . get_string('advancedsearch', 'data') . '</label>';
    echo '</div>';
    echo '<div id="advsearch-save-sec" class="ml-auto '. $regsearchclass . '">';
    echo '&nbsp;<input type="submit" class="btn btn-secondary" value="' . get_string('savesettings', 'data') . '" />';
    echo '</div>';
    echo '</div>';
    echo '<div>';

    echo '<br />';
    echo '<div class="' . $advancedsearchclass . '" id="data_adv_form">';
    echo '<table class="boxaligncenter">';

    // print ASC or DESC
    echo '<tr><td colspan="2">&nbsp;</td></tr>';
    $i = 0;

    // Determine if we are printing all fields for advanced search, or the template for advanced search
    // If a template is not defined, use the deafault template and display all fields.
    $asearchtemplate = $data->asearchtemplate;
    if (empty($asearchtemplate)) {
        $asearchtemplate = datafos_generate_default_template($data, 'asearchtemplate', 0, false, false);
    }

    static $fields = array();
    static $dataid = null;

    if (empty($dataid)) {
        $dataid = $data->id;
    } else if ($dataid != $data->id) {
        $fields = array();
    }

    if (empty($fields)) {
        $fieldrecords = $DB->get_records('data_fields_fos', array('dataid'=>$data->id));
        foreach ($fieldrecords as $fieldrecord) {
            $fields[]= datafos_get_field($fieldrecord, $data);
        }
    }

    // Replacing tags
    $patterns = array();
    $replacement = array();

    // Then we generate strings to replace for normal tags
    $otherfields = [];
    foreach ($fields as $field) {
        $fieldname = $field->field->name;
        $fieldname = preg_quote($fieldname, '/');
        $searchfield = datafos_get_field_from_id($field->field->id, $data);

        if ($searchfield->type === 'unknown') {
            continue;
        }
        if (!empty($search_array[$field->field->id]->datafos)) {
            $searchinput = $searchfield->display_search_field($search_array[$field->field->id]->datafos);
        } else {
            $searchinput = $searchfield->display_search_field();
        }
        $patterns[] = "/\[\[$fieldname\]\]/i";
        $replacement[] = $searchinput;
        // Extra field information.
        $patterns[] = "/\[\[$fieldname#name\]\]/i";
        $replacement[] = $field->field->name;
        $patterns[] = "/\[\[$fieldname#description\]\]/i";
        $replacement[] = $field->field->description;
        // Other fields.
        if (strpos($asearchtemplate, "[[" . $field->field->name . "]]") === false) {
            $otherfields[] = [
                'fieldname' => $searchfield->field->name,
                'fieldcontent' => $searchinput,
            ];
        }
    }
    $patterns[] = "/##otherfields##/";
    if (!empty($otherfields)) {
        $replacement[] = $OUTPUT->render_from_template(
            'mod_datafos/fields_otherfields',
            ['fields' => $otherfields]
        );
    } else {
        $replacement[] = '';
    }

    $fn = !empty($search_array[DATAFOS_FIRSTNAME]->datafos) ? $search_array[DATAFOS_FIRSTNAME]->datafos : '';
    $ln = !empty($search_array[DATAFOS_LASTNAME]->datafos) ? $search_array[DATAFOS_LASTNAME]->datafos : '';
    $patterns[]    = '/##firstname##/';
    $replacement[] = '<label class="accesshide" for="u_fn">' . get_string('authorfirstname', 'datafos') . '</label>' .
                     '<input type="text" class="form-control" size="16" id="u_fn" name="u_fn" value="' . s($fn) . '" />';
    $patterns[]    = '/##lastname##/';
    $replacement[] = '<label class="accesshide" for="u_ln">' . get_string('authorlastname', 'datafos') . '</label>' .
                     '<input type="text" class="form-control" size="16" id="u_ln" name="u_ln" value="' . s($ln) . '" />';

    if (core_tag_tag::is_enabled('mod_datafos', 'data_records_fos')) {
        $patterns[] = "/##tags##/";
        $selectedtags = isset($search_array[DATAFOS_TAGS]->rawtagnames) ? $search_array[DATAFOS_TAGS]->rawtagnames : [];
        $replacement[] = datafos_generate_tag_form(false, $selectedtags);
    }

    // actual replacement of the tags

    $options = new stdClass();
    $options->para=false;
    $options->noclean=true;
    echo '<tr><td>';
    echo preg_replace($patterns, $replacement, format_text($asearchtemplate, FORMAT_HTML, $options));
    echo '</td></tr>';

    echo '<tr><td colspan="4"><br/>' .
         '<input type="submit" class="btn btn-primary mr-1" value="' . get_string('savesettings', 'data') . '" />' .
         '<input type="submit" class="btn btn-secondary" name="resetadv" value="' . get_string('resetsettings', 'data') . '" />' .
         '</td></tr>';
    echo '</table>';
    echo '</div>';
    echo '</form>';
    echo '</div>';
    echo '<hr/>';
}

/**
 * @global object
 * @global object
 * @param object $data
 * @param object $record
 * @param bool $print if the result must be printed or returner.
 * @return void Output echo'd
 */
function datafos_print_ratings($data, $record, bool $print = true) {
    global $OUTPUT;
    $result = '';
    if (!empty($record->rating)){
        $result = $OUTPUT->render($record->rating);
    }
    if (!$print) {
        return $result;
    }
    echo $result;
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function datafos_get_view_actions() {
    return array('view');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function datafos_get_post_actions() {
    return array('add','update','record delete');
}

/**
 * @param string $name
 * @param int $dataid
 * @param int $fieldid
 * @return bool
 */
function datafos_fieldname_exists($name, $dataid, $fieldid = 0) {
    global $DB;

    if (!is_numeric($name)) {
        $like = $DB->sql_like('df.name', ':name', false);
    } else {
        $like = "df.name = :name";
    }
    $params = array('name'=>$name);
    if ($fieldid) {
        $params['dataid']   = $dataid;
        $params['fieldid1'] = $fieldid;
        $params['fieldid2'] = $fieldid;
        return $DB->record_exists_sql("SELECT * FROM {data_fields_fos} df
                                        WHERE $like AND df.dataid = :dataid
                                              AND ((df.id < :fieldid1) OR (df.id > :fieldid2))", $params);
    } else {
        $params['dataid']   = $dataid;
        return $DB->record_exists_sql("SELECT * FROM {data_fields_fos} df
                                        WHERE $like AND df.dataid = :dataid", $params);
    }
}

/**
 * @param array $fieldinput
 */
function datafos_convert_arrays_to_strings(&$fieldinput) {
    foreach ($fieldinput as $key => $val) {
        if (is_array($val)) {
            $str = '';
            foreach ($val as $inner) {
                $str .= $inner . ',';
            }
            $str = substr($str, 0, -1);

            $fieldinput->$key = $str;
        }
    }
}


/**
 * Converts a database (module instance) to use the Roles System
 *
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @uses CAP_PREVENT
 * @uses CAP_ALLOW
 * @param object $data a datafos object with the same attributes as a record
 *                     from the datafos database table
 * @param int $datamodid the id of the datafos module, from the modules table
 * @param array $teacherroles array of roles that have archetype teacher
 * @param array $studentroles array of roles that have archetype student
 * @param array $guestroles array of roles that have archetype guest
 * @param int $cmid the course_module id for this datafos instance
 * @return boolean datafos module was converted or not
 */
function datafos_convert_to_roles($data, $teacherroles=array(), $studentroles=array(), $cmid=NULL) {
    global $CFG, $DB, $OUTPUT;

    if (!isset($data->participants) && !isset($data->assesspublic)
            && !isset($data->groupmode)) {
        // We assume that this database has already been converted to use the
        // Roles System. above fields get dropped the datafos module has been
        // upgraded to use Roles.
        return false;
    }

    if (empty($cmid)) {
        // We were not given the course_module id. Try to find it.
        if (!$cm = get_coursemodule_from_instance('datafos', $data->id)) {
            echo $OUTPUT->notification('Could not get the course module for the datafos');
            return false;
        } else {
            $cmid = $cm->id;
        }
    }
    $context = context_module::instance($cmid);


    // $data->participants:
    // 1 - Only teachers can add entries
    // 3 - Teachers and students can add entries
    switch ($data->participants) {
        case 1:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/datafos:writeentry', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/datafos:writeentry', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
        case 3:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/datafos:writeentry', CAP_ALLOW, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/datafos:writeentry', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
    }

    // $data->assessed:
    // 2 - Only teachers can rate posts
    // 1 - Everyone can rate posts
    // 0 - No one can rate posts
    switch ($data->assessed) {
        case 0:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/datafos:rate', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/datafos:rate', CAP_PREVENT, $teacherrole->id, $context->id);
            }
            break;
        case 1:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/datafos:rate', CAP_ALLOW, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/datafos:rate', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
        case 2:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/datafos:rate', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/datafos:rate', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
    }

    // $data->assesspublic:
    // 0 - Students can only see their own ratings
    // 1 - Students can see everyone's ratings
    switch ($data->assesspublic) {
        case 0:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/datafos:viewrating', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/datafos:viewrating', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
        case 1:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/datafos:viewrating', CAP_ALLOW, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/datafos:viewrating', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
    }

    if (empty($cm)) {
        $cm = $DB->get_record('course_modules', array('id'=>$cmid));
    }

    switch ($cm->groupmode) {
        case NOGROUPS:
            break;
        case SEPARATEGROUPS:
            foreach ($studentroles as $studentrole) {
                assign_capability('moodle/site:accessallgroups', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
        case VISIBLEGROUPS:
            foreach ($studentroles as $studentrole) {
                assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
    }
    return true;
}

/**
 * Returns the best name to show for a preset
 *
 * @param string $shortname
 * @param  string $path
 * @return string
 * @deprecated since Moodle 4.1 MDL-75148 - please, use the preset::get_name_from_plugin() function instead.
 * @todo MDL-75189 This will be deleted in Moodle 4.5.
 * @see preset::get_name_from_plugin()
 */
function datafos_preset_name($shortname, $path) {
    debugging('data_preset_name() is deprecated. Please use preset::get_name_from_plugin() instead.', DEBUG_DEVELOPER);

    return preset::get_name_from_plugin($shortname);
}

/**
 * Returns an array of all the available presets.
 *
 * @return array
 * @deprecated since Moodle 4.1 MDL-75148 - please, use the manager::get_available_presets() function instead.
 * @todo MDL-75189 This will be deleted in Moodle 4.5.
 * @see manager::get_available_presets()
 */
function datafos_get_available_presets($context) {
    debugging('datafos_get_available_presets() is deprecated. Please use manager::get_available_presets() instead.', DEBUG_DEVELOPER);

    $cm = get_coursemodule_from_id('', $context->instanceid, 0, false, MUST_EXIST);
    $manager = manager::create_from_coursemodule($cm);
    return $manager->get_available_presets();
}

/**
 * Gets an array of all of the presets that users have saved to the site.
 *
 * @param stdClass $context The context that we are looking from.
 * @param array $presets
 * @return array An array of presets
 * @deprecated since Moodle 4.1 MDL-75148 - please, use the manager::get_available_saved_presets() function instead.
 * @todo MDL-75189 This will be deleted in Moodle 4.5.
 * @see manager::get_available_saved_presets()
 */
function datafos_get_available_site_presets($context, array $presets=array()) {
    debugging(
        'datafos_get_available_site_presets() is deprecated. Please use manager::get_available_saved_presets() instead.',
        DEBUG_DEVELOPER
    );

    $cm = get_coursemodule_from_id('', $context->instanceid, 0, false, MUST_EXIST);
    $manager = manager::create_from_coursemodule($cm);
    $savedpresets = $manager->get_available_saved_presets();
    return array_merge($presets, $savedpresets);
}

/**
 * Deletes a saved preset.
 *
 * @param string $name
 * @return bool
 * @deprecated since Moodle 4.1 MDL-75187 - please, use the preset::delete() function instead.
 * @todo MDL-75189 This will be deleted in Moodle 4.5.
 * @see preset::delete()
 */
function datafos_delete_site_preset($name) {
    debugging('data_delete_site_preset() is deprecated. Please use preset::delete() instead.', DEBUG_DEVELOPER);

    $fs = get_file_storage();

    $files = $fs->get_directory_files(DATAFOS_PRESET_CONTEXT, DATAFOS_PRESET_COMPONENT, DATAFOS_PRESET_FILEAREA, 0, '/'.$name.'/');
    if (!empty($files)) {
        foreach ($files as $file) {
            $file->delete();
        }
    }

    $dir = $fs->get_file(DATAFOS_PRESET_CONTEXT, DATAFOS_PRESET_COMPONENT, DATAFOS_PRESET_FILEAREA, 0, '/'.$name.'/', '.');
    if (!empty($dir)) {
        $dir->delete();
    }
    return true;
}

/**
 * Prints the heads for a page
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $data
 * @param string $currenttab
 * @param string $actionbar
 */
function datafos_print_header($course, $cm, $data, $currenttab='', string $actionbar = '') {

    global $CFG, $displaynoticegood, $displaynoticebad, $OUTPUT, $PAGE, $USER;

    echo $OUTPUT->header();

    echo $actionbar;

    // Print any notices

    if (!empty($displaynoticegood)) {
        echo $OUTPUT->notification($displaynoticegood, 'notifysuccess');    // good (usually green)
    } else if (!empty($displaynoticebad)) {
        echo $OUTPUT->notification($displaynoticebad);                     // bad (usuually red)
    }
}

/**
 * Can user add more entries?
 *
 * @param object $data
 * @param mixed $currentgroup
 * @param int $groupmode
 * @param stdClass $context
 * @return bool
 */
function datafos_user_can_add_entry($data, $currentgroup, $groupmode, $context = null) {
    global $DB;

    // Don't let add entry to a database that has no fields.
    if (!$DB->record_exists('data_fields_fos', ['dataid' => $data->id])) {
        return false;
    }

    if (empty($context)) {
        $cm = get_coursemodule_from_instance('datafos', $data->id, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
    }

    if (has_capability('mod/datafos:manageentries', $context)) {
        // no entry limits apply if user can manage

    } else if (!has_capability('mod/datafos:writeentry', $context)) {
        return false;

    } else if (datafos_atmaxentries($data)) {
        return false;
    } else if (datafos_in_readonly_period($data)) {
        // Check whether we're in a read-only period
        return false;
    }

    if (!$groupmode or has_capability('moodle/site:accessallgroups', $context)) {
        return true;
    }

    if ($currentgroup) {
        return groups_is_member($currentgroup);
    } else {
        //else it might be group 0 in visible mode
        if ($groupmode == VISIBLEGROUPS){
            return true;
        } else {
            return false;
        }
    }
}

/**
 * Check whether the current user is allowed to manage the given record considering manageentries capability,
 * data_in_readonly_period() result, ownership (determined by data_isowner()) and manageapproved setting.
 * @param mixed $record record object or id
 * @param object $data datafos object
 * @param object $context context object
 * @return bool returns true if the user is allowd to edit the entry, false otherwise
 */
function datafos_user_can_manage_entry($record, $data, $context) {
    global $DB;

    if (has_capability('mod/datafos:manageentries', $context)) {
        return true;
    }

    // Check whether this activity is read-only at present.
    $readonly = datafos_in_readonly_period($data);

    if (!$readonly) {
        // Get record object from db if just id given like in data_isowner.
        // ...done before calling data_isowner() to avoid querying db twice.
        if (!is_object($record)) {
            if (!$record = $DB->get_record('data_records_fos', array('id' => $record))) {
                return false;
            }
        }
        if (datafos_isowner($record)) {
            if ($data->approval && $record->approved) {
                return $data->manageapproved == 1;
            } else {
                return true;
            }
        }
    }

    return false;
}

/**
 * Check whether the specified database activity is currently in a read-only period
 *
 * @param object $data
 * @return bool returns true if the time fields in $data indicate a read-only period; false otherwise
 */
function datafos_in_readonly_period($data) {
    $now = time();
    if (!$data->timeviewfrom && !$data->timeviewto) {
        return false;
    } else if (($data->timeviewfrom && $now < $data->timeviewfrom) || ($data->timeviewto && $now > $data->timeviewto)) {
        return false;
    }
    return true;
}

/**
 * Check if the files in a directory are the expected for a preset.
 *
 * @return bool Wheter the defined $directory has or not all the expected preset files.
 *
 * @deprecated since Moodle 4.1 MDL-75148 - please, use the preset::is_directory_a_preset() function instead.
 * @todo MDL-75189 This will be deleted in Moodle 4.5.
 * @see manager::is_directory_a_preset()
 */
function datafos_is_directory_a_preset($directory) {
    debugging('is_directory_a_preset() is deprecated. Please use preset::is_directory_a_preset() instead.', DEBUG_DEVELOPER);

    return preset::is_directory_a_preset($directory);
}

/**
 * Abstract class used for datafos preset importers
 *
 * @deprecated since Moodle 4.1 MDL-75140 - please do not use this class any more.
 * @todo MDL-75189 Final deprecation in Moodle 4.5.
 */
abstract class datafos_preset_importer {

    protected $course;
    protected $cm;
    protected $module;
    protected $directory;

    /**
     * Constructor
     *
     * @param stdClass $course
     * @param stdClass $cm
     * @param stdClass $module
     * @param string $directory
     */
    public function __construct($course, $cm, $module, $directory) {
        debugging(
            'data_preset_importer is deprecated. Please use mod\\datafos\\local\\importer\\preset_importer instead',
            DEBUG_DEVELOPER
        );

        $this->course = $course;
        $this->cm = $cm;
        $this->module = $module;
        $this->directory = $directory;
    }

    /**
     * Returns the name of the directory the preset is located in
     * @return string
     */
    public function get_directory() {
        return basename($this->directory);
    }

    /**
     * Retreive the contents of a file. That file may either be in a conventional directory of the Moodle file storage
     * @param file_storage $filestorage. should be null if using a conventional directory
     * @param stored_file $fileobj the directory to look in. null if using a conventional directory
     * @param string $dir the directory to look in. null if using the Moodle file storage
     * @param string $filename the name of the file we want
     * @return string the contents of the file or null if the file doesn't exist.
     */
    public function datafos_preset_get_file_contents(&$filestorage, &$fileobj, $dir, $filename) {
        if(empty($filestorage) || empty($fileobj)) {
            if (substr($dir, -1)!='/') {
                $dir .= '/';
            }
            if (file_exists($dir.$filename)) {
                return file_get_contents($dir.$filename);
            } else {
                return null;
            }
        } else {
            if ($filestorage->file_exists(DATAFOS_PRESET_CONTEXT, DATAFOS_PRESET_COMPONENT, DATAFOS_PRESET_FILEAREA, 0, $fileobj->get_filepath(), $filename)) {
                $file = $filestorage->get_file(DATAFOS_PRESET_CONTEXT, DATAFOS_PRESET_COMPONENT, DATAFOS_PRESET_FILEAREA, 0, $fileobj->get_filepath(), $filename);
                return $file->get_content();
            } else {
                return null;
            }
        }

    }
    /**
     * Gets the preset settings
     * @global moodle_database $DB
     * @return stdClass
     */
    public function get_preset_settings() {
        global $DB, $CFG;
        require_once($CFG->libdir.'/xmlize.php');

        $fs = $fileobj = null;
        if (!preset::is_directory_a_preset($this->directory)) {
            //maybe the user requested a preset stored in the Moodle file storage

            $fs = get_file_storage();
            $files = $fs->get_area_files(DATAFOS_PRESET_CONTEXT, DATAFOS_PRESET_COMPONENT, DATAFOS_PRESET_FILEAREA);

            //preset name to find will be the final element of the directory
            $explodeddirectory = explode('/', $this->directory);
            $presettofind = end($explodeddirectory);

            //now go through the available files available and see if we can find it
            foreach ($files as $file) {
                if (($file->is_directory() && $file->get_filepath()=='/') || !$file->is_directory()) {
                    continue;
                }
                $presetname = trim($file->get_filepath(), '/');
                if ($presetname==$presettofind) {
                    $this->directory = $presetname;
                    $fileobj = $file;
                }
            }

            if (empty($fileobj)) {
                throw new \moodle_exception('invalidpreset', 'datafos', '', $this->directory);
            }
        }

        $allowed_settings = array(
            'intro',
            'comments',
            'requiredentries',
            'requiredentriestoview',
            'maxentries',
            'rssarticles',
            'approval',
            'defaultsortdir',
            'defaultsort');

        $result = new stdClass;
        $result->settings = new stdClass;
        $result->importfields = array();
        $result->currentfields = $DB->get_records('data_fields_fos', array('dataid'=>$this->module->id));
        if (!$result->currentfields) {
            $result->currentfields = array();
        }


        /* Grab XML */
        $presetxml = $this->datafos_preset_get_file_contents($fs, $fileobj, $this->directory,'preset.xml');
        $parsedxml = xmlize($presetxml, 0);

        /* First, do settings. Put in user friendly array. */
        $settingsarray = $parsedxml['preset']['#']['settings'][0]['#'];
        $result->settings = new StdClass();
        foreach ($settingsarray as $setting => $value) {
            if (!is_array($value) || !in_array($setting, $allowed_settings)) {
                // unsupported setting
                continue;
            }
            $result->settings->$setting = $value[0]['#'];
        }

        /* Now work out fields to user friendly array */
        $fieldsarray = $parsedxml['preset']['#']['field'];
        foreach ($fieldsarray as $field) {
            if (!is_array($field)) {
                continue;
            }
            $f = new StdClass();
            foreach ($field['#'] as $param => $value) {
                if (!is_array($value)) {
                    continue;
                }
                $f->$param = $value[0]['#'];
            }
            $f->dataid = $this->module->id;
            $f->type = clean_param($f->type, PARAM_ALPHA);
            $result->importfields[] = $f;
        }
        /* Now add the HTML templates to the settings array so we can update d */
        $result->settings->singletemplate     = $this->datafos_preset_get_file_contents($fs, $fileobj,$this->directory,"singletemplate.html");
        $result->settings->listtemplate       = $this->datafos_preset_get_file_contents($fs, $fileobj,$this->directory,"listtemplate.html");
        $result->settings->listtemplateheader = $this->datafos_preset_get_file_contents($fs, $fileobj,$this->directory,"listtemplateheader.html");
        $result->settings->listtemplatefooter = $this->datafos_preset_get_file_contents($fs, $fileobj,$this->directory,"listtemplatefooter.html");
        $result->settings->addtemplate        = $this->datafos_preset_get_file_contents($fs, $fileobj,$this->directory,"addtemplate.html");
        $result->settings->rsstemplate        = $this->datafos_preset_get_file_contents($fs, $fileobj,$this->directory,"rsstemplate.html");
        $result->settings->rsstitletemplate   = $this->datafos_preset_get_file_contents($fs, $fileobj,$this->directory,"rsstitletemplate.html");
        $result->settings->csstemplate        = $this->datafos_preset_get_file_contents($fs, $fileobj,$this->directory,"csstemplate.css");
        $result->settings->jstemplate         = $this->datafos_preset_get_file_contents($fs, $fileobj,$this->directory,"jstemplate.js");
        $result->settings->asearchtemplate    = $this->datafos_preset_get_file_contents($fs, $fileobj,$this->directory,"asearchtemplate.html");

        $result->settings->instance = $this->module->id;
        return $result;
    }

    /**
     * Import the preset into the given database module
     * @return bool
     */
    function import($overwritesettings) {
        global $DB, $CFG, $OUTPUT;

        $params = $this->get_preset_settings();
        $settings = $params->settings;
        $newfields = $params->importfields;
        $currentfields = $params->currentfields;
        $preservedfields = array();

        /* Maps fields and makes new ones */
        if (!empty($newfields)) {
            /* We require an injective mapping, and need to know what to protect */
            foreach ($newfields as $nid => $newfield) {
                $cid = optional_param("field_$nid", -1, PARAM_INT);
                if ($cid == -1) {
                    continue;
                }
                if (array_key_exists($cid, $preservedfields)){
                    throw new \moodle_exception('notinjectivemap', 'datafos');
                }
                else $preservedfields[$cid] = true;
            }
            $missingfieldtypes = [];
            foreach ($newfields as $nid => $newfield) {
                $cid = optional_param("field_$nid", -1, PARAM_INT);

                /* A mapping. Just need to change field params. Data kept. */
                if ($cid != -1 and isset($currentfields[$cid])) {
                    $fieldobject = datafos_get_field_from_id($currentfields[$cid]->id, $this->module);
                    foreach ($newfield as $param => $value) {
                        if ($param != "id") {
                            $fieldobject->field->$param = $value;
                        }
                    }
                    unset($fieldobject->field->similarfield);
                    $fieldobject->update_field();
                    unset($fieldobject);
                } else {
                    /* Make a new field */
                    $filepath = "field/$newfield->type/field.class.php";
                    if (!file_exists($filepath)) {
                        $missingfieldtypes[] = $newfield->name;
                        continue;
                    }
                    include_once($filepath);

                    if (!isset($newfield->description)) {
                        $newfield->description = '';
                    }
                    $classname = 'datafos_field_'.$newfield->type;
                    $fieldclass = new $classname($newfield, $this->module);
                    $fieldclass->insert_field();
                    unset($fieldclass);
                }
            }
            if (!empty($missingfieldtypes)) {
                echo $OUTPUT->notification(get_string('missingfieldtypeimport', 'datafos') . html_writer::alist($missingfieldtypes));
            }
        }

        /* Get rid of all old unused datafos */
        foreach ($currentfields as $cid => $currentfield) {
            if (!array_key_exists($cid, $preservedfields)) {
                /* Data not used anymore so wipe! */
                echo "Deleting field $currentfield->name<br />";

                // Delete all information related to fields.
                $todelete = datafos_get_field_from_id($currentfield->id, $this->module);
                $todelete->delete_field();
            }
        }

        // handle special settings here
        if (!empty($settings->defaultsort)) {
            if (is_numeric($settings->defaultsort)) {
                // old broken value
                $settings->defaultsort = 0;
            } else {
                $settings->defaultsort = (int)$DB->get_field('data_fields_fos', 'id', array('dataid'=>$this->module->id, 'name'=>$settings->defaultsort));
            }
        } else {
            $settings->defaultsort = 0;
        }

        // do we want to overwrite all current database settings?
        if ($overwritesettings) {
            // all supported settings
            $overwrite = array_keys((array)$settings);
        } else {
            // only templates and sorting
            $overwrite = array('singletemplate', 'listtemplate', 'listtemplateheader', 'listtemplatefooter',
                               'addtemplate', 'rsstemplate', 'rsstitletemplate', 'csstemplate', 'jstemplate',
                               'asearchtemplate', 'defaultsortdir', 'defaultsort');
        }

        // now overwrite current datafos settings
        foreach ($this->module as $prop=>$unused) {
            if (in_array($prop, $overwrite)) {
                $this->module->$prop = $settings->$prop;
            }
        }

        datafos_update_instance($this->module);

        return $this->cleanup();
    }

    /**
     * Any clean up routines should go here
     * @return bool
     */
    public function cleanup() {
        return true;
    }
}

/**
 * Data preset importer for uploaded presets
 *
 * @deprecated since Moodle 4.1 MDL-75140 - please do not use this class any more.
 * @todo MDL-75189 Final deprecation in Moodle 4.5.
 */
class datafos_preset_upload_importer extends datafos_preset_importer {
    public function __construct($course, $cm, $module, $filepath) {
        global $USER;

        debugging(
            'data_preset_upload_importer is deprecated. Please use mod\\datafos\\local\\importer\\preset_upload_importer instead',
            DEBUG_DEVELOPER
        );

        if (is_file($filepath)) {
            $fp = get_file_packer();
            if ($fp->extract_to_pathname($filepath, $filepath.'_extracted')) {
                fulldelete($filepath);
            }
            $filepath .= '_extracted';
        }
        parent::__construct($course, $cm, $module, $filepath);
    }

    public function cleanup() {
        return fulldelete($this->directory);
    }
}

/**
 * Data preset importer for existing presets
 *
 * @deprecated since Moodle 4.1 MDL-75140 - please do not use this class any more.
 * @todo MDL-75189 Final deprecation in Moodle 4.5.
 */
class datafos_preset_existing_importer extends datafos_preset_importer {
    protected $userid;
    public function __construct($course, $cm, $module, $fullname) {
        global $USER;

        debugging(
            'data_preset_existing_importer is deprecated. Please use mod\\datafos\\local\\importer\\preset_existing_importer instead',
            DEBUG_DEVELOPER
        );

        list($userid, $shortname) = explode('/', $fullname, 2);
        $context = context_module::instance($cm->id);
        if ($userid && ($userid != $USER->id) && !has_capability('mod/datafos:manageuserpresets', $context) && !has_capability('mod/datafos:viewalluserpresets', $context)) {
           throw new coding_exception('Invalid preset provided');
        }

        $this->userid = $userid;
        $filepath = datafos_preset_path($course, $userid, $shortname);
        parent::__construct($course, $cm, $module, $filepath);
    }
    public function get_userid() {
        return $this->userid;
    }
}

/**
 * @global object
 * @global object
 * @param object $course
 * @param int $userid
 * @param string $shortname
 * @return string
 */
function datafos_preset_path($course, $userid, $shortname) {
    global $USER, $CFG;

    $context = context_course::instance($course->id);

    $userid = (int)$userid;

    $path = null;
    if ($userid > 0 && ($userid == $USER->id || has_capability('mod/datafos:viewalluserpresets', $context))) {
        $path = $CFG->dataroot.'/datafos/preset/'.$userid.'/'.$shortname;
    } else if ($userid == 0) {
        $path = $CFG->dirroot.'/mod/datafos/preset/'.$shortname;
    } else if ($userid < 0) {
        $path = $CFG->tempdir.'/datafos/'.-$userid.'/'.$shortname;
    }

    return $path;
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the datafos.
 *
 * @param MoodleQuickForm $mform form passed by reference
 */
function datafos_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'dataheader', get_string('modulenameplural', 'datafos'));
    $mform->addElement('checkbox', 'reset_data', get_string('deleteallentries', 'data'));

    $mform->addElement('checkbox', 'reset_data_notenrolled', get_string('deletenotenrolled', 'datafos'));
    $mform->disabledIf('reset_data_notenrolled', 'reset_data', 'checked');

    $mform->addElement('checkbox', 'reset_data_ratings', get_string('deleteallratings'));
    $mform->disabledIf('reset_data_ratings', 'reset_data', 'checked');

    $mform->addElement('checkbox', 'reset_data_comments', get_string('deleteallcomments'));
    $mform->disabledIf('reset_data_comments', 'reset_data', 'checked');

    $mform->addElement('checkbox', 'reset_DATAFOS_TAGS', get_string('removealldatatags', 'datafos'));
    $mform->disabledIf('reset_DATAFOS_TAGS', 'reset_data', 'checked');
}

/**
 * Course reset form defaults.
 * @return array
 */
function datafos_reset_course_form_defaults($course) {
    return array('reset_data'=>0, 'reset_data_ratings'=>1, 'reset_data_comments'=>1, 'reset_data_notenrolled'=>0);
}

/**
 * Removes all grades from gradebook
 *
 * @global object
 * @global object
 * @param int $courseid
 * @param string $type optional type
 */
function datafos_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $sql = "SELECT d.*, cm.idnumber as cmidnumber, d.course as courseid
              FROM {datafos} d, {course_modules} cm, {modules} m
             WHERE m.name='datafos' AND m.id=cm.module AND cm.instance=d.id AND d.course=?";

    if ($datas = $DB->get_records_sql($sql, array($courseid))) {
        foreach ($datas as $data) {
            datafos_grade_item_update($data, 'reset');
        }
    }
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * datafos responses for course $data->courseid.
 *
 * @global object
 * @global object
 * @param object $data the datafos submitted from the reset course.
 * @return array status array
 */
function datafos_reset_userdata($data) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/filelib.php');
    require_once($CFG->dirroot.'/rating/lib.php');

    $componentstr = get_string('modulenameplural', 'datafos');
    $status = array();

    $allrecordssql = "SELECT r.id
                        FROM {data_records_fos} r
                             INNER JOIN {datafos} d ON r.dataid = d.id
                       WHERE d.course = ?";

    $alldatassql = "SELECT d.id
                      FROM {datafos} d
                     WHERE d.course=?";

    $rm = new rating_manager();
    $ratingdeloptions = new stdClass;
    $ratingdeloptions->component = 'mod_datafos';
    $ratingdeloptions->ratingarea = 'entry';

    // Set the file storage - may need it to remove files later.
    $fs = get_file_storage();

    // delete entries if requested
    if (!empty($data->reset_data)) {
        $DB->delete_records_select('comments', "itemid IN ($allrecordssql) AND commentarea='database_entry'", array($data->courseid));
        $DB->delete_records_select('data_content_fos', "recordid IN ($allrecordssql)", array($data->courseid));
        $DB->delete_records_select('data_records_fos', "dataid IN ($alldatassql)", array($data->courseid));

        if ($datas = $DB->get_records_sql($alldatassql, array($data->courseid))) {
            foreach ($datas as $dataid=>$unused) {
                if (!$cm = get_coursemodule_from_instance('datafos', $dataid)) {
                    continue;
                }
                $datacontext = context_module::instance($cm->id);

                // Delete any files that may exist.
                $fs->delete_area_files($datacontext->id, 'mod_datafos', 'content');

                $ratingdeloptions->contextid = $datacontext->id;
                $rm->delete_ratings($ratingdeloptions);

                core_tag_tag::delete_instances('mod_datafos', null, $datacontext->id);
            }
        }

        if (empty($data->reset_gradebook_grades)) {
            // remove all grades from gradebook
            datafos_reset_gradebook($data->courseid);
        }
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallentries', 'datafos'), 'error'=>false);
    }

    // remove entries by users not enrolled into course
    if (!empty($data->reset_data_notenrolled)) {
        $recordssql = "SELECT r.id, r.userid, r.dataid, u.id AS userexists, u.deleted AS userdeleted
                         FROM {data_records_fos} r
                              JOIN {datafos} d ON r.dataid = d.id
                              LEFT JOIN {user} u ON r.userid = u.id
                        WHERE d.course = ? AND r.userid > 0";

        $course_context = context_course::instance($data->courseid);
        $notenrolled = array();
        $fields = array();
        $rs = $DB->get_recordset_sql($recordssql, array($data->courseid));
        foreach ($rs as $record) {
            if (array_key_exists($record->userid, $notenrolled) or !$record->userexists or $record->userdeleted
              or !is_enrolled($course_context, $record->userid)) {
                //delete ratings
                if (!$cm = get_coursemodule_from_instance('datafos', $record->dataid)) {
                    continue;
                }
                $datacontext = context_module::instance($cm->id);
                $ratingdeloptions->contextid = $datacontext->id;
                $ratingdeloptions->itemid = $record->id;
                $rm->delete_ratings($ratingdeloptions);

                // Delete any files that may exist.
                if ($contents = $DB->get_records('data_content_fos', array('recordid' => $record->id), '', 'id')) {
                    foreach ($contents as $content) {
                        $fs->delete_area_files($datacontext->id, 'mod_datafos', 'content', $content->id);
                    }
                }
                $notenrolled[$record->userid] = true;

                core_tag_tag::remove_all_item_tags('mod_datafos', 'data_records_fos', $record->id);

                $DB->delete_records('comments', array('itemid' => $record->id, 'commentarea' => 'database_entry'));
                $DB->delete_records('data_content_fos', array('recordid' => $record->id));
                $DB->delete_records('data_records_fos', array('id' => $record->id));
            }
        }
        $rs->close();
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deletenotenrolled', 'datafos'), 'error'=>false);
    }

    // remove all ratings
    if (!empty($data->reset_data_ratings)) {
        if ($datas = $DB->get_records_sql($alldatassql, array($data->courseid))) {
            foreach ($datas as $dataid=>$unused) {
                if (!$cm = get_coursemodule_from_instance('datafos', $dataid)) {
                    continue;
                }
                $datacontext = context_module::instance($cm->id);

                $ratingdeloptions->contextid = $datacontext->id;
                $rm->delete_ratings($ratingdeloptions);
            }
        }

        if (empty($data->reset_gradebook_grades)) {
            // remove all grades from gradebook
            datafos_reset_gradebook($data->courseid);
        }

        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallratings'), 'error'=>false);
    }

    // remove all comments
    if (!empty($data->reset_data_comments)) {
        $DB->delete_records_select('comments', "itemid IN ($allrecordssql) AND commentarea='database_entry'", array($data->courseid));
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallcomments'), 'error'=>false);
    }

    // Remove all the tags.
    if (!empty($data->reset_DATAFOS_TAGS)) {
        if ($datas = $DB->get_records_sql($alldatassql, array($data->courseid))) {
            foreach ($datas as $dataid => $unused) {
                if (!$cm = get_coursemodule_from_instance('datafos', $dataid)) {
                    continue;
                }

                $context = context_module::instance($cm->id);
                core_tag_tag::delete_instances('mod_datafos', null, $context->id);

            }
        }
        $status[] = array('component' => $componentstr, 'item' => get_string('tagsdeleted', 'datafos'), 'error' => false);
    }

    // updating dates - shift may be negative too
    if ($data->timeshift) {
        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        shift_course_mod_dates('datafos', array('timeavailablefrom', 'timeavailableto',
            'timeviewfrom', 'timeviewto', 'assesstimestart', 'assesstimefinish'), $data->timeshift, $data->courseid);
        $status[] = array('component'=>$componentstr, 'item'=>get_string('datechanged'), 'error'=>false);
    }

    return $status;
}

/**
 * Returns all other caps used in module
 *
 * @return array
 */
function datafos_get_extra_capabilities() {
    return ['moodle/rating:view', 'moodle/rating:viewany', 'moodle/rating:viewall', 'moodle/rating:rate',
            'moodle/comment:view', 'moodle/comment:post', 'moodle/comment:delete'];
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function datafos_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES:    return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return true;
        case FEATURE_RATE:                    return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_COMMENT:                 return true;
        case FEATURE_MOD_PURPOSE:             return MOD_PURPOSE_COLLABORATION;

        default: return null;
    }
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Lists all browsable file areas
 *
 * @package  mod_datafos
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @return array
 */
function datafos_get_file_areas($course, $cm, $context) {
    return array('content' => get_string('areacontent', 'mod_datafos'));
}

/**
 * File browsing support for datafos module.
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param cm_info $cm
 * @param context $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info_stored file_info_stored instance or null if not found
 */
function datafos_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG, $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }

    if (!isset($areas[$filearea])) {
        return null;
    }

    if (is_null($itemid)) {
        require_once($CFG->dirroot.'/mod/datafos/locallib.php');
        return new data_file_info_container($browser, $course, $cm, $context, $areas, $filearea);
    }

    if (!$content = $DB->get_record('data_content_fos', array('id'=>$itemid))) {
        return null;
    }

    if (!$field = $DB->get_record('data_fields_fos', array('id'=>$content->fieldid))) {
        return null;
    }

    if (!$record = $DB->get_record('data_records_fos', array('id'=>$content->recordid))) {
        return null;
    }

    if (!$data = $DB->get_record('datafos', array('id'=>$field->dataid))) {
        return null;
    }

    //check if approved
    if ($data->approval and !$record->approved and !datafos_isowner($record) and !has_capability('mod/datafos:approve', $context)) {
        return null;
    }

    // group access
    if ($record->groupid) {
        $groupmode = groups_get_activity_groupmode($cm, $course);
        if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
            if (!groups_is_member($record->groupid)) {
                return null;
            }
        }
    }

    $fieldobj = datafos_get_field($field, $data, $cm);

    $filepath = is_null($filepath) ? '/' : $filepath;
    $filename = is_null($filename) ? '.' : $filename;
    if (!$fieldobj->file_ok($filepath.$filename)) {
        return null;
    }

    $fs = get_file_storage();
    if (!($storedfile = $fs->get_file($context->id, 'mod_datafos', $filearea, $itemid, $filepath, $filename))) {
        return null;
    }

    // Checks to see if the user can manage files or is the owner.
    // TODO MDL-33805 - Do not use userid here and move the capability check above.
    if (!has_capability('moodle/course:managefiles', $context) && $storedfile->get_userid() != $USER->id) {
        return null;
    }

    $urlbase = $CFG->wwwroot.'/pluginfile.php';

    return new file_info_stored($browser, $context, $storedfile, $urlbase, $itemid, true, true, false, false);
}

/**
 * Serves the datafos attachments. Implements needed access control ;-)
 *
 * @package  mod_datafos
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function datafos_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if ($filearea === 'content') {
        $contentid = (int)array_shift($args);

        if (!$content = $DB->get_record('data_content_fos', array('id'=>$contentid))) {
            return false;
        }

        if (!$field = $DB->get_record('data_fields_fos', array('id'=>$content->fieldid))) {
            return false;
        }

        if (!$record = $DB->get_record('data_records_fos', array('id'=>$content->recordid))) {
            return false;
        }

        if (!$data = $DB->get_record('datafos', array('id'=>$field->dataid))) {
            return false;
        }

        if ($data->id != $cm->instance) {
            // hacker attempt - context does not match the contentid
            return false;
        }

        //check if approved
        if ($data->approval and !$record->approved and !datafos_isowner($record) and !has_capability('mod/datafos:approve', $context)) {
            return false;
        }

        // group access
        if ($record->groupid) {
            $groupmode = groups_get_activity_groupmode($cm, $course);
            if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
                if (!groups_is_member($record->groupid)) {
                    return false;
                }
            }
        }

        $fieldobj = datafos_get_field($field, $data, $cm);

        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_datafos/content/$content->id/$relativepath";

        if (!$fieldobj->file_ok($relativepath)) {
            return false;
        }

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }

        // finally send the file
        send_stored_file($file, 0, 0, true, $options); // download MUST be forced - security!
    }

    return false;
}


function datafos_extend_navigation($navigation, $course, $module, $cm) {
    global $CFG, $OUTPUT, $USER, $DB;
    require_once($CFG->dirroot . '/mod/datafos/locallib.php');

    $rid = optional_param('rid', 0, PARAM_INT);

    $data = $DB->get_record('datafos', array('id'=>$cm->instance));
    $currentgroup = groups_get_activity_group($cm);
    $groupmode = groups_get_activity_groupmode($cm);

     $numentries = datafos_numentries($data);
    $canmanageentries = has_capability('mod/datafos:manageentries', context_module::instance($cm->id));

    if ($data->entriesleft = datafos_get_entries_left_to_add($data, $numentries, $canmanageentries)) {
        $entriesnode = $navigation->add(get_string('entrieslefttoadd', 'datafos', $data));
        $entriesnode->add_class('note');
    }

    $navigation->add(get_string('listview', 'datafos'), new moodle_url('/mod/datafos/view.php', array('d'=>$cm->instance)));
    if (!empty($rid)) {
        $navigation->add(get_string('singleview', 'datafos'), new moodle_url('/mod/datafos/view.php', array('d'=>$cm->instance, 'rid'=>$rid)));
    } else {
        $navigation->add(get_string('singleview', 'datafos'), new moodle_url('/mod/datafos/view.php', array('d'=>$cm->instance, 'mode'=>'single')));
    }
    $navigation->add(get_string('search', 'datafos'), new moodle_url('/mod/datafos/view.php', array('d'=>$cm->instance, 'mode'=>'asearch')));
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $datanode The node to add module settings to
 */
function datafos_extend_settings_navigation(settings_navigation $settings, navigation_node $datanode) {
    global $DB, $CFG, $USER;

    $data = $DB->get_record('datafos', array("id" => $settings->get_page()->cm->instance));

    $currentgroup = groups_get_activity_group($settings->get_page()->cm);
    $groupmode = groups_get_activity_groupmode($settings->get_page()->cm);

    // Took out participation list here!
    if (datafos_user_can_add_entry($data, $currentgroup, $groupmode, $settings->get_page()->cm->context)) {
        if (empty($editentry)) { //TODO: undefined
            $addstring = get_string('add', 'datafos');
        } else {
            $addstring = get_string('editentry', 'datafos');
        }
        $addentrynode = $datanode->add($addstring,
            new moodle_url('/mod/datafos/edit.php', array('d' => $settings->get_page()->cm->instance)));
        $addentrynode->set_show_in_secondary_navigation(false);
    }

    if (has_capability(DATAFOS_CAP_EXPORT, $settings->get_page()->cm->context)) {
        // The capability required to Export database records is centrally defined in 'lib.php'
        // and should be weaker than those required to edit Templates, Fields and Presets.
        $exportentriesnode = $datanode->add(get_string('exportentries', 'data'),
            new moodle_url('/mod/datafos/export.php', array('d' => $data->id)));
        $exportentriesnode->set_show_in_secondary_navigation(false);
    }
    if (has_capability('mod/datafos:manageentries', $settings->get_page()->cm->context)) {
        $importentriesnode = $datanode->add(get_string('importentries', 'data'),
            new moodle_url('/mod/datafos/import.php', array('d' => $data->id)));
        $importentriesnode->set_show_in_secondary_navigation(false);
    }

    if (has_capability('mod/datafos:managetemplates', $settings->get_page()->cm->context)) {
        $currenttab = '';
        if ($currenttab == 'list') {
            $defaultemplate = 'listtemplate';
        } else if ($currenttab == 'add') {
            $defaultemplate = 'addtemplate';
        } else if ($currenttab == 'asearch') {
            $defaultemplate = 'asearchtemplate';
        } else {
            $defaultemplate = 'singletemplate';
        }

        $datanode->add(get_string('presets', 'datafos'), new moodle_url('/mod/datafos/preset.php', array('d' => $data->id)));
        $datanode->add(get_string('fields', 'data'),
            new moodle_url('/mod/datafos/field.php', array('d' => $data->id)));
        $datanode->add(get_string('templates', 'datafos'),
            new moodle_url('/mod/datafos/templates.php', array('d' => $data->id)));
    }

    if (!empty($CFG->enablerssfeeds) && !empty($CFG->data_enablerssfeeds) && $data->rssarticles > 0) {
        require_once("$CFG->libdir/rsslib.php");

        $string = get_string('rsstype', 'datafos');

        $url = new moodle_url(rss_get_url($settings->get_page()->cm->context->id, $USER->id, 'mod_datafos', $data->id));
        $datanode->add($string, $url, settings_navigation::TYPE_SETTING, null, null, new pix_icon('i/rss', ''));
    }
}

/**
 * Save the database configuration as a preset.
 *
 * @param stdClass $course The course the database module belongs to.
 * @param stdClass $cm The course module record
 * @param stdClass $data The database record
 * @param string $path
 * @return bool
 * @deprecated since Moodle 4.1 MDL-75142 - please, use the preset::save() function instead.
 * @todo MDL-75189 This will be deleted in Moodle 4.5.
 * @see preset::save()
 */
function datafos_presets_save($course, $cm, $data, $path) {
    debugging('data_presets_save() is deprecated. Please use preset::save() instead.', DEBUG_DEVELOPER);

    $manager = manager::create_from_instance($data);
    $preset = preset::create_from_instance($manager, $path);
    return $preset->save();
}

/**
 * Generates the XML for the database module provided
 *
 * @global moodle_database $DB
 * @param stdClass $course The course the database module belongs to.
 * @param stdClass $cm The course module record
 * @param stdClass $data The database record
 * @return string The XML for the preset
 * @deprecated since Moodle 4.1 MDL-75142 - please, use the protected preset::generate_preset_xml() function instead.
 * @todo MDL-75189 This will be deleted in Moodle 4.5.
 * @see preset::generate_preset_xml()
 */
function datafos_presets_generate_xml($course, $cm, $data) {
    debugging(
        'data_presets_generate_xml() is deprecated. Please use the protected preset::generate_preset_xml() instead.',
        DEBUG_DEVELOPER
    );

    $manager = manager::create_from_instance($data);
    $preset = preset::create_from_instance($manager, $data->name);
    $reflection = new \ReflectionClass(preset::class);
    $method = $reflection->getMethod('generate_preset_xml');
    $method->setAccessible(true);
    return $method->invokeArgs($preset, []);
}

/**
 * Export current fields and presets.
 *
 * @param stdClass $course The course the database module belongs to.
 * @param stdClass $cm The course module record
 * @param stdClass $data The database record
 * @param bool $tostorage
 * @return string the full path to the exported preset file.
 * @deprecated since Moodle 4.1 MDL-75142 - please, use the preset::export() function instead.
 * @todo MDL-75189 This will be deleted in Moodle 4.5.
 * @see preset::export()
 */
function datafos_presets_export($course, $cm, $data, $tostorage=false) {
    debugging('data_presets_export() is deprecated. Please use preset::export() instead.', DEBUG_DEVELOPER);

    $manager = manager::create_from_instance($data);
    $preset = preset::create_from_instance($manager, $data->name);
    return $preset->export();
}

/**
 * Running addtional permission check on plugin, for example, plugins
 * may have switch to turn on/off comments option, this callback will
 * affect UI display, not like pluginname_comment_validate only throw
 * exceptions.
 * Capability check has been done in comment->check_permissions(), we
 * don't need to do it again here.
 *
 * @package  mod_datafos
 * @category comment
 *
 * @param stdClass $comment_param {
 *              context  => context the context object
 *              courseid => int course id
 *              cm       => stdClass course module object
 *              commentarea => string comment area
 *              itemid      => int itemid
 * }
 * @return array
 */
function datafos_comment_permissions($comment_param) {
    global $CFG, $DB;
    if (!$record = $DB->get_record('data_records_fos', array('id'=>$comment_param->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }
    if (!$data = $DB->get_record('datafos', array('id'=>$record->dataid))) {
        throw new comment_exception('invalidid', 'datafos');
    }
    if ($data->comments) {
        return array('post'=>true, 'view'=>true);
    } else {
        return array('post'=>false, 'view'=>false);
    }
}

/**
 * Validate comment parameter before perform other comments actions
 *
 * @package  mod_datafos
 * @category comment
 *
 * @param stdClass $comment_param {
 *              context  => context the context object
 *              courseid => int course id
 *              cm       => stdClass course module object
 *              commentarea => string comment area
 *              itemid      => int itemid
 * }
 * @return boolean
 */
function datafos_comment_validate($comment_param) {
    global $DB;
    // validate comment area
    if ($comment_param->commentarea != 'database_entry') {
        throw new comment_exception('invalidcommentarea');
    }
    // validate itemid
    if (!$record = $DB->get_record('data_records_fos', array('id'=>$comment_param->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }
    if (!$data = $DB->get_record('datafos', array('id'=>$record->dataid))) {
        throw new comment_exception('invalidid', 'datafos');
    }
    if (!$course = $DB->get_record('course', array('id'=>$data->course))) {
        throw new comment_exception('coursemisconf');
    }
    if (!$cm = get_coursemodule_from_instance('datafos', $data->id, $course->id)) {
        throw new comment_exception('invalidcoursemodule');
    }
    if (!$data->comments) {
        throw new comment_exception('commentsoff', 'datafos');
    }
    $context = context_module::instance($cm->id);

    //check if approved
    if ($data->approval and !$record->approved and !datafos_isowner($record) and !has_capability('mod/datafos:approve', $context)) {
        throw new comment_exception('notapprovederror', 'datafos');
    }

    // group access
    if ($record->groupid) {
        $groupmode = groups_get_activity_groupmode($cm, $course);
        if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
            if (!groups_is_member($record->groupid)) {
                throw new comment_exception('notmemberofgroup');
            }
        }
    }
    // validate context id
    if ($context->id != $comment_param->context->id) {
        throw new comment_exception('invalidcontext');
    }
    // validation for comment deletion
    if (!empty($comment_param->commentid)) {
        if ($comment = $DB->get_record('comments', array('id'=>$comment_param->commentid))) {
            if ($comment->commentarea != 'database_entry') {
                throw new comment_exception('invalidcommentarea');
            }
            if ($comment->contextid != $comment_param->context->id) {
                throw new comment_exception('invalidcontext');
            }
            if ($comment->itemid != $comment_param->itemid) {
                throw new comment_exception('invalidcommentitemid');
            }
        } else {
            throw new comment_exception('invalidcommentid');
        }
    }
    return true;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function datafos_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-datafos-*'=>get_string('page-mod-datafos-x', 'datafos'));
    return $module_pagetype;
}

/**
 * Get all of the record ids from a database activity.
 *
 * @param int    $dataid      The dataid of the database module.
 * @param object $selectdata  Contains an additional sql statement for the
 *                            where clause for group and approval fields.
 * @param array  $params      Parameters that coincide with the sql statement.
 * @return array $idarray     An array of record ids
 */
function datafos_get_all_recordids($dataid, $selectdata = '', $params = null) {
    global $DB;
    $initsql = 'SELECT r.id
                  FROM {data_records_fos} r
                 WHERE r.dataid = :dataid';
    if ($selectdata != '') {
        $initsql .= $selectdata;
        $params = array_merge(array('dataid' => $dataid), $params);
    } else {
        $params = array('dataid' => $dataid);
    }
    $initsql .= ' GROUP BY r.id';
    $initrecord = $DB->get_recordset_sql($initsql, $params);
    $idarray = array();
    foreach ($initrecord as $data) {
        $idarray[] = $data->id;
    }
    // Close the record set and free up resources.
    $initrecord->close();
    return $idarray;
}

/**
 * Get the ids of all the records that match that advanced search criteria
 * This goes and loops through each criterion one at a time until it either
 * runs out of records or returns a subset of records.
 *
 * @param array $recordids    An array of record ids.
 * @param array $searcharray  Contains information for the advanced search criteria
 * @param int $dataid         The datafos id of the database.
 * @return array $recordids   An array of record ids.
 */
function datafos_get_advance_search_ids($recordids, $searcharray, $dataid) {
    // Check to see if we have any record IDs.
    if (empty($recordids)) {
        // Send back an empty search.
        return array();
    }
    $searchcriteria = array_keys($searcharray);
    // Loop through and reduce the IDs one search criteria at a time.
    foreach ($searchcriteria as $key) {
        $recordids = datafos_get_recordids($key, $searcharray, $dataid, $recordids);
        // If we don't have anymore IDs then stop.
        if (!$recordids) {
            break;
        }
    }
    return $recordids;
}

/**
 * Gets the record IDs given the search criteria
 *
 * @param string $alias       Record alias.
 * @param array $searcharray  Criteria for the search.
 * @param int $dataid         Data ID for the database
 * @param array $recordids    An array of record IDs.
 * @return array $nestarray   An arry of record IDs
 */
function datafos_get_recordids($alias, $searcharray, $dataid, $recordids) {
    global $DB;
    $searchcriteria = $alias;   // Keep the criteria.
    $nestsearch = $searcharray[$alias];
    // searching for content outside of mdl_data_content
    if ($alias < 0) {
        $alias = '';
    }
    list($insql, $params) = $DB->get_in_or_equal($recordids, SQL_PARAMS_NAMED);
    $nestselect = 'SELECT c' . $alias . '.recordid
                     FROM {data_content_fos} c' . $alias . '
               INNER JOIN {data_fields_fos} f
                       ON f.id = c' . $alias . '.fieldid
               INNER JOIN {data_records_fos} r
                       ON r.id = c' . $alias . '.recordid
               INNER JOIN {user} u
                       ON u.id = r.userid ';
    $nestwhere = 'WHERE r.dataid = :dataid
                    AND c' . $alias .'.recordid ' . $insql . '
                    AND ';

    $params['dataid'] = $dataid;
    if (count($nestsearch->params) != 0) {
        $params = array_merge($params, $nestsearch->params);
        $nestsql = $nestselect . $nestwhere . $nestsearch->sql;
    } else if ($searchcriteria == DATAFOS_TIMEMODIFIED) {
        $nestsql = $nestselect . $nestwhere . $nestsearch->field . ' >= :timemodified GROUP BY c' . $alias . '.recordid';
        $params['timemodified'] = $nestsearch->datafos;
    } else if ($searchcriteria == DATAFOS_TAGS) {
        if (empty($nestsearch->rawtagnames)) {
            return [];
        }
        $i = 0;
        $tagwhere = [];
        $tagselect = '';
        foreach ($nestsearch->rawtagnames as $tagrawname) {
            $tagselect .= " INNER JOIN {tag_instance} ti_$i
                                    ON ti_$i.component = 'mod_datafos'
                                   AND ti_$i.itemtype = 'data_records_fos'
                                   AND ti_$i.itemid = r.id
                            INNER JOIN {tag} t_$i
                                    ON ti_$i.tagid = t_$i.id ";
            $tagwhere[] = " t_$i.rawname = :trawname_$i ";
            $params["trawname_$i"] = $tagrawname;
            $i++;
        }
        $nestsql = $nestselect . $tagselect . $nestwhere . implode(' AND ', $tagwhere);
    } else {    // First name or last name.
        $thing = $DB->sql_like($nestsearch->field, ':search1', false);
        $nestsql = $nestselect . $nestwhere . $thing . ' GROUP BY c' . $alias . '.recordid';
        $params['search1'] = "%$nestsearch->datafos%";
    }
    $nestrecords = $DB->get_recordset_sql($nestsql, $params);
    $nestarray = array();
    foreach ($nestrecords as $data) {
        $nestarray[] = $data->recordid;
    }
    // Close the record set and free up resources.
    $nestrecords->close();
    return $nestarray;
}

/**
 * Returns an array with an sql string for advanced searches and the parameters that go with them.
 *
 * @param int $sort            DATA_*
 * @param stdClass $data       Data module object
 * @param array $recordids     An array of record IDs.
 * @param string $selectdata   Information for the where and select part of the sql statement.
 * @param string $sortorder    Additional sort parameters
 * @return array sqlselect     sqlselect['sql'] has the sql string, sqlselect['params'] contains an array of parameters.
 */
function datafos_get_advanced_search_sql($sort, $data, $recordids, $selectdata, $sortorder) {
    global $DB;

    $userfieldsapi = \core_user\fields::for_userpic()->excluding('id');
    $namefields = $userfieldsapi->get_sql('u', false, '', '', false)->selects;

    if ($sort == 0) {
        $nestselectsql = 'SELECT r.id, r.approved, r.timecreated, r.timemodified, r.userid, ' . $namefields . '
                        FROM {data_content_fos} c,
                             {data_records_fos} r,
                             {user} u ';
        $groupsql = ' GROUP BY r.id, r.approved, r.timecreated, r.timemodified, r.userid, u.firstname, u.lastname, ' . $namefields;
    } else {
        // Sorting through 'Other' criteria
        if ($sort <= 0) {
            switch ($sort) {
                case DATAFOS_LASTNAME:
                    $sortcontentfull = "u.lastname";
                    break;
                case DATAFOS_FIRSTNAME:
                    $sortcontentfull = "u.firstname";
                    break;
                case DATAFOS_APPROVED:
                    $sortcontentfull = "r.approved";
                    break;
                case DATAFOS_TIMEMODIFIED:
                    $sortcontentfull = "r.timemodified";
                    break;
                case DATAFOS_TIMEADDED:
                default:
                    $sortcontentfull = "r.timecreated";
            }
        } else {
            $sortfield = datafos_get_field_from_id($sort, $data);
            $sortcontent = $DB->sql_compare_text('c.' . $sortfield->get_sort_field());
            $sortcontentfull = $sortfield->get_sort_sql($sortcontent);
        }

        $nestselectsql = 'SELECT r.id, r.approved, r.timecreated, r.timemodified, r.userid, ' . $namefields . ',
                                 ' . $sortcontentfull . '
                              AS sortorder
                            FROM {data_content_fos} c,
                                 {data_records_fos} r,
                                 {user} u ';
        $groupsql = ' GROUP BY r.id, r.approved, r.timecreated, r.timemodified, r.userid, ' . $namefields . ', ' .$sortcontentfull;
    }

    // Default to a standard Where statement if $selectdata is empty.
    if ($selectdata == '') {
        $selectdata = 'WHERE c.recordid = r.id
                         AND r.dataid = :dataid
                         AND r.userid = u.id ';
    }

    // Find the field we are sorting on
    if ($sort > 0 or datafos_get_field_from_id($sort, $data)) {
        $selectdata .= ' AND c.fieldid = :sort AND s.recordid = r.id';
        $nestselectsql .= ',{data_content_fos} s ';
    }

    // If there are no record IDs then return an sql statment that will return no rows.
    if (count($recordids) != 0) {
        list($insql, $inparam) = $DB->get_in_or_equal($recordids, SQL_PARAMS_NAMED);
    } else {
        list($insql, $inparam) = $DB->get_in_or_equal(array('-1'), SQL_PARAMS_NAMED);
    }
    $nestfromsql = $selectdata . ' AND c.recordid ' . $insql . $groupsql;
    $sqlselect['sql'] = "$nestselectsql $nestfromsql $sortorder";
    $sqlselect['params'] = $inparam;
    return $sqlselect;
}

/**
 * Checks to see if the user has permission to delete the preset.
 * @param stdClass $context  Context object.
 * @param stdClass $preset  The preset object that we are checking for deletion.
 * @return bool  Returns true if the user can delete, otherwise false.
 * @deprecated since Moodle 4.1 MDL-75187 - please, use the preset::can_manage() function instead.
 * @todo MDL-75189 This will be deleted in Moodle 4.5.
 * @see preset::can_manage()
 */
function datafos_user_can_delete_preset($context, $preset) {
    global $USER;

    debugging('data_user_can_delete_preset() is deprecated. Please use manager::can_manage() instead.', DEBUG_DEVELOPER);

    if ($context->contextlevel == CONTEXT_MODULE && isset($preset->name)) {
        $cm = get_coursemodule_from_id('', $context->instanceid, 0, false, MUST_EXIST);
        $manager = manager::create_from_coursemodule($cm);
        $todelete = preset::create_from_instance($manager, $preset->name);
        return $todelete->can_manage();
    }

    if (has_capability('mod/datafos:manageuserpresets', $context)) {
        return true;
    } else {
        $candelete = false;
        $userid = $preset instanceof preset ? $preset->get_userid() : $preset->userid;
        if ($userid == $USER->id) {
            $candelete = true;
        }
        return $candelete;
    }
}

/**
 * Delete a record entry.
 *
 * @param int $recordid The ID for the record to be deleted.
 * @param object $data The datafos object for this activity.
 * @param int $courseid ID for the current course (for logging).
 * @param int $cmid The course module ID.
 * @return bool True if the record deleted, false if not.
 */
function datafos_delete_record($recordid, $data, $courseid, $cmid) {
    global $DB, $CFG;

    if ($deleterecord = $DB->get_record('data_records_fos', array('id' => $recordid))) {
        if ($deleterecord->dataid == $data->id) {
            if ($contents = $DB->get_records('data_content_fos', array('recordid' => $deleterecord->id))) {
                foreach ($contents as $content) {
                    if ($field = datafos_get_field_from_id($content->fieldid, $data)) {
                        $field->delete_content($content->recordid);
                    }
                }
                $DB->delete_records('data_content_fos', array('recordid'=>$deleterecord->id));
                $DB->delete_records('data_records_fos', array('id'=>$deleterecord->id));

                // Delete cached RSS feeds.
                if (!empty($CFG->enablerssfeeds)) {
                    require_once($CFG->dirroot.'/mod/datafos/rsslib.php');
                    data_rss_delete_file($data);
                }

                core_tag_tag::remove_all_item_tags('mod_datafos', 'data_records_fos', $recordid);

                // Trigger an event for deleting this record.
                $event = \mod_datafos\event\record_deleted::create(array(
                    'objectid' => $deleterecord->id,
                    'context' => context_module::instance($cmid),
                    'courseid' => $courseid,
                    'other' => array(
                        'dataid' => $deleterecord->dataid
                    )
                ));
                $event->add_record_snapshot('data_records_fos', $deleterecord);
                $event->trigger();
                $course = get_course($courseid);
                $cm = get_coursemodule_from_instance('datafos', $data->id, 0, false, MUST_EXIST);
                datafos_update_completion_state($data, $course, $cm);

                return true;
            }
        }
    }

    return false;
}

/**
 * Check for required fields, and build a list of fields to be updated in a
 * submission.
 *
 * @param $mod stdClass The current recordid - provided as an optimisation.
 * @param $fields array The field datafos
 * @param $datarecord stdClass The submitted datafos.
 * @return stdClass containing:
 * * string[] generalnotifications Notifications for the form as a whole.
 * * string[] fieldnotifications Notifications for a specific field.
 * * bool validated Whether the field was validated successfully.
 * * data_field_base[] fields The field objects to be update.
 */
function datafos_process_submission(stdClass $mod, $fields, stdClass $datarecord) {
    $result = new stdClass();

    // Empty form checking - you can't submit an empty form.
    $emptyform = true;
    $requiredfieldsfilled = true;
    $fieldsvalidated = true;

    // Store the notifications.
    $result->generalnotifications = array();
    $result->fieldnotifications = array();

    // Store the instantiated classes as an optimisation when processing the result.
    // This prevents the fields being re-initialised when updating.
    $result->fields = array();

    $submitteddata = array();
    foreach ($datarecord as $fieldname => $fieldvalue) {
        if (strpos($fieldname, '_')) {
            $namearray = explode('_', $fieldname, 3);
            $fieldid = $namearray[1];
            if (!isset($submitteddata[$fieldid])) {
                $submitteddata[$fieldid] = array();
            }
            if (count($namearray) === 2) {
                $subfieldid = 0;
            } else {
                $subfieldid = $namearray[2];
            }

            $fielddata = new stdClass();
            $fielddata->fieldname = $fieldname;
            $fielddata->value = $fieldvalue;
            $submitteddata[$fieldid][$subfieldid] = $fielddata;
        }
    }

    $OneUploadFieldNotEmpty = false;
    //$OneNeedSelected = false;

    // Check all form fields which have the required are filled.
    foreach ($fields as $fieldrecord) {
        // Check whether the field has any datafos.
        $fieldhascontent = false;

        $field = datafos_get_field($fieldrecord, $mod);
        if (isset($submitteddata[$fieldrecord->id])) {
            // Field validation check.
            if (method_exists($field, 'field_validation')) {
                $errormessage = $field->field_validation($submitteddata[$fieldrecord->id]);
                if ($errormessage) {
                    $result->fieldnotifications[$field->field->name][] = $errormessage;
                    $fieldsvalidated = false;
                }
            }
            foreach ($submitteddata[$fieldrecord->id] as $fieldname => $value) {
                if ($field->notemptyfield($value->value, $value->fieldname)) {
                    // The field has content and the form is not empty.
                    $fieldhascontent = true;
                    $emptyform = false;
                }
            }
        }

      /*  //KTT CUSTOMIZATION
        if ($field->field->name === "File EN" && $fieldhascontent){
            $OneUploadFieldNotEmpty = true;
        }elseif ($field->field->name === "File ES" && $fieldhascontent){
            $OneUploadFieldNotEmpty = true;
        }elseif ($field->field->name === "File PT" && $fieldhascontent){
            $OneUploadFieldNotEmpty = true;
        }elseif ($field->field->name === "File FR" && $fieldhascontent){
            $OneUploadFieldNotEmpty = true;
        }elseif ($field->field->name === "Link" && $fieldhascontent){
            $OneUploadFieldNotEmpty = true;
        }

        /*if ($field->field->name === "Needs1" && $fieldhascontent){
            $OneNeedSelected = true;
        }elseif ($field->field->name === "Needs2" && $fieldhascontent){
            $OneNeedSelected = true;
        }*/
        //----------

        // If the field is required, add a notification to that effect.
        if ($field->field->required && !$fieldhascontent) {
            /*if (!isset($result->fieldnotifications[$field->field->name])) {
                $result->fieldnotifications[$field->field->name] = array();
            }
            $result->fieldnotifications[$field->field->name][] = get_string('errormustsupplyvalue', 'datafos');*/
            if(count($result->generalnotifications)===0){
                $result->generalnotifications[] = get_string('errormustsupplyvaluegeneral', 'datafos');
            }
            $requiredfieldsfilled = false;
        }

        // Update the field.
        if (isset($submitteddata[$fieldrecord->id])) {
            foreach ($submitteddata[$fieldrecord->id] as $value) {
                $result->fields[$value->fieldname] = $field;
            }
        }
    }

    //KTT CUSTOMIZATION
    /*if (!$OneUploadFieldNotEmpty){
        /*if (!isset($result->fieldnotifications["File EN"])) {
            $result->fieldnotifications["File EN"] = array();
        }
        $result->fieldnotifications["File EN"][] = get_string('errormustsupplyvalue', 'datafos');*/
      /*  if(count($result->generalnotifications)===0){
            $result->generalnotifications[] = get_string('errormustsupplyvaluegeneral', 'datafos');
        }
        $requiredfieldsfilled = false;
     }

    /*if (!$OneNeedSelected){
        if (!isset($result->fieldnotifications["Needs1"])) {
            $result->fieldnotifications["Needs1"] = array();
        }
        $result->fieldnotifications["Needs1"][] = get_string('errormustsupplyvalue', 'datafos');
        if(count($result->generalnotifications)===0){
            $result->generalnotifications[] = "Please check all the form fields and make to not leave any mandatory field blank";
        }
        $requiredfieldsfilled = false;
    }*/

    //------------------

    if ($emptyform) {
        // The form is empty.
        $result->generalnotifications[] = get_string('emptyaddform', 'datafos');
    }

    $result->validated = $requiredfieldsfilled && !$emptyform && $fieldsvalidated;

    return $result;
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every datafos event in the site is checked, else
 * only datafos events belonging to the course specified are checked.
 * This function is used, in its new format, by restore_refresh_events()
 *
 * @param int $courseid
 * @param int|stdClass $instance Data module instance or ID.
 * @param int|stdClass $cm Course module object or ID (not used in this module).
 * @return bool
 */
function datafos_refresh_events($courseid = 0, $instance = null, $cm = null) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/datafos/locallib.php');

    // If we have instance information then we can just update the one event instead of updating all events.
    if (isset($instance)) {
        if (!is_object($instance)) {
            $instance = $DB->get_record('datafos', array('id' => $instance), '*', MUST_EXIST);
        }
        datafos_set_events($instance);
        return true;
    }

    if ($courseid) {
        if (! $data = $DB->get_records("datafos", array("course" => $courseid))) {
            return true;
        }
    } else {
        if (! $data = $DB->get_records("datafos")) {
            return true;
        }
    }

    foreach ($data as $datum) {
        datafos_set_events($datum);
    }
    return true;
}

/**
 * Fetch the configuration for this database activity.
 *
 * @param   stdClass    $database   The object returned from the database for this instance
 * @param   string      $key        The name of the key to retrieve. If none is supplied, then all configuration is returned
 * @param   mixed       $default    The default value to use if no value was found for the specified key
 * @return  mixed                   The returned value
 */
function datafos_get_config($database, $key = null, $default = null) {
    if (!empty($database->config)) {
        $config = json_decode($database->config);
    } else {
        $config = new stdClass();
    }

    if ($key === null) {
        return $config;
    }

    if (property_exists($config, $key)) {
        return $config->$key;
    }
    return $default;
}

/**
 * Update the configuration for this database activity.
 *
 * @param   stdClass    $database   The object returned from the database for this instance
 * @param   string      $key        The name of the key to set
 * @param   mixed       $value      The value to set for the key
 */
function datafos_set_config(&$database, $key, $value) {
    // Note: We must pass $database by reference because there may be subsequent calls to update_record and these should
    // not overwrite the configuration just set.
    global $DB;

    $config = datafos_get_config($database);

    if (!isset($config->$key) || $config->$key !== $value) {
        $config->$key = $value;
        $database->config = json_encode($config);
        $DB->set_field('datafos', 'config', $database->config, ['id' => $database->id]);
    }
}
/**
 * Sets the automatic completion state for this database item based on the
 * count of on its entries.
 * @since Moodle 3.3
 * @param object $data The datafos object for this activity
 * @param object $course Course
 * @param object $cm course-module
 */
function datafos_update_completion_state($data, $course, $cm) {
    // If completion option is enabled, evaluate it and return true/false.
    $completion = new completion_info($course);
    if ($data->completionentries && $completion->is_enabled($cm)) {
        $numentries = datafos_numentries($data);
        // Check the number of entries required against the number of entries already made.
        if ($numentries >= $data->completionentries) {
            $completion->update_state($cm, COMPLETION_COMPLETE);
        } else {
            $completion->update_state($cm, COMPLETION_INCOMPLETE);
        }
    }
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @deprecated since Moodle 4.1 MDL-75146 - please do not use this function any more.
 * @todo MDL-75189 Final deprecation in Moodle 4.5.
 * @param  stdClass $data       datafos object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.3
 */
function datafos_view($data, $course, $cm, $context) {
    global $CFG;
    debugging('data_view is deprecated. Use mod_datafos\\manager::set_module_viewed instead', DEBUG_DEVELOPER);
    require_once($CFG->libdir . '/completionlib.php');

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $data->id
    );

    $event = \mod_datafos\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('datafos', $data);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Get icon mapping for font-awesome.
 */
function mod_datafos_get_fontawesome_icon_map() {
    return [
        'mod_datafos:field/checkbox' => 'fa-check-square-o',
        'mod_datafos:field/date' => 'fa-calendar-o',
        'mod_datafos:field/file' => 'fa-file',
        'mod_datafos:field/latlong' => 'fa-globe',
        'mod_datafos:field/menu' => 'fa-bars',
        'mod_datafos:field/multimenu' => 'fa-bars',
        'mod_datafos:field/number' => 'fa-hashtag',
        'mod_datafos:field/picture' => 'fa-picture-o',
        'mod_datafos:field/radiobutton' => 'fa-circle-o',
        'mod_datafos:field/textarea' => 'fa-font',
        'mod_datafos:field/text' => 'fa-i-cursor',
        'mod_datafos:field/url' => 'fa-link',
    ];
}

/*
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module datafos
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function datafos_check_updates_since(cm_info $cm, $from, $filter = array()) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/datafos/locallib.php');

    $updates = course_check_module_updates_since($cm, $from, array(), $filter);

    // Check for new entries.
    $updates->entries = (object) array('updated' => false);

    $data = $DB->get_record('datafos', array('id' => $cm->instance), '*', MUST_EXIST);
    $searcharray = [];
    $searcharray[DATAFOS_TIMEMODIFIED] = new stdClass();
    $searcharray[DATAFOS_TIMEMODIFIED]->sql     = '';
    $searcharray[DATAFOS_TIMEMODIFIED]->params  = array();
    $searcharray[DATAFOS_TIMEMODIFIED]->field   = 'r.timemodified';
    $searcharray[DATAFOS_TIMEMODIFIED]->datafos    = $from;

    $currentgroup = groups_get_activity_group($cm);
    // Teachers should retrieve all entries when not in separate groups.
    if (has_capability('mod/datafos:manageentries', $cm->context) && groups_get_activity_groupmode($cm) != SEPARATEGROUPS) {
        $currentgroup = 0;
    }
    list($entries, $maxcount, $totalcount, $page, $nowperpage, $sort, $mode) =
        datafos_search_entries($data, $cm, $cm->context, 'list', $currentgroup, '', null, null, 0, 0, true, $searcharray);

    if (!empty($entries)) {
        $updates->entries->updated = true;
        $updates->entries->itemids = array_keys($entries);
    }

    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_datafos_core_calendar_provide_event_action(calendar_event $event,
                                                     \core_calendar\action_factory $factory,
                                                     int $userid = 0) {
    global $USER;

    if (!$userid) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['datafos'][$event->instance];

    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }

    $now = time();

    if (!empty($cm->customdata['timeavailableto']) && $cm->customdata['timeavailableto'] < $now) {
        // The module has closed so the user can no longer submit anything.
        return null;
    }

    // The module is actionable if we don't have a start time or the start time is
    // in the past.
    $actionable = (empty($cm->customdata['timeavailablefrom']) || $cm->customdata['timeavailablefrom'] <= $now);

    return $factory->create_instance(
        get_string('add', 'datafos'),
        new \moodle_url('/mod/datafos/view.php', array('id' => $cm->id)),
        1,
        $actionable
    );
}

/**
 * Add a get_coursemodule_info function in case any database type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function datafos_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, completionentries, timeavailablefrom, timeavailableto';
    if (!$data = $DB->get_record('datafos', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $data->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('datafos', $data, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionentries'] = $data->completionentries;
    }
    // Other properties that may be used in calendar or on dashboard.
    if ($data->timeavailablefrom) {
        $result->customdata['timeavailablefrom'] = $data->timeavailablefrom;
    }
    if ($data->timeavailableto) {
        $result->customdata['timeavailableto'] = $data->timeavailableto;
    }

    return $result;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_datafos_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionentries':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionentriesdesc', 'datafos', $val);
                }
                break;
            default:
                break;
        }
    }
    return $descriptions;
}

/**
 * This function calculates the minimum and maximum cutoff values for the timestart of
 * the given event.
 *
 * It will return an array with two values, the first being the minimum cutoff value and
 * the second being the maximum cutoff value. Either or both values can be null, which
 * indicates there is no minimum or maximum, respectively.
 *
 * If a cutoff is required then the function must return an array containing the cutoff
 * timestamp and error string to display to the user if the cutoff value is violated.
 *
 * A minimum and maximum cutoff return value will look like:
 * [
 *     [1505704373, 'The due date must be after the sbumission start date'],
 *     [1506741172, 'The due date must be before the cutoff date']
 * ]
 *
 * @param calendar_event $event The calendar event to get the time range for
 * @param stdClass $instance The module instance to get the range from
 * @return array
 */
function mod_datafos_core_calendar_get_valid_event_timestart_range(\calendar_event $event, \stdClass $instance) {
    $mindate = null;
    $maxdate = null;

    if ($event->eventtype == DATAFOS_EVENT_TYPE_OPEN) {
        // The start time of the open event can't be equal to or after the
        // close time of the database activity.
        if (!empty($instance->timeavailableto)) {
            $maxdate = [
                $instance->timeavailableto,
                get_string('openafterclose', 'datafos')
            ];
        }
    } else if ($event->eventtype == DATAFOS_EVENT_TYPE_CLOSE) {
        // The start time of the close event can't be equal to or earlier than the
        // open time of the database activity.
        if (!empty($instance->timeavailablefrom)) {
            $mindate = [
                $instance->timeavailablefrom,
                get_string('closebeforeopen', 'datafos')
            ];
        }
    }

    return [$mindate, $maxdate];
}

/**
 * This function will update the datafos module according to the
 * event that has been modified.
 *
 * It will set the timeopen or timeclose value of the datafos instance
 * according to the type of event provided.
 *
 * @throws \moodle_exception
 * @param \calendar_event $event
 * @param stdClass $data The module instance to get the range from
 */
function mod_datafos_core_calendar_event_timestart_updated(\calendar_event $event, \stdClass $data) {
    global $DB;

    if (empty($event->instance) || $event->modulename != 'datafos') {
        return;
    }

    if ($event->instance != $data->id) {
        return;
    }

    if (!in_array($event->eventtype, [DATAFOS_EVENT_TYPE_OPEN, DATAFOS_EVENT_TYPE_CLOSE])) {
        return;
    }

    $courseid = $event->courseid;
    $modulename = $event->modulename;
    $instanceid = $event->instance;
    $modified = false;

    $coursemodule = get_fast_modinfo($courseid)->instances[$modulename][$instanceid];
    $context = context_module::instance($coursemodule->id);

    // The user does not have the capability to modify this activity.
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    if ($event->eventtype == DATAFOS_EVENT_TYPE_OPEN) {
        // If the event is for the datafos activity opening then we should
        // set the start time of the datafos activity to be the new start
        // time of the event.
        if ($data->timeavailablefrom != $event->timestart) {
            $data->timeavailablefrom = $event->timestart;
            $data->timemodified = time();
            $modified = true;
        }
    } else if ($event->eventtype == DATAFOS_EVENT_TYPE_CLOSE) {
        // If the event is for the datafos activity closing then we should
        // set the end time of the datafos activity to be the new start
        // time of the event.
        if ($data->timeavailableto != $event->timestart) {
            $data->timeavailableto = $event->timestart;
            $modified = true;
        }
    }

    if ($modified) {
        $data->timemodified = time();
        $DB->update_record('datafos', $data);
        $event = \core\event\course_module_updated::create_from_cm($coursemodule, $context);
        $event->trigger();
    }
}

/**
 * Callback to fetch the activity event type lang string.
 *
 * @param string $eventtype The event type.
 * @return lang_string The event type lang string.
 */
function mod_datafos_core_calendar_get_event_action_string(string $eventtype): string {
    $modulename = get_string('modulename', 'datafos');

    switch ($eventtype) {
        case DATAFOS_EVENT_TYPE_OPEN:
            $identifier = 'calendarstart';
            break;
        case DATAFOS_EVENT_TYPE_CLOSE:
            $identifier = 'calendarend';
            break;
        default:
            return get_string('requiresaction', 'calendar', $modulename);
    }

    return get_string($identifier, 'datafos', $modulename);
}
