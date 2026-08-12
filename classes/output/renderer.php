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

namespace local_academic_timetabler\output;

use local_academic_timetabler\licensing\license_manager;
use plugin_renderer_base;

/**
 * Output renderer for local_academic_timetabler.
 *
 * @package     local_academic_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Render main plugin dashboard interface.
     *
     * @param mixed $page Page context object or array.
     * @return string Rendered HTML content.
     */
    public function render_dashboard($page) {
        $isenterprise = license_manager::is_enterprise();
        $settingsurl = new \moodle_url('/admin/settings.php', ['section' => 'local_academic_timetabler_settings']);
        $contextdata = [
            'is_enterprise' => $isenterprise,
            'tier_name' => $isenterprise ? 'Enterprise Edition' : 'Community Edition',
            'tier_notice' => $isenterprise
                ? get_string('license_enterprise_active', 'local_academic_timetabler')
                : get_string('license_community_notice', 'local_academic_timetabler'),
            'settings_url' => $settingsurl->out(false),
        ];

        return $this->render_from_template('local_academic_timetabler/dashboard', $contextdata);
    }
}
