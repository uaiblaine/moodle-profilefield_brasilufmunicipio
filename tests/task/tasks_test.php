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

namespace profilefield_brasilufmunicipio\task;

/**
 * Tests for the tasks that fill the municipality list.
 *
 * Coverage is declared in this docblock rather than with a CoversClass attribute, because the moodle-cs of the
 * Moodle 4.5 leg cannot read attributes.
 *
 * @package    profilefield_brasilufmunicipio
 * @copyright  2026 Anderson Blaine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \profilefield_brasilufmunicipio\task\update_municipios
 * @covers     \profilefield_brasilufmunicipio\task\populate_municipios
 * @covers     \profilefield_brasilufmunicipio\api::update_municipios
 */
final class tasks_test extends \advanced_testcase {
    /**
     * Start from an empty municipality table; a site may have filled it from IBGE already.
     */
    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();
        $DB->delete_records('profilefield_brasilufmunicipio');
        // The site's own installation queued one already.
        $DB->delete_records('task_adhoc', ['classname' => '\\' . populate_municipios::class]);
    }

    /**
     * Queue an IBGE answer for every UF, the one for the UF given being unusable.
     *
     * @param string|null $failing The UF whose answer is unusable, or null.
     */
    private function mock_ibge(?string $failing): void {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        foreach (array_reverse(array_keys(\profilefield_brasilufmunicipio\api::get_ufs())) as $i => $uf) {
            \curl::mock_response($uf === $failing ? '' : json_encode([['id' => 1000 + $i, 'nome' => "Capital $uf"]]));
        }
    }

    /**
     * The scheduled task succeeds when every UF was refreshed and fails, naming the UF, when one was not.
     */
    public function test_update_municipios(): void {
        $this->resetAfterTest();
        $this->mock_ibge(null);
        (new update_municipios())->execute();

        $this->mock_ibge('BA');
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('errorupdatefailed', 'profilefield_brasilufmunicipio', 'BA'));
        (new update_municipios())->execute();
    }

    /**
     * The adhoc task fills the table and, when a UF fails, queues only that UF for later instead of failing.
     */
    public function test_populate_municipios(): void {
        global $DB;
        $this->mock_ibge('TO');
        $this->expectOutputRegex('/: TO$/m');

        (new populate_municipios())->execute();

        $this->assertSame(26, $DB->count_records('profilefield_brasilufmunicipio'));
        $retries = \core\task\manager::get_adhoc_tasks(populate_municipios::class);
        $this->assertCount(1, $retries);
        $retry = reset($retries);
        $this->assertEquals((object)['ufs' => ['TO'], 'attempt' => 2], $retry->get_custom_data());
        $this->assertGreaterThan(time(), $retry->get_next_run_time());

        // The retry asks for TO alone, and queues nothing once it succeeds.
        \curl::mock_response(json_encode([['id' => 1721000, 'nome' => 'Palmas']]));
        $DB->delete_records('task_adhoc');
        $retry->execute();
        $names = array_column(\profilefield_brasilufmunicipio\api::get_municipios('TO'), 'municipio');
        $this->assertSame(['Palmas'], array_values($names));
        $this->assertEmpty(\core\task\manager::get_adhoc_tasks(populate_municipios::class));
    }

    /**
     * The last attempt queues nothing more: the scheduled task takes over.
     */
    public function test_populate_municipios_gives_up(): void {
        $this->expectOutputRegex('/: TO$/m');
        \curl::mock_response('');
        $task = new populate_municipios();
        $task->set_custom_data(['ufs' => ['TO'], 'attempt' => populate_municipios::MAX_ATTEMPTS]);

        $task->execute();

        $this->assertEmpty(\core\task\manager::get_adhoc_tasks(populate_municipios::class));
        // The control: the attempt before the last queues a retry.
        \curl::mock_response('');
        $task->set_custom_data(['ufs' => ['TO'], 'attempt' => populate_municipios::MAX_ATTEMPTS - 1]);
        $task->execute();
        $this->assertCount(1, \core\task\manager::get_adhoc_tasks(populate_municipios::class));
    }

    /**
     * Installing the plugin queues the first fill rather than asking IBGE during the installation.
     *
     * @covers ::xmldb_profilefield_brasilufmunicipio_install
     */
    public function test_install_queues_the_first_fill(): void {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/field/brasilufmunicipio/db/install.php');
        $this->resetAfterTest();
        $this->assertEmpty(\core\task\manager::get_adhoc_tasks(populate_municipios::class));

        xmldb_profilefield_brasilufmunicipio_install();

        $this->assertCount(1, \core\task\manager::get_adhoc_tasks(populate_municipios::class));
    }
}
