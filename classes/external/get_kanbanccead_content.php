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
 * Class for delivering kanbanccead content
 *
 * @package    mod_kanbanccead
 * @copyright  2023-2024 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kanbanccead\external;

// Compatibility with Moodle < 4.2.
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');
require_once($CFG->dirroot . '/mod/kanbanccead/lib.php');

use coding_exception;
use context_module;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use mod_kanbanccead\boardmanager;
use mod_kanbanccead\constants;
use mod_kanbanccead\helper;
use mod_kanbanccead\numberfilter;
use mod_kanbanccead\updateformatter;
use moodle_exception;
use required_capability_exception;
use restricted_context_exception;
use stdClass;

/**
 * Class for delivering kanbanccead content
 *
 * @copyright  2023-2024 ISB Bayern
 * @author     Stefan Hanauska
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_kanbanccead_content extends external_api {
    /**
     * Returns description of method parameters for the execute webservice function.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED),
            'boardid' => new external_value(PARAM_INT, 'board id', VALUE_REQUIRED),
            'timestamp' => new external_value(PARAM_INT, 'only get values modified after this timestamp', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Returns description of method parameters for the get_kanbanccead_content_init webservice function.
     *
     * @return external_function_parameters
     */
    public static function get_kanbanccead_content_init_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED),
            'boardid' => new external_value(PARAM_INT, 'board id', VALUE_REQUIRED),
            'timestamp' => new external_value(PARAM_INT, 'only get values modified after this timestamp', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Returns description of method parameters for the get_kanbanccead_content_update webservice function.
     *
     * @return external_function_parameters
     */
    public static function get_kanbanccead_content_update_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED),
            'boardid' => new external_value(PARAM_INT, 'board id', VALUE_REQUIRED),
            'timestamp' => new external_value(PARAM_INT, 'only get values modified after this timestamp', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Definition of return values of the get_kanbanccead_content webservice function.
     *
     * @return external_single_structure
     */
    public static function get_kanbanccead_content_init_returns(): external_single_structure {
        return
            new external_single_structure(
                [
                    'common' => new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'cmid'),
                        'timestamp' => new external_value(PARAM_INT, 'timestamp'),
                        'userid' => new external_value(PARAM_INT, 'current user id'),
                        'lang' => new external_value(PARAM_TEXT, 'language for the ui'),
                        'liveupdate' => new external_value(PARAM_INT, 'seconds between two live updates'),
                        'template' => new external_value(PARAM_INT, 'boardid for template', VALUE_OPTIONAL, 0),
                        'groupmode' => new external_value(PARAM_INT, 'group mode'),
                        'boardmode' => new external_value(PARAM_INT, 'board mode'),
                        'boardgroupid' => new external_value(PARAM_INT, 'default group board id'),
                        'boardselector' => new external_single_structure([
                            'show' => new external_value(PARAM_BOOL, 'whether the board selector is shown'),
                            'label' => new external_value(PARAM_TEXT, 'label shown in the selector button'),
                            'currentlabel' => new external_value(PARAM_TEXT, 'current board label'),
                            'shortcurrentlabel' => new external_value(PARAM_TEXT, 'short current board label', VALUE_OPTIONAL, ''),
                            'icon' => new external_value(PARAM_TEXT, 'current board icon', VALUE_OPTIONAL, ''),
                            'summary' => new external_value(PARAM_TEXT, 'summary line below the selector', VALUE_OPTIONAL, ''),
                            'groupmemberslabel' => new external_value(PARAM_TEXT, 'group member count label', VALUE_OPTIONAL, ''),
                            'hasgroupmembers' => new external_value(
                                PARAM_BOOL,
                                'whether the current group has members available to display',
                                VALUE_OPTIONAL,
                                false
                            ),
                            'groupmembers' => new external_multiple_structure(
                                new external_single_structure([
                                    'id' => new external_value(PARAM_INT, 'user id'),
                                    'fullname' => new external_value(PARAM_TEXT, 'user fullname'),
                                    'userpicture' => new external_value(PARAM_RAW, 'user picture'),
                                ]),
                                '',
                                VALUE_OPTIONAL
                            ),
                            'boards' => new external_multiple_structure(
                                new external_single_structure([
                                    'id' => new external_value(PARAM_INT, 'group id'),
                                    'label' => new external_value(PARAM_TEXT, 'board label'),
                                    'icon' => new external_value(PARAM_TEXT, 'board icon', VALUE_OPTIONAL, ''),
                                    'url' => new external_value(PARAM_URL, 'board url'),
                                    'current' => new external_value(PARAM_BOOL, 'whether this is the current board'),
                                ]),
                                '',
                                VALUE_OPTIONAL
                            ),
                        ]),
                        'groupselector' => new external_value(PARAM_RAW, 'group selector'),
                        'userboards' => new external_value(PARAM_INT, 'userboards'),
                        'history' => new external_value(PARAM_INT, 'history'),
                        'updatefails' => new external_value(PARAM_INT, 'updatefails', VALUE_OPTIONAL, 0),
                        'usenumbers' => new external_value(PARAM_INT, 'use numbers for the cards'),
                    ]),
                    'board' => new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'board id'),
                        'sequence' => new external_value(PARAM_TEXT, 'order of the columns in the board'),
                        'timemodified' => new external_value(PARAM_INT, 'timemodified'),
                        'locked' => new external_value(PARAM_INT, 'lock state'),
                        'userid' => new external_value(PARAM_INT, 'userboard for userid', VALUE_OPTIONAL, 0),
                        'groupid' => new external_value(PARAM_INT, 'groupboard for groupid', VALUE_OPTIONAL, 0),
                        'template' => new external_value(PARAM_INT, 'board is a template', VALUE_OPTIONAL, 0),
                        'heading' => new external_value(PARAM_TEXT, 'heading of the board'),
                    ]),
                    'columns' => new external_multiple_structure(
                        new external_single_structure(
                            [
                                'id' => new external_value(PARAM_INT, 'column id'),
                                'title' => new external_value(PARAM_TEXT, 'column title'),
                                'sequence' => new external_value(PARAM_TEXT, 'order of the cards in the column'),
                                'locked' => new external_value(PARAM_BOOL, 'lock state of the column'),
                                'options' => new external_value(PARAM_TEXT, 'options for the column'),
                            ],
                            '',
                            VALUE_OPTIONAL
                        )
                    ),
                    'cards' => new external_multiple_structure(
                        new external_single_structure(
                            [
                                'id' => new external_value(PARAM_INT, 'card id'),
                                'title' => new external_value(PARAM_TEXT, 'card title'),
                                'kanbanccead_column' => new external_value(PARAM_INT, 'column'),
                                'duedate' => new external_value(PARAM_INT, 'due date'),
                                'options' => new external_value(PARAM_TEXT, 'options for the card'),
                                'assignees' => new external_multiple_structure(
                                    new external_value(PARAM_INT, 'user id'),
                                    VALUE_OPTIONAL
                                ),
                                'selfassigned' => new external_value(
                                    PARAM_BOOL,
                                    'is current user assigned to the card?',
                                    VALUE_OPTIONAL,
                                    false
                                ),
                                'completed' => new external_value(
                                    PARAM_BOOL,
                                    'is card completed?',
                                    VALUE_OPTIONAL,
                                    false
                                ),
                                'completedat' => new external_value(
                                    PARAM_INT,
                                    'completion timestamp from history',
                                    VALUE_OPTIONAL,
                                    0
                                ),
                                'hasdescription' => new external_value(
                                    PARAM_BOOL,
                                    'has a description?',
                                    VALUE_OPTIONAL,
                                    false
                                ),
                                'description' => new external_value(
                                    PARAM_RAW,
                                    'description',
                                    VALUE_OPTIONAL,
                                    ''
                                ),
                                'hasattachment' => new external_value(
                                    PARAM_BOOL,
                                    'has an attachment?',
                                    VALUE_OPTIONAL,
                                    false
                                ),
                                'attachments' => new external_multiple_structure(
                                    new external_single_structure([
                                        'url' => new external_value(PARAM_URL, 'attachment url', VALUE_REQUIRED),
                                        'name' => new external_value(PARAM_TEXT, 'filename', VALUE_REQUIRED),
                                    ]),
                                    'attachments',
                                    VALUE_OPTIONAL,
                                    []
                                ),
                                'discussion' => new external_value(
                                    PARAM_BOOL,
                                    'has a discussion?',
                                    VALUE_OPTIONAL,
                                    false
                                ),
                                'createdby' => new external_value(
                                    PARAM_INT,
                                    'original creator of the card',
                                    VALUE_OPTIONAL,
                                    0
                                ),
                                'canedit' => new external_value(
                                    PARAM_BOOL,
                                    'current user can edit this card?',
                                    VALUE_OPTIONAL,
                                    false
                                ),
                                'number' => new external_value(
                                    PARAM_INT,
                                    'number of the card',
                                ),
                            ],
                            '',
                            VALUE_OPTIONAL
                        )
                    ),
                    'users' => new external_multiple_structure(
                        new external_single_structure(
                            [
                                'id' => new external_value(PARAM_INT, 'user id'),
                                'fullname' => new external_value(PARAM_TEXT, 'user fullname'),
                                'userpicture' => new external_value(PARAM_RAW, 'user picture'),
                            ],
                            '',
                            VALUE_OPTIONAL
                        ),
                        '',
                        VALUE_OPTIONAL
                    ),
                    'capabilities' => new external_multiple_structure(
                        new external_single_structure(
                            [
                                'id' => new external_value(PARAM_TEXT, 'capability name'),
                                'value' => new external_value(PARAM_BOOL, 'capability value'),
                            ],
                            '',
                            VALUE_OPTIONAL
                        ),
                    ),
                    'discussions' => new external_multiple_structure(
                        new external_single_structure(
                            [
                                'id' => new external_value(PARAM_INT, 'id'),
                                'timecreated' => new external_value(PARAM_INT, 'timecreated'),
                                'userid' => new external_value(PARAM_INT, 'userid'),
                                'kanbanccead_card' => new external_value(PARAM_INT, 'card id'),
                                'content' => new external_value(PARAM_TEXT, 'discussion message'),
                                'username' => new external_value(PARAM_TEXT, 'user name'),
                                'candelete' => new external_value(PARAM_BOOL, 'whether the current user can delete this message'),
                            ],
                            '',
                            VALUE_OPTIONAL
                        ),
                    ),
                    'history' => new external_multiple_structure(
                        new external_single_structure(
                            [
                                'id' => new external_value(PARAM_INT, 'id'),
                                'timestamp' => new external_value(PARAM_INT, 'timestamp'),
                                'userid' => new external_value(PARAM_INT, 'userid'),
                                'kanbanccead_card' => new external_value(PARAM_INT, 'card id'),
                                'kanbanccead_column' => new external_value(PARAM_INT, 'column'),
                                'content' => new external_value(PARAM_TEXT, 'discussion message'),
                                'affectedusername' => new external_value(PARAM_TEXT, 'user name'),
                            ],
                            '',
                            VALUE_OPTIONAL
                        ),
                    ),
                ]
            );
    }

    /**
     * This method returns the requested data.
     *
     * @param int $cmid the course module id of the kanbanccead board
     * @param int $boardid the id of the kanbanccead board
     * @param int $timestamp the timestamp of the state present in the frontend
     * @return array The requested content, divided into board, columns and cards
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function get_kanbanccead_content_init(int $cmid, int $boardid, int $timestamp = 0): array {
        return self::execute($cmid, $boardid, $timestamp);
    }

    /**
     * This method returns the requested data.
     *
     * @param int $cmid the course module id of the kanbanccead board
     * @param int $boardid the id of the kanbanccead board
     * @param int $timestamp the timestamp of the state present in the frontend
     * @return array The requested content, divided into board, columns and cards
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function get_kanbanccead_content_update(int $cmid, int $boardid, int $timestamp = 0): array {
        return self::execute($cmid, $boardid, $timestamp, true);
    }

    /**
     * Definition of return values of the get_kanbanccead_content_update webservice function.
     *
     * @return external_single_structure
     */
    public static function get_kanbanccead_content_update_returns(): external_single_structure {
        return new external_single_structure(
            [
                'update' => new external_value(PARAM_RAW, 'update JSON'),
            ]
        );
    }

    /**
     * Get kanbanccead content from database.
     *
     * @param int $cmid the course module id of the kanbanccead board
     * @param int $boardid the id of the kanbanccead board
     * @param int $timestamp the timestamp of the state present in the frontend
     * @param bool $asupdate whether to format content as update for StateMananger
     * @return array The requested content, divided into board, columns and cards
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function execute(int $cmid, int $boardid, int $timestamp = 0, bool $asupdate = false): array {
        global $DB, $OUTPUT, $USER;
        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'boardid' => $boardid,
            'timestamp' => $timestamp,
        ]);
        $cmid = $params['cmid'];
        $boardid = $params['boardid'];
        $timestamp = $params['timestamp'];
        [$course, $cminfo] = get_course_and_cm_from_cmid($cmid);
        $context = context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/kanbanccead:view', $context);

        // Get the values of some capabilities for output.
        $capabilities = [
            'addcard' => has_capability('mod/kanbanccead:addcard', $context),
            'manageallcards' => has_capability('mod/kanbanccead:manageallcards', $context),
            'manageassignedcards' => has_capability('mod/kanbanccead:manageallcards', $context),
            'assignself' => has_capability('mod/kanbanccead:assignself', $context),
            'assignothers' => has_capability('mod/kanbanccead:assignothers', $context),
            'managecolumns' => has_capability('mod/kanbanccead:managecolumns', $context),
            'editallboards' => has_capability('mod/kanbanccead:editallboards', $context),
            'manageboard' => has_capability('mod/kanbanccead:manageboard', $context),
            'viewhistory' => has_capability('mod/kanbanccead:viewhistory', $context),
            'viewallboards' => has_capability('mod/kanbanccead:viewallboards', $context),
        ];

        $params['board'] = $boardid;
        $params['timestamp'] = $timestamp;

        $boardmanager = new boardmanager($cmid, $boardid);

        $kanbanccead = $DB->get_record('kanbanccead', ['id' => $cminfo->instance]);
        $boardmode = (int)($kanbanccead->boardmode ?? constants::MOD_KANBANCCEAD_BOARDMODE_SHARED);

        $kanbancceadboard = helper::get_cached_board($boardid);
        helper::check_permissions_for_user_or_group(
            $kanbancceadboard,
            $context,
            $cminfo,
            constants::MOD_KANBANCCEAD_VIEW
        );
        $groupid = $kanbancceadboard->groupid;

        $kanbancceadboard->heading = get_string('courseboard', 'mod_kanbanccead');
        $boardselector = [
            'show' => false,
            'label' => '',
            'currentlabel' => '',
            'boards' => [],
        ];
        $groupmode = groups_get_activity_groupmode($cminfo, $course);
        $currentgroupid = !empty($groupmode) ? groups_get_activity_group($cminfo, true) : 0;
        $canaccessotherboards = $capabilities['viewallboards'] || $capabilities['editallboards'];

        if (!$asupdate) {
            $selectorcurrentgroupid = 0;
            if ($boardmode == constants::MOD_KANBANCCEAD_BOARDMODE_GROUP) {
                if ($canaccessotherboards) {
                    // Keep the configured group board available in the selector
                    // for teachers/managers even when current board differs.
                    $selectorcurrentgroupid = $boardmanager->get_preferred_board_group_id();
                    if (empty($selectorcurrentgroupid)) {
                        $selectorcurrentgroupid = (int)$currentgroupid;
                    }
                } else if (!empty($kanbancceadboard->groupid)) {
                    // Students should only see their effective current group board.
                    $selectorcurrentgroupid = (int)$kanbancceadboard->groupid;
                } else {
                    $selectorcurrentgroupid = (int)$currentgroupid;
                }
            }

            if (!empty($kanbancceadboard->groupid)) {
                $kanbancceadboard->heading = get_string(
                    'groupboard',
                    'mod_kanbanccead',
                    groups_get_group_name($kanbancceadboard->groupid)
                );
            }

            if (!empty($kanbancceadboard->userid)) {
                $boarduser = \core_user::get_user($kanbancceadboard->userid);
                $kanbancceadboard->heading = get_string('userboard', 'mod_kanbanccead', fullname($boarduser));
            }

            if (!empty($kanbancceadboard->template)) {
                $kanbancceadboard->heading = get_string('template', 'mod_kanbanccead');
            }

            $boardselectorboards = $boardmanager->get_board_selector_items(
                (int)$selectorcurrentgroupid,
                $canaccessotherboards && $boardmode == constants::MOD_KANBANCCEAD_BOARDMODE_GROUP,
                !empty($kanbanccead->userboards)
            );
            if (!empty($boardselectorboards)) {
                $currentposition = 1;
                foreach ($boardselectorboards as $index => $item) {
                    if (!empty($item['current'])) {
                        $currentposition = $index + 1;
                        break;
                    }
                }

                $groupmemberslabel = '';
                $hasgroupmembers = false;
                $groupmembers = [];
                if (!empty($kanbancceadboard->groupid)) {
                    $members = groups_get_members((int)$kanbancceadboard->groupid);
                    $membercount = is_array($members) ? count($members) : 0;
                    $groupmemberslabel = get_string('groupmemberscount', 'mod_kanbanccead', $membercount);
                    if (!empty($members)) {
                        $hasgroupmembers = true;
                        foreach ($members as $member) {
                            $groupmembers[] = [
                                'id' => (int)$member->id,
                                'fullname' => fullname($member),
                                'userpicture' => $OUTPUT->user_picture($member, ['link' => false]),
                            ];
                        }
                    }
                }

                $boardselector = [
                    'show' => true,
                    'label' => get_string('currentboard', 'mod_kanbanccead'),
                    'currentlabel' => $kanbancceadboard->heading,
                    'shortcurrentlabel' => self::get_board_short_label($kanbancceadboard),
                    'icon' => self::get_board_icon($kanbancceadboard),
                    'summary' => get_string(
                        'boardviewsummary',
                        'mod_kanbanccead',
                        (object) [
                            'current' => $currentposition,
                            'total' => count($boardselectorboards),
                        ]
                    ),
                    'groupmemberslabel' => $groupmemberslabel,
                    'hasgroupmembers' => $hasgroupmembers,
                    'groupmembers' => $groupmembers,
                    'boards' => $boardselectorboards,
                ];
            }
        }

        if (!(empty($kanbancceadboard->userid) && empty($kanbancceadboard->groupid))) {
            $restrictcaps = false;
            if (!empty($kanbancceadboard->userid) && $kanbancceadboard->userid != $USER->id) {
                require_capability('mod/kanbanccead:viewallboards', $context);
                $restrictcaps = true;
            }
            if (!empty($kanbancceadboard->groupid)) {
                $members = groups_get_members($kanbancceadboard->groupid, 'u.id');
                $members = array_map(function ($v) {
                    return intval($v->id);
                }, $members);
                $ismember = in_array($USER->id, $members);
                if (
                    ($boardmode == constants::MOD_KANBANCCEAD_BOARDMODE_GROUP ||
                        $groupmode == SEPARATEGROUPS ||
                        $groupmode == VISIBLEGROUPS) && !$ismember
                ) {
                    $restrictcaps = true;
                }
            }
            if ($restrictcaps) {
                $editcap = has_capability('mod/kanbanccead:editallboards', $context);
                foreach ($capabilities as $cap => $value) {
                    $capabilities[$cap] &= $editcap;
                }
            }
        }

        $common = new stdClass();
        $common->timestamp = time();
        $common->id = $cmid;
        $common->userid = $USER->id;
        // Additional information in the locale (e.g. ".UTF-8") cannot be parsed by the browser.
        $common->lang = explode('.', get_string('locale', 'langconfig'))[0];
        $common->lang = str_replace('_', '-', $common->lang);
        $common->liveupdate = get_config('mod_kanbanccead', 'liveupdatetime');
        $common->boardmode = $boardmode;
        $common->boardgroupid = $boardmanager->get_preferred_board_group_id();
        $common->boardselector = $boardselector;
        $common->userboards = $kanbanccead->userboards;
        $common->groupmode = $groupmode;
        $common->groupselector = '';
        $common->history = $kanbanccead->history;
        $common->updatefails = 0;
        $common->usenumbers = $kanbanccead->usenumbers;
        $common->linknumbers = $kanbanccead->linknumbers;

        if (!$asupdate) {
            $common->template = $DB->get_field_sql(
                'SELECT id
                 FROM {kanbanccead_board}
                 WHERE template = 1 AND kanbanccead_instance = :instance
                 ORDER BY timemodified DESC',
                ['instance' => $kanbancceadboard->kanbanccead_instance],
                IGNORE_MULTIPLE
            );
            if (empty($common->template)) {
                $common->template = 0;
            }
        }

        $kanbancceadusers = [];
        $kanbancceaduserids = [];

        $sql = 'kanbanccead_board = :board AND timemodified > :timestamp';

        $timestampcolumns = helper::get_cached_timestamp($boardid, constants::MOD_KANBANCCEAD_COLUMN);
        $timestampcards = helper::get_cached_timestamp($boardid, constants::MOD_KANBANCCEAD_CARD);
        $boardchanged = intval($kanbancceadboard->timemodified) > $timestamp;
        $columnschanged = $timestamp <= $timestampcolumns;
        $cardschanged = $timestamp <= $timestampcards;

        if ($asupdate && !$boardchanged && !$columnschanged && !$cardschanged) {
            return [
                'update' => '[]',
            ];
        }

        if ($columnschanged) {
            $kanbancceadcolumns = $DB->get_records_select('kanbanccead_column', $sql, $params);
        } else {
            $kanbancceadcolumns = [];
        }
        foreach ($kanbancceadcolumns as $kanbancceadcolumn) {
            $kanbancceadcolumn->title = clean_param($kanbancceadcolumn->title, PARAM_TEXT);
        }

        if ($cardschanged) {
            $kanbancceadcards = $DB->get_records_select('kanbanccead_card', $sql, $params);
        } else {
            $kanbancceadcards = [];
        }

        $kanbancceadcardids = array_map(fn($card) => $card->id, $kanbancceadcards);
        if (!empty($kanbancceadcardids) || (!empty($kanbanccead->userboards) && $capabilities['viewallboards'])) {
            $users = get_enrolled_users($context);
            foreach ($users as $user) {
                $kanbancceadusers[$user->id] = [
                    'id' => $user->id,
                    'fullname' => fullname($user),
                    'userpicture' => $OUTPUT->user_picture($user, ['link' => false]),
                ];
            }
        }
        if (!empty($kanbancceadcardids)) {
            [$sql, $params] = $DB->get_in_or_equal($kanbancceadcardids);
            $sql = 'kanbanccead_card ' . $sql;
            $kanbancceadassigneesraw = $DB->get_records_select('kanbanccead_assignee', $sql, $params);
            $kanbancceadassignees = [];
            $kanbancceaduserids = [];
            $completedtimestamps = [];
            foreach ($kanbancceadassigneesraw as $assignee) {
                if (!empty($kanbancceadusers[$assignee->userid])) {
                    $kanbancceadassignees[$assignee->kanbanccead_card][] = $assignee->userid;
                    $kanbancceaduserids[] = $assignee->userid;
                }
            }
            [$insql, $inparams] = $DB->get_in_or_equal($kanbancceadcardids, SQL_PARAMS_NAMED);
            $historyparams = array_merge(
                $inparams,
                [
                    'boardid' => $boardid,
                    'type' => constants::MOD_KANBANCCEAD_CARD,
                    'action' => 'completed',
                ]
            );
            $completedhistory = $DB->get_records_sql(
                "SELECT kanbanccead_card, MAX(timestamp) AS completedat
                   FROM {kanbanccead_history}
                  WHERE kanbanccead_board = :boardid
                    AND type = :type
                    AND action = :action
                    AND kanbanccead_card {$insql}
               GROUP BY kanbanccead_card",
                $historyparams
            );
            foreach ($completedhistory as $row) {
                $completedtimestamps[(int)$row->kanbanccead_card] = (int)$row->completedat;
            }
            foreach ($kanbancceadcards as $card) {
                if (empty($kanbancceadassignees[$card->id])) {
                    $kanbancceadassignees[$card->id] = [];
                }
                $card->title = clean_param($card->title, PARAM_TEXT);
                $card->assignees = $kanbancceadassignees[$card->id];
                $card->selfassigned = in_array($USER->id, $card->assignees);
                $card->canedit = $boardmanager->can_user_manage_specific_card($card->id);
                $card->hasdescription = !empty($card->description);
                $card->completedat = (!empty($card->completed) && !empty($completedtimestamps[$card->id])) ?
                    $completedtimestamps[$card->id] :
                    0;
                $card->discussions = [];
                $card->description = file_rewrite_pluginfile_urls(
                    format_text($card->description),
                    'pluginfile.php',
                    $context->id,
                    'mod_kanbanccead',
                    'attachments',
                    $card->id
                );
                if ($common->usenumbers && $common->linknumbers) {
                    $card->description = numberfilter::filter($card->description);
                }
                $card->attachments = helper::get_attachments($context->id, $card->id);
                $card->hasattachment = count($card->attachments) > 0;
            }
        }

        $caps = [];

        foreach ($capabilities as $k => $v) {
            $caps[] = ['id' => $k, 'value' => $v];
        }

        if ($asupdate) {
            $formatter = new updateformatter();
            $formatter->put('common', (array) $common);
            if ($boardchanged) {
                $formatter->put('board', (array) $kanbancceadboard);
            }
            foreach ($kanbancceadcolumns as $column) {
                $formatter->put('columns', (array) $column);
            }
            foreach ($kanbancceadcards as $card) {
                $formatter->put('cards', (array) $card);
            }
            foreach ($kanbancceaduserids as $userid) {
                $formatter->put('users', (array) $kanbancceadusers[$userid]);
            }
            return [
                'update' => $formatter->get_formatted_updates(),
            ];
        }

        // This shouldn't be done for content updates as it would make it necessary to query all columns everytime.
        $columnids = array_map(fn($column) => $column->id, $kanbancceadcolumns);
        $kanbancceadboard->sequence = helper::heal_missing_columns($kanbancceadboard->sequence, $columnids);

        return [
            'common' => $common,
            'board' => $kanbancceadboard,
            'columns' => $kanbancceadcolumns,
            'cards' => $kanbancceadcards,
            'users' => $kanbancceadusers,
            'capabilities' => $caps,
            'discussions' => [],
            'history' => [],
        ];
    }

    /**
     * Build a compact label for the board selector button.
     *
     * @param stdClass $board Board record.
     * @return string
     */
    private static function get_board_short_label(stdClass $board): string {
        if (!empty($board->groupid)) {
            return groups_get_group_name((int)$board->groupid);
        }
        if (!empty($board->userid)) {
            $user = \core_user::get_user((int)$board->userid);
            return $user ? fullname($user) : get_string('userboard', 'mod_kanbanccead', '');
        }
        if (!empty($board->template)) {
            return get_string('template', 'mod_kanbanccead');
        }
        return get_string('courseboard', 'mod_kanbanccead');
    }

    /**
     * Build the current board icon name.
     *
     * @param stdClass $board Board record.
     * @return string
     */
    private static function get_board_icon(stdClass $board): string {
        if (!empty($board->groupid)) {
            return 'i/group';
        }
        if (!empty($board->userid)) {
            return 'i/user';
        }
        return '';
    }

    /**
     * Parameters for get_discussion_update().
     *
     * @return external_function_parameters
     */
    public static function get_discussion_update_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED),
            'boardid' => new external_value(PARAM_INT, 'board id', VALUE_REQUIRED),
            'cardid' => new external_value(PARAM_INT, 'card id', VALUE_REQUIRED),
            'timestamp' => new external_value(PARAM_INT, 'only get values modified after this timestamp', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Definition of return values of the get_discussion_update webservice function.
     *
     * @return external_single_structure
     */
    public static function get_discussion_update_returns(): external_single_structure {
        return new external_single_structure(
            [
                'update' => new external_value(PARAM_RAW, 'update JSON'),
            ]
        );
    }

    /**
     * Get card discussion from database.
     *
     * @param int $cmid the course module id of the kanbanccead board
     * @param int $boardid the id of the kanbanccead board
     * @param int $cardid the id of the card
     * @param int $timestamp the timestamp of the discussion present in the frontend
     * @return array The requested content
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function get_discussion_update(int $cmid, int $boardid, int $cardid, int $timestamp = 0): array {
        global $DB, $USER;
        [$course, $cminfo] = get_course_and_cm_from_cmid($cmid);
        $context = context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/kanbanccead:view', $context);

        $boardmanager = new boardmanager($cmid, $boardid);
        $kanbancceadboard = $boardmanager->get_board();

        helper::check_permissions_for_user_or_group($kanbancceadboard, $context, $cminfo, constants::MOD_KANBANCCEAD_VIEW);

        $sql = 'kanbanccead_card = :cardid AND timecreated > :timestamp';
        $params['cardid'] = $cardid;
        $params['timestamp'] = $timestamp;

        $discussions = $DB->get_records_select('kanbanccead_comment', $sql, $params);

        $formatter = new updateformatter();
        foreach ($discussions as $discussion) {
            $discussion->content = format_text($discussion->content, FORMAT_HTML);
            $discussion->candelete = $discussion->userid == $USER->id || has_capability('mod/kanbanccead:manageboard', $context);
            $discussion->username = fullname(\core_user::get_user($discussion->userid));
            if (!empty($boardmanager->get_instance()->usenumbers) && !empty($boardmanager->get_instance()->linknumbers)) {
                $discussion->content = numberfilter::filter($discussion->content);
            }
            $formatter->put('discussions', (array) $discussion, false);
        }
        return [
            'update' => $formatter->get_formatted_updates(),
        ];
    }

    /**
     * Parameters for get_history_update().
     *
     * @return external_function_parameters
     */
    public static function get_history_update_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED),
            'boardid' => new external_value(PARAM_INT, 'board id', VALUE_REQUIRED),
            'cardid' => new external_value(PARAM_INT, 'card id', VALUE_REQUIRED),
            'timestamp' => new external_value(PARAM_INT, 'only get values modified after this timestamp', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Definition of return values of the get_history_update webservice function.
     *
     * @return external_single_structure
     */
    public static function get_history_update_returns(): external_single_structure {
        return new external_single_structure(
            [
                'update' => new external_value(PARAM_RAW, 'update JSON'),
            ]
        );
    }

    /**
     * Get card history from database.
     *
     * @param int $cmid the course module id of the kanbanccead board
     * @param int $boardid the id of the kanbanccead board
     * @param int $cardid the id of the card
     * @param int $timestamp the timestamp of the history present in the frontend
     * @return array The requested content
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function get_history_update(int $cmid, int $boardid, int $cardid, int $timestamp = 0): array {
        global $DB;
        [$course, $cminfo] = get_course_and_cm_from_cmid($cmid);
        $context = context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/kanbanccead:viewhistory', $context);

        $formatter = new updateformatter();
        $kanbanccead = $DB->get_record('kanbanccead', ['id' => $cminfo->instance]);
        if (!empty($kanbanccead->history)) {
            $kanbancceadboard = helper::get_cached_board($boardid);

            helper::check_permissions_for_user_or_group($kanbancceadboard, $context, $cminfo, constants::MOD_KANBANCCEAD_VIEW);

            $sql = 'kanbanccead_card = :id AND timestamp > :time';
            $params = ['id' => $cardid, 'time' => $timestamp];
            $historyitems = $DB->get_records_select('kanbanccead_history', $sql, $params);

            foreach ($historyitems as $item) {
                $item->affectedusername = get_string('unknownuser');
                $item->username = get_string('unknownuser');
                if (!empty($item->userid)) {
                    $user = \core_user::get_user($item->userid);
                    if ($user) {
                        $item->username = fullname($user);
                    }
                }
                if (!empty($item->affected_userid)) {
                    $affecteduser = \core_user::get_user($item->affected_userid);
                    if ($affecteduser) {
                        $item->affectedusername = fullname($affecteduser);
                    }
                }

                $type = constants::MOD_KANBANCCEAD_TYPES[$item->type];
                // One has to be careful, because $item->parameters theoretically could contain user input.
                $item->parameters = helper::sanitize_json_string($item->parameters);
                $item = (object) array_merge((array) $item, json_decode($item->parameters, true));
                $historyitem = [];
                $historyitem['id'] = $item->id;
                $historyitem['text'] = get_string('history_' . $type . '_' . $item->action, 'mod_kanbanccead', $item);
                $historyitem['timestamp'] = $item->timestamp;
                $historyitem['kanbanccead_card'] = $cardid;
                $formatter->put("history", $historyitem);
            }
        }
        return [
            'update' => $formatter->get_formatted_updates(),
        ];
    }

    /**
     * Get the timestamp of the latest entry in a db table from cache.
     *
     * @param int $type one of constants::MOD_KANBANCCEAD_BOARD, constants::MOD_KANBANCCEAD_COLUMN
     *     or constants::MOD_KANBANCCEAD_CARD
     * @param int $id Id of the board
     * @return mixed timestamp or false if none found
     */
    public static function get_cached_timestamp(int $type, int $id): mixed {
        $cache = \cache::make('mod_kanbanccead', 'timestamp');
        return $cache->get(join('-', [$type, $id]));
    }

    /**
     * Set the timestamp of the latest entry in a db table from cache.
     *
     * @param int $type one of constants::MOD_KANBANCCEAD_BOARD, constants::MOD_KANBANCCEAD_COLUMN
     *     or constants::MOD_KANBANCCEAD_CARD
     * @param int $timestamp value
     * @param int $id Id of the board
     */
    public static function set_cached_timestamp(int $type, int $timestamp, int $id): void {
        $cache = \cache::make('mod_kanbanccead', 'timestamp');
        $cache->set(join('-', [$type, $id]), $timestamp);
    }
}
