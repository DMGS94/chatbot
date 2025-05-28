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
 * Chatbot JavaScript module for direct API integration
 *
 * @module     chatbot/chatbot
 * @copyright  2025 ISTEC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/str', 'core/notification'], function($, Ajax, Str, Notification) {
    
    // Private variables
    var sessionId = '';
    var userId = 0;
    var courseId = 0;
    var strings = {};
    
    /**
     * Initialize the chatbot
     *
     * @param {Object} config Configuration object
     */
    var init = function(config) {
        userId = config.userid || 0;
        courseId = config.courseid || 0;
        
        // Load required strings
        var requiredStrings = [
            {key: 'send', component: 'block_chatbot'},
            {key: 'type_message', component: 'block_chatbot'},
            {key: 'api_error', component: 'block_chatbot'},
            {key: 'you', component: 'block_chatbot'},
            {key: 'assistant', component: 'block_chatbot'}
        ];
        
        Str.get_strings(requiredStrings).then(function(results) {
            strings = {
                send: results[0],
                type_message: results[1],
                api_error: results[2],
                you: results[3],
                assistant: results[4]
            };
            
            // Set up event handlers
            setupEventHandlers();
            
            return true;
        }).catch(Notification.exception);
    };
    
    /**
     * Set up event handlers
     */
    var setupEventHandlers = function() {
        // Send button click
        $('#chatbot-send').on('click', function() {
            sendMessage();
        });
        
        // Input keypress (Enter)
        $('#chatbot-input').on('keypress', function(e) {
            if (e.which === 13) {
                sendMessage();
                e.preventDefault();
            }
        });
    };
    
    /**
     * Send message to chatbot
     */
    var sendMessage = function() {
        var messageInput = $('#chatbot-input');
        var message = messageInput.val().trim();
        
        if (message === '') {
            return;
        }
        
        // Clear input
        messageInput.val('');
        
        // Add user message to chat
        addMessageToChat(message, 'user');
        
        // Show loading indicator
        showLoading();
        
        // Send message to API
        sendMessageToAPI(message).then(function(response) {
            // Hide loading indicator
            hideLoading();
            
            if (response.status === 'success') {
                // Add bot response to chat
                addMessageToChat(response.message, 'bot');
                
                // Update session ID
                if (response.sessionid) {
                    sessionId = response.sessionid;
                }
            } else {
                // Show error
                Notification.alert(strings.api_error, response.message);
            }
            
            return true;
        }).catch(function(error) {
            // Hide loading indicator
            hideLoading();
            
            // Show error
            Notification.exception(error);
        });
    };
    
    /**
     * Send message to API
     *
     * @param {string} message Message to send
     * @return {Promise} Promise object
     */
    var sendMessageToAPI = function(message) {
        console.log('Enviando mensagem para API:', message);
        console.log('URL do endpoint:', M.cfg.wwwroot + '/blocks/chatbot/chat_api.php');
        console.log('Dados:', {
            message: message,
            courseid: courseId,
            sessionid: sessionId
        });
        
        return $.ajax({
            url: M.cfg.wwwroot + '/blocks/chatbot/chat_api.php',
            type: 'POST',
            data: {
                message: message,
                courseid: courseId,
                sessionid: sessionId
            },
            dataType: 'json'
        }).done(function(response) {
            console.log('Resposta recebida:', response);
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('Erro na requisição:', textStatus, errorThrown);
            console.log('Resposta completa:', jqXHR.responseText);
        });
    };
    
    /**
     * Add message to chat
     *
     * @param {string} message Message text
     * @param {string} sender Message sender ('user' or 'bot')
     */
    var addMessageToChat = function(message, sender) {
        var messagesContainer = $('#chatbot-messages');
        var messageClass = (sender === 'user') ? 'user-message' : 'bot-message';
        var senderName = (sender === 'user') ? strings.you : strings.assistant;
        
        var messageHtml = '<div class="message ' + messageClass + '">' +
                          '<div class="message-sender">' + senderName + '</div>' +
                          '<div class="message-text">' + formatMessage(message) + '</div>' +
                          '</div>';
        
        messagesContainer.append(messageHtml);
        
        // Scroll to bottom
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    };
    
    /**
     * Format message text (convert URLs to links, etc.)
     *
     * @param {string} message Message text
     * @return {string} Formatted message
     */
    var formatMessage = function(message) {
        // Convert URLs to links
        var urlRegex = /(https?:\/\/[^\s]+)/g;
        return message.replace(urlRegex, function(url) {
            return '<a href="' + url + '" target="_blank">' + url + '</a>';
        });
    };
    
    /**
     * Show loading indicator
     */
    var showLoading = function() {
        var messagesContainer = $('#chatbot-messages');
        var loadingHtml = '<div class="message bot-message loading-message">' +
                          '<div class="loading-indicator"><span>.</span><span>.</span><span>.</span></div>' +
                          '</div>';
        
        messagesContainer.append(loadingHtml);
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    };
    
    /**
     * Hide loading indicator
     */
    var hideLoading = function() {
        $('.loading-message').remove();
    };
    
    return {
        init: init
    };
});
