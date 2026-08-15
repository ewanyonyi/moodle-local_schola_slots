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

if (!class_exists('local_academic_timetabler_admin_setting_pricing')) {
    /**
     * Custom admin setting element to render clean System Performance & Cloud Solver Information.
     */
    class local_academic_timetabler_admin_setting_pricing extends admin_setting {
        public function __construct() {
            $this->nosave = true;
            parent::__construct('local_academic_timetabler/commercial_plans', '', '', '');
        }

        public function get_setting() {
            return true;
        }

        public function get_defaultsetting() {
            return true;
        }

        public function write_setting($data) {
            return '';
        }

        public function output_html($data, $query = '') {
            $licensekey = get_config('local_academic_timetabler', 'license_key');
            $licensekey = trim((string)$licensekey);

            $iscloudactive = !empty($licensekey);
            $checkouturl = \local_academic_timetabler\licensing\license_manager::get_checkout_url();

            $statuscard = '';
            if ($iscloudactive) {
                $statuscard = '
                <div class="alert alert-success d-flex align-items-center justify-content-between mb-4 rounded-3 border-0 shadow-sm p-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3 text-success fs-3"><i class="fa fa-cloud-check"></i></div>
                        <div>
                            <strong class="text-success fs-6">High-Performance Cloud Solver Active</strong>
                            <div class="small text-muted">Off-server solver engine connected. Unlimited course scheduling, campus venue capacity, and priority processing enabled.</div>
                        </div>
                    </div>
                    <span class="badge bg-success text-white px-3 py-2 rounded-pill"><i class="fa fa-check-circle me-1"></i> CLOUD CONNECTED</span>
                </div>';
            } else {
                $statuscard = '
                <div class="alert alert-light border d-flex align-items-center justify-content-between mb-4 rounded-3 shadow-sm p-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3 text-primary fs-3"><i class="fa fa-server"></i></div>
                        <div>
                            <strong class="text-dark fs-6">Native Server Processing Engine (Active)</strong>
                            <div class="small text-muted">Course and exam timetables are generated locally on your Moodle server. Ideal for departments & mid-sized schools.</div>
                        </div>
                    </div>
                    <span class="badge bg-secondary text-white px-3 py-2 rounded-pill"><i class="fa fa-check me-1"></i> NATIVE MODE</span>
                </div>';
            }

            return '
            <div class="mt-4 p-4 bg-white border rounded shadow-sm mb-4">
                <h5 class="font-weight-bold text-dark mb-1"><i class="fa fa-microchip me-2 text-primary"></i>Solver Engine Architecture</h5>
                <p class="text-muted small mb-4">Choose between native server processing or off-server cloud acceleration for large datasets.</p>
                
                ' . $statuscard . '

                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <div class="col">
                        <div class="card h-100 border p-4 rounded-3 bg-light">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fa fa-hdd text-secondary fs-4 me-2"></i>
                                <h6 class="font-weight-bold text-dark m-0">Native Local Engine</h6>
                            </div>
                            <ul class="list-unstyled mb-3 small text-muted">
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> Runs directly inside your Moodle PHP environment</li>
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> Master weekly timetables & exam scheduling</li>
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> Bell schedule wizard & venue management</li>
                            </ul>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card h-100 border border-primary-subtle p-4 rounded-3 bg-white">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-cloud text-primary fs-4 me-2"></i>
                                    <h6 class="font-weight-bold text-dark m-0">Cloud Solver Acceleration</h6>
                                </div>
                                <span class="badge bg-primary-subtle text-primary font-weight-normal px-2 py-1">Optional</span>
                            </div>
                            <ul class="list-unstyled mb-3 small text-muted">
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> Off-server high-speed solver engine</li>
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> Unlimited course datasets & campus venues</li>
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> Automated background tasks & batch venue import</li>
                            </ul>
                            <div class="pt-2 mt-auto">
                                <a href="' . s($checkouturl) . '" target="_blank" class="btn btn-outline-primary btn-sm font-weight-bold w-100 py-2">
                                    Learn About Cloud Engine Services &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
        }
    }
}

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
            '<div><strong>Schola Slots</strong> is installed and operational.</div>' .
            '<a href="' . new moodle_url('/local/academic_timetabler/index.php') . '" class="btn btn-primary font-weight-bold">Open Schola Slots Dashboard</a>' .
            '</div>'
        ));

        $settings->add(new admin_setting_configtext(
            'local_academic_timetabler/license_key',
            'Cloud Solver API Key',
            'Optional. Enter your Cloud Solver API key to connect off-server high-performance solver services.',
            '',
            PARAM_TEXT
        ));

        $settings->add(new local_academic_timetabler_admin_setting_pricing());

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
