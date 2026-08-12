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

namespace local_academic_timetabler\licensing;

/**
 * License manager for local_academic_timetabler commercial tier enforcement.
 *
 * @package     local_academic_timetabler
 * @copyright   2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @author      Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class license_manager {
    /** @var string Community tier identifier. */
    public const TIER_COMMUNITY = 'community';

    /** @var string Enterprise tier identifier. */
    public const TIER_ENTERPRISE = 'enterprise';

    /** @var int Course limit for community tier. */
    public const COMMUNITY_COURSE_LIMIT = 30;

    /**
     * Get configured license key from admin settings.
     *
     * @return string License key string.
     */
    public static function get_license_key(): string {
        return get_config('local_academic_timetabler', 'license_key') ?: '';
    }

    /**
     * Get current active license tier.
     *
     * @return string Active tier ('community' or 'enterprise').
     */
    public static function get_tier(): string {
        $licensekey = trim(self::get_license_key());
        if (empty($licensekey)) {
            return self::TIER_COMMUNITY;
        }

        // Validate cryptographic key prefix / structure.
        if (str_starts_with($licensekey, 'ATT-ENT-') && strlen($licensekey) >= 20) {
            return self::TIER_ENTERPRISE;
        }

        return self::TIER_COMMUNITY;
    }

    /**
     * Check if site is operating on enterprise tier.
     *
     * @return bool True if enterprise tier active.
     */
    public static function is_enterprise(): bool {
        return self::get_tier() === self::TIER_ENTERPRISE;
    }

    /**
     * Get maximum allowed courses for current tier.
     *
     * @return int Max courses (0 means unlimited).
     */
    public static function get_max_courses(): int {
        return self::is_enterprise() ? 0 : self::COMMUNITY_COURSE_LIMIT;
    }

    /**
     * Check if solver can process given number of courses under active tier.
     *
     * @param int $coursecount Total courses to solve.
     * @return bool True if allowed under current license.
     */
    public static function can_solve_courses(int $coursecount): bool {
        $maxcourses = self::get_max_courses();
        if ($maxcourses === 0) {
            return true;
        }
        return $coursecount <= $maxcourses;
    }
}
