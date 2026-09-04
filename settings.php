<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Admin settings for mod_kanbanccead
 *
 * @package     mod_kanbanccead
 * @copyright   2023-2024 ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'mod_kanbanccead/liveupdatetime',
        get_string('liveupdatetime', 'kanbanccead'),
        get_string('liveupdatetimedescription', 'kanbanccead'),
        10,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configcheckbox(
        'mod_kanbanccead/enablehistory',
        get_string('enablehistory', 'kanbanccead'),
        get_string('enablehistorydescription', 'kanbanccead'),
        true,
        PARAM_BOOL
    ));
}
