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

            // Tier Detection: 0=Community, 1=Starter, 2=Pro, 3=Enterprise
            $currenttier = 0;
            if (!empty($licensekey)) {
                $keyupper = strtoupper($licensekey);
                if (strpos($keyupper, 'ENT') !== false || strpos($keyupper, 'ENTERPRISE') !== false) {
                    $currenttier = 3;
                } else if (strpos($keyupper, 'PRO') !== false || strpos($keyupper, 'UNIV') !== false) {
                    $currenttier = 2;
                } else if (strpos($keyupper, 'START') !== false) {
                    $currenttier = 1;
                } else {
                    $currenttier = 2;
                }
            }

            // Top Status & Suggested Upgrade Banner
            $bannerhtml = '';
            if ($currenttier === 3) {
                $bannerhtml = '
                <div class="alert alert-success d-flex align-items-center justify-content-between mb-4 shadow-sm rounded-3">
                    <div>
                        <strong class="text-success fs-6"><i class="fa fa-shield-check me-2"></i> Current Plan: Enterprise System ($1,499/yr)</strong>
                        <div class="small text-muted mt-1">You are on our highest tier! Multi-site Moodle deployment, direct SIS/ERP integration support, and dedicated SLA technical onboarding are active.</div>
                    </div>
                    <span class="badge bg-success text-white px-3 py-2 rounded-pill"><i class="fa fa-check-circle me-1"></i> HIGHEST TIER ACTIVE</span>
                </div>';
            } else if ($currenttier === 2) {
                $bannerhtml = '
                <div class="alert alert-primary d-flex align-items-center justify-content-between mb-4 shadow-sm rounded-3">
                    <div>
                        <strong class="text-primary fs-6"><i class="fa fa-check-circle me-2"></i> Current Plan: Pro University ($499/yr)</strong>
                        <div class="small text-muted mt-1">Suggested Upgrade: <strong>Enterprise System ($1,499/yr)</strong> for multi-campus Moodle networks, direct SIS/ERP integration, and dedicated SLA onboarding.</div>
                    </div>
                    <a href="https://lemonsqueezy.com" target="_blank" class="btn btn-dark font-weight-bold px-3 py-2 ms-3">Upgrade to Enterprise &rarr;</a>
                </div>';
            } else if ($currenttier === 1) {
                $bannerhtml = '
                <div class="alert alert-warning d-flex align-items-center justify-content-between mb-4 shadow-sm rounded-3">
                    <div>
                        <strong class="text-dark fs-6"><i class="fa fa-check-circle me-2"></i> Current Plan: Starter Edition ($199/yr)</strong>
                        <div class="small text-muted mt-1">Suggested Upgrade: <strong>Pro University ($499/yr)</strong> to unlock <strong>UNLIMITED active courses, campus rooms</strong>, and combined course/exam solvers.</div>
                    </div>
                    <a href="https://lemonsqueezy.com" target="_blank" class="btn btn-primary font-weight-bold px-3 py-2 ms-3">Upgrade to Pro University &rarr;</a>
                </div>';
            } else {
                $bannerhtml = '
                <div class="alert alert-secondary d-flex align-items-center justify-content-between mb-4 shadow-sm rounded-3">
                    <div>
                        <strong class="text-dark fs-6"><i class="fa fa-info-circle me-2"></i> Current Plan: Community Edition (Free)</strong>
                        <div class="small text-muted mt-1">Suggested Upgrade: <strong>Pro University ($499/yr)</strong> to unlock full university constraint engine, automated background tasks, and priority support.</div>
                    </div>
                    <a href="https://lemonsqueezy.com" target="_blank" class="btn btn-primary font-weight-bold px-3 py-2 ms-3">Upgrade License &rarr;</a>
                </div>';
            }

            // Card Footer Button Helper
            $getFooterButton = function($cardtier) use ($currenttier) {
                if ($cardtier === $currenttier) {
                    return '<button class="btn btn-outline-success w-100 font-weight-bold py-2" disabled><i class="fa fa-check-circle me-1"></i> Current Active Plan</button>';
                } else if ($cardtier > $currenttier) {
                    $btnclass = ($cardtier === 2) ? 'btn-primary shadow-sm' : ($cardtier === 3 ? 'btn-dark shadow-sm' : 'btn-outline-primary');
                    $label = ($cardtier === 1) ? 'Upgrade to Starter' : (($cardtier === 2) ? 'Upgrade to Pro University' : 'Upgrade to Enterprise');
                    return '<a href="https://lemonsqueezy.com" target="_blank" class="btn ' . $btnclass . ' w-100 font-weight-bold py-2">' . $label . ' &rarr;</a>';
                } else {
                    return '<button class="btn btn-light text-muted w-100 font-weight-bold py-2" disabled>Included in Current Plan</button>';
                }
            };

            // Card Badge Helper
            $getBadge = function($cardtier) use ($currenttier) {
                if ($cardtier === $currenttier) {
                    return '<span class="position-absolute top-0 end-0 translate-middle-y me-3 badge bg-success text-white font-weight-bold px-3 py-2 rounded-pill shadow-sm"><i class="fa fa-check me-1"></i> CURRENT PLAN</span>';
                } else if ($cardtier === $currenttier + 1) {
                    return '<span class="position-absolute top-0 end-0 translate-middle-y me-3 badge bg-warning text-dark font-weight-bold px-3 py-2 rounded-pill shadow-sm"><i class="fa fa-arrow-up me-1"></i> SUGGESTED UPGRADE</span>';
                } else if ($cardtier === 2 && $currenttier === 0) {
                    return '<span class="position-absolute top-0 end-0 translate-middle-y me-3 badge bg-primary text-white font-weight-bold px-3 py-2 rounded-pill shadow-sm">MOST POPULAR</span>';
                }
                return '';
            };

            // Card Class Helper
            $getCardClass = function($cardtier) use ($currenttier) {
                if ($cardtier === $currenttier) {
                    return 'card h-100 border border-2 border-success p-4 d-flex flex-column rounded-3 position-relative shadow-sm bg-white';
                } else if ($cardtier === $currenttier + 1) {
                    return 'card h-100 border border-2 border-warning p-4 d-flex flex-column rounded-3 position-relative shadow-sm bg-white';
                }
                return 'card h-100 border p-4 d-flex flex-column rounded-3 bg-light';
            };

            return '
            <div class="mt-4 p-4 bg-white border rounded shadow-sm mb-4">
                <h4 class="font-weight-bold text-dark mb-1">Commercial Licensing & Enterprise Tiers</h4>
                <p class="text-muted small mb-4">Manage your institutional capacity and unlock advanced solver features with LemonSqueezy license keys.</p>
                
                ' . $bannerhtml . '

                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <!-- Tier 1: Starter / High School -->
                    <div class="col">
                        <div class="' . $getCardClass(1) . '">
                            ' . $getBadge(1) . '
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
                                ' . $getFooterButton(1) . '
                            </div>
                        </div>
                    </div>

                    <!-- Tier 2: Pro / University -->
                    <div class="col">
                        <div class="' . $getCardClass(2) . '">
                            ' . $getBadge(2) . '
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
                                ' . $getFooterButton(2) . '
                            </div>
                        </div>
                    </div>

                    <!-- Tier 3: Enterprise Multi-Site -->
                    <div class="col">
                        <div class="' . $getCardClass(3) . '">
                            ' . $getBadge(3) . '
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
                                ' . $getFooterButton(3) . '
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
