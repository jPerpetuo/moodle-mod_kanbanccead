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

namespace mod_kanbanccead\completion;

/**
 * Custom completion rules for mod_kanbanccead
 *
 * @package     mod_kanbanccead
 * @copyright   2023-2024 ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends \core_completion\activity_custom_completion {
    /**
     * Returns completion state of the custom completion rules
     *
     * @param string $rule
     * @return integer
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $kanbanccead = $DB->get_record(
            "kanbanccead",
            ["id" => $this->cm->instance],
            'completioncreate, completioncomplete',
            MUST_EXIST
        );

        if ($rule == 'completioncreate') {
            if ($kanbanccead->completioncreate > 0) {
                $count = $DB->get_field_sql(
                    '
                    SELECT COUNT(DISTINCT c.id)
                    FROM {kanbanccead_board} b
                    INNER JOIN {kanbanccead_card} c ON b.kanbanccead_instance = :kanbancceadid AND c.kanbanccead_board = b.id
                    WHERE c.createdby = :userid',
                    ['userid' => $this->userid, 'kanbancceadid' => $this->cm->instance]
                );
                return ($count >= $kanbanccead->completioncreate ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE);
            }
        }
        if ($rule == 'completioncomplete') {
            if ($kanbanccead->completioncomplete > 0) {
                $count = $DB->get_field_sql(
                    '
                    SELECT COUNT(DISTINCT c.id)
                    FROM {kanbanccead_board} b
                    INNER JOIN {kanbanccead_card} c ON b.kanbanccead_instance = :kanbancceadid
                           AND c.kanbanccead_board = b.id AND c.completed != 0
                    INNER JOIN {kanbanccead_assignee} a ON a.kanbanccead_card = c.id
                    WHERE a.userid = :userid',
                    ['userid' => $this->userid, 'kanbancceadid' => $this->cm->instance]
                );
                return ($count >= $kanbanccead->completioncomplete ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE);
            }
        }

        return COMPLETION_INCOMPLETE;
    }

    /**
     * Defines the names of custom completion rules.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
            'completioncreate',
            'completioncomplete',
        ];
    }

    /**
     * Returns the descriptions of the custom completion rules
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        $completioncreate = $this->cm->customdata['customcompletionrules']['completioncreate'] ?? 0;
        $completioncomplete = $this->cm->customdata['customcompletionrules']['completioncomplete'] ?? 0;

        return [
            'completioncreate' => get_string('completiondetail:create', 'kanbanccead', $completioncreate),
            'completioncomplete' => get_string('completiondetail:complete', 'kanbanccead', $completioncomplete),
        ];
    }

    /**
     * Returns the sort order of completion rules
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completioncreate',
            'completioncomplete',
        ];
    }
}
