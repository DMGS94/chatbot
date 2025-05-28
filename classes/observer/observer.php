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
 * Event observer for chatbot block
 *
 * @package    block_chatbot
 * @copyright  2025 ISTEC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer class for block_chatbot
 */
class block_chatbot_observer {

    /**
     * Observer for resource creation event
     *
     * @param \core\event\base $event The event
     * @return bool Success status
     */
    public static function resource_created(\core\event\base $event) {
        global $DB, $CFG;
        
        // Get the resource module information
        $resourceid = $event->objectid;
        $courseid = $event->courseid;
        $contextid = $event->contextid;
        $userid = $event->userid;
        
        // Get the resource record
        $resource = $DB->get_record('resource', array('id' => $resourceid), '*', MUST_EXIST);
        
        // Check if it's a PDF or other document type we want to process
        $mimetype = $resource->contenttype;
        $supportedtypes = array(
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        );
        
        if (!in_array($mimetype, $supportedtypes)) {
            // Not a supported document type, exit
            return true;
        }
        
        // Get file information
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $contextid,
            'mod_resource',
            'content',
            0,
            'sortorder DESC, id ASC',
            false
        );
        
        if (empty($files)) {
            // No files found
            return true;
        }
        
        $file = reset($files);
        
        // Create a temporary directory to store the file
        $tempdir = make_temp_directory('block_chatbot');
        $tempfilepath = $tempdir . '/' . $file->get_filename();
        
        // Save the file to the temporary directory
        $file->copy_content_to($tempfilepath);
        
        // Send the file to Flowise API
        $result = self::send_to_flowise($tempfilepath, $resource->name, $courseid, $userid);
        
        // Clean up the temporary file
        unlink($tempfilepath);
        
        // Log the result
        if ($result) {
            // Success - log to Moodle log
            $event = \block_chatbot\event\document_processed::create(array(
                'objectid' => $resourceid,
                'context' => context_module::instance($event->contextinstanceid),
                'other' => array(
                    'resourcename' => $resource->name,
                    'status' => 'success'
                )
            ));
            $event->trigger();
        } else {
            // Failed - log to Moodle log
            $event = \block_chatbot\event\document_process_failed::create(array(
                'objectid' => $resourceid,
                'context' => context_module::instance($event->contextinstanceid),
                'other' => array(
                    'resourcename' => $resource->name,
                    'status' => 'failed'
                )
            ));
            $event->trigger();
        }
        
        return $result;
    }
    
    /**
     * Observer for resource updated event
     *
     * @param \core\event\base $event The event
     * @return bool Success status
     */
    public static function resource_updated(\core\event\base $event) {
        // Reuse the same logic as resource_created
        return self::resource_created($event);
    }
    
    /**
     * Send file to Flowise API
     *
     * @param string $filepath Path to the file
     * @param string $filename Name of the file
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @return bool Success status
     */
    public static function send_to_flowise($filepath, $filename, $courseid, $userid)  {
        global $CFG;
        
        // Verificar se é um PDF
        $mimetype = mime_content_type($filepath);
        if ($mimetype !== 'application/pdf') {
            error_log('Flowise upload error: Apenas arquivos PDF são suportados');
            return false;
        }
        
        // Endpoint correto para o PDF File Loader
        $uploadendpoint = "http://localhost:3000/api/v1/nodes/pdfFile";
        
        // Inicializar cURL
        $ch = curl_init() ;
        
        // Preparar o ficheiro para upload
        $cfile = new \CURLFile($filepath, $mimetype, $filename);
        
        // Metadados do Moodle
        $metadata = array(
            'source' => 'moodle',
            'courseid' => $courseid,
            'coursename' => get_course($courseid)->fullname,
            'userid' => $userid,
            'username' => fullname(get_complete_user_data('id', $userid)),
            'uploadtime' => time(),
            'documenttype' => 'course_resource'
        );
        
        // Preparar os dados POST - baseado na interface do PDF File Loader
        $postdata = array(
            'file' => $cfile,
            'usage' => 'perPage',  // Um documento por página
            'legacyBuild' => 'false',
            'metadata' => json_encode($metadata)
        );
        
        // Configurar opções cURL
        curl_setopt($ch, CURLOPT_URL, $uploadendpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer rUSCBhWX2L2xlamT7siiLrS4v2QsGiW51qIBBlGrS_U',
            'Accept: application/json'
        ));
        
        // Executar sessão cURL
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE) ;
        $error = curl_error($ch);
        
        // Registar para depuração
        error_log('Flowise upload response: ' . $response);
        if (!empty($error)) {
            error_log('Flowise upload error: ' . $error);
        }
        
        // Fechar sessão cURL
        curl_close($ch);
        
        // Verificar se a requisição foi bem-sucedida
        if ($httpcode >= 200 && $httpcode < 300 && $response)  {
            // Registar upload bem-sucedido no banco de dados
            self::log_document_upload($filename, $courseid, $userid, true, json_decode($response, true));
            return true;
        } else {
            // Registar falha de upload no banco de dados
            self::log_document_upload($filename, $courseid, $userid, false, array(
                'error' => $response, 
                'curl_error' => $error,
                'http_code' => $httpcode
            ) );
            return false;
        }
    }    
    /**
     * Log document upload to database
     *
     * @param string $filename Filename
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @param bool $success Whether upload was successful
     * @param array $responsedata Response data from Flowise API
     * @return int|bool ID of the new record, or false if failed
     */
    private static function log_document_upload($filename, $courseid, $userid, $success, $responsedata = null) {
        global $DB;
        
        try {
            $record = new \stdClass();
            $record->filename = $filename;
            $record->courseid = $courseid;
            $record->userid = $userid;
            $record->success = $success ? 1 : 0;
            $record->responsedata = $responsedata ? json_encode($responsedata) : null;
            $record->timecreated = time();
            
            return $DB->insert_record('block_chatbot_uploads', $record);
        } catch (\Exception $e) {
            error_log('Error logging document upload: ' . $e->getMessage());
            return false;
        }
    }
}
