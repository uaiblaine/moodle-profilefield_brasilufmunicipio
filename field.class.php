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

    private $ufs = [
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

    /**
     * Add fields for editing a text profile field.
     * @param moodleform $mform
     */
    public function edit_field_add($mform) {
        global $PAGE;

        if (!empty($this->data)) {
            $data = json_decode($this->data);
            $municipio = $data->municipio;
        } else {
            $municipio = null;
        }

        $mform->addElement('hidden', 'profile_field_ufmunicipio', 1);
        $mform->setType('profile_field_ufmunicipio', PARAM_INT);

        $mform->addElement('select', 'profile_field_ufmunicipio[uf]', get_string('uf', 'profilefield_brasilufmunicipio'), $this->ufs);
        $mform->setType('profile_field_ufmunicipio[uf]', PARAM_TEXT);

        $mform->addElement('select', 'profile_field_ufmunicipio[municipio]', get_string('municipio', 'profilefield_brasilufmunicipio'), [], 'disabled');
        $mform->addHelpButton('profile_field_ufmunicipio[municipio]', 'municipio', 'profilefield_brasilufmunicipio');

        $PAGE->requires->js_call_amd('profilefield_brasilufmunicipio/field', 'init', [$municipio]);
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
     */
    public function edit_save_data_preprocess($data, $datarecord) {
        $url = 'https://servicodados.ibge.gov.br/api/v1/localidades/municipios/';
        $curl = new \curl();
        $res = $curl->get($url . $data['municipio']);
        $display = '';
        if ($res) {
            $res = json_decode($res);
            $data['nome'] = $res->nome;
        }
        return json_encode($data);
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
        if (!empty($this->data)) {
            $data = json_decode($this->data);
            if ($data) {
                $user->profile_field_ufmunicipio = ['uf' => $data->uf, 'municipio' => $data->municipio];
            }
        }
    }

    /**
     * Display the data for this field
     * @return string
     */
    public function display_data() {
        $data = json_decode($this->data);
        $display = $data->uf . ' / ' . $data->nome;
        return $display;
    }
}
