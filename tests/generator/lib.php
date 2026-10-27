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
 * Data generator for the Brasil UF e Município profile field.
 *
 * @package    profilefield_brasilufmunicipio
 * @category   test
 * @copyright  2026 Anderson Blaine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profilefield_brasilufmunicipio_generator extends component_generator_base {
    /**
     * Add a municipality to the list the scheduled task fills from IBGE on a real site.
     *
     * @param array|stdClass $record The uf, ibgeid and municipio.
     * @return stdClass The created record.
     */
    public function create_municipality($record): stdClass {
        global $DB;
        $record = (object)(array)$record;
        $record->id = $DB->insert_record('profilefield_brasilufmunicipio', $record);
        return $record;
    }
}
