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
 * Privacy provider for the Brasil UF e Município profile field.
 *
 * @package    profilefield_brasilufmunicipio
 * @copyright  2026 Anderson Blaine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace profilefield_brasilufmunicipio\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Exports and deletes the values users hold in fields of this type.
 *
 * The values live in core's user_info_data, one row per user and field, in the user's context. The plugin's own
 * table holds the IBGE municipality list and no user data. Mirrors core's profilefield_text provider.
 *
 * @package    profilefield_brasilufmunicipio
 * @copyright  2026 Anderson Blaine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /** The datatype that marks this plugin's fields in user_info_field. */
    private const DATATYPE = 'brasilufmunicipio';

    /**
     * Describe the user data this field type stores.
     *
     * @param collection $collection The collection to add the items to.
     * @return collection The collection with this plugin's items.
     */
    public static function get_metadata(collection $collection): collection {
        return $collection->add_database_table('user_info_data', [
            'userid' => 'privacy:metadata:profilefield_brasilufmunicipio:userid',
            'fieldid' => 'privacy:metadata:profilefield_brasilufmunicipio:fieldid',
            'data' => 'privacy:metadata:profilefield_brasilufmunicipio:data',
        ], 'privacy:metadata:profilefield_brasilufmunicipio:tableexplanation');
    }

    /**
     * Get the user context of a user who holds a value in a field of this type.
     *
     * @param int $userid The user to search.
     * @return contextlist The user's context, or an empty list.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {user_info_data} uda
                  JOIN {user_info_field} uif ON uif.id = uda.fieldid
                  JOIN {context} ctx ON ctx.instanceid = uda.userid AND ctx.contextlevel = :contextlevel
                 WHERE uda.userid = :userid
                   AND uif.datatype = :datatype";
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_USER,
            'userid' => $userid,
            'datatype' => self::DATATYPE,
        ]);
        return $contextlist;
    }

    /**
     * Get the users who hold a value in a field of this type within a user context.
     *
     * @param userlist $userlist The userlist to fill.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_user) {
            return;
        }
        $sql = "SELECT uda.userid
                  FROM {user_info_data} uda
                  JOIN {user_info_field} uif ON uif.id = uda.fieldid
                 WHERE uda.userid = :userid
                   AND uif.datatype = :datatype";
        $userlist->add_from_sql('userid', $sql, ['userid' => $context->instanceid, 'datatype' => self::DATATYPE]);
    }

    /**
     * Export the values the user holds in fields of this type.
     *
     * @param approved_contextlist $contextlist The approved contexts to export for.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_USER || $context->instanceid != $user->id) {
                continue;
            }
            foreach (self::get_records((int)$user->id) as $record) {
                $value = json_decode($record->data);
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'profilefield_brasilufmunicipio'), $record->name],
                    (object)[
                        'name' => $record->name,
                        'description' => $record->description,
                        'uf' => $value->uf ?? '',
                        'codmunicipio' => $value->codmunicipio ?? '',
                        'municipio' => $value->nome ?? '',
                        'data' => $record->data,
                    ]
                );
            }
        }
    }

    /**
     * Delete the values held in a user context.
     *
     * @param \context $context The context to delete in.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel == CONTEXT_USER) {
            self::delete_data((int)$context->instanceid);
        }
    }

    /**
     * Delete the values of the approved users within a user context.
     *
     * @param approved_userlist $userlist The approved context and users.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        if ($context instanceof \context_user && in_array($context->instanceid, $userlist->get_userids())) {
            self::delete_data((int)$context->instanceid);
        }
    }

    /**
     * Delete the values the user holds in the approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_USER && $context->instanceid == $user->id) {
                self::delete_data((int)$user->id);
            }
        }
    }

    /**
     * Delete a user's values in fields of this type.
     *
     * @param int $userid The user.
     * @return void
     */
    private static function delete_data(int $userid): void {
        global $DB;
        $DB->delete_records_select(
            'user_info_data',
            'userid = :userid AND fieldid IN (SELECT id FROM {user_info_field} WHERE datatype = :datatype)',
            ['userid' => $userid, 'datatype' => self::DATATYPE]
        );
    }

    /**
     * Get a user's values in fields of this type, with each field's name and description.
     *
     * @param int $userid The user.
     * @return array Records with data, name and description, keyed by user_info_data id.
     */
    private static function get_records(int $userid): array {
        global $DB;
        $sql = "SELECT uda.id, uda.data, uif.name, uif.description
                  FROM {user_info_data} uda
                  JOIN {user_info_field} uif ON uif.id = uda.fieldid
                 WHERE uda.userid = :userid
                   AND uif.datatype = :datatype";
        return $DB->get_records_sql($sql, ['userid' => $userid, 'datatype' => self::DATATYPE]);
    }
}
