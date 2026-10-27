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
 * Brasil UF e Município profile field.
 *
 * @package    profilefield_brasilufmunicipio
 * @copyright  2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use profilefield_brasilufmunicipio\api;

/**
 * A UF select and a municipality select filled from the chosen UF.
 *
 * The value is stored as JSON {uf, codmunicipio, nome}; nome is present only when known. The form draws a hidden
 * integer element under the field's own name, whose value is ignored, and the two selects beside it.
 *
 * @package    profilefield_brasilufmunicipio
 * @copyright  2021 Daniel Neis Araujo <daniel@adapta.online>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_field_brasilufmunicipio extends profile_field_base {
    /**
     * Add the hidden element and the two selects to the form.
     *
     * @param MoodleQuickForm $mform The form.
     * @return void
     */
    public function edit_field_add($mform) {
        global $PAGE;

        $ufs = $this->field->param2 ? api::get_ufs() : $this->get_available_ufs();
        $fieldname = $this->inputname;

        $mform->addElement('hidden', $fieldname, '1');
        $mform->setType($fieldname, PARAM_INT);

        $mform->addElement(
            'select',
            $fieldname . '_uf',
            get_string('uf', 'profilefield_brasilufmunicipio'),
            ['' => get_string('choosedots')] + $ufs
        );
        $mform->setType($fieldname . '_uf', PARAM_TEXT);

        // Field.js replaces the options with the chosen UF's municipalities; the stored one is listed so a frozen
        // (locked) select can still show it.
        $value = $this->get_value();
        $options = ['' => get_string('choosedots')];
        if ($value && $value->codmunicipio !== '' && $value->nome !== '') {
            $options[$value->codmunicipio] = $value->nome;
        }
        $mform->addElement(
            'select',
            $fieldname . '_municipio',
            get_string('municipio', 'profilefield_brasilufmunicipio'),
            $options
        );
        $mform->addHelpButton($fieldname . '_municipio', 'municipio', 'profilefield_brasilufmunicipio');

        // A form redisplayed after a failed validation keeps the municipality the user picked.
        $municipio = optional_param($fieldname . '_municipio', null, PARAM_ALPHANUM) ?? ($value->codmunicipio ?? '');

        $PAGE->requires->js_call_amd('profilefield_brasilufmunicipio/field', 'init', [$municipio, $fieldname]);
    }

    /**
     * Return the field type and null properties, used to clean values sent to the sign-up web service.
     *
     * @return array The param type and the null property.
     */
    public function get_field_properties() {
        return [PARAM_TEXT, NULL_NOT_ALLOWED];
    }

    /**
     * Turn a submission into the value to store.
     *
     * From a form, $data is the hidden element's integer, and the two selects are read from the request: the
     * municipality select has no server-side options, so the form's own data never carries it. A CSV upload or a web
     * service hands over the value itself, which is converted instead. Neither fact alone tells the two apart: the
     * upload form draws the selects too, and a web service may send a number, so the form path needs both.
     *
     * @param mixed $data The value of the field's own element.
     * @param stdClass $datarecord The record that will be saved.
     * @return string The JSON value, or '' when nothing was chosen.
     * @throws moodle_exception When a value handed over by a CSV upload or a web service is not usable.
     */
    public function edit_save_data_preprocess($data, $datarecord) {
        if ($this->is_locked_for_user()) {
            // Core freezes only the hidden element; the selects are still in the request.
            return (string)$this->data;
        }
        $uf = optional_param($this->inputname . '_uf', null, PARAM_TEXT);
        if ($uf !== null && (is_int($data) || ctype_digit((string)$data))) {
            $municipio = optional_param($this->inputname . '_municipio', '', PARAM_TEXT);
            return $this->encode($uf, $municipio, '') ?? '';
        }
        $value = $this->convert_external_data($data);
        if ($value === null) {
            throw new moodle_exception('errorinvalidvalue', 'profilefield_brasilufmunicipio');
        }
        return $value;
    }

    /**
     * Freeze the two selects with the hidden element when the user may not change a locked field.
     *
     * @param MoodleQuickForm $mform The form.
     * @return void
     */
    public function edit_field_set_locked($mform) {
        parent::edit_field_set_locked($mform);
        if ($this->is_locked_for_user()) {
            $mform->hardFreeze([$this->inputname . '_uf', $this->inputname . '_municipio']);
        }
    }

    /**
     * Whether nothing usable is stored; older versions stored '1' for an empty choice.
     *
     * @return bool
     */
    public function is_empty() {
        return $this->get_value() === null;
    }

    /**
     * Convert a value from a CSV upload or a web service into the value to store.
     *
     * Accepts the stored JSON (with the code, the name or both) or 'UF / municipality name'.
     *
     * @param mixed $value The external value.
     * @return string|null The JSON value, '' for an empty value, or null when the value is not usable.
     */
    public function convert_external_data($value) {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }
        $decoded = json_decode($value);
        if (is_object($decoded)) {
            $member = fn(string $name): string => is_scalar($decoded->$name ?? null) ? (string)$decoded->$name : '';
            return $this->encode($member('uf'), $member('codmunicipio'), $member('nome'));
        }
        if (preg_match('~^([A-Za-z]{2})\s*/\s*(.+)$~u', $value, $matches)) {
            return $this->encode(strtoupper($matches[1]), '', trim($matches[2]));
        }
        return null;
    }

    /**
     * Load the stored value into the user object the edit form is filled from.
     *
     * @param stdClass $user The user object.
     * @return void
     */
    public function edit_load_user_data($user) {
        $value = $this->get_value();
        if ($value === null) {
            $user->{$this->inputname} = '';
            $user->{$this->inputname . '_uf'} = '';
            $user->{$this->inputname . '_municipio'} = '';
            return;
        }
        $user->{$this->inputname} = $value->uf . ($value->nome !== '' ? ' / ' . $value->nome : '');
        $user->{$this->inputname . '_uf'} = $value->uf;
        $user->{$this->inputname . '_municipio'} = $value->codmunicipio;
    }

    /**
     * Display the stored value as 'UF / municipality'.
     *
     * @return string Escaped text, or '' when nothing is stored.
     */
    public function display_data() {
        $value = $this->get_value();
        if ($value === null) {
            return '';
        }
        return s($value->nome !== '' ? $value->uf . ' / ' . $value->nome : $value->uf);
    }

    /**
     * Check the UF against the field's available UFs, or an uploaded value against the accepted formats.
     *
     * A required field needs a UF here as well as in the browser. The municipality is required in the browser only:
     * forms that submit through a web service do not have it in the request at validation time.
     *
     * @param stdClass $usernew The submitted user data.
     * @return array Errors keyed by element name.
     */
    public function edit_validate_field($usernew) {
        $errors = [];
        if ($this->is_locked_for_user()) {
            // The value is kept as stored; an error on a frozen select would block the whole form.
            return $errors;
        }
        $ufname = $this->inputname . '_uf';
        if (property_exists($usernew, $ufname)) {
            $uf = (string)$usernew->{$ufname};
            if ($uf !== '' && !in_array($uf, $this->get_available_ufs(), true)) {
                $errors[$ufname] = get_string('errorunavailableuf', 'profilefield_brasilufmunicipio');
            } else if ($uf === '' && $this->requires_choice()) {
                $errors[$ufname] = get_string('required');
            }
        } else if (isset($usernew->{$this->inputname}) && $this->convert_external_data($usernew->{$this->inputname}) === null) {
            // A CSV upload or a web service: the value itself was posted.
            $errors[$this->inputname] = get_string('errorinvalidvalue', 'profilefield_brasilufmunicipio');
        }
        return $errors;
    }

    /**
     * Make both selects required when the user edits their own profile or signs up.
     *
     * @param MoodleQuickForm $mform The form.
     * @return void
     */
    public function edit_field_set_required($mform) {
        if ($this->requires_choice()) {
            $mform->addRule($this->inputname . '_uf', get_string('required'), 'required', null, 'client');
            $mform->addRule($this->inputname . '_municipio', get_string('required'), 'required', null, 'client');
        }
    }

    /**
     * Whether the field is locked for the current user, by core's rule in {@see profile_field_base::edit_field_set_locked()}.
     *
     * @return bool
     */
    private function is_locked_for_user(): bool {
        return $this->is_locked() && !has_capability('moodle/user:update', context_system::instance());
    }

    /**
     * Whether this user must choose: core requires a field only from the user editing their own profile or signing up.
     *
     * @return bool
     */
    private function requires_choice(): bool {
        global $USER;
        return $this->is_required() && ($this->userid == $USER->id || isguestuser());
    }

    /**
     * Build the value to store from a UF and a municipality code or name.
     *
     * The name is taken from the municipality table when the code is found in the UF; a CSV value that gives only the
     * name gets the code the same way. A code the table does not know is kept without a name, so a site whose table
     * is not filled yet still stores the choice.
     *
     * @param string $uf The UF code.
     * @param string $code The IBGE code, or ''.
     * @param string $name The municipality name, or ''.
     * @return string|null The JSON value, '' when no UF was chosen, or null when the UF is not available.
     */
    private function encode(string $uf, string $code, string $name): ?string {
        if ($uf === '') {
            return '';
        }
        if (!in_array($uf, $this->get_available_ufs(), true)) {
            return null;
        }
        if (!api::is_ibgeid($code)) {
            $code = '';
        }
        $record = $code !== '' ? api::get_municipio_by_ibgeid($uf, $code) : api::get_municipio_by_name($uf, $name);
        if ($record) {
            $code = (string)$record->ibgeid;
            $name = $record->municipio;
        } else if ($code !== '') {
            $name = '';
        }
        $value = ['uf' => $uf, 'codmunicipio' => $code];
        if ($name !== '') {
            $value['nome'] = $name;
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Decode the stored value.
     *
     * Values uploaded with only a name get their code from the municipality table here, so the select can show them.
     *
     * @return stdClass|null Object with uf, codmunicipio and nome (each a string), or null when nothing usable is stored;
     *         older versions stored '1' for an empty choice.
     */
    private function get_value(): ?stdClass {
        $decoded = json_decode((string)$this->data);
        if (!is_object($decoded) || !isset($decoded->uf) || !array_key_exists((string)$decoded->uf, api::get_ufs())) {
            return null;
        }
        $value = (object)[
            'uf' => (string)$decoded->uf,
            'codmunicipio' => (string)($decoded->codmunicipio ?? ''),
            'nome' => (string)($decoded->nome ?? ''),
        ];
        if ($value->codmunicipio === '' && ($record = api::get_municipio_by_name($value->uf, $value->nome))) {
            $value->codmunicipio = (string)$record->ibgeid;
        }
        return $value;
    }

    /**
     * Get the UFs the field accepts: the ones the administrator chose, or every UF when none was chosen.
     *
     * @return array UF code => UF code
     */
    private function get_available_ufs(): array {
        if (empty($this->field->param1)) {
            return api::get_ufs();
        }
        $ufs = explode(',', $this->field->param1);
        return array_combine($ufs, $ufs);
    }
}
