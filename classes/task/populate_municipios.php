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
 * @package    profilefield_brasilufmunicipio
 * @copyright  2026 Anderson Blaine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class populate_municipios extends \core\task\adhoc_task {
    /**
     * Refresh every UF; a UF whose refresh failed fails the task, so the task queue retries it.
     *
     * @return void
     * @throws \moodle_exception When the refresh of any UF failed.
     */
    public function execute(): void {
        $failed = api::update_municipios();
        if ($failed) {
            throw new \moodle_exception('errorupdatefailed', 'profilefield_brasilufmunicipio', '', implode(', ', $failed));
        }
    }
}
