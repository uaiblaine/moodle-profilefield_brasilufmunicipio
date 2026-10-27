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
 * Web service that lists the municipalities of a UF.
 *
 * @package    profilefield_brasilufmunicipio
 * @copyright  2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace profilefield_brasilufmunicipio\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use profilefield_brasilufmunicipio\api;

/**
 * Lists the municipalities of a UF for the field's municipality select.
 *
 * Declared without login in db/services.php: the field is shown on the sign-up page, and the list is public IBGE data.
 *
 * @package    profilefield_brasilufmunicipio
 * @category   external
 * @copyright  2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_municipios extends external_api {
    /**
     * Describe the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'uf' => new external_value(PARAM_ALPHA, 'UF code'),
        ]);
    }

    /**
     * List the municipalities of a UF.
     *
     * @param string $uf The UF code.
     * @return array Municipalities sorted by name; empty for an unknown UF.
     */
    public static function execute(string $uf): array {
        ['uf' => $uf] = self::validate_parameters(self::execute_parameters(), ['uf' => $uf]);
        return array_values(array_map(fn(\stdClass $record): array => [
            'id' => (int)$record->id,
            'uf' => $record->uf,
            'ibgeid' => (string)$record->ibgeid,
            'municipio' => $record->municipio,
        ], api::get_municipios($uf)));
    }

    /**
     * Describe the result.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Record id'),
                'uf' => new external_value(PARAM_ALPHA, 'UF code'),
                'ibgeid' => new external_value(PARAM_ALPHANUM, 'IBGE code of the municipality'),
                'municipio' => new external_value(PARAM_TEXT, 'Municipality name'),
            ])
        );
    }
}
