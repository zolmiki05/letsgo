<?php
/**
 * Front controller – single entry point for all HTTP requests.
 * All non-file, non-directory requests are routed here via mod_rewrite.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';

$router = new Router();

// --- Auth ---
$router->get('/login',     'AuthController@loginForm');
$router->post('/login',    'AuthController@login');
$router->get('/register',  'AuthController@registerForm');
$router->post('/register', 'AuthController@register');
$router->post('/logout',          'AuthController@logout');
$router->get('/profile/password',  'AuthController@passwordForm');
$router->post('/profile/password', 'AuthController@changePassword');

// --- Dashboard ---
$router->get('/', 'DashboardController@index');

// --- Admin ---
$router->get('/admin/settings',           'AdminController@settings');
$router->post('/admin/settings',          'AdminController@saveSettings');
$router->get('/admin/invites',            'AdminController@invites');
$router->post('/admin/invites/generate',  'AdminController@generateInvite');
$router->get('/admin/users',              'AdminController@users');
$router->post('/admin/users/{id}/ban',    'AdminController@banUser');
$router->post('/admin/users/{id}/unban',  'AdminController@unbanUser');
$router->post('/admin/users/{id}/delete', 'AdminController@deleteUser');

// --- Groups ---
$router->get('/groups/create',           'GroupController@createForm');
$router->post('/groups/create',          'GroupController@create');
$router->get('/groups/{id}',             'GroupController@show');
$router->post('/groups/{id}/delete',     'GroupController@delete');
$router->post('/groups/{id}/invite',     'GroupController@generateInvite');

// --- Events ---
$router->get('/groups/{id}/events/create',  'EventController@createForm');
$router->post('/groups/{id}/events/create', 'EventController@create');
$router->get('/events/{id}',                'EventController@show');
$router->post('/events/{id}/delete',        'EventController@delete');
$router->post('/events/{id}/status',        'EventController@updateStatus');
$router->post('/events/{id}/feedback',      'EventController@saveFeedback');

// --- Invite join ---
$router->get('/join',  'InviteController@join');
$router->post('/join', 'InviteController@join');

$router->dispatch();
