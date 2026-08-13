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
 * Manage reusable Schedule Templates for local_academic_timetabler.
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

$url = new moodle_url('/local/academic_timetabler/templates.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title('Schedule Templates');
$PAGE->set_heading('Manage Schedule Templates');

// -------------------------------------------------------------------
// Action: Delete Template
// -------------------------------------------------------------------
if ($action === 'delete' && $id > 0 && confirm_sesskey()) {
    $DB->delete_records('local_att_templates', ['id' => $id]);
    redirect($url, 'Schedule template deleted successfully.');
}

// -------------------------------------------------------------------
// Action: Apply Template to Active Time Slots
// -------------------------------------------------------------------
if ($action === 'apply' && $id > 0 && confirm_sesskey()) {
    $template = $DB->get_record('local_att_templates', ['id' => $id]);
    if ($template && !empty($template->slots_json)) {
        $slotsdata = json_decode($template->slots_json, true);
        if (is_array($slotsdata)) {
            $mode = optional_param('mode', 'overwrite', PARAM_ALPHA);
            if ($mode === 'overwrite') {
                $DB->delete_records('local_att_slots');
            }

            $count = 0;
            // Apply slots for Mon-Fri (or specified days)
            foreach ($slotsdata as $s) {
                $day = isset($s['dayofweek']) ? (int)$s['dayofweek'] : 1;
                $DB->insert_record('local_att_slots', (object)[
                    'dayofweek' => $day,
                    'starttime' => $s['starttime'] ?? '08:00',
                    'endtime'   => $s['endtime'] ?? '09:30',
                    'type'      => $s['type'] ?? 'class',
                ]);
                $count++;
            }

            $slotsurl = new moodle_url('/local/academic_timetabler/slots.php');
            redirect($slotsurl, "Template '{$template->name}' applied successfully! {$count} time slots configured.");
        }
    }
    redirect($url, 'Failed to apply template or invalid template format.', null, \core\output\notification::NOTIFY_ERROR);
}

// -------------------------------------------------------------------
// Action: Save / Create / Edit Template
// -------------------------------------------------------------------
$edittemplate = null;
if ($action === 'edit' && $id > 0) {
    $edittemplate = $DB->get_record('local_att_templates', ['id' => $id]);
}

if ($data = data_submitted() && confirm_sesskey() && optional_param('save_template', 0, PARAM_INT)) {
    $templateid  = optional_param('templateid', 0, PARAM_INT);
    $name        = optional_param('name', '', PARAM_TEXT);
    $description = optional_param('description', '', PARAM_TEXT);
    $slotsraw    = optional_param('slots_json', '', PARAM_RAW);

    if (!empty($name)) {
        // Validate or format JSON
        $slotsarr = json_decode($slotsraw, true);
        if (!is_array($slotsarr)) {
            // Default 7am-6pm standard day if invalid JSON
            $slotsarr = [
                ['dayofweek' => 1, 'starttime' => '07:00', 'endtime' => '08:30', 'type' => 'class'],
                ['dayofweek' => 1, 'starttime' => '08:30', 'endtime' => '10:00', 'type' => 'class'],
                ['dayofweek' => 1, 'starttime' => '10:00', 'endtime' => '10:30', 'type' => 'break'],
                ['dayofweek' => 1, 'starttime' => '10:30', 'endtime' => '12:00', 'type' => 'class'],
                ['dayofweek' => 1, 'starttime' => '12:00', 'endtime' => '13:30', 'type' => 'break'],
                ['dayofweek' => 1, 'starttime' => '13:30', 'endtime' => '15:00', 'type' => 'class'],
                ['dayofweek' => 1, 'starttime' => '15:00', 'endtime' => '16:30', 'type' => 'class'],
            ];
        }

        $now = time();
        $record = (object)[
            'name'         => $name,
            'description'  => $description,
            'slots_json'   => json_encode($slotsarr),
            'timemodified' => $now,
        ];

        if ($templateid > 0) {
            $record->id = $templateid;
            $DB->update_record('local_att_templates', $record);
            redirect($url, "Schedule template '{$name}' updated successfully.");
        } else {
            $record->timecreated = $now;
            $DB->insert_record('local_att_templates', $record);
            redirect($url, "Schedule template '{$name}' created successfully.");
        }
    }
}

echo $OUTPUT->header();

echo \local_academic_timetabler\output\renderer::render_nav_header('templates');

// -------------------------------------------------------------------
// Add / Edit Template Form Card
// -------------------------------------------------------------------
$cardtitle = $edittemplate ? 'Edit Schedule Template' : 'Create New Schedule Template';
$btnlabel  = $edittemplate ? 'Update Template' : 'Save Template';

$defaultjson = json_encode([
    ['dayofweek' => 1, 'starttime' => '07:00', 'endtime' => '08:30', 'type' => 'class'],
    ['dayofweek' => 1, 'starttime' => '08:30', 'endtime' => '10:00', 'type' => 'class'],
    ['dayofweek' => 1, 'starttime' => '10:00', 'endtime' => '10:30', 'type' => 'break'],
    ['dayofweek' => 1, 'starttime' => '10:30', 'endtime' => '12:00', 'type' => 'class'],
    ['dayofweek' => 1, 'starttime' => '12:00', 'endtime' => '13:30', 'type' => 'break'],
    ['dayofweek' => 1, 'starttime' => '13:30', 'endtime' => '15:00', 'type' => 'class'],
    ['dayofweek' => 1, 'starttime' => '15:00', 'endtime' => '16:30', 'type' => 'class'],
], JSON_PRETTY_PRINT);

$jsonval = $edittemplate ? json_encode(json_decode($edittemplate->slots_json), JSON_PRETTY_PRINT) : $defaultjson;

echo html_writer::start_div('card shadow-sm mb-4 bg-white border-0');
echo html_writer::div(html_writer::tag('h5', $cardtitle, ['class' => 'mb-0 font-weight-bold']), 'card-header bg-dark text-white p-3');
echo html_writer::start_div('card-body p-4');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'save_template', 'value' => '1']);
if ($edittemplate) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'templateid', 'value' => $edittemplate->id]);
}

echo html_writer::start_div('row g-3');
echo html_writer::start_div('col-md-6');
echo html_writer::tag('label', 'Template Name', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'name', 'class' => 'form-control p-2',
    'placeholder' => 'e.g. Standard Semester 1 Class Grid', 'required' => 'required',
    'value' => $edittemplate ? s($edittemplate->name) : '',
]);
echo html_writer::end_div();

echo html_writer::start_div('col-md-6');
echo html_writer::tag('label', 'Description', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'description', 'class' => 'form-control p-2',
    'placeholder' => 'e.g. Mon-Fri 1.5hr classes with Morning Tea & Lunch breaks',
    'value' => $edittemplate ? s($edittemplate->description) : '',
]);
echo html_writer::end_div();

echo html_writer::start_div('col-12');
echo html_writer::tag('label', 'Time Slot Definitions (JSON Format)', ['class' => 'form-label font-weight-bold text-dark']);
echo html_writer::tag('textarea', s($jsonval), [
    'name' => 'slots_json', 'class' => 'form-control font-monospace', 'rows' => 7, 'required' => 'required',
]);
echo html_writer::tag('small', 'Specify slot items as a JSON array of objects with keys: dayofweek (1=Mon..7=Sun), starttime (HH:MM), endtime (HH:MM), and type (class, lab, break, exam).', ['class' => 'text-muted']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('mt-4 pt-3 border-top d-flex gap-2');
echo html_writer::tag('button', $btnlabel, ['type' => 'submit', 'class' => 'btn btn-success font-weight-bold px-4 py-2 shadow-sm']);
if ($edittemplate) {
    echo html_writer::link($url, 'Cancel Edit', ['class' => 'btn btn-outline-secondary px-4 py-2']);
}
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

// -------------------------------------------------------------------
// Saved Schedule Templates Repository Table
// -------------------------------------------------------------------
$templates = $DB->get_records('local_att_templates', null, 'name ASC');

echo html_writer::start_div('d-flex align-items-center justify-content-between mb-3');
echo html_writer::tag('h4', 'Saved Schedule Templates (' . count($templates) . ')', ['class' => 'mb-0 font-weight-bold']);
echo html_writer::end_div();

if (empty($templates)) {
    echo html_writer::div('No custom schedule templates saved yet. Create a new template above or save your active slots from Manage Time Slots.', 'alert alert-info shadow-sm');
} else {
    $table = new html_table();
    $table->head = ['Template Name', 'Description', 'Slots Count', 'Last Modified', 'Actions'];
    $table->attributes = ['class' => 'table table-striped table-bordered align-middle bg-white shadow-sm'];

    foreach ($templates as $t) {
        $slotsarr = json_decode($t->slots_json, true);
        $slotcount = is_array($slotsarr) ? count($slotsarr) : 0;
        $modified  = date('Y-m-d H:i', $t->timemodified);

        $applyurl = new moodle_url($url, ['action' => 'apply', 'id' => $t->id, 'sesskey' => sesskey()]);
        $applybtn = html_writer::link($applyurl, 'Apply to Active Slots', [
            'class' => 'btn btn-sm btn-success font-weight-bold me-2',
            'onclick' => "return confirm('Apply template \"{$t->name}\"? This will configure active weekly time slots.');",
        ]);

        $editurl = new moodle_url($url, ['action' => 'edit', 'id' => $t->id]);
        $editbtn = html_writer::link($editurl, 'Edit', ['class' => 'btn btn-sm btn-outline-primary me-2']);

        $delurl = new moodle_url($url, ['action' => 'delete', 'id' => $t->id, 'sesskey' => sesskey()]);
        $delbtn = html_writer::link($delurl, 'Delete', [
            'class' => 'btn btn-sm btn-outline-danger',
            'onclick' => "return confirm('Delete template \"{$t->name}\"?');",
        ]);

        $table->data[] = [
            '<strong>' . s($t->name) . '</strong>',
            s($t->description ?: '&mdash;'),
            '<span class="badge bg-secondary px-2 py-1 fs-6">' . $slotcount . ' Slots</span>',
            $modified,
            $applybtn . $editbtn . $delbtn,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
