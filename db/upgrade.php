<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin upgrade steps are defined here.
 *
 * @package     profilefield_brasilufmunicipio
 * @category    upgrade
 * @copyright   2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute profilefield_brasilufmunicipio upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_profilefield_brasilufmunicipio_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2022030304) {
        // Re-encode stored values without escaped Unicode; a value that is not JSON is left as it is.
        $fieldids = $DB->get_fieldset_select('user_info_field', 'id', 'datatype = ?', ['brasilufmunicipio']);
        foreach ($fieldids as $fieldid) {
            $rs = $DB->get_recordset('user_info_data', ['fieldid' => $fieldid], '', 'id, data');
            foreach ($rs as $d) {
                $decoded = json_decode($d->data);
                if ($decoded !== null) {
                    $DB->set_field('user_info_data', 'data', json_encode($decoded, JSON_UNESCAPED_UNICODE), ['id' => $d->id]);
                }
            }
            $rs->close();
        }
        upgrade_plugin_savepoint(true, 2022030304, 'profilefield', 'brasilufmunicipio');
    }

    if ($oldversion < 2025090300) {
        // Define table profilefield_brasilufmunicipio to be created.
        $table = new xmldb_table('profilefield_brasilufmunicipio');

        // Adding fields to table profilefield_brasilufmunicipio.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('uf', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ibgeid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('municipio', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table profilefield_brasilufmunicipio.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table profilefield_brasilufmunicipio.
        $table->add_index('uf', XMLDB_INDEX_NOTUNIQUE, ['uf']);

        // Conditionally launch create table for profilefield_brasilufmunicipio.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Brasilufmunicipio savepoint reached.
        upgrade_plugin_savepoint(true, 2025090300, 'profilefield', 'brasilufmunicipio');
    }

    if ($oldversion < 2025090400) {
        // Filled by a task: an upgrade step must not wait on IBGE.
        \core\task\manager::queue_adhoc_task(new \profilefield_brasilufmunicipio\task\populate_municipios(), true);
        // Brasilufmunicipio savepoint reached.
        upgrade_plugin_savepoint(true, 2025090400, 'profilefield', 'brasilufmunicipio');
    }

    return true;
}
