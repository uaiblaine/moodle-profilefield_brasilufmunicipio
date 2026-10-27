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
 * First fill of the municipality list.
 *
 * @package    profilefield_brasilufmunicipio
 * @copyright  2026 Anderson Blaine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace profilefield_brasilufmunicipio\task;

use profilefield_brasilufmunicipio\api;

/**
 * Fills the municipality list once after installation or upgrade, so the field works before the monthly refresh.
 *
 * It never fails: Behat's site installation runs queued adhoc tasks and dies on one that throws, which would make
 * every such installation depend on IBGE answering. The UFs that could not be refreshed are queued again an hour
 * later instead, up to MAX_ATTEMPTS runs; after that the monthly scheduled task is the next attempt.
 *
 * @package    profilefield_brasilufmunicipio
 * @copyright  2026 Anderson Blaine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class populate_municipios extends \core\task\adhoc_task {
    /** Runs before the task gives the remaining UFs up to the scheduled task. */
    public const MAX_ATTEMPTS = 24;

    /**
     * Refresh the UFs of this run, every UF on the first, and queue the ones that failed for an hour later.
     *
     * Custom data: ufs, the UF codes still to refresh (absent on the first run), and attempt, this run's number.
     *
     * @return void
     */
    public function execute(): void {
        $data = $this->get_custom_data();
        $attempt = (int)($data->attempt ?? 1);
        $failed = api::update_municipios(isset($data->ufs) ? (array)$data->ufs : null);
        if (!$failed) {
            return;
        }
        mtrace(get_string('errorupdatefailed', 'profilefield_brasilufmunicipio', implode(', ', $failed)));
        if ($attempt >= self::MAX_ATTEMPTS) {
            return;
        }
        $retry = new self();
        $retry->set_custom_data(['ufs' => $failed, 'attempt' => $attempt + 1]);
        $retry->set_next_run_time(time() + HOURSECS);
        \core\task\manager::queue_adhoc_task($retry);
    }
}
