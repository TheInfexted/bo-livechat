<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Canned Response Action API (no auth filter)
$routes->post('api/canned-response-action', 'CannedResponseActionController::executeAction');
$routes->options('api/canned-response-action', 'CannedResponseActionController::executeAction');

// Domain-based home route - redirect to appropriate dashboard based on domain and user type
$routes->get('/', function() {
    helper('domain');
    $session = session();
    
    if (isAdminDomain()) {
        // Admin domain - only allow admin users
        if ($session->has('user_id')) {
            return redirect()->to('dashboard');
        } else {
            return redirect()->to('login');
        }
    } elseif (isClientDomain()) {
        // Client domain - only allow clients and agents
        if ($session->has('client_user_id') || $session->has('agent_user_id')) {
            return redirect()->to('dashboard');
        } else {
            return redirect()->to('login');
        }
    } else {
        // Unknown domain - redirect to login
        return redirect()->to('login');
    }
});

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
$routes->match(['GET', 'POST'], '/api/getChatroomLink', 'ChatController::getChatroomLink');

// Chat History routes (accessible to authenticated users)
$routes->group('chat-history', ['filter' => 'authfilter'], function($routes) {
    $routes->get('/', 'ChatHistoryController::index');
    $routes->get('view/(:segment)', 'ChatHistoryController::view/$1');
    $routes->get('export', 'ChatHistoryController::export');
    $routes->get('get-stats', 'ChatHistoryController::getStats');
    $routes->get('get-chat-history-for-api-key', 'ChatHistoryController::getChatHistoryForApiKey');
});

// Domain-aware dashboard route
$routes->get('dashboard', 'DomainAwareDashboard::index');

// Client routes (legacy - keeping for backwards compatibility)
$routes->group('client', ['filter' => ['client_domain', 'clientfilter']], function($routes) {
    $routes->get('/', 'ClientController::dashboard');
    $routes->get('dashboard', 'ClientController::dashboard');
    $routes->get('api-keys', 'ClientController::apiKeys');
    $routes->get('chat-history', 'ClientController::chatHistory');
    $routes->get('manage-chats', 'ClientController::manageChats');
    $routes->get('profile', 'ClientController::profile');
    $routes->post('profile/update', 'ClientController::updateProfile');
    $routes->post('profile/update-api', 'ClientController::updateApiCredentials');
    $routes->get('realtime-stats', 'ClientController::getRealtimeStats');
    $routes->get('sessions-data', 'ClientController::getSessionsData');
    $routes->get('session-details/(:segment)', 'ClientController::getSessionDetails/$1');
    $routes->get('chat-history/view/(:segment)', 'ClientController::viewChatHistory/$1');
    
    // Keyword Responses routes (clients only)
    $routes->get('keyword-responses', 'ClientController::keywordResponses');
    $routes->get('get-keyword-response/(:segment)', 'ClientController::getKeywordResponse/$1');
    $routes->post('save-keyword-response', 'ClientController::saveKeywordResponse');
    $routes->post('delete-keyword-response', 'ClientController::deleteKeywordResponse');
    
    // Canned Responses routes (clients and agents)
    $routes->get('canned-responses', 'ClientController::cannedResponses');
    $routes->get('canned-responses-for-api-key', 'ClientController::getCannedResponsesForApiKey');
    $routes->get('get-canned-response/(:segment)', 'ClientController::getCannedResponse/$1');
    $routes->post('save-canned-response', 'ClientController::saveCannedResponse');
    $routes->post('delete-canned-response', 'ClientController::deleteCannedResponse');
    $routes->post('toggle-canned-response-status', 'ClientController::toggleCannedResponseStatus');
    
    
    // API Integration routes
    $routes->get('api-integration-config', 'ClientController::getApiIntegrationConfig');
    $routes->post('save-api-integration', 'ClientController::saveApiIntegration');
    $routes->post('test-api-integration', 'ClientController::testApiIntegration');
    
    // Agent Management routes (clients only)
    $routes->get('manage-agents', 'ClientController::manageAgents');
    $routes->post('agents/add', 'ClientController::addAgent');
    $routes->post('agents/edit', 'ClientController::editAgent');
    $routes->post('agents/delete', 'ClientController::deleteAgent');
});

// Admin routes (legacy - keeping for backwards compatibility)
$routes->group('admin', ['filter' => ['admin_domain', 'adminfilter']], function($routes) {
    $routes->get('/', 'AdminController::dashboard');
    $routes->get('dashboard', 'AdminController::dashboard');
    $routes->get('agents', 'AdminController::agents');
    $routes->post('agents/edit', 'AdminController::editAgent');
    $routes->post('agents/add', 'AdminController::addAgent');
    $routes->post('agents/delete', 'AdminController::deleteAgent');
    $routes->get('settings', 'AdminController::settings');
    $routes->post('settings/save', 'AdminController::saveSettings');
    $routes->get('customers', 'AdminController::customers');
    $routes->get('customers/(:segment)', 'AdminController::customerDetails/$1');
    
    // API Key Management routes
    $routes->get('api-keys', 'AdminController::apiKeys');
    $routes->post('api-keys/create', 'AdminController::createApiKey');
    $routes->post('api-keys/update', 'AdminController::updateApiKey');
    $routes->get('api-keys/edit/(:num)', 'AdminController::editApiKey/$1');
    $routes->post('api-keys/suspend/(:num)', 'AdminController::suspendApiKey/$1');
    $routes->post('api-keys/activate/(:num)', 'AdminController::activateApiKey/$1');
    $routes->post('api-keys/revoke/(:num)', 'AdminController::revokeApiKey/$1');
    $routes->post('api-keys/delete/(:num)', 'AdminController::deleteApiKey/$1');
    
    // Client Management routes
    $routes->get('manage-clients', 'ManageClientController::index');
    $routes->post('manage-clients/add', 'ManageClientController::add');
    $routes->post('manage-clients/edit', 'ManageClientController::edit');
    $routes->post('manage-clients/delete', 'ManageClientController::delete');
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

// Domain-aware login routes - Handle domain detection in controllers
$routes->get('login', 'DomainAwareAuth::login');
$routes->post('login', 'DomainAwareAuth::attemptLogin');
$routes->get('logout', 'DomainAwareAuth::logout');

// Legacy admin routes (keeping for backwards compatibility)
$routes->get('/admin/login', 'AdminAuth::login');
$routes->post('/admin/login', 'AdminAuth::attemptLogin');
$routes->get('/admin/logout', 'AdminAuth::logout');

// Chat API routes for clients and admin
$routes->group('chat', ['filter' => 'authfilter'], function($routes) {
    $routes->post('sendMessage', 'ChatController::sendMessage');
    $routes->post('acceptSession', 'ChatController::acceptSession');
    $routes->get('getMessages/(:segment)', 'ChatController::getMessages/$1');
    $routes->post('closeSession', 'ChatController::closeSession');
    $routes->get('checkSessionStatus/(:segment)', 'ChatController::checkSessionStatus/$1');
    $routes->get('getSessionDetails/(:segment)', 'ChatController::getSessionDetails/$1');
});

// API routes for WebSocket fallback (optional)
$routes->group('api', function($routes) {
    $routes->post('chat/send-message', 'ChatController::sendMessage');
    $routes->get('chat/check-status/(:segment)', 'ChatController::checkStatus/$1');
    
    // Client lookup API (for frontend integration)
    $routes->post('client/get-id-by-email', 'ClientController::getClientIdByEmail');
    
    // Widget API validation routes (no auth filter - public API)
    $routes->post('widget/validate', 'WidgetAuthController::validateWidget');
    $routes->post('widget/validate-session', 'WidgetAuthController::validateChatStart');
    $routes->post('widget/log-message', 'WidgetAuthController::logMessageSent');
});
