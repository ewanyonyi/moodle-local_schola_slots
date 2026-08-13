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

$url = new moodle_url('/local/academic_timetabler/slots.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('manage_slots', 'local_academic_timetabler'));
$PAGE->set_heading(get_string('manage_slots', 'local_academic_timetabler'));

if ($action === 'delete' && $id > 0 && confirm_sesskey()) {
    $DB->delete_records('local_att_slots', ['id' => $id]);
    redirect($url, 'Time slot deleted successfully.');
}

$editslot = null;
if ($action === 'edit' && $id > 0) {
    $editslot = $DB->get_record('local_att_slots', ['id' => $id]);
}

if ($data = data_submitted() && confirm_sesskey()) {
    $editslotid = optional_param('slotid', 0, PARAM_INT);
    $dayofweek = optional_param('dayofweek', 1, PARAM_INT);
    $starttime = optional_param('starttime', '', PARAM_TEXT);
    $endtime = optional_param('endtime', '', PARAM_TEXT);
    $selecttype = optional_param('type_select', 'Class Lecture', PARAM_TEXT);
    $customtype = optional_param('custom_type', '', PARAM_TEXT);

    $finaltype = ($selecttype === 'custom' && !empty($customtype)) ? $customtype : $selecttype;

    if (!empty($starttime) && !empty($endtime)) {
        $record = (object)[
            'type' => $finaltype,
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

$cardheader = $editslot ? 'Edit Weekly Time Slot' : 'Add New Weekly Time Slot';
$btnlabel = $editslot ? 'Update Time Slot' : 'Save Time Slot';

echo html_writer::start_div('card shadow-sm mb-4');
echo html_writer::div(html_writer::tag('h5', $cardheader, ['class' => 'mb-0']), 'card-header bg-light');
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

$typeoptions = [
    'Class Lecture' => 'Class Lecture',
    'Exam Period' => 'Exam Period',
    'Lunch Break' => 'Lunch Break',
    'Tea / Morning Break' => 'Tea / Morning Break',
    'Institutional Assembly' => 'Institutional Assembly',
    'Sports & Co-curricular' => 'Sports & Co-curricular',
    'custom' => '-- Custom Slot Type... --',
];

$currenttype = $editslot ? $editslot->type : 'Class Lecture';
$iscustom = $editslot && !array_key_exists($currenttype, $typeoptions);
$selectedselect = $iscustom ? 'custom' : $currenttype;

echo html_writer::start_div('row g-3');
echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', 'Day of Week', ['class' => 'form-label']);
$selectedday = $editslot ? $editslot->dayofweek : 1;
echo html_writer::select($days, 'dayofweek', $selectedday, false, ['class' => 'form-select']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-2');
echo html_writer::tag('label', 'Start Time', ['class' => 'form-label']);
echo html_writer::empty_tag('input', [
    'type' => 'time', 'name' => 'starttime', 'class' => 'form-control',
    'value' => $editslot ? s($editslot->starttime) : '08:00', 'required' => 'required',
]);
echo html_writer::end_div();

echo html_writer::start_div('col-md-2');
echo html_writer::tag('label', 'End Time', ['class' => 'form-label']);
echo html_writer::empty_tag('input', [
    'type' => 'time', 'name' => 'endtime', 'class' => 'form-control',
    'value' => $editslot ? s($editslot->endtime) : '10:00', 'required' => 'required',
]);
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', 'Slot Type / Purpose', ['class' => 'form-label']);
$selectscript = "if(this.value==='custom'){document.getElementById('custom_type_wrapper').style.display='block';}" .
                "else{document.getElementById('custom_type_wrapper').style.display='none';}";
echo html_writer::select($typeoptions, 'type_select', $selectedselect, false, [
    'class' => 'form-select', 'onchange' => $selectscript,
]);
echo html_writer::end_div();

$wrapperstyle = $iscustom ? 'display:block;' : 'display:none;';
echo html_writer::start_div('col-md-2', ['id' => 'custom_type_wrapper', 'style' => $wrapperstyle]);
echo html_writer::tag('label', 'Specify Custom Type', ['class' => 'form-label']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'custom_type', 'class' => 'form-control',
    'placeholder' => 'e.g. Prayer Break', 'value' => $iscustom ? s($currenttype) : '',
]);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('mt-3');
echo html_writer::tag('button', $btnlabel, ['type' => 'submit', 'class' => 'btn btn-success me-2']);
if ($editslot) {
    echo html_writer::link($url, 'Cancel Edit', ['class' => 'btn btn-secondary']);
}
echo html_writer::end_div();
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

$slots = $DB->get_records('local_att_slots', null, 'dayofweek ASC, starttime ASC');
echo html_writer::tag('h4', 'Configured Timetable Slots');

if (empty($slots)) {
    echo html_writer::div(get_string('no_slots', 'local_academic_timetabler'), 'alert alert-info');
} else {
    $table = new html_table();
    $table->head = ['ID', 'Day', 'Time Window', 'Slot Type / Purpose', 'Actions'];
    $table->attributes = ['class' => 'table table-striped table-bordered align-middle'];

    foreach ($slots as $slot) {
        $editurl = new moodle_url($url, ['action' => 'edit', 'id' => $slot->id]);
        $editbtn = html_writer::link($editurl, 'Edit', ['class' => 'btn btn-sm btn-outline-primary me-2']);

        $deleteurl = new moodle_url($url, ['action' => 'delete', 'id' => $slot->id, 'sesskey' => sesskey()]);
        $deletebtn = html_writer::link($deleteurl, 'Delete', [
            'class' => 'btn btn-sm btn-outline-danger',
            'onclick' => 'return confirm("Are you sure you want to delete this time slot?");',
        ]);

        $dayname = $days[$slot->dayofweek] ?? 'Unknown';

        $table->data[] = [
            $slot->id,
            $dayname,
            s($slot->starttime) . ' - ' . s($slot->endtime),
            s($slot->type),
            $editbtn . $deletebtn,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
