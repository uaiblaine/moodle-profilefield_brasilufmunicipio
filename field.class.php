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
 * Static Text profile field.
 *
 * @package    profilefield_brasilufmunicipio
 * @copyright  2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class profile_field_brasilufmunicipio
 *
 * @copyright  2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_field_brasilufmunicipio extends profile_field_base {

    /**
     * @var array List o UFs.
     */
    private $ufs = [];

    /**
     * Add fields for editing a brasilufmunicipio profile field.
     * @param moodleform $mform
     */
    public function edit_field_add($mform) {
        global $PAGE;

        if ($this->field->param2) {
            $ufs = $this->get_all_ufs();
        } else {
            $ufs = $this->get_available_ufs();
        }
        $this->ufs = array_merge([''  => get_string('choosedots')], $ufs);

        $fieldname = $this->inputname;

        $mform->addElement('hidden', $fieldname, '1', '');
        $mform->setType($fieldname, PARAM_INT);

        $fieldnameuf = $fieldname . '_uf';
        $mform->addElement('select', $fieldnameuf, get_string('uf', 'profilefield_brasilufmunicipio'), $this->ufs);
        $mform->setType($fieldnameuf, PARAM_TEXT);

        $fieldnamemunicipio = $fieldname . '_municipio';
        $mform->addElement('select',
            $fieldnamemunicipio, get_string('municipio', 'profilefield_brasilufmunicipio'), [], '');
        $mform->addHelpButton($fieldnamemunicipio, 'municipio', 'profilefield_brasilufmunicipio');

        if (!empty($this->data)) {
            $data = json_decode($this->data);
            if (empty($data->codmunicipio)) {
                $municipio = '';
                global $USER;
                if (!empty($data->uf)) {
                    $url = 'https://servicodados.ibge.gov.br/api/v1/localidades/estados/';
                    $curl = new \curl();
                    $res = $curl->get($url);
                    if ($res) {
                        $res = json_decode($res);
                        foreach ($res as $uf) {
                            if ($uf->sigla == $data->uf) {
                                $url = 'https://servicodados.ibge.gov.br/api/v1/localidades/estados/'.$uf->id .'/municipios';
                                $curl = new \curl();
                                $res2 = $curl->get($url);
                                if ($res2) {
                                    $res2 = json_decode($res2);
                                    foreach ($res2 as $mun) {
                                        if (isset($mun->nome) && isset($data->nome) && ($mun->nome == $data->nome)) {
                                            $municipio = $mun->id;
                                            break(2);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $municipio = $data->codmunicipio;
            }
        } else {
            $municipio = optional_param($fieldnamemunicipio, '', PARAM_TEXT);
        }

        $PAGE->requires->js_call_amd('profilefield_brasilufmunicipio/field', 'init', [$municipio, $fieldname]);
    }

    /**
     * Return the field type and null properties.
     * This will be used for validating the data submitted by a user.
     *
     * @return array the param type and null property
     * @since Moodle 3.2
     */
    public function get_field_properties() {
        return array(PARAM_TEXT, NULL_NOT_ALLOWED);
    }

    /**
     * Saves the data coming from form
     *
     * @param stdClass $data data coming from the form
     * @param stdClass $datarecord The object that will be used to save the record
     */
    public function edit_save_data_preprocess($data, $datarecord) {
        $url = 'https://servicodados.ibge.gov.br/api/v1/localidades/municipios/';
        $uf = optional_param($this->inputname . '_uf', '', PARAM_TEXT);
        $mun = optional_param($this->inputname . '_municipio', '', PARAM_TEXT);
        if (empty($uf) && empty($mun)) {
            return $data;
        } else {
            $curl = new \curl();
            $res = $curl->get($url . $mun);
            $data = ['uf' => $uf, 'codmunicipio' => $mun];
            if ($res) {
                if ($res = json_decode($res)) {
                   $data['nome'] = $res->nome;
               }
            }
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Sets the default data for the field in the form object
     * @param  moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_default($mform) {
        $mform->setDefault($this->inputname, 1);
    }

    /**
     * When passing the user object to the form class for the edit profile page
     * we should load the key for the saved data
     *
     * Overwrites the base class method.
     *
     * @param stdClass $user User object.
     */
    public function edit_load_user_data($user) {
        if (empty($this->data)) {
            $user->{$this->inputname} = '';
        } else {
            $data = json_decode($this->data);
            if ($data) {
                $user->{$this->inputname} = $data->uf;
                $user->{$this->inputname . '_uf'} = $data->uf;
                if (!empty($data->codmunicipio)) {
                    $user->{$this->inputname . '_municipio'} = $data->codmunicipio;
                }
                if (!empty($data->nome)) {
                    $user->{$this->inputname} .= ' / ' . $data->nome;
                }
            } else {
                $user->{$this->inputname} = '';
                $user->{$this->inputname . '_uf'} = '';
                $user->{$this->inputname . '_municipio'} = '';
            }
        }
    }

    /**
     * Display the data for this field
     * @return string
     */
    public function display_data() {
        $data = json_decode($this->data);
        $display = '';
        if (!empty($data->uf)) {
            $display .= $data->uf . ' / ';
        }
        if (!empty($data->nome)) {
            $display .= $data->nome;
        }
        return $display;
    }

    public function edit_validate_field($data) {
        $errors = [];
        $fieldname = $this->inputname . '_uf';
        if (!empty($data->{$fieldname}) && !in_array($data->{$fieldname}, $this->get_available_ufs())) {
            $errors[$fieldname] = get_string('errorunavailableuf', 'profilefield_brasilufmunicipio');
        }
        return $errors;
    }

    /**
     * Sets the required flag for the field in the form object
     *
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_required($mform) {
        global $USER;
        if ($this->is_required() && ($this->userid == $USER->id || isguestuser())) {
            $mform->addRule($this->inputname.'_uf', get_string('required'), 'required', null, 'client');
            $mform->addRule($this->inputname.'_municipio', get_string('required'), 'required', null, 'client');
        }
    }

    private function get_available_ufs() {
        $availableufs = [];
       if (is_null($this->field->param1)) {
               return $this->get_all_ufs();
       }
        if ($ufs = explode(',', $this->field->param1)) {
            foreach ($ufs as $f) {
                $availableufs[$f] = $f;
            }
        }
        return $availableufs;
    }

    private function get_all_ufs() {
        return [
            'AC' => 'AC',
            'AL' => 'AL',
            'AM' => 'AM',
            'AP' => 'AP',
            'BA' => 'BA',
            'CE' => 'CE',
            'DF' => 'DF',
            'ES' => 'ES',
            'GO' => 'GO',
            'MA' => 'MA',
            'MG' => 'MG',
            'MS' => 'MS',
            'MT' => 'MT',
            'PA' => 'PA',
            'PB' => 'PB',
            'PE' => 'PE',
            'PI' => 'PI',
            'PR' => 'PR',
            'RJ' => 'RJ',
            'RN' => 'RN',
            'RO' => 'RO',
            'RR' => 'RR',
            'RS' => 'RS',
            'SC' => 'SC',
            'SE' => 'SE',
            'SP' => 'SP',
            'TO' => 'TO'
        ];
    }
}
