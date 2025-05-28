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
 * Event handlers for block_chatbot
 *
 * @package    block_chatbot
 * @copyright  2025 ISTEC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\mod_resource\event\resource_created',
        'callback' => 'block_chatbot_observer::resource_created',
        'includefile' => '/blocks/chatbot/classes/observer/observer.php',
        'internal' => false,
        'priority' => 9999
    ),
    array(
        'eventname' => '\mod_resource\event\resource_updated',
        'callback' => 'block_chatbot_observer::resource_updated',
        'includefile' => '/blocks/chatbot/classes/observer/observer.php',
        'internal' => false,
        'priority' => 9999
    )
);
