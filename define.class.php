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
 * Text profile field definition.
 *
 * @package    profilefield_brasilufmunicipio
 * @copyright  2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class profile_define_brasilufmunicipio
 *
 * @copyright  2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_define_brasilufmunicipio extends profile_define_base {

    /**
     * Add elements for creating/editing a brasilufmunicipio profile field.
     * @param moodleform $form
     */
    public function define_form_specific($form) {
        global $DB;
        $options = [
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
        $select = $form->createElement('select', 'param1',
            get_string('availableufs', 'profilefield_brasilufmunicipio'),
            $options, ['multiple' => true, 'size' => 13]);
        $select->setMultiple(true);

        $form->addElement($select);
        $id = optional_param('id', 0, PARAM_INT);
        if ($id) {
            if ($availableufs = $DB->get_field('user_info_field', 'param1', ['id' => $id])) {
                $select->setSelected($availableufs);
            }
        }

        $form->addElement('advcheckbox', 'param2',
            get_string('showall', 'profilefield_brasilufmunicipio'));
    }

    /**
     * Preprocess data from the add/edit profile field form before it is saved.
     *
     * This method is a hook for the child classes to overwrite.
     *
     * @param array|stdClass $data from the add/edit profile field form
     * @return array|stdClass processed data object
     */
    public function define_save_preprocess($data) {
        if (!empty($data->param1)) {
            $data->param1 = implode(',', $data->param1);
        }
        return $data;
    }
}
