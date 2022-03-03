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

defined('MOODLE_INTERNAL') || die();

/**
 * Execute profilefield_brasilufmunicipio upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_profilefield_brasilufmunicipio_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // For further information please read {@link https://docs.moodle.org/dev/Upgrade_API}.
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at {@link https://docs.moodle.org/dev/XMLDB_editor}.

    if ($oldversion < 2022030301) {
        $fieldssql = "SELECT id FROM {user_info_field} WHERE datatype = ?";
        $fields = $DB->get_records_sql($fieldssql, ['brasilufmunicipio']);
        foreach ($fields as $f) {
            $sql = "SELECT id, data
                      FROM {user_info_data} ud
                      WHERE ud.fieldid = ?";
            $data = $DB->get_records_sql($sql, [$f->id]);
            foreach ($data as $d) {
                $record = new stdclass();
                $record->id = $d->id;
                $record->data = json_encode(json_decode($d->data), JSON_UNESCAPED_UNICODE);
                $DB->update_record('user_info_data', $record);
            }
        }
        upgrade_plugin_savepoint(true, 2022030304, 'profilefield', 'brasilufmunicipio');
    }

    return true;
}
