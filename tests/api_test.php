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

namespace profilefield_brasilufmunicipio;

/**
 * Tests for the municipality table and its refresh from IBGE.
 *
 * Coverage is declared in this docblock rather than with a CoversClass attribute, because the moodle-cs of the
 * Moodle 4.5 leg cannot read attributes.
 *
 * @package    profilefield_brasilufmunicipio
 * @copyright  2026 Anderson Blaine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \profilefield_brasilufmunicipio\api
 */
final class api_test extends \advanced_testcase {
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
     * Queue one mocked IBGE answer per UF, in the order the refresh asks for them.
     *
     * \curl::mock_response() serves the last queued answer first, so the list is queued backwards.
     *
     * @param array $bodies UF code => answer body; a UF left out answers with one municipality.
     */
    private function mock_ibge(array $bodies): void {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        foreach (array_reverse(array_keys(api::get_ufs())) as $i => $uf) {
            \curl::mock_response($bodies[$uf] ?? json_encode([['id' => 1000 + $i, 'nome' => "Capital $uf"]]));
        }
    }

    /**
     * Municipalities come sorted by name, and an unknown UF lists none.
     */
    public function test_get_municipios(): void {
        global $DB;
        $this->resetAfterTest();
        $DB->insert_record('profilefield_brasilufmunicipio', ['uf' => 'SC', 'ibgeid' => 2, 'municipio' => 'Joinville']);
        $DB->insert_record('profilefield_brasilufmunicipio', ['uf' => 'SC', 'ibgeid' => 1, 'municipio' => 'Blumenau']);
        $DB->insert_record('profilefield_brasilufmunicipio', ['uf' => 'PR', 'ibgeid' => 3, 'municipio' => 'Curitiba']);

        $this->assertSame(['Blumenau', 'Joinville'], array_values(array_column(api::get_municipios('SC'), 'municipio')));
        $this->assertSame([], api::get_municipios('XX'));
    }

    /**
     * A code is looked up within its UF only, and a non-numeric code finds nothing without querying.
     */
    public function test_get_municipio_by_ibgeid(): void {
        global $DB;
        $this->resetAfterTest();
        $DB->insert_record('profilefield_brasilufmunicipio', ['uf' => 'SC', 'ibgeid' => 4205407, 'municipio' => 'Florianópolis']);

        $this->assertSame('Florianópolis', api::get_municipio_by_ibgeid('SC', '4205407')->municipio);
        $this->assertNull(api::get_municipio_by_ibgeid('SP', '4205407'));
        $this->assertNull(api::get_municipio_by_ibgeid('SC', ''));
        $this->assertNull(api::get_municipio_by_ibgeid('SC', '4205407 OR 1=1'));
    }

    /**
     * A name is looked up within its UF, ignoring case.
     */
    public function test_get_municipio_by_name(): void {
        global $DB;
        $this->resetAfterTest();
        $DB->insert_record('profilefield_brasilufmunicipio', ['uf' => 'SC', 'ibgeid' => 4205407, 'municipio' => 'Florianópolis']);

        $this->assertSame('4205407', (string)api::get_municipio_by_name('SC', 'FLORIANÓPOLIS')->ibgeid);
        $this->assertNull(api::get_municipio_by_name('SP', 'Florianópolis'));
        $this->assertNull(api::get_municipio_by_name('SC', ''));
    }

    /**
     * A refresh replaces each UF's rows, and a UF whose answer is unusable keeps its rows and is reported.
     *
     * Unusable: an empty list, an object or a string instead of a list, an item without its code.
     */
    public function test_update_municipios_keeps_a_failed_uf(): void {
        global $DB;
        $this->resetAfterTest();
        $DB->insert_record('profilefield_brasilufmunicipio', ['uf' => 'SC', 'ibgeid' => 4205407, 'municipio' => 'Florianópolis']);
        $DB->insert_record('profilefield_brasilufmunicipio', ['uf' => 'SP', 'ibgeid' => 1, 'municipio' => 'Old']);
        $DB->insert_record('profilefield_brasilufmunicipio', ['uf' => 'RJ', 'ibgeid' => 2, 'municipio' => 'Old']);
        $this->mock_ibge([
            'SC' => '[]',
            'RJ' => '{"erro":"indisponível"}',
            'MG' => json_encode([['nome' => 'Sem código']]),
            'PA' => '"fora do ar"',
            'SP' => json_encode([['id' => 3550308, 'nome' => 'São Paulo'], ['id' => 3509502, 'nome' => 'Campinas']]),
        ]);

        $this->assertSame(['MG', 'PA', 'RJ', 'SC'], api::update_municipios());

        $this->assertSame(['Florianópolis'], array_values(array_column(api::get_municipios('SC'), 'municipio')));
        $this->assertSame(['Old'], array_values(array_column(api::get_municipios('RJ'), 'municipio')));
        $this->assertSame(['Campinas', 'São Paulo'], array_values(array_column(api::get_municipios('SP'), 'municipio')));
        $this->assertSame(['Capital AC'], array_values(array_column(api::get_municipios('AC'), 'municipio')));
    }
}
