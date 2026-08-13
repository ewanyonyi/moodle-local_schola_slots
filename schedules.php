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

$view = optional_param('view', 'grid', PARAM_ALPHA); // 'grid' or 'table'
$roomid = optional_param('roomid', 0, PARAM_INT);
$teacherid = optional_param('teacherid', 0, PARAM_INT);

$url = new moodle_url('/local/academic_timetabler/schedules.php', [
    'view' => $view, 'roomid' => $roomid, 'teacherid' => $teacherid,
]);
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

$generateurl = new moodle_url('/local/academic_timetabler/index.php', [
    'action' => 'generate',
    'sesskey' => sesskey(),
]);

echo html_writer::start_div('mb-4 d-flex align-items-center flex-wrap');
echo html_writer::link($navurls['index'], 'Overview', ['class' => 'btn btn-outline-primary me-2']);
echo html_writer::link($navurls['rooms'], 'Manage Rooms', ['class' => 'btn btn-outline-primary me-2']);
echo html_writer::link($navurls['slots'], 'Manage Time Slots', ['class' => 'btn btn-outline-primary me-2']);
echo html_writer::link($navurls['schedules'], 'View Timetables', ['class' => 'btn btn-primary me-2']);
echo html_writer::link($generateurl, '⚡ Generate Timetable', ['class' => 'btn btn-success font-weight-bold shadow-sm']);
echo html_writer::end_div();

$days = [
    1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
    4 => 'Thursday', 5 => 'Friday',
];

// Fetch rooms and teachers for dropdown filters
$allrooms = $DB->get_records('local_att_rooms', null, 'name ASC');
$roomoptions = [0 => '-- All Campus Venues --'];
foreach ($allrooms as $r) {
    $roomoptions[$r->id] = $r->name . ' (' . $r->capacity . ' seats)';
}

$allteachers = $DB->get_records_sql("SELECT DISTINCT u.id, u.firstname, u.lastname
                                      FROM {user} u
                                      JOIN {local_att_schedules} s ON s.teacherid = u.id
                                  ORDER BY u.lastname ASC");
$teacheroptions = [0 => '-- All Faculty Members --'];
foreach ($allteachers as $t) {
    $teacheroptions[$t->id] = fullname($t);
}

// -------------------------------------------------------------------
// Export & View Control Toolbar
// -------------------------------------------------------------------
$csvexporturl = new moodle_url('/local/academic_timetabler/export.php', [
    'action' => 'csv', 'roomid' => $roomid, 'teacherid' => $teacherid,
]);
$pdfexporturl = new moodle_url('/local/academic_timetabler/export.php', [
    'action' => 'print', 'roomid' => $roomid, 'teacherid' => $teacherid, 'autoprint' => 1,
]);

echo html_writer::start_div('card shadow-sm mb-4');
echo html_writer::start_div('card-body d-flex flex-wrap align-items-center justify-content-between gap-3');

// Left side: Export / Print Action Buttons
echo html_writer::start_div('d-flex gap-2 flex-wrap');
echo html_writer::link($csvexporturl, '📥 Export to CSV', ['class' => 'btn btn-outline-success font-weight-bold']);
echo html_writer::link($pdfexporturl, '🖨️ Print / Save to PDF', ['class' => 'btn btn-outline-primary font-weight-bold', 'target' => '_blank']);
echo html_writer::end_div();

// Middle: View Mode Toggle
echo html_writer::start_div('btn-group', ['role' => 'group']);
$gridactive = ($view === 'grid') ? 'btn-primary' : 'btn-outline-primary';
$tableactive = ($view === 'table') ? 'btn-primary' : 'btn-outline-primary';
echo html_writer::link(new moodle_url($url, ['view' => 'grid']), '📅 Weekly Matrix View', ['class' => "btn {$gridactive}"]);
echo html_writer::link(new moodle_url($url, ['view' => 'table']), '📋 Master List View', ['class' => "btn {$tableactive}"]);
echo html_writer::end_div();

// Right side: Filter Controls
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $url->out(false), 'class' => 'd-flex gap-2 align-items-center flex-wrap']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'view', 'value' => $view]);
echo html_writer::select($roomoptions, 'roomid', $roomid, false, ['class' => 'form-select form-select-sm', 'onchange' => 'this.form.submit()']);
echo html_writer::select($teacheroptions, 'teacherid', $teacherid, false, ['class' => 'form-select form-select-sm', 'onchange' => 'this.form.submit()']);
if ($roomid > 0 || $teacherid > 0) {
    echo html_writer::link(new moodle_url($url, ['roomid' => 0, 'teacherid' => 0]), 'Reset Filters', ['class' => 'btn btn-sm btn-outline-secondary']);
}
echo html_writer::end_tag('form');

echo html_writer::end_div();
echo html_writer::end_div();

// -------------------------------------------------------------------
// Query Schedules
// -------------------------------------------------------------------
$where = [];
$params = [];
if ($roomid > 0) {
    $where[] = 's.roomid = :roomid';
    $params['roomid'] = $roomid;
}
if ($teacherid > 0) {
    $where[] = 's.teacherid = :teacherid';
    $params['teacherid'] = $teacherid;
}
$wherestr = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT s.id, s.schedule_type, c.shortname AS coursecode, c.fullname AS coursename,
               r.name AS roomname, sl.dayofweek, sl.starttime, sl.endtime,
               u.firstname, u.lastname
          FROM {local_att_schedules} s
          JOIN {course} c ON c.id = s.courseid
          JOIN {local_att_rooms} r ON r.id = s.roomid
          JOIN {local_att_slots} sl ON sl.id = s.slotid
          LEFT JOIN {user} u ON u.id = s.teacherid
          {$wherestr}
      ORDER BY sl.dayofweek ASC, sl.starttime ASC, r.name ASC";

$schedules = $DB->get_records_sql($sql, $params);

if (empty($schedules)) {
    echo html_writer::div(get_string('no_schedules', 'local_academic_timetabler'), 'alert alert-info');
} else if ($view === 'grid') {
    // -------------------------------------------------------------------
    // 5-Day Weekly Matrix View
    // -------------------------------------------------------------------
    echo html_writer::tag('h4', 'Interactive 5-Day Weekly Timetable Grid Matrix', ['class' => 'mb-3']);

    // Extract unique time windows
    $timeblocks = [];
    foreach ($schedules as $s) {
        $window = s($s->starttime) . ' - ' . s($s->endtime);
        if (!in_array($window, $timeblocks)) {
            $timeblocks[] = $window;
        }
    }
    sort($timeblocks);

    // Group schedules by timeblock and dayofweek
    $matrix = [];
    foreach ($schedules as $s) {
        $window = s($s->starttime) . ' - ' . s($s->endtime);
        $matrix[$window][$s->dayofweek][] = $s;
    }

    echo html_writer::start_div('table-responsive shadow-sm rounded border mb-4');
    echo html_writer::start_tag('table', ['class' => 'table table-bordered align-middle text-center mb-0', 'style' => 'font-size:13px;']);
    echo html_writer::start_tag('thead', ['class' => 'table-dark']);
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', 'Time Window', ['style' => 'width: 14%; font-size:14px;']);
    foreach ($days as $daynum => $dayname) {
        echo html_writer::tag('th', $dayname, ['style' => 'width: 17%; font-size:14px;']);
    }
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');
    foreach ($timeblocks as $timeblock) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', '<strong>' . $timeblock . '</strong>', ['class' => 'bg-light font-weight-bold']);

        for ($day = 1; $day <= 5; $day++) {
            echo html_writer::start_tag('td', ['class' => 'p-2']);
            $entries = $matrix[$timeblock][$day] ?? [];
            if (empty($entries)) {
                echo '<span class="text-muted small">&mdash;</span>';
            } else {
                foreach ($entries as $entry) {
                    $teacher = (!empty($entry->firstname) || !empty($entry->lastname)) ? fullname($entry) : 'Unassigned';
                    echo html_writer::start_div('p-2 mb-1 rounded bg-primary text-white text-start shadow-sm');
                    echo html_writer::div('<strong>' . s($entry->coursecode) . '</strong>', 'fw-bold small');
                    echo html_writer::div('📍 ' . s($entry->roomname), 'small opacity-90');
                    echo html_writer::div('👨‍🏫 ' . s($teacher), 'small opacity-75');
                    echo html_writer::end_div();
                }
            }
            echo html_writer::end_tag('td');
        }
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();

} else {
    // -------------------------------------------------------------------
    // Master List Table View
    // -------------------------------------------------------------------
    echo html_writer::tag('h4', 'Master Schedule Allocations List', ['class' => 'mb-3']);
    $table = new html_table();
    $table->head = ['Schedule ID', 'Course Code & Name', 'Assigned Room', 'Day & Time Window', 'Instructor', 'Type'];
    $table->attributes = ['class' => 'table table-striped table-bordered align-middle'];

    foreach ($schedules as $sched) {
        $dayname = $days[$sched->dayofweek] ?? 'Day ' . $sched->dayofweek;
        $teacher = (!empty($sched->firstname) || !empty($sched->lastname)) ? fullname($sched) : 'Unassigned';

        $table->data[] = [
            $sched->id,
            '<strong>' . s($sched->coursecode) . '</strong> - ' . s($sched->coursename),
            s($sched->roomname),
            '<strong>' . $dayname . '</strong> (' . s($sched->starttime) . ' - ' . s($sched->endtime) . ')',
            s($teacher),
            '<span class="badge bg-secondary text-white">' . strtoupper($sched->schedule_type) . '</span>',
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
