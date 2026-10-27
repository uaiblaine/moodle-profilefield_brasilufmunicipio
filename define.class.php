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
 * Brasil UF e Município profile field definition.
 *
 * @package    profilefield_brasilufmunicipio
 * @copyright  2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Settings of a Brasil UF e Município field: the UFs it offers and whether the select lists every UF.
 *
 * @package    profilefield_brasilufmunicipio
 * @copyright  2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_define_brasilufmunicipio extends profile_define_base {
    /**
     * Add the settings of this field type to the field definition form.
     *
     * param1 holds the available UFs, comma-separated; the multiple select splits that string itself when the form
     * is filled. param2 lists every UF in the select while only the available ones are accepted.
     *
     * @param MoodleQuickForm $form The form.
     * @return void
     */
    public function define_form_specific($form) {
        $select = $form->addElement(
            'select',
            'param1',
            get_string('availableufs', 'profilefield_brasilufmunicipio'),
            \profilefield_brasilufmunicipio\api::get_ufs(),
            ['size' => 13]
        );
        $select->setMultiple(true);
        $form->addHelpButton('param1', 'availableufs', 'profilefield_brasilufmunicipio');

        $form->addElement('advcheckbox', 'param2', get_string('showall', 'profilefield_brasilufmunicipio'));
        $form->addHelpButton('param2', 'showall', 'profilefield_brasilufmunicipio');
    }

    /**
     * Store the chosen UFs as a comma-separated list; none chosen means every UF.
     *
     * @param array|stdClass $data Data from the field definition form.
     * @return array|stdClass The data to save.
     */
    public function define_save_preprocess($data) {
        if (empty($data->param1)) {
            $data->param1 = '';
        } else {
            $data->param1 = implode(',', $data->param1);
        }
        return $data;
    }
}
