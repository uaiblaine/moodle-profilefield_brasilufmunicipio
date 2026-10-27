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

namespace profilefield_brasilufmunicipio\external;

/**
 * Tests for the web service that lists the municipalities of a UF.
 *
 * Coverage is declared in this docblock rather than with a CoversClass attribute, because the moodle-cs of the
 * Moodle 4.5 leg cannot read attributes.
 *
 * @package    profilefield_brasilufmunicipio
 * @category   external
 * @copyright  2026 Anderson Blaine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \profilefield_brasilufmunicipio\external\get_municipios
 */
final class get_municipios_test extends \advanced_testcase {
    /**
     * Start from an empty municipality table; a site may have filled it from IBGE already.
     */
    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();
        $DB->delete_records('profilefield_brasilufmunicipio');
    }

    /**
     * Anyone, logged in or not, gets the municipalities of a UF sorted by name, and none for an unknown UF.
     */
    public function test_execute_without_login(): void {
        global $DB;
        $this->resetAfterTest();
        $DB->insert_record('profilefield_brasilufmunicipio', ['uf' => 'SC', 'ibgeid' => 4209102, 'municipio' => 'Joinville']);
        $DB->insert_record('profilefield_brasilufmunicipio', ['uf' => 'SC', 'ibgeid' => 4202404, 'municipio' => 'Blumenau']);
        $this->setUser(null);
        $_POST['sesskey'] = sesskey();

        $response = \core_external\external_api::call_external_function(
            'profilefield_brasilufmunicipio_get_municipios',
            ['uf' => 'SC'],
            true
        );

        $this->assertFalse($response['error']);
        $this->assertSame(['Blumenau', 'Joinville'], array_column($response['data'], 'municipio'));
        $this->assertSame(['4202404', '4209102'], array_column($response['data'], 'ibgeid'));

        $response = \core_external\external_api::call_external_function(
            'profilefield_brasilufmunicipio_get_municipios',
            ['uf' => 'XX'],
            true
        );
        $this->assertFalse($response['error']);
        $this->assertSame([], $response['data']);
    }
}
