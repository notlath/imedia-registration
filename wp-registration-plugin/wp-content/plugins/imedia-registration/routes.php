<?php

/**
 * IMedia Registration — Route definitions.
 *
 * Phase 5: adds the Outbox surface (index / process / retry). The
 * 39 routes registered in Phases 3-4 are preserved unchanged.
 *
 * Per php-pro: typed closure parameter; middleware via __invoke classes.
 * Per wordpress-pro: CSRF on every state-changing form post.
 */

declare(strict_types=1);

use App\Controllers\{
    AlertsController, ApplicationsController, ContactsController,
    CustomEndpointsController, DashboardController, ExportController,
    FormRouteController, HomeController, LoginController,
    OutboxController, ProfileController, RegistrationsController,
    SettingsController, SubmitController, UsersController
};
use App\Middleware\{AdminAuth, CsrfVerify, HmacVerify};
use App\Core\Router;

return static function (Router $router): void {

    // ----- Public -----
    $router->get('/',  [HomeController::class, 'index']);
    $router->post('/api/submit', [SubmitController::class, 'handle'], [HmacVerify::class]);

    // ----- Admin auth (public) -----
    $router->get('/admin/login',   [LoginController::class, 'showForm']);
    $router->post('/admin/login',  [LoginController::class, 'login']);
    $router->post('/admin/logout', [LoginController::class, 'logout']);

    // ----- Admin (authenticated) -----
    $router->get('/admin', [DashboardController::class, 'index'], [AdminAuth::class]);

    // Registrations
    $router->get('/admin/registrations',                  [RegistrationsController::class, 'index'],     [AdminAuth::class]);
    $router->get('/admin/registrations/new',              [RegistrationsController::class, 'newForm'],   [AdminAuth::class]);
    $router->post('/admin/registrations',                 [RegistrationsController::class, 'create'],    [AdminAuth::class, CsrfVerify::class]);
    $router->get('/admin/registrations/{id}',             [RegistrationsController::class, 'view'],      [AdminAuth::class]);
    $router->get('/admin/registrations/{id}/edit',        [RegistrationsController::class, 'edit'],      [AdminAuth::class]);
    $router->post('/admin/registrations/{id}',            [RegistrationsController::class, 'update'],    [AdminAuth::class, CsrfVerify::class]);
    $router->post('/admin/registrations/{id}/delete',      [RegistrationsController::class, 'delete'],    [AdminAuth::class, CsrfVerify::class]);
    $router->post('/admin/registrations/{id}/restore',    [RegistrationsController::class, 'restore'],   [AdminAuth::class, CsrfVerify::class]);
    $router->post('/admin/registrations/bulk-status',     [RegistrationsController::class, 'bulkStatus'], [AdminAuth::class, CsrfVerify::class]);

    // Alumni
    $router->get('/admin/alumni', [RegistrationsController::class, 'alumni'], [AdminAuth::class]);

    // Alerts
    $router->get('/admin/alerts', [AlertsController::class, 'index'], [AdminAuth::class]);

    // Contacts
    $router->get('/admin/contacts',                  [ContactsController::class, 'index'],  [AdminAuth::class]);
    $router->post('/admin/contacts/{id}/delete',    [ContactsController::class, 'delete'], [AdminAuth::class, CsrfVerify::class]);

    // Applications (OJT + Trainer)
    $router->get('/admin/applications/{type}',              [ApplicationsController::class, 'index'],  [AdminAuth::class]);
    $router->post('/admin/applications/{type}/{id}/delete', [ApplicationsController::class, 'delete'], [AdminAuth::class, CsrfVerify::class]);

    // Custom endpoints
    $router->get('/admin/custom-endpoints',                     [CustomEndpointsController::class, 'index'],       [AdminAuth::class]);
    $router->get('/admin/custom-endpoints/new',                 [CustomEndpointsController::class, 'newForm'],     [AdminAuth::class]);
    $router->post('/admin/custom-endpoints',                    [CustomEndpointsController::class, 'create'],      [AdminAuth::class, CsrfVerify::class]);
    $router->get('/admin/custom-endpoints/{id}/edit',           [CustomEndpointsController::class, 'edit'],        [AdminAuth::class]);
    $router->post('/admin/custom-endpoints/{id}',               [CustomEndpointsController::class, 'update'],      [AdminAuth::class, CsrfVerify::class]);
    $router->post('/admin/custom-endpoints/{id}/delete',        [CustomEndpointsController::class, 'delete'],      [AdminAuth::class, CsrfVerify::class]);
    $router->get('/admin/custom-endpoints/{id}/submissions',    [CustomEndpointsController::class, 'submissions'], [AdminAuth::class]);

    // Form routes (Settings sub-section)
    $router->post('/admin/form-routes/add',    [FormRouteController::class, 'add'],    [AdminAuth::class, CsrfVerify::class]);
    $router->post('/admin/form-routes/delete', [FormRouteController::class, 'delete'], [AdminAuth::class, CsrfVerify::class]);

    // Users
    $router->get('/admin/users',                  [UsersController::class, 'index'],  [AdminAuth::class]);
    $router->get('/admin/users/new',              [UsersController::class, 'newForm'], [AdminAuth::class]);
    $router->post('/admin/users',                 [UsersController::class, 'create'], [AdminAuth::class, CsrfVerify::class]);
    $router->get('/admin/users/{id}/edit',        [UsersController::class, 'edit'],    [AdminAuth::class]);
    $router->post('/admin/users/{id}',            [UsersController::class, 'update'], [AdminAuth::class, CsrfVerify::class]);
    $router->post('/admin/users/{id}/delete',     [UsersController::class, 'delete'], [AdminAuth::class, CsrfVerify::class]);

    // Profile
    $router->get('/admin/profile',  [ProfileController::class, 'show'],   [AdminAuth::class]);
    $router->post('/admin/profile', [ProfileController::class, 'update'], [AdminAuth::class, CsrfVerify::class]);

    // Settings
    $router->get('/admin/settings',            [SettingsController::class, 'show'],       [AdminAuth::class]);
    $router->post('/admin/settings',           [SettingsController::class, 'save'],       [AdminAuth::class, CsrfVerify::class]);
    $router->post('/admin/settings/test-email', [SettingsController::class, 'testEmail'], [AdminAuth::class, CsrfVerify::class]);

    // CSV exports
    $router->get('/admin/export/registrations.csv',         [ExportController::class, 'stream'], [AdminAuth::class]);
    $router->get('/admin/export/contacts.csv',              [ExportController::class, 'stream'], [AdminAuth::class]);
    $router->get('/admin/export/applications-ojt.csv',      [ExportController::class, 'stream'], [AdminAuth::class]);
    $router->get('/admin/export/applications-trainer.csv',  [ExportController::class, 'stream'], [AdminAuth::class]);

    // Outbox (Phase 5)
    $router->get('/admin/outbox',               [OutboxController::class, 'index'],   [AdminAuth::class]);
    $router->post('/admin/outbox/process',      [OutboxController::class, 'process'], [AdminAuth::class, CsrfVerify::class]);
    $router->post('/admin/outbox/{id}/retry',   [OutboxController::class, 'retry'],   [AdminAuth::class, CsrfVerify::class]);

    // Resume download (Phase 5)
    $router->get('/admin/registrations/{id}/resume', [RegistrationsController::class, 'downloadResume'], [AdminAuth::class]);
};
