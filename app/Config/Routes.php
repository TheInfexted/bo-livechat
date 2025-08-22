<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Home route - redirect to admin dashboard
$routes->get('/', 'AdminController::dashboard');

// API routes for customer chat support (minimal, for data access)
$routes->post('/api/chat/assign-agent', 'ChatController::assignAgent');
$routes->get('/api/chat/messages/(:segment)', 'ChatController::getMessages/$1');
$routes->get('/api/chat/session-details/(:segment)', 'ChatController::getSessionDetails/$1');
$routes->post('/api/chat/close-session', 'ChatController::closeSession');
$routes->get('/api/chat/check-session-status/(:segment)', 'ChatController::checkSessionStatus/$1');
$routes->post('/api/chat/canned-response', 'ChatController::sendCannedResponse');
$routes->get('/api/agent/workload', 'ChatController::getAgentWorkload');
$routes->post('/api/admin/close-inactive', 'ChatController::closeInactiveSessions');

// Frontend integration route for getting chatroom links
$routes->match(['get', 'post'], '/api/getChatroomLink', 'ChatController::getChatroomLink');

// Chat History routes (accessible to authenticated users)
$routes->group('chat-history', ['filter' => 'authfilter'], function($routes) {
    $routes->get('/', 'ChatHistoryController::index');
    $routes->get('view/(:segment)', 'ChatHistoryController::view/$1');
    $routes->get('export', 'ChatHistoryController::export');
    $routes->get('get-stats', 'ChatHistoryController::getStats');
});

// Admin routes
$routes->group('admin', ['filter' => 'authfilter'], function($routes) {
    $routes->get('/', 'AdminController::dashboard');
    $routes->get('dashboard', 'AdminController::dashboard');
    $routes->get('chat', 'AdminController::chat');
    $routes->get('agents', 'AdminController::agents');
    $routes->post('agents/edit', 'AdminController::editAgent');
    $routes->post('agents/add', 'AdminController::addAgent');
    $routes->post('agents/delete', 'AdminController::deleteAgent');
    $routes->get('canned-responses', 'AdminController::cannedResponses');
    $routes->get('canned-responses/get/(:segment)', 'AdminController::getCannedResponse/$1');
    $routes->get('canned-responses/get-all', 'AdminController::getAllCannedResponses');
    $routes->post('canned-responses/save', 'AdminController::saveCannedResponse');
    $routes->post('canned-responses/delete', 'AdminController::deleteCannedResponse');
    
    // Keyword responses routes
    $routes->get('keyword-responses', 'AdminController::keywordResponses');
    $routes->get('get-keyword-response/(:segment)', 'AdminController::getKeywordResponse/$1');
    $routes->post('save-keyword-response', 'AdminController::saveKeywordResponse');
    $routes->post('delete-keyword-response', 'AdminController::deleteKeywordResponse');
    
    $routes->get('sessions-data', 'AdminController::sessionsData');
    $routes->get('settings', 'AdminController::settings');
    $routes->post('settings/save', 'AdminController::saveSettings');
    $routes->get('customers', 'AdminController::customers');
    $routes->get('customers/(:segment)', 'AdminController::customerDetails/$1');
    $routes->get('export/chats', 'AdminController::exportChats');
    
    // API Key Management routes
    $routes->get('api-keys', 'AdminController::apiKeys');
    $routes->post('api-keys/create', 'AdminController::createApiKey');
    $routes->post('api-keys/update', 'AdminController::updateApiKey');
    $routes->get('api-keys/edit/(:num)', 'AdminController::editApiKey/$1');
    $routes->post('api-keys/suspend/(:num)', 'AdminController::suspendApiKey/$1');
    $routes->post('api-keys/activate/(:num)', 'AdminController::activateApiKey/$1');
    $routes->post('api-keys/revoke/(:num)', 'AdminController::revokeApiKey/$1');
    $routes->post('api-keys/delete/(:num)', 'AdminController::deleteApiKey/$1');

});

// Real-time notifications (for WebSocket fallback)
$routes->group('api/notifications', function($routes) {
    $routes->get('poll', 'NotificationController::poll');
    $routes->post('mark-read', 'NotificationController::markRead');
});

// Webhook routes (for third-party integrations)
$routes->group('webhook', function($routes) {
    $routes->post('incoming/(:segment)', 'WebhookController::handleIncoming/$1');
    $routes->post('status-update', 'WebhookController::statusUpdate');
});

// Authentication routes
$routes->get('/login', 'Auth::login');
$routes->post('/login', 'Auth::attemptLogin');
$routes->get('/logout', 'Auth::logout');

// API routes for WebSocket fallback (optional)
$routes->group('api', function($routes) {
    $routes->post('chat/send-message', 'ChatController::sendMessage');
    $routes->get('chat/check-status/(:segment)', 'ChatController::checkStatus/$1');
    $routes->get('chat/quick-actions', 'AdminController::getQuickActions');
    
    // Widget API validation routes (no auth filter - public API)
    $routes->post('widget/validate', 'WidgetAuthController::validateWidget');
    $routes->post('widget/validate-session', 'WidgetAuthController::validateChatStart');
    $routes->post('widget/log-message', 'WidgetAuthController::logMessageSent');
});
