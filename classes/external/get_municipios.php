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

namespace profilefield_brasilufmunicipio\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');

/**
 * Provides the profilefield_brasilufmunicipio_get_municipios external function.
 *
 * @package     profilefield_brasilufmunicipio
 * @category    external
 * @copyright   2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_municipios extends \core_external\external_api {

    /**
     * Describes the external function parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): \core_external\external_function_parameters {

        return new \core_external\external_function_parameters([
            'uf' => new \core_external\external_value(PARAM_TEXT, 'UF to get municipios', VALUE_REQUIRED),
        ]);
    }

    /**
     * Finds users with the identity matching the given uf.
     *
     * @param string $uf The search request.
     * @return array
     */
    public static function execute(string $uf): array {
        global $DB, $CFG;

        $params = \core_external\external_api::validate_parameters(self::execute_parameters(), [
            'uf' => $uf,
        ]);
        $uf = $params['uf'];

        $url = 'https://servicodados.ibge.gov.br/api/v1/localidades/estados/';
        $curl = new \curl();
        $res = $curl->get($url . $uf . '/municipios');
        $municipios = [];
        if ($res) {
            $res = json_decode($res);
            foreach ($res as $m) {
                $municipios[] = ['id' => $m->id, 'name' => $m->nome];
            }
        }
        return $municipios;

    }

    /**
     * Describes the external function result value.
     *
     * @return external_description
     */
    public static function execute_returns(): \core_external\external_description {
        return new \core_external\external_multiple_structure(
            new \core_external\external_single_structure([
                'id' => new \core_external\external_value(PARAM_TEXT, 'ID of the Município.'),
                'name' => new \core_external\external_value(PARAM_TEXT, 'Name of the Município.')
            ])
        );
    }
}
