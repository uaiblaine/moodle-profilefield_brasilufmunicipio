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
 * Access to the municipality list and its refresh from IBGE.
 *
 * @package    profilefield_brasilufmunicipio
 * @copyright  2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace profilefield_brasilufmunicipio;

/**
 * Reads the municipality table and refreshes it from the IBGE localities API.
 *
 * @package    profilefield_brasilufmunicipio
 * @copyright  2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {
    /** The plugin's table of municipalities. */
    private const TABLE = 'profilefield_brasilufmunicipio';

    /** IBGE endpoint; the UF code and '/municipios' are appended. */
    private const IBGE_URL = 'https://servicodados.ibge.gov.br/api/v1/localidades/estados/';

    /** The 27 federative units, in the order the selects show them. */
    private const UFS = [
        'AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA',
        'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO',
    ];

    /**
     * Get every UF, as select options.
     *
     * @return array UF code => UF code
     */
    public static function get_ufs(): array {
        return array_combine(self::UFS, self::UFS);
    }

    /**
     * Whether a string can be an IBGE municipality code: the column is an integer of 10 digits, and PostgreSQL fails
     * the whole query when a value compared with it is not a number or is out of its range.
     *
     * @param string $code The candidate.
     * @return bool
     */
    public static function is_ibgeid(string $code): bool {
        return preg_match('/^[0-9]{1,10}$/', $code) === 1;
    }

    /**
     * Get the municipalities of a UF, sorted by name.
     *
     * @param string $uf The UF code.
     * @return array Records with id, uf, ibgeid and municipio, keyed by id; empty for an unknown UF.
     */
    public static function get_municipios(string $uf): array {
        global $DB;
        return $DB->get_records(self::TABLE, ['uf' => $uf], 'municipio ASC');
    }

    /**
     * Find a municipality by its IBGE code within a UF.
     *
     * @param string $uf The UF code.
     * @param string $ibgeid The IBGE code; anything {@see is_ibgeid()} refuses finds nothing.
     * @return \stdClass|null The record, or null.
     */
    public static function get_municipio_by_ibgeid(string $uf, string $ibgeid): ?\stdClass {
        global $DB;
        if (!self::is_ibgeid($ibgeid)) {
            return null;
        }
        // A list refreshed by two overlapping runs of an older version may hold a code twice.
        return $DB->get_record(self::TABLE, ['uf' => $uf, 'ibgeid' => $ibgeid], '*', IGNORE_MULTIPLE) ?: null;
    }

    /**
     * Find a municipality by its name within a UF, ignoring case.
     *
     * Values uploaded in a CSV file may carry the name without the code.
     *
     * @param string $uf The UF code.
     * @param string $name The municipality name.
     * @return \stdClass|null The first match, or null.
     */
    public static function get_municipio_by_name(string $uf, string $name): ?\stdClass {
        global $DB;
        if ($name === '') {
            return null;
        }
        $select = 'uf = :uf AND ' . $DB->sql_equal('municipio', ':name', false);
        $records = $DB->get_records_select(self::TABLE, $select, ['uf' => $uf, 'name' => $name], 'id ASC', '*', 0, 1);
        return reset($records) ?: null;
    }

    /**
     * Replace each UF's municipalities with the list IBGE returns now.
     *
     * A UF whose request fails keeps its current rows, so an IBGE outage never empties the list. Runs are serialised:
     * the adhoc and the scheduled task may overlap, and two interleaved replacements of one UF leave its rows twice.
     *
     * @return array The UF codes whose refresh failed; every UF when another run held the lock throughout.
     */
    public static function update_municipios(): array {
        $lock = \core\lock\lock_config::get_lock_factory('profilefield_brasilufmunicipio')->get_lock('update_municipios', 600);
        if (!$lock) {
            return self::UFS;
        }
        $failed = [];
        try {
            foreach (self::UFS as $uf) {
                if (!self::update_uf($uf)) {
                    $failed[] = $uf;
                }
            }
        } finally {
            $lock->release();
        }
        return $failed;
    }

    /**
     * Replace one UF's municipalities with the list IBGE returns now.
     *
     * @param string $uf The UF code.
     * @return bool False when the answer was unusable and the rows were kept.
     */
    private static function update_uf(string $uf): bool {
        global $DB;
        $municipios = self::fetch_municipios($uf);
        if ($municipios === null) {
            return false;
        }
        $transaction = $DB->start_delegated_transaction();
        try {
            $DB->delete_records(self::TABLE, ['uf' => $uf]);
            $DB->insert_records(self::TABLE, array_map(fn(array $m): array => ['uf' => $uf] + $m, $municipios));
            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }
        return true;
    }

    /**
     * Fetch a UF's municipalities from IBGE.
     *
     * @param string $uf The UF code.
     * @return array|null Rows with ibgeid and municipio, or null when the request or its answer is unusable.
     */
    private static function fetch_municipios(string $uf): ?array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $curl = new \curl();
        $body = $curl->get(self::IBGE_URL . $uf . '/municipios', [], ['CURLOPT_TIMEOUT' => 30]);
        if ($curl->get_errno() || ($curl->get_info()['http_code'] ?? 0) != 200) {
            return null;
        }
        $list = json_decode($body);
        if (!is_array($list) || !$list) {
            return null;
        }
        $municipios = [];
        foreach ($list as $item) {
            if (!isset($item->id, $item->nome) || !is_int($item->id)) {
                return null;
            }
            $municipios[] = ['ibgeid' => $item->id, 'municipio' => $item->nome];
        }
        return $municipios;
    }
}
