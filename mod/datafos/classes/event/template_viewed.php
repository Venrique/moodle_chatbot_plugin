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
 * The mod_datafos template viewed event.
 *
 * @package    mod_datafos
 * @copyright  2014 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_datafos\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_datafos template viewed event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - int datafosid the id of the datafos activity.
 * }
 *
 * @package    mod_datafos
 * @since      Moodle 2.7
 * @copyright  2014 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_viewed extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r'; // Corrected from 'datafos' to 'data'
        $this->data['edulevel'] = self::LEVEL_OTHER; // Corrected from 'datafos' to 'data'
        $this->data['objecttable'] = 'datafos';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventtemplateviewed', 'mod_datafos');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' viewed the template for the datafos activity with course module " .
            "id '{$this->contextinstanceid}'.";
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/datafos/templates.php', array('d' => $this->other['datafosid']));
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception when validation does not pass.
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['datafosid'])) {
            throw new \coding_exception('The \'datafosid\' value must be set in other.');
        }
    }

    /**
     * Mapping for other fields for backup/restore.
     *
     * @return array
     */
    public static function get_other_mapping() {
        return [
            'datafosid' => ['db' => 'datafos', 'restore' => 'datafos']
        ];
    }
}
