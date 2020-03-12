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
 * Plugin upgrade steps are defined here.
 *
 * @package   local_ce
 * @author    David Castro <david.castro@blackboard.com>
 * @copyright Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_ce\model\set;

defined('MOODLE_INTERNAL') || die();

/**
 * Execute local_ce upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_ce_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019121804) {

        // Changing index so we can have several instances.
        $table = new xmldb_table('local_ce_instance');
        $index = new xmldb_index('setelement', XMLDB_INDEX_UNIQUE,
            ['customelementid', 'setid']);

        // Conditionally launch drop index.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $index = new xmldb_index('setelement', XMLDB_INDEX_NOTUNIQUE,
            ['customelementid', 'setid']);

        // Conditionally launch add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Custom elements savepoint reached.
        upgrade_plugin_savepoint(true, 2019121804, 'local', 'ce');
    }

    if ($oldversion < 2019121805) {

        // Adding default icon field to sets.
        $table = new xmldb_table('local_ce_set');
        $field = new xmldb_field('defaulticon', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, set::SET_DEFAULT_ICON);

        // Conditionally launch add field releasecode
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Custom elements savepoint reached.
        upgrade_plugin_savepoint(true, 2019121805, 'local', 'ce');
    }

    return true;
}
