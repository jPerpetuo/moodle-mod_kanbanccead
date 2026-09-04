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
 * mod_kanbanccead service definition.
 *
 * @package    mod_kanbanccead
 * @copyright   2023-2024 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'mod_kanbanccead_get_kanbanccead_content_init' => [
        'classname'   => 'mod_kanbanccead\external\get_kanbanccead_content',
        'methodname'  => 'get_kanbanccead_content_init',
        'description' => 'Retrieves the whole content of the kanbanccead board',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:view',
    ],
    'mod_kanbanccead_get_kanbanccead_content_update' => [
        'classname'   => 'mod_kanbanccead\external\get_kanbanccead_content',
        'methodname'  => 'get_kanbanccead_content_update',
        'description' => 'Retrieves only the updated content of the kanbanccead board since timestamp',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:view',
    ],
    'mod_kanbanccead_get_history_update' => [
        'classname'   => 'mod_kanbanccead\external\get_kanbanccead_content',
        'methodname'  => 'get_history_update',
        'description' => 'Retrieves the history of a the kanbanccead card since timestamp',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:viewhistory',
    ],
    'mod_kanbanccead_get_discussion_update' => [
        'classname'   => 'mod_kanbanccead\external\get_kanbanccead_content',
        'methodname'  => 'get_discussion_update',
        'description' => 'Retrieves the discussion for a card since timestamp',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:view',
    ],
    'mod_kanbanccead_add_column' => [
        'classname'   => 'mod_kanbanccead\external\change_kanbanccead_content',
        'methodname'  => 'add_column',
        'description' => 'Adds a column to the kanbanccead board',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:managecolumns',
    ],
    'mod_kanbanccead_add_card' => [
        'classname'   => 'mod_kanbanccead\external\change_kanbanccead_content',
        'methodname'  => 'add_card',
        'description' => 'Adds a card to a column of the kanbanccead board',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:addcard',
    ],
    'mod_kanbanccead_move_column' => [
        'classname'   => 'mod_kanbanccead\external\change_kanbanccead_content',
        'methodname'  => 'move_column',
        'description' => 'Moves a column within the kanbanccead board',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:managecolumns',
    ],
    'mod_kanbanccead_move_card' => [
        'classname'   => 'mod_kanbanccead\external\change_kanbanccead_content',
        'methodname'  => 'move_card',
        'description' => 'Moves a card within the kanbanccead board',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:manageassignedcards, mod/kanbanccead:manageallcards',
    ],
    'mod_kanbanccead_delete_column' => [
        'classname'   => 'mod_kanbanccead\external\change_kanbanccead_content',
        'methodname'  => 'delete_column',
        'description' => 'Deletes a column and all contained cards from the kanbanccead board',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:managecolumns',
    ],
    'mod_kanbanccead_delete_card' => [
        'classname'   => 'mod_kanbanccead\external\change_kanbanccead_content',
        'methodname'  => 'delete_card',
        'description' => 'Deletes a card from the kanbanccead board',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:manageassignedcards, mod/kanbanccead:manageallcards, mod/kanbanccead:addcard',
    ],
    'mod_kanbanccead_assign_user' => [
        'classname'   => 'mod_kanbanccead\external\change_kanbanccead_content',
        'methodname'  => 'assign_user',
        'description' => 'Assigns a user to a card',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:assignself, mod/kanbanccead:assignothers',
    ],
    'mod_kanbanccead_unassign_user' => [
        'classname'   => 'mod_kanbanccead\external\change_kanbanccead_content',
        'methodname'  => 'unassign_user',
        'description' => 'Unassigns a user to a card',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:assignself, mod/kanbanccead:assignothers',
    ],
    'mod_kanbanccead_set_column_locked' => [
        'classname'   => 'mod_kanbanccead\external\change_kanbanccead_content',
        'methodname'  => 'set_column_locked',
        'description' => 'Changes the lock state of a column',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:managecolumns',
    ],
    'mod_kanbanccead_set_card_complete' => [
        'classname'   => 'mod_kanbanccead\external\change_kanbanccead_content',
        'methodname'  => 'set_card_complete',
        'description' => 'Changes the completion state of a card',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:manageassignedcards, mod/kanbanccead:manageallcards',
    ],
    'mod_kanbanccead_set_board_columns_locked' => [
        'classname'   => 'mod_kanbanccead\external\change_kanbanccead_content',
        'methodname'  => 'set_board_columns_locked',
        'description' => 'Changes the lock state of a whole board',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:manageboard',
    ],
    'mod_kanbanccead_add_discussion_message' => [
        'classname'   => 'mod_kanbanccead\external\change_kanbanccead_content',
        'methodname'  => 'add_discussion_message',
        'description' => 'Adds a message to card discussion',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:manageassignedcards, mod/kanbanccead:manageallcards',
    ],
    'mod_kanbanccead_delete_discussion_message' => [
        'classname'   => 'mod_kanbanccead\external\change_kanbanccead_content',
        'methodname'  => 'delete_discussion_message',
        'description' => 'Deletes a message from card discussion',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:manageassignedcards, mod/kanbanccead:manageallcards',
    ],
    'mod_kanbanccead_save_as_template' => [
        'classname'   => 'mod_kanbanccead\external\change_kanbanccead_content',
        'methodname'  => 'save_as_template',
        'description' => 'Saves the current board as template for the instance',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:manageboard',
    ],
    'mod_kanbanccead_delete_board' => [
        'classname'   => 'mod_kanbanccead\external\change_kanbanccead_content',
        'methodname'  => 'delete_board',
        'description' => 'Deletes the current board',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:manageboard',
    ],
    'mod_kanbanccead_push_card_copy' => [
        'classname'   => 'mod_kanbanccead\external\change_kanbanccead_content',
        'methodname'  => 'push_card_copy',
        'description' => 'Pushes a copy of a card to all boards',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:manageboard',
    ],
    'mod_kanbanccead_duplicate_card' => [
        'classname'   => 'mod_kanbanccead\external\change_kanbanccead_content',
        'methodname'  => 'duplicate_card',
        'description' => 'Duplicates a card within the board',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/kanbanccead:addcard',
    ],
];
