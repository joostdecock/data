<?php 
// Migrate users to new database structure
$app->get('/migrate', 'UserController:migrate');



/*******************/
/* Prefetch routes */
/*******************/

// YAML info bundle
$app->get('/info/yaml', 'InfoController:asYaml');

// JSON info bundle
$app->get('/info/json', 'InfoController:asJson');

// Locale bundles (these are the basis for translations)
$app->get('/info/locale/patterns', 'InfoController:patternsAsLocale');
$app->get('/info/locale/options', 'InfoController:optionsAsLocale');
$app->get('/info/locale/measurements', 'InfoController:measurementsAsLocale');

// Status
$app->get('/status', 'InfoController:status');


/********************/
/* Anonymous routes */
/********************/

// task runner
$app->get('/taskrunner', 'TaskController:taskRunner');

// log error
$app->post('/error', 'ErrorController:log');

// Preflight requests 
$app->options('/[{path:.*}]', function($request, $response, $path = null) {
    $settings = require __DIR__ . '/../src/settings.php';
    return $response
        ->withHeader('Access-Control-Allow-Origin', $settings['settings']['app']['origin'])
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Signup user
$app->post('/signup', 'UserController:signup');

// Resend user activation email
$app->post('/resend', 'UserController:resend');

// Activate user account
$app->get('/activate/{handle}/{token}', 'UserController:activate');

// Confirm user email change
$app->get('/confirm/{handle}/{token}', 'UserController:confirm');

// Recover user password
$app->post('/recover', 'UserController:recover');

// Reset user password
$app->post('/reset', 'UserController:reset');

// Download draft is anonymous coz no ajax
$app->get('/download/{handle}/{format}', 'DraftController:download');

// Referral logging
$app->post('/referral', 'ReferralController:log');

// Load shared draft
$app->get('/shared/draft/{handle}', 'DraftController:loadShared');

// Reply to comment via email
$app->post('/email/comment', 'CommentController:emailReply');

// Load page comments
$app->get('/comments/page/{page:.*}', 'CommentController:pageComments');

// Load recent comments
$app->get('/comments/recent/{count}', 'CommentController:recentComments');

// Load patron list
$app->get('/patrons/list', 'UserController:patronList');

/************************/
/* Authenticated routes */
/************************/

// Check for authenticated user
$app->get('/auth', 'UserController:auth');

// User login
$app->post('/login', 'UserController:login');

// Load user account data
$app->get('/account', 'UserController:load');

// Update user account
$app->put('/account', 'UserController:update');

// Delete user account
$app->delete('/account', 'UserController:remove');

// Load user profile data
$app->get('/profile/{handle}', 'UserController:profile');

// Load user role
$app->get('/role', 'UserController:role');

// Load model data
$app->get('/model/{handle}', 'ModelController:load');

// Update model
$app->put('/model/{handle}', 'ModelController:update');

// Create model
$app->post('/model', 'ModelController:create');

// Remove model
$app->delete('/model/{handle}', 'ModelController:remove');

// Create draft
$app->post('/draft', 'DraftController:create');

// Recreate draft
$app->post('/redraft', 'DraftController:recreate');

// Load draft data
$app->get('/draft/{handle}', 'DraftController:load');

// Update draft
$app->put('/draft/{handle}', 'DraftController:update');

// Remove draft
$app->delete('/draft/{handle}', 'DraftController:remove');

// Create comment
$app->post('/comment', 'CommentController:create');

// Remove comment
$app->delete('/comment/{id}', 'CommentController:remove');

// Export data
$app->get('/export', 'UserController:export');

// Export model data
$app->get('/export/model/{handle}', 'ModelController:export');

// Clone model 
$app->post('/clone/model/{handle}', 'ModelController:klone');

// Tiler
$app->post('/tools/tile', 'ToolsController:tile');

/****************/
/* Admin routes */
/****************/


// Add badge to user profile
$app->post('/admin/user/{handle}/badge/{badge}', 'AdminController:userAddBadge');

// Remove badge from user profile
$app->delete('/admin/user/{handle}/badge/{badge}', 'AdminController:userRemoveBadge');

// Set new user password (by admin)
$app->put('/admin/user/{handle}/password', 'AdminController:userSetPassword');

// Set patron status in user profile
$app->put('/admin/user/{handle}/patron/{tier}', 'AdminController:userSetPatronTier');

// Send patron email
$app->get('/admin/user/{handle}/email/patron', 'AdminController:userSendPatronEmail');

// Load user account
$app->get('/admin/user/{handle}', 'AdminController:userLoad');

// Find users 
$app->get('/admin/find/users/{filter}', 'AdminController:userFind');

// Recent users 
$app->get('/admin/recent/users', 'AdminController:recentUsers');

// Recent referrals 
$app->get('/admin/recent/referrals', 'AdminController:recentReferrals');

// Recent referrals for a host 
$app->get('/admin/recent/referrals/{host}', 'AdminController:recentReferralsForHost');

// List (recent) errors
$app->get('/admin/recent/errors', 'AdminController:errorsRecent');

// List all errors
$app->get('/admin/all/errors', 'AdminController:errorsAll');

// List error group
$app->get('/admin/errors/{hash:.*}', 'AdminController:errorsGroup');

// Update error group
$app->post('/admin/errors/{hash:.*}', 'AdminController:errorsUpdateGroup');

/*******************/
/* Catch-all route */
/*******************/


// Catch-all GET requests that don't match anything
$app->get('/[{name}]', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.html', $args);
});
