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
 * Chatbot block with gamification features
 *
 * @package    block_chatbot
 * @copyright  2025 ISTEC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_chatbot extends block_base {

    /**
     * Initialize the block
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_chatbot');
    }

    /**
 * Get user interaction count
 * 
 * @param int $userid User ID
 * @return int Number of interactions
 */
private function get_user_interactions($userid) {
    global $DB;
    
    try {
        $count = $DB->count_records('block_chatbot_interactions', array('userid' => $userid));
        return $count;
    } catch (Exception $e) {
        // Table might not exist yet
        return 0;
    }
}

/**
 * Get user badges
 * 
 * @param int $userid User ID
 * @return array Array of badge objects
 */
private function get_user_badges($userid) {
    global $DB;
    
    try {
        $badges = $DB->get_records('block_chatbot_badges', array('userid' => $userid));
        return $badges;
    } catch (Exception $e) {
        // Table might not exist yet
        return array();
    }
}


    /**
     * Block settings
     */
    public function has_config() {
        return true;
    }

    /**
     * Allow multiple instances
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Specify which pages this block can appear on
     */
    public function applicable_formats() {
        return array(
            'all' => true,
            'site' => true,
            'site-index' => true,
            'course-view' => true,
            'my' => true
        );
    }

    /**
     * Return the content of this block
     */
    public function get_content() {
        global $CFG, $USER, $DB, $OUTPUT, $COURSE;
    
        if ($this->content !== null) {
            return $this->content;
        }
    
        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';
    
        // Check if user is logged in
        if (!isloggedin() || isguestuser()) {
            $this->content->text = get_string('login_required', 'block_chatbot');
            return $this->content;
        }
    
        // Get user interaction count
        $interactions = $this->get_user_interactions($USER->id);
        
        // Get user badges
        $badges = $this->get_user_badges($USER->id);
    
        // Build the chatbot interface
        $this->content->text .= html_writer::start_div('chatbot-container');
        
        // User stats and badges section
        $this->content->text .= html_writer::start_div('chatbot-stats');
        $this->content->text .= html_writer::tag('h4', get_string('your_stats', 'block_chatbot'));
        $this->content->text .= html_writer::tag('p', get_string('interactions', 'block_chatbot') . ': ' . $interactions);
        
        // Progress to next badge
        $nextBadgeLevel = 10; // Primeiro nível
        if ($interactions >= 10) $nextBadgeLevel = 50;
        if ($interactions >= 50) $nextBadgeLevel = 100;
        if ($interactions >= 100) $nextBadgeLevel = 100; // Máximo atingido
        
        $progress = min(($interactions / $nextBadgeLevel) * 100, 100);
        
        $this->content->text .= html_writer::tag('p', 
            get_string('next_badge', 'block_chatbot') . ': ' . $interactions . '/' . $nextBadgeLevel);
        
        $this->content->text .= html_writer::start_div('progress-container');
        $this->content->text .= html_writer::start_tag('div', array('class' => 'progress'));
        $this->content->text .= html_writer::tag('div', round($progress) . '%', 
            array(
                'class' => 'progress-bar',
                'role' => 'progressbar',
                'style' => 'width: ' . $progress . '%',
                'aria-valuenow' => $progress,
                'aria-valuemin' => '0',
                'aria-valuemax' => '100'
            )
        );
        $this->content->text .= html_writer::end_tag('div');
        $this->content->text .= html_writer::end_div();
        
        // Badges display
        if (!empty($badges)) {
            $this->content->text .= html_writer::start_div('chatbot-badges');
            $this->content->text .= html_writer::tag('h5', get_string('your_badges', 'block_chatbot'));
            foreach ($badges as $badge) {
                $this->content->text .= html_writer::tag('span', $badge->name, array('class' => 'badge badge-success'));
            }
            $this->content->text .= html_writer::end_div();
        }
        $this->content->text .= html_writer::end_div();
        
        // Chatbot direct interface
        $this->content->text .= html_writer::start_div('chatbot-interface');
        $this->content->text .= html_writer::tag('h4', get_string('chat_with_assistant', 'block_chatbot'));
        
        $this->content->text .= html_writer::start_div('chatbot-direct-interface');
        $this->content->text .= html_writer::tag('div', '', array('id' => 'chatbot-messages', 'class' => 'chatbot-messages'));
        $this->content->text .= html_writer::start_tag('div', array('class' => 'chatbot-input-container'));
        $this->content->text .= html_writer::empty_tag('input', array(
            'type' => 'text',
            'id' => 'chatbot-input',
            'class' => 'chatbot-input',
            'placeholder' => get_string('type_message', 'block_chatbot')
        ));
        $this->content->text .= html_writer::tag('button', get_string('send', 'block_chatbot'), array(
            'id' => 'chatbot-send',
            'class' => 'btn btn-primary'
        ));
        $this->content->text .= html_writer::end_tag('div');
        $this->content->text .= html_writer::end_div();
        
        $this->content->text .= html_writer::end_div();
        
        // Chat history section
        $this->content->text .= html_writer::start_div('chatbot-history');
        $this->content->text .= html_writer::tag('h4', get_string('chat_history', 'block_chatbot'));
        $this->content->text .= html_writer::tag('p', html_writer::link(
            new moodle_url('/blocks/chatbot/history.php'),
            get_string('view_history', 'block_chatbot'),
            array('class' => 'btn btn-secondary btn-sm')
        ));
        $this->content->text .= html_writer::end_div();
        
        $this->content->text .= html_writer::end_div();
        
        // Adicionar o script JavaScript para comunicação direta com o Flowise
        $username = fullname($USER);
        $coursename = $COURSE->fullname;
        
        $this->content->text .= "
            <script>
                async function query(data) {
                    const response = await fetch(
                        'http://localhost:3000/api/v1/prediction/ad7941c1-8011-4a7c-b314-2e2c0181076f',
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': 'Bearer rUSCBhWX2L2xlamT7siiLrS4v2QsGiW51qIBBlGrS_U'
                            },
                            body: JSON.stringify(data) 
                        }
                    );
                    const result = await response.json();
                    return result.text || result.answer || JSON.stringify(result);
                }
                
                document.addEventListener('DOMContentLoaded', function() {
                    const messagesContainer = document.getElementById('chatbot-messages');
                    const inputField = document.getElementById('chatbot-input');
                    const sendButton = document.getElementById('chatbot-send');
                    
                    function addMessage(message, sender) {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = 'message ' + (sender === 'user' ? 'user-message' : 'bot-message');
                        
                        const senderDiv = document.createElement('div');
                        senderDiv.className = 'message-sender';
                        senderDiv.textContent = sender === 'user' ? 'Você' : 'Assistente';
                        
                        const textDiv = document.createElement('div');
                        textDiv.className = 'message-text';
                        textDiv.textContent = message;
                        
                        messageDiv.appendChild(senderDiv);
                        messageDiv.appendChild(textDiv);
                        messagesContainer.appendChild(messageDiv);
                        
                        // Scroll to bottom
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                    
                    function showLoading() {
                        const loadingDiv = document.createElement('div');
                        loadingDiv.className = 'message bot-message loading-message';
                        loadingDiv.innerHTML = '<div class=\"loading-indicator\"><span>.</span><span>.</span><span>.</span></div>';
                        messagesContainer.appendChild(loadingDiv);
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                    
                    function hideLoading() {
                        const loadingMessage = document.querySelector('.loading-message');
                        if (loadingMessage) {
                            loadingMessage.remove();
                        }
                    }
                    
                    async function sendMessage() {
                        const message = inputField.value.trim();
                        if (!message) return;
                        
                        // Clear input
                        inputField.value = '';
                        
                        // Add user message
                        addMessage(message, 'user');
                        
                        // Show loading
                        showLoading();
                        
                        try {
                            // Send to Flowise
                            const response = await query({
                                question: message,
                                overrideConfig: {
                                    vars: {
                                        username: \"" . addslashes($username) . "\",
                                        coursename: \"" . addslashes($coursename) . "\"
                                    }
                                }
                            });
                            
                            // Hide loading
                            hideLoading();
                            
                            // Add bot response
                            addMessage(response, 'bot');
                            
                            // Register interaction
                            fetch(M.cfg.wwwroot + '/blocks/chatbot/register_interaction.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'question=' + encodeURIComponent(message) + '&answer=' + encodeURIComponent(response)
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    console.log('Interação registada com sucesso');
                                    if (data.badge_earned) {
                                        alert('🎉 Parabéns! Você ganhou um novo badge: ' + data.badge_name);
                                        // Recarregar a página para atualizar as estatísticas
                                        window.location.reload();
                                    }
                                }
                            })
                            .catch(err => {
                                console.error('Erro ao registar interação:', err);
                            });
                        } catch (error) {
                            hideLoading();
                            addMessage('Desculpe, ocorreu um erro ao processar sua mensagem.', 'bot');
                            console.error('Erro:', error);
                        }
                    }
                    
                    // Event listeners
                    sendButton.addEventListener('click', sendMessage);
                    
                    inputField.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            sendMessage();
                            e.preventDefault();
                        }
                    });
                });
            </script>
        ";
    
        return $this->content;
    }
}