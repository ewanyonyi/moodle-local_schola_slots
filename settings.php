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
     * Custom admin setting element to render commercial pricing plans HTML cleanly without escaping.
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

            // Tier Detection: 0=Starter Edition (Free), 1=Pro Cloud Engine ($499/yr)
            $currenttier = 0;
            if (!empty($licensekey)) {
                $currenttier = 1; // Valid license key unlocks Pro Cloud Engine
            }

            $checkouturl = \local_academic_timetabler\licensing\license_manager::get_checkout_url();

            // Top Status & Suggested Upgrade Banner
            $bannerhtml = '';
            if ($currenttier === 1) {
                $bannerhtml = '
                <div class="alert alert-success d-flex align-items-center justify-content-between mb-4 shadow-sm rounded-3">
                    <div>
                        <strong class="text-success fs-6"><i class="fa fa-shield-check me-2"></i> Current Active Tier: Pro Cloud Engine ($499/yr)</strong>
                        <div class="small text-muted mt-1">High-speed Cloud Solver API active! UNLIMITED courses, campus rooms, combined exam solver, batch CSV room import, and priority ticket support unlocked.</div>
                    </div>
                    <span class="badge bg-success text-white px-3 py-2 rounded-pill"><i class="fa fa-check-circle me-1"></i> PRO CLOUD ACTIVE</span>
                </div>';
            } else {
                $bannerhtml = '
                <div class="alert alert-info d-flex align-items-center justify-content-between mb-4 shadow-sm rounded-3">
                    <div>
                        <strong class="text-dark fs-6"><i class="fa fa-info-circle me-2"></i> Current Tier: Starter Edition (Free Open Source)</strong>
                        <div class="small text-muted mt-1">Included free out-of-the-box for up to 50 courses & 25 rooms. Need off-server high-speed solving or unlimited capacity? Upgrade to <strong>Pro Cloud Engine</strong>.</div>
                    </div>
                    <a href="' . s($checkouturl) . '" target="_blank" class="btn btn-primary font-weight-bold px-3 py-2 ms-3">Upgrade to Pro Cloud Engine &rarr;</a>
                </div>';
            }

            // Card Footer Button Helper
            $getFooterButton = function($cardtier) use ($currenttier, $checkouturl) {
                if ($cardtier === $currenttier) {
                    return '<button class="btn btn-outline-success w-100 font-weight-bold py-2" disabled><i class="fa fa-check-circle me-1"></i> Active Included Plan</button>';
                } else {
                    return '<a href="' . s($checkouturl) . '" target="_blank" class="btn btn-primary shadow-sm w-100 font-weight-bold py-2">Upgrade to Pro Cloud Engine &rarr;</a>';
                }
            };

            // Card Badge Helper
            $getBadge = function($cardtier) use ($currenttier) {
                if ($cardtier === $currenttier) {
                    return '<span class="position-absolute top-0 end-0 translate-middle-y me-3 badge bg-success text-white font-weight-bold px-3 py-2 rounded-pill shadow-sm"><i class="fa fa-check me-1"></i> CURRENT PLAN</span>';
                } else {
                    return '<span class="position-absolute top-0 end-0 translate-middle-y me-3 badge bg-primary text-white font-weight-bold px-3 py-2 rounded-pill shadow-sm">RECOMMENDED FOR UNIVERSITIES</span>';
                }
            };

            // Card Class Helper
            $getCardClass = function($cardtier) use ($currenttier) {
                if ($cardtier === $currenttier) {
                    return 'card h-100 border border-2 border-success p-4 d-flex flex-column rounded-3 position-relative shadow-sm bg-white';
                }
                return 'card h-100 border border-2 border-primary p-4 d-flex flex-column rounded-3 position-relative shadow-sm bg-white';
            };

            return '
            <div class="mt-4 p-4 bg-white border rounded shadow-sm mb-4">
                <h4 class="font-weight-bold text-dark mb-1">Licensing Tiers & Plan Comparison</h4>
                <p class="text-muted small mb-4">Manage institutional capacity and unlock high-speed off-server cloud solver capabilities.</p>
                
                ' . $bannerhtml . '

                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <!-- Starter Edition (Free Open Source) -->
                    <div class="col">
                        <div class="' . $getCardClass(0) . '">
                            ' . $getBadge(0) . '
                            <div class="mb-3">
                                <span class="badge bg-secondary text-white font-weight-bold px-3 py-1">Free / Included</span>
                                <h4 class="font-weight-bold text-dark mt-2 mb-1">Starter Edition</h4>
                                <div class="text-muted small">Standard local PHP solver for departments & schools</div>
                            </div>
                            <div class="my-3 fs-2 font-weight-bold text-dark">$0 <span class="fs-6 text-muted font-weight-normal">/ forever</span></div>
                            <ul class="list-unstyled my-3 flex-grow-1 small">
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> Up to 50 Active Courses</li>
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> Up to 25 Campus Venues & Rooms</li>
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> Local Master Weekly Timetabling</li>
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> Exam Timetabling Engine</li>
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> Bell Schedule Wizard</li>
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> Standard Moodle Community Support</li>
                            </ul>
                            <div class="pt-3 border-top mt-auto">
                                ' . $getFooterButton(0) . '
                            </div>
                        </div>
                    </div>

                    <!-- Pro Cloud Engine ($499/yr) -->
                    <div class="col">
                        <div class="' . $getCardClass(1) . '">
                            ' . $getBadge(1) . '
                            <div class="mb-3">
                                <span class="badge bg-primary text-white font-weight-bold px-3 py-1">University / High Performance</span>
                                <h4 class="font-weight-bold text-dark mt-2 mb-1">Pro Cloud Engine</h4>
                                <div class="text-muted small">High-speed off-server cloud solver for large higher-ed institutions</div>
                            </div>
                            <div class="my-3 fs-2 font-weight-bold text-primary">$499 <span class="fs-6 text-muted font-weight-normal">/ year</span></div>
                            <ul class="list-unstyled my-3 flex-grow-1 small">
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> <strong>UNLIMITED</strong> Active Courses</li>
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> <strong>UNLIMITED</strong> Campus Rooms & Venues</li>
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> High-Speed Off-Server Cloud Solver API</li>
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> Combined Course & Exam Solver</li>
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> Automated Background Solver Tasks</li>
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> Batch CSV / Excel Room Importer</li>
                                <li class="py-1"><i class="fa fa-check text-success me-2"></i> Priority Ticket Support</li>
                            </ul>
                            <div class="pt-3 border-top mt-auto">
                                ' . $getFooterButton(1) . '
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

        $settings->add(new admin_setting_configtext(
            'local_academic_timetabler/checkout_url',
            'LemonSqueezy Store / Checkout URL',
            'URL where administrators purchase or upgrade license keys for your timetabler plugin.',
            'https://saugra.lemonsqueezy.com/buy',
            PARAM_URL
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
