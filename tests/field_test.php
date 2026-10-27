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

namespace profilefield_brasilufmunicipio;

/**
 * Tests for saving, loading and showing the Brasil UF e Município profile field.
 *
 * Coverage is declared in this docblock rather than with a CoversClass attribute, because the moodle-cs of the
 * Moodle 4.5 leg cannot read attributes.
 *
 * @package    profilefield_brasilufmunicipio
 * @copyright  2026 Anderson Blaine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \profile_field_brasilufmunicipio
 */
final class field_test extends \advanced_testcase {
    /** IBGE code of Florianópolis. */
    private const FLORIANOPOLIS = '4205407';

    /** IBGE code of São Paulo. */
    private const SAOPAULO = '3550308';

    /**
     * Load the profile field library and the field class.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        parent::setUpBeforeClass();
        require_once($CFG->dirroot . '/user/profile/lib.php');
        require_once($CFG->dirroot . '/user/profile/field/brasilufmunicipio/field.class.php');
    }

    /**
     * Fill the municipality table with two municipalities.
     */
    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();
        // A site may have filled the table from IBGE already.
        $DB->delete_records('profilefield_brasilufmunicipio');
        $DB->insert_record('profilefield_brasilufmunicipio', ['uf' => 'SC', 'ibgeid' => self::FLORIANOPOLIS,
            'municipio' => 'Florianópolis']);
        $DB->insert_record('profilefield_brasilufmunicipio', ['uf' => 'SP', 'ibgeid' => self::SAOPAULO,
            'municipio' => 'São Paulo']);
    }

    /**
     * Create a field of this type and a user, and load the field for the user.
     *
     * @param array $extra Further user_info_field columns.
     * @return \profile_field_brasilufmunicipio The field.
     */
    private function create_field(array $extra = []): \profile_field_brasilufmunicipio {
        $field = $this->getDataGenerator()->create_custom_profile_field($extra + [
            'datatype' => 'brasilufmunicipio',
            'shortname' => 'ufmunicipio',
            'name' => 'UF e município',
        ]);
        $user = $this->getDataGenerator()->create_user();
        return new \profile_field_brasilufmunicipio((int)$field->id, (int)$user->id);
    }

    /**
     * Save through the field's own save, posting the two selects as the edit form does.
     *
     * @param \profile_field_brasilufmunicipio $field The field.
     * @param string $uf Value of the UF select.
     * @param string $municipio Value of the municipality select.
     * @return string The stored value.
     */
    private function submit_form(\profile_field_brasilufmunicipio $field, string $uf, string $municipio): string {
        $_POST[$field->inputname . '_uf'] = $uf;
        $_POST[$field->inputname . '_municipio'] = $municipio;
        // The hidden element submits an integer, which the field ignores.
        $field->edit_save_data((object)['id' => $field->userid, $field->inputname => 1]);
        return $this->stored($field);
    }

    /**
     * Read back what the field stored for its user.
     *
     * @param \profile_field_brasilufmunicipio $field The field.
     * @return string The stored data.
     */
    private function stored(\profile_field_brasilufmunicipio $field): string {
        global $DB;
        return (string)$DB->get_field('user_info_data', 'data', ['userid' => $field->userid, 'fieldid' => $field->fieldid]);
    }

    /**
     * Store a value directly and reload the field.
     *
     * @param \profile_field_brasilufmunicipio $field The field.
     * @param string $data The value to store.
     * @return \profile_field_brasilufmunicipio The reloaded field.
     */
    private function with_stored(\profile_field_brasilufmunicipio $field, string $data): \profile_field_brasilufmunicipio {
        global $DB;
        $DB->insert_record('user_info_data', ['userid' => $field->userid, 'fieldid' => $field->fieldid, 'data' => $data]);
        return new \profile_field_brasilufmunicipio($field->fieldid, $field->userid);
    }

    /**
     * A UF without a municipality stores the UF and an empty code, without querying the integer column with ''.
     */
    public function test_uf_without_municipio_is_saved(): void {
        $field = $this->create_field();

        $this->assertSame('{"uf":"SC","codmunicipio":""}', $this->submit_form($field, 'SC', ''));
    }

    /**
     * A known municipality stores its name beside the UF and the code.
     */
    public function test_known_municipio_stores_its_name(): void {
        $field = $this->create_field();

        $this->assertSame(
            '{"uf":"SC","codmunicipio":"4205407","nome":"Florianópolis"}',
            $this->submit_form($field, 'SC', self::FLORIANOPOLIS)
        );
    }

    /**
     * A code of another UF is kept without a name, as an unknown code is.
     */
    public function test_code_of_another_uf_gets_no_name(): void {
        $field = $this->create_field();

        $this->assertSame('{"uf":"SC","codmunicipio":"3550308"}', $this->submit_form($field, 'SC', self::SAOPAULO));
    }

    /**
     * A code that is not a number is dropped rather than queried.
     */
    public function test_non_numeric_code_is_dropped(): void {
        $field = $this->create_field();

        $this->assertSame('{"uf":"SC","codmunicipio":""}', $this->submit_form($field, 'SC', '42abc'));
    }

    /**
     * With both selects empty the field stores '', not the hidden element's '1', so it reads as empty.
     */
    public function test_nothing_chosen_stores_empty_string(): void {
        $field = $this->create_field();

        $this->assertSame('', $this->submit_form($field, '', ''));
        $this->assertTrue((new \profile_field_brasilufmunicipio($field->fieldid, $field->userid))->is_empty());
    }

    /**
     * A UF outside the field's available UFs is not stored.
     */
    public function test_unavailable_uf_is_not_stored(): void {
        $field = $this->create_field(['param1' => 'SC']);

        $this->assertSame('', $this->submit_form($field, 'SP', self::SAOPAULO));
        // The control: an available UF is stored.
        $this->assertStringStartsWith('{"uf":"SC"', $this->submit_form($field, 'SC', self::FLORIANOPOLIS));
    }

    /**
     * A CSV upload or a web service posts no selects: the value itself is converted and stored.
     */
    public function test_value_without_selects_is_converted(): void {
        $field = $this->create_field();
        $this->assertArrayNotHasKey($field->inputname . '_uf', $_POST);

        $field->edit_save_data((object)['id' => $field->userid, $field->inputname => '{"uf":"SC","codmunicipio":"4205407"}']);

        $this->assertSame('{"uf":"SC","codmunicipio":"4205407","nome":"Florianópolis"}', $this->stored($field));
    }

    /**
     * The upload form draws the selects too: with them in the request, the uploaded value is still what is stored.
     */
    public function test_uploaded_value_wins_over_the_upload_forms_selects(): void {
        $field = $this->create_field();
        $_POST[$field->inputname . '_uf'] = '';
        $_POST[$field->inputname . '_municipio'] = '';

        $field->edit_save_data((object)['id' => $field->userid, $field->inputname => 'SP / São Paulo']);

        $this->assertSame('{"uf":"SP","codmunicipio":"3550308","nome":"São Paulo"}', $this->stored($field));
    }

    /**
     * A web service value that is not usable is refused rather than stored as empty.
     */
    public function test_unusable_external_value_is_refused(): void {
        $field = $this->with_stored($this->create_field(), '{"uf":"SC","codmunicipio":"4205407","nome":"Florianópolis"}');

        try {
            $field->edit_save_data((object)['id' => $field->userid, $field->inputname => 'Florianópolis']);
            $this->fail('An unusable value was accepted.');
        } catch (\moodle_exception $e) {
            $this->assertSame('errorinvalidvalue', $e->errorcode);
        }
        $this->assertSame('{"uf":"SC","codmunicipio":"4205407","nome":"Florianópolis"}', $this->stored($field));
    }

    /**
     * A user may not change a locked field, though the selects are in the request.
     */
    public function test_locked_field_keeps_its_value(): void {
        $stored = '{"uf":"SC","codmunicipio":"4205407","nome":"Florianópolis"}';
        $field = $this->with_stored($this->create_field(['locked' => 1]), $stored);
        $this->setUser($field->userid);

        $this->assertSame($stored, $this->submit_form($field, 'SP', self::SAOPAULO));
        // The control: an administrator may.
        $this->setAdminUser();
        $this->assertStringStartsWith('{"uf":"SP"', $this->submit_form($field, 'SP', self::SAOPAULO));
    }

    /**
     * A locked field whose stored UF is no longer available does not block the user's form.
     */
    public function test_locked_field_skips_validation(): void {
        $field = $this->create_field(['locked' => 1, 'param1' => 'SC']);
        $name = $field->inputname;
        $this->setUser($field->userid);

        $this->assertSame([], $field->edit_validate_field((object)[$name => 0, $name . '_uf' => 'SP']));
        // The control: an administrator is told.
        $this->setAdminUser();
        $this->assertArrayHasKey($name . '_uf', $field->edit_validate_field((object)[$name => 0, $name . '_uf' => 'SP']));
    }

    /**
     * A code too long for the integer column is dropped rather than queried.
     */
    public function test_overlong_code_is_dropped(): void {
        $field = $this->create_field();

        $this->assertSame('{"uf":"SC","codmunicipio":""}', $this->submit_form($field, 'SC', str_repeat('9', 20)));
    }

    /**
     * External values in each accepted format, and the ones refused.
     *
     * @dataProvider external_values_provider
     * @param string $value The external value.
     * @param string|null $expected The converted value, or null when refused.
     */
    public function test_convert_external_data(string $value, ?string $expected): void {
        $field = $this->create_field(['param1' => 'SC,SP']);

        $this->assertSame($expected, $field->convert_external_data($value));
    }

    /**
     * External values and what they convert to.
     *
     * @return array Test cases.
     */
    public static function external_values_provider(): array {
        return [
            'empty' => ['', ''],
            'json with code' => [
                '{"uf":"SP","codmunicipio":"3550308"}',
                '{"uf":"SP","codmunicipio":"3550308","nome":"São Paulo"}',
            ],
            'json with name only' => [
                '{"uf":"SC","nome":"florianópolis"}',
                '{"uf":"SC","codmunicipio":"4205407","nome":"Florianópolis"}',
            ],
            'json with an unknown name' => [
                '{"uf":"SC","nome":"Lugar Nenhum"}',
                '{"uf":"SC","codmunicipio":"","nome":"Lugar Nenhum"}',
            ],
            'uf and name' => ['sc / Florianópolis', '{"uf":"SC","codmunicipio":"4205407","nome":"Florianópolis"}'],
            'unavailable uf' => ['{"uf":"RJ","codmunicipio":"3304557"}', null],
            'not a value' => ['Florianópolis', null],
            'legacy placeholder' => ['1', null],
            'json with a list for a member' => ['{"uf":["SC"]}', ''],
        ];
    }

    /**
     * A CSV upload validates the uploaded value; the edit form validates the chosen UF.
     */
    public function test_validation(): void {
        $field = $this->create_field(['param1' => 'SC']);
        $name = $field->inputname;

        $this->assertArrayHasKey($name, $field->edit_validate_field((object)[$name => 'Florianópolis']));
        $this->assertSame([], $field->edit_validate_field((object)[$name => 'SC / Florianópolis']));
        $this->assertArrayHasKey($name . '_uf', $field->edit_validate_field((object)[$name => 0, $name . '_uf' => 'SP']));
        $this->assertSame([], $field->edit_validate_field((object)[$name => 0, $name . '_uf' => 'SC']));
    }

    /**
     * A required field needs a UF from the user editing their own profile, and not from an administrator.
     */
    public function test_required_uf(): void {
        $field = $this->create_field(['required' => 1]);
        $name = $field->inputname;
        $this->setUser($field->userid);

        $this->assertArrayHasKey($name . '_uf', $field->edit_validate_field((object)[$name => 0, $name . '_uf' => '']));
        $this->setAdminUser();
        $this->assertSame([], $field->edit_validate_field((object)[$name => 0, $name . '_uf' => '']));
    }

    /**
     * A field saved with no UF chosen offers every UF.
     */
    public function test_no_available_uf_means_every_uf(): void {
        $field = $this->create_field(['param1' => '']);

        $this->assertSame([], $field->edit_validate_field((object)[$field->inputname => 0, $field->inputname . '_uf' => 'TO']));
    }

    /**
     * The stored value fills the edit form, including a code found by name for a value uploaded without one.
     */
    public function test_load_user_data(): void {
        $field = $this->with_stored($this->create_field(), '{"uf":"SC","codmunicipio":"","nome":"Florianópolis"}');
        $user = new \stdClass();

        $field->edit_load_user_data($user);

        $this->assertSame('SC / Florianópolis', $user->{$field->inputname});
        $this->assertSame('SC', $user->{$field->inputname . '_uf'});
        $this->assertSame(self::FLORIANOPOLIS, $user->{$field->inputname . '_municipio'});
    }

    /**
     * A row holding the '1' that older versions stored for an empty choice loads as an empty selection.
     */
    public function test_legacy_one_loads_as_empty(): void {
        $field = $this->with_stored($this->create_field(), '1');
        $user = new \stdClass();

        $field->edit_load_user_data($user);

        $this->assertSame('', $user->{$field->inputname});
        $this->assertSame('', $user->{$field->inputname . '_uf'});
        $this->assertSame('', $user->{$field->inputname . '_municipio'});
        $this->assertSame('', $field->display_data());
        $this->assertTrue($field->is_empty());
    }

    /**
     * The display escapes the stored name.
     */
    public function test_display_escapes(): void {
        $field = $this->with_stored($this->create_field(), '{"uf":"SC","codmunicipio":"","nome":"Rio \"A\" & B"}');

        $this->assertSame('SC / Rio &quot;A&quot; &amp; B', $field->display_data());
    }

    /**
     * A stored UF outside the UF list is not shown.
     */
    public function test_display_ignores_an_unknown_uf(): void {
        $field = $this->with_stored($this->create_field(), '{"uf":"<b>","codmunicipio":""}');

        $this->assertSame('', $field->display_data());
        // The control: a known UF alone is shown.
        $field = $this->with_stored($this->create_field(['shortname' => 'other']), '{"uf":"SC","codmunicipio":""}');
        $this->assertSame('SC', $field->display_data());
    }
}
