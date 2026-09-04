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
 * Restore steps for mod_kanbanccead
 *
 * @package     mod_kanbanccead
 * @copyright   2023-2024 ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_kanbanccead_activity_structure_step extends restore_activity_structure_step {
    /**
     * List of elements that can be restored
     *
     * @return array
     * @throws base_step_exception
     */
    protected function define_structure(): array {
        $paths = [];
        $paths[] = new restore_path_element('kanbanccead', '/activity/kanbanccead');
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('board', '/activity/kanbanccead/boards/kanbanccead_board');
        $paths[] = new restore_path_element('column', '/activity/kanbanccead/boards/kanbanccead_board/columns/kanbanccead_column');
        $paths[] = new restore_path_element(
            'card',
            '/activity/kanbanccead/boards/kanbanccead_board/columns/kanbanccead_column/cards/kanbanccead_card'
        );

        if ($userinfo) {
            $paths[] = new restore_path_element(
                'assignee',
                '/activity/kanbanccead/boards/kanbanccead_board/columns/kanbanccead_column'
                    . '/cards/kanbanccead_card/assignees/kanbanccead_assignee'
            );
            $paths[] = new restore_path_element(
                'discussion_comment',
                '/activity/kanbanccead/boards/kanbanccead_board/columns/kanbanccead_column'
                    . '/cards/kanbanccead_card/discussions/kanbanccead_discussion_comment'
            );
            $paths[] = new restore_path_element(
                'historyitem',
                '/activity/kanbanccead/boards/kanbanccead_board/historyitems/kanbanccead_history'
            );
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore a kanbanccead record.
     *
     * @param array|object $data
     * @throws base_step_exception
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_kanbanccead($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $includegroups = (bool)$this->get_setting_value('groups');
        $destinationgroups = [];
        if (!empty($data->boardmode) && (int)$data->boardmode === \mod_kanbanccead\constants::MOD_KANBANCCEAD_BOARDMODE_GROUP) {
            $destinationgroups = groups_get_all_groups($this->get_courseid(), 0, 0, 'g.id, g.name');
        }
        if (!$includegroups) {
            $data->boardgroups = '';
            $data->boardgroupid = 0;
        } else if (!empty($data->boardgroups)) {
            $mappedgroupids = [];
            $groupids = preg_split('/[;,]/', (string)$data->boardgroups, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($groupids as $groupid) {
                $mappedgroupid = $this->get_mappingid('group', (int)$groupid);
                if (!empty($mappedgroupid)) {
                    $mappedgroupids[] = (int)$mappedgroupid;
                }
            }
            $data->boardgroups = implode(',', array_unique($mappedgroupids));
        }
        if ((int)$data->boardmode === \mod_kanbanccead\constants::MOD_KANBANCCEAD_BOARDMODE_GROUP && empty($destinationgroups)) {
            $data->boardmode = \mod_kanbanccead\constants::MOD_KANBANCCEAD_BOARDMODE_SHARED;
            $data->boardgroups = '';
            $data->boardgroupid = 0;
        }

        $newid = $DB->insert_record('kanbanccead', $data);
        $this->set_mapping('kanbanccead_id', $oldid, $newid);
        $this->apply_activity_instance($newid);
    }

    /**
     * Restore a board record.
     *
     * @param array|object $data
     * @throws base_step_exception
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_board($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        if ($this->get_setting_value('userinfo')) {
            $data->userid = $this->get_mappingid('user', $data->userid);
            $data->groupid = $this->get_mappingid('group', $data->groupid);
        } else {
            // The structural source becomes a reusable template in the destination course.
            $data->userid = 0;
            $data->groupid = 0;
            $data->template = 1;
        }
        $data->kanbanccead_instance = $this->get_mappingid('kanbanccead_id', $data->kanbanccead_instance);

        $newid = $DB->insert_record('kanbanccead_board', $data);
        $this->set_mapping('kanbanccead_board_id', $oldid, $newid);
    }

    /**
     * Restore a column record.
     *
     * @param array|object $data
     * @throws base_step_exception
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_column($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->kanbanccead_board = $this->get_mappingid('kanbanccead_board_id', $data->kanbanccead_board);

        $newid = $DB->insert_record('kanbanccead_column', $data);
        $this->set_mapping('kanbanccead_column_id', $oldid, $newid);
    }

    /**
     * Restore a card record.
     *
     * @param array|object $data
     * @throws base_step_exception
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_card($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $userinfo = $this->get_setting_value('userinfo');
        if (!$userinfo) {
            $data->discussion = 0;
        }

        $data->kanbanccead_column = $this->get_mappingid('kanbanccead_column_id', $data->kanbanccead_column);
        $data->kanbanccead_board = $this->get_mappingid('kanbanccead_board_id', $data->kanbanccead_board);
        $data->originalid = $this->get_mappingid('kanbanccead_card_id', $data->originalid);
        $data->createdby = $this->get_mappingid('user', $data->createdby);

        if (empty($data->number)) {
            $data->number = $DB->get_field(
                'kanbanccead_card',
                'MAX(number)',
                ['kanbanccead_board' => $data->kanbanccead_board]
            ) + 1;
        }

        $newid = $DB->insert_record('kanbanccead_card', $data);
        $this->set_mapping('kanbanccead_card_id', $oldid, $newid, true);
        $this->add_related_files('mod_kanbanccead', 'attachments', 'kanbanccead_card_id', null, $oldid);
    }

    /**
     * Restore an assignes record.
     *
     * @param array|object $data
     * @throws base_step_exception
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_assignee($data): void {
        global $DB;

        $data = (object) $data;

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->kanbanccead_card = $this->get_mappingid('kanbanccead_card_id', $data->kanbanccead_card);

        $DB->insert_record('kanbanccead_assignee', $data);
    }

    /**
     * Restore an historyitem record.
     *
     * @param array|object $data
     * @throws base_step_exception
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_historyitem($data): void {
        global $DB;

        $data = (object) $data;

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->kanbanccead_card = $this->get_mappingid('kanbanccead_card_id', $data->kanbanccead_card);
        $data->kanbanccead_column = $this->get_mappingid('kanbanccead_column_id', $data->kanbanccead_column);
        $data->kanbanccead_board = $this->get_mappingid('kanbanccead_board_id', $data->kanbanccead_board);
        $data->affected_userid = $this->get_mappingid('user', $data->affected_userid);

        $DB->insert_record('kanbanccead_history', $data);
    }

    /**
     * Restore an discussion_comment record.
     *
     * @param array|object $data
     * @throws base_step_exception
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_discussion_comment($data): void {
        global $DB;

        $data = (object) $data;

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->kanbanccead_card = $this->get_mappingid('kanbanccead_card_id', $data->kanbanccead_card);

        $DB->insert_record('kanbanccead_discussion_comment', $data);
    }

    /**
     * Extra actions to take once restore is complete.
     */
    protected function after_execute(): void {
        global $DB;
        $this->add_related_files('mod_kanbanccead', 'intro', null);

        $kanbancceadboards = $DB->get_records('kanbanccead_board', ['kanbanccead_instance' => $this->task->get_activityid()]);

        foreach ($kanbancceadboards as $board) {
            if ($board->sequence == '') {
                continue;
            }
            $seq = explode(',', $board->sequence);
            foreach ($seq as $key => $columnid) {
                $seq[$key] = $this->get_mappingid('kanbanccead_column_id', $columnid);
            }
            $DB->update_record('kanbanccead_board', ['id' => $board->id, 'sequence' => join(',', $seq)]);
            mod_kanbanccead\helper::update_cached_board($board->id);

            $kanbancceadcolumns = $DB->get_records('kanbanccead_column', ['kanbanccead_board' => $board->id]);

            foreach ($kanbancceadcolumns as $column) {
                if (!$this->get_setting_value('userinfo')) {
                    // Card IDs are not restored without user data.
                    $DB->set_field('kanbanccead_column', 'sequence', '', ['id' => $column->id]);
                    continue;
                }
                if ($column->sequence == '') {
                    continue;
                }
                $seqcard = explode(',', $column->sequence);
                foreach ($seqcard as $cardkey => $cardid) {
                    $seqcard[$cardkey] = $this->get_mappingid('kanbanccead_card_id', $cardid);
                }
                $DB->update_record('kanbanccead_column', ['id' => $column->id, 'sequence' => join(',', $seqcard)]);
            }
        }
    }
}
