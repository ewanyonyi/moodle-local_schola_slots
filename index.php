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
 * Main admin dashboard view for local_academic_timetabler.
 *
 * @package     local_academic_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/academic_timetabler:manage', $context);

$PAGE->set_url(new moodle_url('/local/academic_timetabler/index.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_academic_timetabler'));
$PAGE->set_heading(get_string('pluginname', 'local_academic_timetabler'));

$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'generate' && confirm_sesskey()) {
    global $DB;
    \core_php_time_limit::raise(600);
    raise_memory_limit(MEMORY_EXTRA);

    // Exclude Site Home course (id = 1)
    $courses = $DB->get_records_select('course', 'id > 1 AND visible = 1', null, 'id ASC');
    $slots   = $DB->get_records('local_att_slots');
    $rooms   = $DB->get_records('local_att_rooms');

    if (empty($rooms)) {
        redirect(
            new moodle_url('/local/academic_timetabler/rooms.php'),
            'Please configure at least one room before generating timetables.',
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    if (empty($slots)) {
        redirect(
            new moodle_url('/local/academic_timetabler/slots.php'),
            'Please configure time slots before generating timetables.',
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    // Community edition limit handling: cap courses to limit if on community tier
    $tier = \local_academic_timetabler\licensing\license_manager::get_tier();
    if ($tier === \local_academic_timetabler\licensing\license_manager::TIER_COMMUNITY) {
        $courses = array_slice($courses, 0, \local_academic_timetabler\licensing\license_manager::COMMUNITY_COURSE_LIMIT, true);
    }

    try {
        $solver = new \local_academic_timetabler\algorithm\solver($slots, $rooms);
        $solver->load_courses($courses);

        if ($solver->solve_all()) {
            $solution = $solver->get_solution();
            $DB->delete_records('local_att_schedules');

            $count = 0;
            foreach ($solution['classes'] ?? [] as $assignedkey => $sched) {
                $courseid = $sched['course_id'] ?? (int)$assignedkey;
                $DB->insert_record('local_att_schedules', (object)[
                    'schedule_type' => 'class',
                    'courseid'     => $courseid,
                    'roomid'       => $sched['room_id'],
                    'slotid'       => $sched['slot_id'],
                    'teacherid'    => $sched['teacher_id'],
                ]);
                $count++;
            }
            redirect(
                new moodle_url('/local/academic_timetabler/schedules.php'),
                "Timetable generated successfully! {$count} course sessions assigned conflict-free.",
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } else {
            redirect(
                new moodle_url('/local/academic_timetabler/index.php'),
                "Notice: Solver could not assign all courses without conflicts. Try adding more rooms or time slots.",
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }
    } catch (\Exception $e) {
        redirect(
            new moodle_url('/local/academic_timetabler/index.php'),
            "Error running timetable generator: " . $e->getMessage(),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

echo $OUTPUT->header();
$output = $PAGE->get_renderer('local_academic_timetabler');
echo $output->render_dashboard([]);
echo $OUTPUT->footer();
