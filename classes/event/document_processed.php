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
 * Document processed event
 *
 * @package    block_chatbot
 * @copyright  2025 ISTEC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_chatbot\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Document processed event class
 */
class document_processed extends \core\event\base {

    /**
     * Init method
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'resource';
    }

    /**
     * Get name
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_document_processed', 'block_chatbot');
    }

    /**
     * Get description
     *
     * @return string
     */
    public function get_description() {
        return "The document '{$this->other['resourcename']}' was successfully processed and sent to Flowise.";
    }

    /**
     * Get URL
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/resource/view.php', ['id' => $this->contextinstanceid]);
    }
}
