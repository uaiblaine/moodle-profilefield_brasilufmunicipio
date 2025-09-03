<?php

namespace profilefield_brasilufmunicipio;

require_once($CFG->libdir.'/filelib.php');

class api {

    private static function get_municipios_from_ibge(string $uf): array {
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

    public static function get_municipios(string $uf) {
        global $DB;
        return $DB->get_records('profilefield_brasilufmunicipio', ['uf' => $uf]);
    }

    public static function get_municipio_by_ibgeid($id) {
        global $DB;
        return $DB->get_record('profilefield_brasilufmunicipio', ['ibgeid' => $id]);
    }

    public static function update_municipios() {
        global $DB;
        $ufs = [
            'AC',
            'AL',
            'AM',
            'AP',
            'BA',
            'CE',
            'DF',
            'ES',
            'GO',
            'MA',
            'MG',
            'MS',
            'MT',
            'PA',
            'PB',
            'PE',
            'PI',
            'PR',
            'RJ',
            'RN',
            'RO',
            'RR',
            'RS',
            'SC',
            'SE',
            'SP',
            'TO'
        ];
        $tablename = 'profilefield_brasilufmunicipio';
        foreach ($ufs as $uf) {
            $municipios = self::get_municipios_from_ibge($uf);
            $transaction = $DB->start_delegated_transaction();
            $DB->execute("DELETE FROM {{$tablename}} WHERE uf = :uf", ['uf' => $uf]);
            foreach ($municipios as $m) {
                $data = (object)[
                    'uf' => $uf,
                    'ibgeid' => $m['id'],
                    'municipio' => $m['name']
                ];
                $DB->insert_record_raw($tablename, $data, false, true);
            }
            $transaction->allow_commit();
        }
    }
}
