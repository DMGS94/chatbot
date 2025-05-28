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
 * Badge awarded event
 *
 * @package    block_chatbot
 * @copyright  2025 ISTEC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_chatbot\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Badge awarded event class
 */
class badge_awarded extends \core\event\base {

    /**
     * Init method
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'block_chatbot_badges';
    }

    /**
     * Get name
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_badge_awarded', 'block_chatbot');
    }

    /**
     * Get description
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' was awarded the '{$this->other['badgename']}' badge.";
    }

    /**
     * Get URL
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/blocks/chatbot/history.php', ['userid' => $this->userid]);
    }
}
