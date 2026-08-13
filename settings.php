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

        $pricinghtml = '
        <div class="mt-4 p-4 bg-white border rounded shadow-sm">
            <h4 class="font-weight-bold text-dark mb-1">Commercial Licensing & Enterprise Tiers</h4>
            <p class="text-muted small mb-4">Choose the right capacity for your institution. All commercial plans include LemonSqueezy license keys and automated updates.</p>
            
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <!-- Tier 1: Starter / High School -->
                <div class="col">
                    <div class="card h-100 border p-4 d-flex flex-column rounded-3 bg-light">
                        <div class="mb-3">
                            <span class="badge bg-secondary text-white font-weight-bold px-3 py-1">High School / Department</span>
                            <h4 class="font-weight-bold text-dark mt-2 mb-1">Starter Edition</h4>
                            <div class="text-muted small">Ideal for small institutes & high schools</div>
                        </div>
                        <div class="my-3 fs-2 font-weight-bold text-dark">$199 <span class="fs-6 text-muted font-weight-normal">/ year</span></div>
                        <ul class="list-unstyled my-3 flex-grow-1 small">
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Up to 100 Active Courses</li>
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Up to 50 Campus Rooms</li>
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Master Course Timetable Solver</li>
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Institutional Schedule Profiles</li>
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Standard Email Support</li>
                        </ul>
                        <div class="pt-3 border-top mt-auto">
                            <a href="https://lemonsqueezy.com" target="_blank" class="btn btn-outline-primary w-100 font-weight-bold py-2">Get Starter Plan &rarr;</a>
                        </div>
                    </div>
                </div>

                <!-- Tier 2: Pro / University (Popular) -->
                <div class="col">
                    <div class="card h-100 border border-2 border-primary p-4 d-flex flex-column rounded-3 position-relative shadow-sm bg-white">
                        <span class="position-absolute top-0 end-0 translate-middle-y me-3 badge bg-primary text-white font-weight-bold px-3 py-2 rounded-pill shadow-sm">MOST POPULAR</span>
                        <div class="mb-3">
                            <span class="badge bg-primary text-white font-weight-bold px-3 py-1">University / College</span>
                            <h4 class="font-weight-bold text-dark mt-2 mb-1">Pro University</h4>
                            <div class="text-muted small">Complete scheduling for higher education</div>
                        </div>
                        <div class="my-3 fs-2 font-weight-bold text-primary">$499 <span class="fs-6 text-muted font-weight-normal">/ year</span></div>
                        <ul class="list-unstyled my-3 flex-grow-1 small">
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> <strong>UNLIMITED</strong> Active Courses</li>
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> <strong>UNLIMITED</strong> Campus Rooms & Venues</li>
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Combined Course & Exam Solver</li>
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Background Adhoc Task Solver</li>
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Custom Break Windows & Rules</li>
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Priority Ticket Support</li>
                        </ul>
                        <div class="pt-3 border-top mt-auto">
                            <a href="https://lemonsqueezy.com" target="_blank" class="btn btn-primary w-100 font-weight-bold py-2 shadow-sm">Get Pro University &rarr;</a>
                        </div>
                    </div>
                </div>

                <!-- Tier 3: Enterprise Multi-Site -->
                <div class="col">
                    <div class="card h-100 border p-4 d-flex flex-column rounded-3 bg-light">
                        <div class="mb-3">
                            <span class="badge bg-dark text-white font-weight-bold px-3 py-1">Multi-Site System</span>
                            <h4 class="font-weight-bold text-dark mt-2 mb-1">Enterprise System</h4>
                            <div class="text-muted small">For multi-campus university networks</div>
                        </div>
                        <div class="my-3 fs-2 font-weight-bold text-dark">$1,499 <span class="fs-6 text-muted font-weight-normal">/ year</span></div>
                        <ul class="list-unstyled my-3 flex-grow-1 small">
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> <strong>Multi-Site / Multi-Moodle</strong> Deployment</li>
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Custom Solver Constraint Engineering</li>
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Dedicated Technical Onboarding SLA</li>
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> Direct SIS/ERP Integration Support</li>
                            <li class="py-1"><i class="fa fa-check text-success me-2"></i> 1-on-1 Admin Training Session</li>
                        </ul>
                        <div class="pt-3 border-top mt-auto">
                            <a href="https://lemonsqueezy.com" target="_blank" class="btn btn-outline-dark w-100 font-weight-bold py-2">Contact Enterprise &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>';

        $settings->add(new admin_setting_heading(
            'local_academic_timetabler/commercial_plans',
            '',
            $pricinghtml
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
