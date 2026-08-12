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
 * View generated timetables for local_academic_timetabler.
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

$url = new moodle_url('/local/academic_timetabler/schedules.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('manage_schedules', 'local_academic_timetabler'));
$PAGE->set_heading(get_string('manage_schedules', 'local_academic_timetabler'));

echo $OUTPUT->header();

$navurls = [
    'index' => new moodle_url('/local/academic_timetabler/index.php'),
    'rooms' => new moodle_url('/local/academic_timetabler/rooms.php'),
    'slots' => new moodle_url('/local/academic_timetabler/slots.php'),
    'schedules' => $url,
];

echo html_writer::start_div('mb-4');
echo html_writer::link($navurls['index'], 'Overview', ['class' => 'btn btn-outline-primary me-2']);
echo html_writer::link($navurls['rooms'], 'Manage Rooms', ['class' => 'btn btn-outline-primary me-2']);
echo html_writer::link($navurls['slots'], 'Manage Time Slots', ['class' => 'btn btn-outline-primary me-2']);
echo html_writer::link($navurls['schedules'], 'View Timetables', ['class' => 'btn btn-primary me-2']);
echo html_writer::end_div();

$sql = "SELECT s.id, s.schedule_type, c.fullname AS coursename, r.name AS roomname,
               sl.dayofweek, sl.starttime, sl.endtime, u.firstname, u.lastname
          FROM {local_att_schedules} s
          JOIN {course} c ON c.id = s.courseid
          JOIN {local_att_rooms} r ON r.id = s.roomid
          JOIN {local_att_slots} sl ON sl.id = s.slotid
          LEFT JOIN {user} u ON u.id = s.teacherid
      ORDER BY sl.dayofweek ASC, sl.starttime ASC";

$schedules = $DB->get_records_sql($sql);

$days = [
    1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
    4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
];

echo html_writer::tag('h4', 'Master Schedule Allocations');

if (empty($schedules)) {
    echo html_writer::div(get_string('no_schedules', 'local_academic_timetabler'), 'alert alert-info');
} else {
    $table = new html_table();
    $table->head = ['Schedule ID', 'Course Name', 'Assigned Room', 'Day & Time', 'Instructor', 'Type'];
    $table->attributes = ['class' => 'table table-striped table-bordered align-middle'];

    foreach ($schedules as $sched) {
        $dayname = $days[$sched->dayofweek] ?? 'Day ' . $sched->dayofweek;
        $teacher = (!empty($sched->firstname) || !empty($sched->lastname))
            ? fullname($sched)
            : 'Unassigned';

        $table->data[] = [
            $sched->id,
            s($sched->coursename),
            s($sched->roomname),
            $dayname . ' (' . s($sched->starttime) . ' - ' . s($sched->endtime) . ')',
            s($teacher),
            strtoupper($sched->schedule_type),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
