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
 * View a kanbanccead instance
 *
 * @package     mod_kanbanccead
 * @copyright   2023-2024 ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$coursecontext = context_course::instance($course->id);

$PAGE->set_url('/mod/kanbanccead/index.php', ['id' => $id]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();

$modulenameplural = get_string('modulenameplural', 'mod_kanbanccead');
echo $OUTPUT->heading($modulenameplural);

$kanbancceads = get_all_instances_in_course('kanbanccead', $course);
$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $table->head = [get_string('sectionname', 'format_' . $course->format), get_string('name')];
    $table->align = ['left', 'left'];
} else {
    $table->head  = [get_string('name')];
    $table->align = ['left'];
}

$kanbancceadfound = false;

foreach ($kanbancceads as $kanbanccead) {
    $context = context_module::instance($kanbanccead->coursemodule, IGNORE_MISSING);
    if (!$context || !has_capability('mod/kanbanccead:view', $context)) {
        continue;
    }

    $kanbancceadfound = true;
    $linkcss = null;

    if (!$kanbanccead->visible) {
        $linkcss = ['class' => 'dimmed'];
    }

    $link = html_writer::link(
        new moodle_url('/mod/kanbanccead/view.php', ['id' => $kanbanccead->coursemodule]),
        $kanbanccead->name,
        $linkcss
    );

    if ($usesections) {
        $table->data[] = [get_section_name($course, $kanbanccead->section), $link];
    } else {
        $table->data[] = [$link];
    }
}

if (!$kanbancceadfound) {
    notice(get_string('nokanbancceadinstances', 'mod_kanbanccead'), new moodle_url('/course/view.php', ['id' => $course->id]));
} else {
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
