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

    /** @var string Starter tier identifier. */
    public const TIER_STARTER = 'starter';

    /** @var string Pro University tier identifier (highest tier). */
    public const TIER_PRO = 'pro';

    /** @var int Course limit for Community tier. */
    public const COMMUNITY_COURSE_LIMIT = 30;

    /** @var int Room limit for Community tier. */
    public const COMMUNITY_ROOM_LIMIT = 10;

    /** @var int Course limit for Starter tier. */
    public const STARTER_COURSE_LIMIT = 100;

    /** @var int Room limit for Starter tier. */
    public const STARTER_ROOM_LIMIT = 50;

    /** @var string LemonSqueezy validation endpoint. */
    public const LEMONSQUEEZY_VALIDATE_URL = 'https://api.lemonsqueezy.com/v1/licenses/validate';

    /** @var int Cache validity duration in seconds (7 days). */
    public const CACHE_TTL = 604800;

    /**
     * Get configured license key from admin settings.
     *
     * @return string License key string.
     */
    public static function get_license_key(): string {
        return get_config('local_academic_timetabler', 'license_key') ?: '';
    }

    /**
     * Validate key against LemonSqueezy API with 7-day local cache.
     *
     * @param string $licensekey License key string.
     * @return bool True if valid license.
     */
    public static function validate_lemonsqueezy_key(string $licensekey): bool {
        global $CFG;
        if (empty($licensekey)) {
            return false;
        }

        $lastcheck = (int)get_config('local_academic_timetabler', 'license_last_check');
        $cachedvalid = (bool)get_config('local_academic_timetabler', 'license_cached_valid');
        $cachedkey = get_config('local_academic_timetabler', 'license_cached_key');

        // Return cached result if key matches and cache TTL is fresh.
        if ($cachedkey === $licensekey && (time() - $lastcheck) < self::CACHE_TTL) {
            return $cachedvalid;
        }

        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        $params = ['license_key' => $licensekey];
        $response = $curl->post(self::LEMONSQUEEZY_VALIDATE_URL, $params);

        if ($curl->get_errno()) {
            // Offline fallback: check prefix if API call fails due to network issues.
            return $cachedkey === $licensekey ? $cachedvalid : str_starts_with(strtoupper($licensekey), 'ATT-');
        }

        $data = json_decode($response, true);
        $isvalid = !empty($data['valid']) && ($data['valid'] === true);

        // Update local cache.
        set_config('license_last_check', time(), 'local_academic_timetabler');
        set_config('license_cached_valid', $isvalid ? 1 : 0, 'local_academic_timetabler');
        set_config('license_cached_key', $licensekey, 'local_academic_timetabler');

        return $isvalid;
    }

    /**
     * Get current active license tier.
     *
     * @return string Active tier ('community', 'starter', or 'pro').
     */
    public static function get_tier(): string {
        $licensekey = trim(self::get_license_key());
        if (empty($licensekey)) {
            return self::TIER_COMMUNITY;
        }

        $keyupper = strtoupper($licensekey);
        if (strpos($keyupper, 'START') !== false) {
            return self::TIER_STARTER;
        }

        // Developer keys starting with ATT- or valid LemonSqueezy key -> Pro University
        if (str_starts_with($keyupper, 'ATT-') || self::validate_lemonsqueezy_key($licensekey)) {
            return self::TIER_PRO;
        }

        return self::TIER_COMMUNITY;
    }

    /**
     * Get human-readable active tier name.
     *
     * @return string Display name of the active tier.
     */
    public static function get_tier_name(): string {
        $tier = self::get_tier();
        if ($tier === self::TIER_PRO) {
            return 'Pro University';
        } else if ($tier === self::TIER_STARTER) {
            return 'Starter Edition';
        }
        return 'Community Edition';
    }

    /**
     * Check if site is operating on Pro University tier.
     *
     * @return bool True if Pro University tier active.
     */
    public static function is_pro(): bool {
        return self::get_tier() === self::TIER_PRO;
    }

    /**
     * Check if site is operating on Starter tier or higher.
     *
     * @return bool True if Starter or Pro tier active.
     */
    public static function is_starter_or_higher(): bool {
        return self::get_tier() !== self::TIER_COMMUNITY;
    }

    /**
     * Get maximum allowed courses for current tier.
     *
     * @return int Max courses (0 means unlimited).
     */
    public static function get_max_courses(): int {
        $tier = self::get_tier();
        if ($tier === self::TIER_PRO) {
            return 0; // Unlimited
        } else if ($tier === self::TIER_STARTER) {
            return self::STARTER_COURSE_LIMIT;
        }
        return self::COMMUNITY_COURSE_LIMIT;
    }

    /**
     * Get maximum allowed rooms for current tier.
     *
     * @return int Max rooms (0 means unlimited).
     */
    public static function get_max_rooms(): int {
        $tier = self::get_tier();
        if ($tier === self::TIER_PRO) {
            return 0; // Unlimited
        } else if ($tier === self::TIER_STARTER) {
            return self::STARTER_ROOM_LIMIT;
        }
        return self::COMMUNITY_ROOM_LIMIT;
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

    /**
     * Check if site can batch import rooms via CSV.
     *
     * @return bool True if allowed under current license.
     */
    public static function can_batch_import_rooms(): bool {
        return self::is_pro();
    }

    /**
     * Check if site can generate examination timetables.
     *
     * @return bool True if allowed under current license.
     */
    public static function can_solve_exams(): bool {
        return self::is_starter_or_higher();
    }
}
