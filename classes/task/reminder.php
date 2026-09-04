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
 * Reminder task
 *
 * @package    mod_kanbanccead
 * @copyright   2023-2024 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kanbanccead\task;

use mod_kanbanccead\helper;

/**
 * Reminder task
 *
 * @package    mod_kanbanccead
 * @copyright   2023-2024 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reminder extends \core\task\scheduled_task {
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('remindertask', 'mod_kanbanccead');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;
        $time = time();
        $kanbancceadcards = $DB->get_records_sql(
            'SELECT ' . $DB->sql_concat('c.id', "'-'", 'a.userid') . ' as uniqid,
                    c.id as id, c.title as title, k.name as boardname, c.duedate as duedate, a.userid as userid, k.id as instance
               FROM {kanbanccead_card} c
         INNER JOIN {kanbanccead_assignee} a ON a.kanbanccead_card = c.id
                AND c.duedate != 0
                AND c.reminder_sent = 0
                AND c.completed = 0
                AND (c.duedate < :time OR (c.reminderdate != 0 AND c.reminderdate < :time2))
         INNER JOIN {kanbanccead_board} b ON b.id = c.kanbanccead_board
         INNER JOIN {kanbanccead} k ON b.kanbanccead_instance = k.id',
            ['time' => $time, 'time2' => $time]
        );
        foreach ($kanbancceadcards as $kanbancceadcard) {
            [$course, $cminfo] = get_course_and_cm_from_instance($kanbancceadcard->instance, 'kanbanccead');
            $user = \core_user::get_user($kanbancceadcard->userid);
            helper::fix_current_language($user->lang);
            $kanbancceadcard->duedate = userdate($kanbancceadcard->duedate, get_string('strftimedate', 'langconfig'));
            helper::send_notification($cminfo, 'due', [$kanbancceadcard->userid], $kanbancceadcard, null, true);
            $data = new \stdClass();
            $data->id = $kanbancceadcard->id;
            $data->reminder_sent = 1;
            $DB->update_record('kanbanccead_card', $data);
        }
    }
}
