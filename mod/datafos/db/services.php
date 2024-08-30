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
 * Database external functions and service definitions.
 *
 * @package    mod_datafos
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.9
 */

$functions = array(

    'mod_datafos_get_databases_by_courses' => array(
        'classname' => 'mod_datafos_external',
        'methodname' => 'get_databases_by_courses',
        'description' => 'Returns a list of database instances in a provided set of courses, if
            no courses are provided then all the database instances the user has access to will be returned.',
        'type' => 'read',
        'capabilities' => 'mod/datafos:viewentry',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_datafos_view_database' => array(
        'classname'     => 'mod_datafos_external',
        'methodname'    => 'view_database',
        'description'   => 'Simulate the view.php web interface datafos: trigger events, completion, etc...',
        'type'          => 'write',
        'capabilities'  => 'mod/datafos:viewentry',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_datafos_get_data_access_information' => array(
        'classname'     => 'mod_datafos_external',
        'methodname'    => 'get_data_access_information',
        'description'   => 'Return access information for a given database.',
        'type'          => 'read',
        'capabilities'  => 'mod/datafos:viewentry',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_datafos_get_entries' => array(
        'classname'     => 'mod_datafos_external',
        'methodname'    => 'get_entries',
        'description'   => 'Return the complete list of entries of the given database.',
        'type'          => 'read',
        'capabilities'  => 'mod/datafos:viewentry',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_datafos_get_entry' => array(
        'classname'     => 'mod_datafos_external',
        'methodname'    => 'get_entry',
        'description'   => 'Return one entry record from the database, including contents optionally.',
        'type'          => 'read',
        'capabilities'  => 'mod/datafos:viewentry',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_datafos_get_fields' => array(
        'classname'     => 'mod_datafos_external',
        'methodname'    => 'get_fields',
        'description'   => 'Return the list of configured fields for the given database.',
        'type'          => 'read',
        'capabilities'  => 'mod/datafos:viewentry',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_datafos_search_entries' => array(
        'classname'     => 'mod_datafos_external',
        'methodname'    => 'search_entries',
        'description'   => 'Search for entries in the given database.',
        'type'          => 'read',
        'capabilities'  => 'mod/datafos:viewentry',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_datafos_approve_entry' => array(
        'classname'     => 'mod_datafos_external',
        'methodname'    => 'approve_entry',
        'description'   => 'Approves or unapproves an entry.',
        'type'          => 'write',
        'capabilities'  => 'mod/datafos:approve',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_datafos_delete_entry' => array(
        'classname'     => 'mod_datafos_external',
        'methodname'    => 'delete_entry',
        'description'   => 'Deletes an entry.',
        'type'          => 'write',
        'capabilities'  => 'mod/datafos:manageentries',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_datafos_add_entry' => array(
        'classname'     => 'mod_datafos_external',
        'methodname'    => 'add_entry',
        'description'   => 'Adds a new entry.',
        'type'          => 'write',
        'capabilities'  => 'mod/datafos:writeentry',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_datafos_update_entry' => array(
        'classname'     => 'mod_datafos_external',
        'methodname'    => 'update_entry',
        'description'   => 'Updates an existing entry.',
        'type'          => 'write',
        'capabilities'  => 'mod/datafos:writeentry',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_datafos_delete_saved_preset' => array(
        'classname'   => 'mod_datafos\external\delete_saved_preset',
        'methodname'  => 'execute',
        'classpath'   => 'mod/datafos/classes/external/delete_saved_preset.php',
        'description' => 'Delete a saved preset in datafos plugin.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'mod/datafos:managepresets',
    ),
    'mod_datafos_get_mapping_information' => array(
        'classname'     => 'mod_datafos\external\get_mapping_information',
        'description'   => 'Get importing information',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'mod/datafos:managetemplates',
    ),
);
