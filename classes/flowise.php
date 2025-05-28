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
 * Flowise API integration for chatbot block
 *
 * @package    block_chatbot
 * @copyright  2025 ISTEC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class to manage Flowise API integration
 */
class block_chatbot_flowise {
    
    /**
     * Get Flowise API URL
     *
     * @return string|bool API URL or false if not configured
     */
    public static function get_api_url() {
        $apiurl = get_config('block_chatbot', 'flowise_api_url');
        
        if (empty($apiurl)) {
            return false;
        }
        
        return rtrim($apiurl, '/');
    }
    
    /**
     * Get Flowise API key
     *
     * @return string|bool API key or false if not configured
     */
    public static function get_api_key() {
        $apikey = get_config('block_chatbot', 'flowise_api_key');
        
        if (empty($apikey)) {
            return false;
        }
        
        return $apikey;
    }
    
    /**
     * Check if Flowise API is configured
     *
     * @return bool True if configured, false otherwise
     */
    public static function is_configured() {
        return (self::get_api_url() !== false && self::get_api_key() !== false);
    }
    
    /**
     * Send a document to Flowise API
     *
     * @param string $filepath Path to the file
     * @param string $filename Name of the file
     * @param array $metadata Additional metadata
     * @return array|bool Response data or false on failure
     */
    public static function send_document($filepath, $filename, $metadata = array()) {
        if (!self::is_configured()) {
            return false;
        }
        
        $apiurl = self::get_api_url();
        $apikey = self::get_api_key();
        
        // Prepare the API endpoint for document upload
        $uploadendpoint = $apiurl . '/api/v1/upload-document';
        
        // Initialize cURL session
        $ch = curl_init();
        
        // Prepare the file for upload
        $cfile = new \CURLFile($filepath, mime_content_type($filepath), $filename);
        
        // Prepare the POST data
        $postdata = array(
            'file' => $cfile,
            'metadata' => json_encode($metadata)
        );
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $uploadendpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $apikey,
            'Accept: application/json'
        ));
        
        // Execute cURL session
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Close cURL session
        curl_close($ch);
        
        // Check if the request was successful
        if ($httpcode >= 200 && $httpcode < 300 && $response) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    /**
     * Send a chat message to Flowise API
     *
     * @param string $message User message
     * @param string $sessionid Session ID for conversation context
     * @param array $metadata Additional metadata
     * @return array|bool Response data or false on failure
     */
    public static function send_chat_message($message, $sessionid = '', $metadata = array()) {
        if (!self::is_configured()) {
            return false;
        }
        
        $apiurl = self::get_api_url();
        $apikey = self::get_api_key();
        
        // Prepare the API endpoint for chat
        $chatendpoint = $apiurl . '/api/v1/prediction/chat';
        
        // Prepare the POST data
        $postdata = array(
            'question' => $message,
            'sessionId' => $sessionid,
            'metadata' => $metadata
        );
        
        // Initialize cURL session
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $chatendpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $apikey,
            'Content-Type: application/json',
            'Accept: application/json'
        ));
        
        // Execute cURL session
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Close cURL session
        curl_close($ch);
        
        // Check if the request was successful
        if ($httpcode >= 200 && $httpcode < 300 && $response) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    /**
     * Process a chat interaction and record it for gamification
     *
     * @param int $userid User ID
     * @param string $message User message
     * @param int $courseid Course ID
     * @param string $sessionid Session ID for conversation context
     * @return array|bool Response data or false on failure
     */
    public static function process_chat_interaction($userid, $message, $courseid, $sessionid = '') {
        global $DB;
        
        // Send message to Flowise
        $metadata = array(
            'userid' => $userid,
            'courseid' => $courseid,
            'source' => 'moodle_plugin'
        );
        
        $response = self::send_chat_message($message, $sessionid, $metadata);
        
        if (!$response || !isset($response['text'])) {
            return false;
        }
        
        // Record interaction for gamification
        require_once(__DIR__ . '/gamification.php');
        $interactionid = block_chatbot_gamification::record_interaction(
            $userid,
            $message,
            $response['text'],
            $courseid
        );
        
        if (!$interactionid) {
            return false;
        }
        
        // Add interaction ID to response
        $response['interactionId'] = $interactionid;
        
        return $response;
    }
    
    /**
     * Test connection to Flowise API
     *
     * @return bool True if connection successful, false otherwise
     */
    public static function test_connection() {
        if (!self::is_configured()) {
            return false;
        }
        
        $apiurl = self::get_api_url();
        $apikey = self::get_api_key();
        
        // Prepare the API endpoint for health check
        $healthendpoint = $apiurl . '/api/v1/health';
        
        // Initialize cURL session
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $healthendpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $apikey,
            'Accept: application/json'
        ));
        
        // Execute cURL session
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Close cURL session
        curl_close($ch);
        
        // Check if the request was successful
        return ($httpcode >= 200 && $httpcode < 300);
    }
}
