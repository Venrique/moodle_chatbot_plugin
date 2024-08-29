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

namespace mod_datafos\output;

use mod_datafos\manager;
use mod_datafos\preset;
use moodle_url;
use url_select;

class action_bar {

    /** @var int $id The database module id. */
    private $id;

    /** @var int $cmid The database course module id. */
    private $cmid;

    /** @var moodle_url $currenturl The URL of the current page. */
    private $currenturl;

    /**
     * The class constructor.
     *
     * @param int $id The database module id.
     * @param moodle_url $pageurl The URL of the current page.
     */
    public function __construct(int $id, moodle_url $pageurl) {
        $this->id = $id;
        [$course, $cm] = get_course_and_cm_from_instance($this->id, 'datafos');
        $this->cmid = $cm->id;
        $this->currenturl = $pageurl;

        // Depura el valor de cmid
        error_log('Constructor: cmid = ' . $this->cmid);
    }

    /**
     * Generate the output for the action bar in the presets page.
     *
     * @return string The HTML code for the action selector in the presets page.
     */
    public function get_presets_action_bar(): string {
        global $PAGE;

        $renderer = $PAGE->get_renderer('mod_datafos');
        $presetsactionbar = new presets_action_bar($this->cmid, $this->get_presets_actions_select(true));

        // Depura el valor de cmid en el método get_presets_action_bar
        error_log('get_presets_action_bar: cmid = ' . $this->cmid);

        return $renderer->render_presets_action_bar($presetsactionbar);
    }

    /**
     * Helper method to get the selector for the presets action.
     *
     * @param bool $hasimport Whether the Import buttons must be included or not.
     * @return \action_menu|null The selector object used to display the presets actions. Null when the import button is not
     * displayed and the database hasn't any fields.
     */
    protected function get_presets_actions_select(bool $hasimport = false): ?\action_menu {
        global $DB;

        $hasfields = $DB->record_exists('data_fields_fos', ['dataid' => $this->id]);

        // Depura el valor de cmid en el método get_presets_actions_select
        error_log('get_presets_actions_select: cmid = ' . $this->cmid);
        error_log('get_presets_actions_select: id = ' . $this->id);
        error_log('Database has fields: ' . ($hasfields ? 'Yes' : 'No'));

        // Early return if the database has no fields and the import action won't be displayed.
        if (!$hasfields && !$hasimport) {
            return null;
        }

        $actionsselect = new \action_menu();
        $actionsselect->set_menu_trigger(get_string('actions'), 'btn btn-secondary');

        if ($hasimport) {
            // Import.
            $actionsselectparams = ['id' => $this->cmid];
            $actionsselect->add(new \action_menu_link(
                new moodle_url('/mod/datafos/preset.php', $actionsselectparams),
                null,
                get_string('importpreset', 'mod_datafos'),
                false,
                ['datafos-action' => 'importpresets', 'datafos-dataid' => $this->cmid]
            ));
        }

        // If the database has no fields, export and save as preset options shouldn't be displayed.
        if ($hasfields) {
            // Export.
            $actionsselectparams = ['id' => $this->cmid, 'action' => 'export'];
            $actionsselect->add(new \action_menu_link(
                new moodle_url('/mod/datafos/preset.php', $actionsselectparams),
                null,
                get_string('exportpreset', 'mod_datafos'),
                false
            ));
            // Save as preset.
            $actionsselect->add(new \action_menu_link(
                new moodle_url('/mod/datafos/preset.php', $actionsselectparams),
                null,
                get_string('saveaspreset', 'mod_datafos'),
                false,
                ['datafos-action' => 'saveaspreset', 'datafos-dataid' => $this->id]
            ));
        }

        return $actionsselect;
    }
}
