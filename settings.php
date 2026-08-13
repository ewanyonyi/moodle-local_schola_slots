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
 * Admin settings definition for local_academic_timetabler.
 *
 * @package     local_academic_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_academic_timetabler_settings',
        get_string('pluginname', 'local_academic_timetabler')
    );

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_heading(
            'local_academic_timetabler/dashboard_heading',
            '',
            '<div class="alert alert-info d-flex align-items-center justify-content-between my-2">' .
            '<div><strong>Academic & Exam Timetabler</strong> is installed and ready.</div>' .
            '<a href="' . new moodle_url('/local/academic_timetabler/index.php') . '" class="btn btn-primary font-weight-bold">Open Timetabler Dashboard</a>' .
            '</div>'
        ));

        $settings->add(new admin_setting_configtext(
            'local_academic_timetabler/license_key',
            get_string('license_key', 'local_academic_timetabler'),
            get_string('license_key_desc', 'local_academic_timetabler'),
            '',
            PARAM_TEXT
        ));

        $distoptions = [
            'balanced' => 'Equal 5-Day Load Balancing (Mon - Fri)',
            'mon_to_sat' => '6-Day Institution Schedule (Mon - Sat)',
            'mon_to_thu' => '4-Day Compact Schedule (Mon - Thu)',
            'frontload' => 'Sequential Day Frontloading (Mon - Fri)',
        ];

        $settings->add(new admin_setting_configselect(
            'local_academic_timetabler/day_distribution',
            'Weekly Day Distribution Strategy',
            'Select how the solver engine spreads course sessions across weekdays.',
            'balanced',
            $distoptions
        ));
    }

    $ADMIN->add('localplugins', $settings);

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_academic_timetabler_dashboard',
        get_string('manage_timetabler', 'local_academic_timetabler'),
        new moodle_url('/local/academic_timetabler/index.php'),
        'local/academic_timetabler:manage'
    ));
}
