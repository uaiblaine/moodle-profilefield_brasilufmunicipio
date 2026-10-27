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

namespace profilefield_brasilufmunicipio\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;

/**
 * Tests for the privacy provider.
 *
 * Coverage is declared in this docblock rather than with a CoversClass attribute, because the moodle-cs of the
 * Moodle 4.5 leg cannot read attributes.
 *
 * @package    profilefield_brasilufmunicipio
 * @category   test
 * @copyright  2026 Anderson Blaine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \profilefield_brasilufmunicipio\privacy\provider
 */
final class provider_test extends provider_testcase {
    /** @var \stdClass A field of this type. */
    private \stdClass $field;

    /** @var \stdClass A text field, whose data the provider must never touch. */
    private \stdClass $textfield;

    /**
     * Create a field of this type and a text field.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->field = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'brasilufmunicipio', 'shortname' => 'cidade', 'name' => 'Cidade',
        ]);
        $this->textfield = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text', 'shortname' => 'apelido', 'name' => 'Apelido',
        ]);
    }

    /**
     * Create a user holding a value in both fields.
     *
     * @return \stdClass The user.
     */
    private function create_user_with_data(): \stdClass {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('user_info_data', ['userid' => $user->id, 'fieldid' => $this->field->id,
            'data' => '{"uf":"SC","codmunicipio":"4205407","nome":"Florianópolis"}']);
        $DB->insert_record('user_info_data', ['userid' => $user->id, 'fieldid' => $this->textfield->id, 'data' => 'Zé']);
        return $user;
    }

    /**
     * Count a user's values in a field.
     *
     * @param \stdClass $user The user.
     * @param \stdClass $field The field.
     * @return int
     */
    private function count_data(\stdClass $user, \stdClass $field): int {
        global $DB;
        return $DB->count_records('user_info_data', ['userid' => $user->id, 'fieldid' => $field->id]);
    }

    /**
     * The provider satisfies core's compliance check, which plugin CI never runs.
     */
    public function test_the_component_is_compliant(): void {
        $this->assertTrue((new \core_privacy\manager())->component_is_compliant('profilefield_brasilufmunicipio'));
    }

    /**
     * The user context is listed for a user holding a value, and nothing for one who does not.
     */
    public function test_get_contexts_and_users(): void {
        $user = $this->create_user_with_data();
        $other = $this->getDataGenerator()->create_user();
        $context = \context_user::instance($user->id);

        $contextids = provider::get_contexts_for_userid((int)$user->id)->get_contextids();
        $this->assertSame([(int)$context->id], array_map('intval', $contextids));
        $this->assertSame([], provider::get_contexts_for_userid((int)$other->id)->get_contextids());

        $userlist = new userlist($context, 'profilefield_brasilufmunicipio');
        provider::get_users_in_context($userlist);
        $this->assertSame([(int)$user->id], array_map('intval', $userlist->get_userids()));
    }

    /**
     * The export carries the UF, the code and the name.
     */
    public function test_export_user_data(): void {
        $user = $this->create_user_with_data();
        $context = \context_user::instance($user->id);

        provider::export_user_data(new approved_contextlist($user, 'profilefield_brasilufmunicipio', [$context->id]));

        $data = writer::with_context($context)->get_data([get_string('pluginname', 'profilefield_brasilufmunicipio'), 'Cidade']);
        $this->assertSame('SC', $data->uf);
        $this->assertSame('4205407', $data->codmunicipio);
        $this->assertSame('Florianópolis', $data->municipio);
    }

    /**
     * Each delete removes this type's value and leaves the text field's.
     */
    public function test_deletes(): void {
        $users = [$this->create_user_with_data(), $this->create_user_with_data(), $this->create_user_with_data()];
        $contexts = array_map(fn(\stdClass $user) => \context_user::instance($user->id), $users);

        provider::delete_data_for_all_users_in_context($contexts[0]);
        provider::delete_data_for_user(new approved_contextlist($users[1], 'profilefield_brasilufmunicipio', [$contexts[1]->id]));
        provider::delete_data_for_users(new approved_userlist($contexts[2], 'profilefield_brasilufmunicipio', [$users[2]->id]));

        foreach ($users as $user) {
            $this->assertSame(0, $this->count_data($user, $this->field));
            $this->assertSame(1, $this->count_data($user, $this->textfield));
        }
    }

    /**
     * Deletes outside the user's own context remove nothing.
     */
    public function test_deletes_outside_the_user_context(): void {
        $user = $this->create_user_with_data();
        $other = $this->getDataGenerator()->create_user();
        $othercontext = \context_user::instance($other->id);

        provider::delete_data_for_all_users_in_context(\context_system::instance());
        provider::delete_data_for_user(new approved_contextlist($user, 'profilefield_brasilufmunicipio', [$othercontext->id]));
        provider::delete_data_for_users(
            new approved_userlist(\context_user::instance($user->id), 'profilefield_brasilufmunicipio', [$other->id])
        );

        $this->assertSame(1, $this->count_data($user, $this->field));
    }
}
