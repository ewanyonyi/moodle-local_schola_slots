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
 * Manage venues and rooms for local_academic_timetabler.
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

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

$url = new moodle_url('/local/academic_timetabler/rooms.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('manage_rooms', 'local_academic_timetabler'));
$PAGE->set_heading(get_string('manage_rooms', 'local_academic_timetabler'));

if ($action === 'delete' && $id > 0 && confirm_sesskey()) {
    $DB->delete_records('local_att_rooms', ['id' => $id]);
    redirect($url, 'Room deleted successfully.');
}

$editroom = null;
if ($action === 'edit' && $id > 0) {
    $editroom = $DB->get_record('local_att_rooms', ['id' => $id]);
}

if ($data = data_submitted() && confirm_sesskey()) {
    $editid = optional_param('roomid', 0, PARAM_INT);
    $name = optional_param('name', '', PARAM_TEXT);
    $capacity = optional_param('capacity', 0, PARAM_INT);
    $islab = optional_param('is_lab', 0, PARAM_INT);

    if (!empty($name) && $capacity > 0) {
        $record = (object)[
            'name' => $name,
            'capacity' => $capacity,
            'is_lab' => $islab ? 1 : 0,
        ];

        if ($editid > 0) {
            $record->id = $editid;
            $DB->update_record('local_att_rooms', $record);
            redirect($url, 'Room updated successfully.');
        } else {
            $DB->insert_record('local_att_rooms', $record);
            redirect($url, 'Room added successfully.');
        }
    }
}

echo $OUTPUT->header();

$navurls = [
    'index' => new moodle_url('/local/academic_timetabler/index.php'),
    'rooms' => $url,
    'slots' => new moodle_url('/local/academic_timetabler/slots.php'),
    'schedules' => new moodle_url('/local/academic_timetabler/schedules.php'),
];

echo html_writer::start_div('mb-4');
echo html_writer::link($navurls['index'], 'Overview', ['class' => 'btn btn-outline-primary me-2']);
echo html_writer::link($navurls['rooms'], 'Manage Rooms', ['class' => 'btn btn-primary me-2']);
echo html_writer::link($navurls['slots'], 'Manage Time Slots', ['class' => 'btn btn-outline-primary me-2']);
echo html_writer::link($navurls['schedules'], 'View Timetables', ['class' => 'btn btn-outline-primary me-2']);
echo html_writer::end_div();

$cardheader = $editroom ? 'Edit Campus Room / Venue' : 'Add New Campus Room / Venue';
$btnlabel = $editroom ? 'Update Room' : 'Save Room';

echo html_writer::start_div('card shadow-sm mb-4');
echo html_writer::div(html_writer::tag('h5', $cardheader, ['class' => 'mb-0']), 'card-header bg-light');
echo html_writer::start_div('card-body');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
if ($editroom) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'roomid', 'value' => $editroom->id]);
}

echo html_writer::start_div('row g-3');
echo html_writer::start_div('col-md-5');
echo html_writer::tag('label', 'Room / Venue Name', ['class' => 'form-label']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'name', 'class' => 'form-control',
    'placeholder' => 'e.g. Science Complex 101', 'required' => 'required',
    'value' => $editroom ? s($editroom->name) : '',
]);
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', 'Seating Capacity', ['class' => 'form-label']);
echo html_writer::empty_tag('input', [
    'type' => 'number', 'name' => 'capacity', 'class' => 'form-control',
    'placeholder' => '100', 'required' => 'required', 'min' => '1',
    'value' => $editroom ? $editroom->capacity : '',
]);
echo html_writer::end_div();

echo html_writer::start_div('col-md-4 d-flex align-items-end');
echo html_writer::start_div('form-check mb-2');
$checkboxattrs = [
    'type' => 'checkbox', 'name' => 'is_lab', 'value' => '1',
    'class' => 'form-check-input', 'id' => 'is_lab_check',
];
if ($editroom && $editroom->is_lab) {
    $checkboxattrs['checked'] = 'checked';
}
echo html_writer::empty_tag('input', $checkboxattrs);
echo html_writer::tag('label', 'Laboratory / Computer Studio', ['class' => 'form-check-label', 'for' => 'is_lab_check']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('mt-3');
echo html_writer::tag('button', $btnlabel, ['type' => 'submit', 'class' => 'btn btn-success me-2']);
if ($editroom) {
    echo html_writer::link($url, 'Cancel Edit', ['class' => 'btn btn-secondary']);
}
echo html_writer::end_div();
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

$rooms = $DB->get_records('local_att_rooms');
echo html_writer::tag('h4', 'Configured Campus Venues');

if (empty($rooms)) {
    echo html_writer::div(get_string('no_rooms', 'local_academic_timetabler'), 'alert alert-info');
} else {
    $table = new html_table();
    $table->head = ['ID', 'Room Name', 'Capacity', 'Type', 'Actions'];
    $table->attributes = ['class' => 'table table-striped table-bordered align-middle'];

    foreach ($rooms as $room) {
        $editurl = new moodle_url($url, ['action' => 'edit', 'id' => $room->id]);
        $editbtn = html_writer::link($editurl, 'Edit', ['class' => 'btn btn-sm btn-outline-primary me-2']);

        $deleteurl = new moodle_url($url, ['action' => 'delete', 'id' => $room->id, 'sesskey' => sesskey()]);
        $deletebtn = html_writer::link($deleteurl, 'Delete', [
            'class' => 'btn btn-sm btn-outline-danger',
            'onclick' => 'return confirm("Are you sure you want to delete this room?");',
        ]);

        $table->data[] = [
            $room->id,
            s($room->name),
            $room->capacity,
            $room->is_lab ? 'Lab / Studio' : 'Lecture Hall',
            $editbtn . $deletebtn,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
