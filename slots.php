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
 * Manage weekly time slots for local_academic_timetabler.
 * Supports functional slot types (class, lab, break, exam) and batch setup tools.
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
$id     = optional_param('id', 0, PARAM_INT);

$url = new moodle_url('/local/academic_timetabler/slots.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('manage_slots', 'local_academic_timetabler'));
$PAGE->set_heading(get_string('manage_slots', 'local_academic_timetabler'));

// -------------------------------------------------------------------
// Action: Delete Single Slot
// -------------------------------------------------------------------
if ($action === 'delete' && $id > 0 && confirm_sesskey()) {
    $DB->delete_records('local_att_slots', ['id' => $id]);
    redirect($url, 'Time slot deleted successfully.');
}

// -------------------------------------------------------------------
// Action: Clear All Slots
// -------------------------------------------------------------------
if ($action === 'clearall' && confirm_sesskey()) {
    $DB->delete_records('local_att_slots');
    redirect($url, 'All configured time slots cleared successfully.');
}

// -------------------------------------------------------------------
// Action: Batch Generator Tool
// -------------------------------------------------------------------
if ($action === 'preset' && confirm_sesskey()) {
    $presettype = optional_param('preset_type', 'academic', PARAM_ALPHA);
    $wipeexisting = optional_param('wipe_existing', 0, PARAM_INT);

    if ($wipeexisting) {
        $DB->delete_records('local_att_slots');
    }

    $inserted = 0;
    // Generate for Monday to Friday (days 1-5)
    for ($day = 1; $day <= 5; $day++) {
        if ($presettype === 'academic') {
            $templates = [
                ['07:00', '08:30', 'class'],
                ['08:30', '10:00', 'class'],
                ['10:00', '10:30', 'break'], // Tea Break
                ['10:30', '12:00', 'class'],
                ['12:00', '13:30', 'break'], // Lunch Break
                ['13:30', '15:00', 'class'],
                ['15:00', '16:30', 'class'],
                ['16:30', '18:00', 'class'],
            ];
        } else if ($presettype === 'exam') {
            $templates = [
                ['08:30', '11:30', 'exam'],
                ['11:30', '13:30', 'break'], // Exam Intermission
                ['13:30', '16:30', 'exam'],
            ];
        } else if ($presettype === 'lab') {
            $templates = [
                ['14:00', '17:00', 'lab'],
            ];
        }

        foreach ($templates as $t) {
            $DB->insert_record('local_att_slots', (object)[
                'dayofweek' => $day,
                'starttime' => $t[0],
                'endtime'   => $t[1],
                'type'      => $t[2],
            ]);
            $inserted++;
        }
    }
    redirect($url, "Preset '{$presettype}' generated successfully! {$inserted} time slots added for Mon-Fri.");
}

// -------------------------------------------------------------------
// Action: Add / Edit Single Slot
// -------------------------------------------------------------------
$editslot = null;
if ($action === 'edit' && $id > 0) {
    $editslot = $DB->get_record('local_att_slots', ['id' => $id]);
}

if ($data = data_submitted() && confirm_sesskey()) {
    $editslotid = optional_param('slotid', 0, PARAM_INT);
    $dayofweek = optional_param('dayofweek', 1, PARAM_INT);
    $starttime = optional_param('starttime', '', PARAM_TEXT);
    $endtime = optional_param('endtime', '', PARAM_TEXT);
    $type      = optional_param('type', 'class', PARAM_ALPHA);

    if (!empty($starttime) && !empty($endtime)) {
        $record = (object)[
            'type' => $type,
            'dayofweek' => $dayofweek,
            'starttime' => $starttime,
            'endtime' => $endtime,
        ];

        if ($editslotid > 0) {
            $record->id = $editslotid;
            $DB->update_record('local_att_slots', $record);
            redirect($url, 'Time slot updated successfully.');
        } else {
            $DB->insert_record('local_att_slots', $record);
            redirect($url, 'Time slot added successfully.');
        }
    }
}

echo $OUTPUT->header();

echo \local_academic_timetabler\output\renderer::render_nav_header('slots');

// -------------------------------------------------------------------
// Batch Slot Preset Generator Card
// -------------------------------------------------------------------
echo html_writer::start_div('card border-0 shadow-sm mb-4 bg-white');
echo html_writer::div(html_writer::tag('h5', '1-Click Batch Slot Generator (7 AM - 6 PM Templates)', ['class' => 'mb-0 font-weight-bold']), 'card-header bg-dark text-white p-3');
echo html_writer::start_div('card-body p-4');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'preset']);

$presetoptions = [
    'academic' => 'Standard Academic Day (07:00 - 18:00 with Tea & Lunch Breaks)',
    'exam'     => 'Examination Period (08:30 - 11:30 Morning & 13:30 - 16:30 Afternoon Exams)',
    'lab'      => 'Practical Lab Afternoon Blocks (14:00 - 17:00 Mon-Fri)',
];

echo html_writer::start_div('row g-3 align-items-center');
echo html_writer::start_div('col-md-6');
echo html_writer::tag('label', 'Select Schedule Template', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::select($presetoptions, 'preset_type', 'academic', false, ['class' => 'form-select p-2']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-3 pt-4');
echo html_writer::checkbox('wipe_existing', '1', true, ' Clear existing slots first', ['class' => 'form-check-input']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-3 pt-4 text-end');
echo html_writer::tag('button', 'Generate Batch Slots', ['type' => 'submit', 'class' => 'btn btn-success font-weight-bold px-4 py-2 shadow-sm']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

// -------------------------------------------------------------------
// Add / Edit Single Slot Form Card
// -------------------------------------------------------------------
$cardheader = $editslot ? 'Edit Weekly Time Slot' : 'Add Custom Time Slot';
$btnlabel   = $editslot ? 'Update Time Slot' : 'Save Time Slot';

echo html_writer::start_div('card shadow-sm mb-4');
echo html_writer::div(html_writer::tag('h5', $cardheader, ['class' => 'mb-0 font-weight-bold']), 'card-header bg-light');
echo html_writer::start_div('card-body');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
if ($editslot) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'slotid', 'value' => $editslot->id]);
}

$days = [
    1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
    4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
];

$slottypes = [
    'class' => 'Class Lecture (Standard Teaching Window)',
    'lab'   => 'Laboratory Practical (Extended Block)',
    'break' => 'Break / Blockout (Lunch, Tea Break, Assembly - Excluded from Solver)',
    'exam'  => 'Examination Period (Dedicated Exam Block)',
];

$selectedtype = $editslot ? $editslot->type : 'class';

echo html_writer::start_div('row g-3');
echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', 'Day of Week', ['class' => 'form-label font-weight-bold']);
$selectedday = $editslot ? $editslot->dayofweek : 1;
echo html_writer::select($days, 'dayofweek', $selectedday, false, ['class' => 'form-select']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-2');
echo html_writer::tag('label', 'Start Time', ['class' => 'form-label font-weight-bold']);
echo html_writer::empty_tag('input', [
    'type' => 'time', 'name' => 'starttime', 'class' => 'form-control',
    'value' => $editslot ? s($editslot->starttime) : '08:00', 'required' => 'required',
]);
echo html_writer::end_div();

echo html_writer::start_div('col-md-2');
echo html_writer::tag('label', 'End Time', ['class' => 'form-label font-weight-bold']);
echo html_writer::empty_tag('input', [
    'type' => 'time', 'name' => 'endtime', 'class' => 'form-control',
    'value' => $editslot ? s($editslot->endtime) : '09:30', 'required' => 'required',
]);
echo html_writer::end_div();

echo html_writer::start_div('col-md-5');
echo html_writer::tag('label', 'Slot Type / Functional Category', ['class' => 'form-label font-weight-bold']);
echo html_writer::select($slottypes, 'type', $selectedtype, false, ['class' => 'form-select']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('mt-3 d-flex gap-2');
echo html_writer::tag('button', $btnlabel, ['type' => 'submit', 'class' => 'btn btn-primary font-weight-bold']);
if ($editslot) {
    echo html_writer::link($url, 'Cancel Edit', ['class' => 'btn btn-outline-secondary']);
}
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

// -------------------------------------------------------------------
// Configured Time Slots Table
// -------------------------------------------------------------------
$slots = $DB->get_records('local_att_slots', null, 'dayofweek ASC, starttime ASC');

echo html_writer::start_div('d-flex align-items-center justify-content-between mb-3');
echo html_writer::tag('h4', 'Configured Weekly Time Slots (' . count($slots) . ')', ['class' => 'mb-0 font-weight-bold']);
if (!empty($slots)) {
    $clearallurl = new moodle_url($url, ['action' => 'clearall', 'sesskey' => sesskey()]);
    echo html_writer::link($clearallurl, 'Clear All Slots', [
        'class' => 'btn btn-sm btn-outline-danger font-weight-bold',
        'onclick' => 'return confirm("Are you sure you want to clear all configured time slots?");',
    ]);
}
echo html_writer::end_div();

if (empty($slots)) {
    echo html_writer::div(get_string('no_slots', 'local_academic_timetabler'), 'alert alert-info');
} else {
    $table = new html_table();
    $table->head = ['ID', 'Day', 'Time Window', 'Functional Category', 'Actions'];
    $table->attributes = ['class' => 'table table-striped table-bordered align-middle'];

    foreach ($slots as $slot) {
        $dayname = $days[$slot->dayofweek] ?? ('Day ' . $slot->dayofweek);
        $editurl = new moodle_url($url, ['action' => 'edit', 'id' => $slot->id]);
        $editbtn = html_writer::link($editurl, 'Edit', ['class' => 'btn btn-sm btn-outline-primary me-2']);

        $delurl = new moodle_url($url, ['action' => 'delete', 'id' => $slot->id, 'sesskey' => sesskey()]);
        $delbtn = html_writer::link($delurl, 'Delete', [
            'class' => 'btn btn-sm btn-outline-danger',
            'onclick' => 'return confirm("Delete this time slot?");',
        ]);

        $typebadge = match ($slot->type) {
            'break' => '<span class="badge bg-danger text-white px-2 py-1">BREAK / BLOCKOUT</span>',
            'lab'   => '<span class="badge bg-info text-dark px-2 py-1">LABORATORY</span>',
            'exam'  => '<span class="badge bg-warning text-dark px-2 py-1">EXAM PERIOD</span>',
            default => '<span class="badge bg-secondary text-white px-2 py-1">CLASS LECTURE</span>',
        };

        $table->data[] = [
            $slot->id,
            '<strong>' . $dayname . '</strong>',
            s($slot->starttime) . ' &mdash; ' . s($slot->endtime),
            $typebadge,
            $editbtn . $delbtn,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
