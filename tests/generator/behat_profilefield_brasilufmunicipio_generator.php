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
 * Behat data generator for the Brasil UF e Município profile field.
 *
 * @package    profilefield_brasilufmunicipio
 * @category   test
 * @copyright  2026 Anderson Blaine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_profilefield_brasilufmunicipio_generator extends behat_generator_base {
    /**
     * Get the entities Behat can create with this generator.
     *
     * @return array Entity name => definition.
     */
    protected function get_creatable_entities(): array {
        return [
            'municipalities' => [
                'singular' => 'municipality',
                'datagenerator' => 'municipality',
                'required' => ['uf', 'ibgeid', 'municipio'],
            ],
        ];
    }
}
