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
 * Gamification manager for chatbot block
 *
 * @package    block_chatbot
 * @copyright  2025 ISTEC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class to manage gamification features
 */
class block_chatbot_gamification {
    
    /**
     * Badge levels and their requirements
     */
    const BADGE_LEVELS = [
        'novice' => 10,    // 10 interactions
        'explorer' => 50,  // 50 interactions
        'master' => 100    // 100 interactions
    ];
    
    /**
     * Record a new interaction for a user
     *
     * @param int $userid User ID
     * @param string $question User's question
     * @param string $answer Chatbot's answer
     * @param int $courseid Course ID
     * @return int|bool The new interaction ID or false on failure
     */
    public static function record_interaction($userid, $question, $answer, $courseid) {
        global $DB;
        
        // Create interaction record
        $record = new \stdClass();
        $record->userid = $userid;
        $record->courseid = $courseid;
        $record->question = $question;
        $record->answer = $answer;
        $record->timecreated = time();
        
        // Insert record
        $interactionid = $DB->insert_record('block_chatbot_interactions', $record);
        
        if ($interactionid) {
            // Check if user earned any badges
            self::check_and_award_badges($userid);
            return $interactionid;
        }
        
        return false;
    }
    
    /**
     * Check if user has earned any badges and award them
     *
     * @param int $userid User ID
     * @return bool Success status
     */
    public static function check_and_award_badges($userid) {
        global $DB;
        
        // Get user's interaction count
        $interactioncount = $DB->count_records('block_chatbot_interactions', ['userid' => $userid]);
        
        // Get user's existing badges
        $existingbadges = $DB->get_records('block_chatbot_badges', ['userid' => $userid], '', 'badgetype');
        $existingbadgetypes = array_keys($existingbadges);
        
        $awarded = false;
        
        // Check each badge level
        foreach (self::BADGE_LEVELS as $badgetype => $requiredcount) {
            // If user has enough interactions and doesn't already have this badge
            if ($interactioncount >= $requiredcount && !in_array($badgetype, $existingbadgetypes)) {
                // Award the badge
                $awarded = self::award_badge($userid, $badgetype) || $awarded;
            }
        }
        
        return $awarded;
    }
    
    /**
     * Award a badge to a user
     *
     * @param int $userid User ID
     * @param string $badgetype Badge type (novice, explorer, master)
     * @return bool Success status
     */
    public static function award_badge($userid, $badgetype) {
        global $DB;
        
        // Validate badge type
        if (!array_key_exists($badgetype, self::BADGE_LEVELS)) {
            return false;
        }
        
        // Create badge record
        $record = new \stdClass();
        $record->userid = $userid;
        $record->badgetype = $badgetype;
        $record->timecreated = time();
        
        // Get badge name and description from language strings
        $record->name = get_string('badge_' . $badgetype, 'block_chatbot');
        $record->description = get_string('badge_' . $badgetype . '_desc', 'block_chatbot');
        
        // Insert record
        $badgeid = $DB->insert_record('block_chatbot_badges', $record);
        
        if ($badgeid) {
            // Trigger badge awarded event
            $event = \block_chatbot\event\badge_awarded::create([
                'objectid' => $badgeid,
                'context' => \context_user::instance($userid),
                'other' => [
                    'badgetype' => $badgetype,
                    'badgename' => $record->name
                ]
            ]);
            $event->trigger();
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get user's badges
     *
     * @param int $userid User ID
     * @return array Array of badge objects
     */
    public static function get_user_badges($userid) {
        global $DB;
        
        return $DB->get_records('block_chatbot_badges', ['userid' => $userid], 'timecreated ASC');
    }
    
    /**
     * Get user's interaction count
     *
     * @param int $userid User ID
     * @return int Number of interactions
     */
    public static function get_interaction_count($userid) {
        global $DB;
        
        return $DB->count_records('block_chatbot_interactions', ['userid' => $userid]);
    }
    
    /**
     * Get user's recent interactions
     *
     * @param int $userid User ID
     * @param int $limit Maximum number of interactions to return
     * @return array Array of interaction objects
     */
    public static function get_recent_interactions($userid, $limit = 10) {
        global $DB;
        
        return $DB->get_records('block_chatbot_interactions', 
            ['userid' => $userid], 
            'timecreated DESC', 
            '*', 
            0, 
            $limit
        );
    }
    
    /**
     * Get user's progress towards next badge
     *
     * @param int $userid User ID
     * @return array Progress information
     */
    public static function get_progress_to_next_badge($userid) {
        global $DB;
        
        // Get user's interaction count
        $interactioncount = self::get_interaction_count($userid);
        
        // Get user's existing badges
        $existingbadges = $DB->get_records('block_chatbot_badges', ['userid' => $userid], '', 'badgetype');
        $existingbadgetypes = array_keys($existingbadges);
        
        // Find the next badge to earn
        $nextbadge = null;
        $nextbadgetype = null;
        $nextbadgecount = PHP_INT_MAX;
        
        foreach (self::BADGE_LEVELS as $badgetype => $requiredcount) {
            if (!in_array($badgetype, $existingbadgetypes) && $requiredcount < $nextbadgecount) {
                $nextbadgetype = $badgetype;
                $nextbadgecount = $requiredcount;
            }
        }
        
        // If no next badge found, user has all badges
        if ($nextbadgetype === null) {
            return [
                'has_next_badge' => false,
                'current_count' => $interactioncount,
                'next_badge_name' => '',
                'next_badge_count' => 0,
                'progress_percent' => 100
            ];
        }
        
        // Calculate progress percentage
        $progressPercent = min(($interactioncount / $nextbadgecount) * 100, 99);
        
        return [
            'has_next_badge' => true,
            'current_count' => $interactioncount,
            'next_badge_type' => $nextbadgetype,
            'next_badge_name' => get_string('badge_' . $nextbadgetype, 'block_chatbot'),
            'next_badge_count' => $nextbadgecount,
            'progress_percent' => $progressPercent
        ];
    }
}
