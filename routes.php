<?php

/**
 * IMedia Registration — Route definitions.
 *
 * Phase 5: adds the Outbox surface (index / process / retry), bulk-status,
 * resume download, and form-routes CRUD.
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

return static function ( Router $router ): void {

    // ----- Public -----
    $router->get('/', array( HomeController::class, 'index' ));
    $router->post('/api/submit', array( SubmitController::class, 'handle' ), array( HmacVerify::class ));

    // ----- Admin auth (public) -----
    $router->get('/admin/login', array( LoginController::class, 'showForm' ));
    $router->post('/admin/login', array( LoginController::class, 'login' ));
    $router->post('/admin/logout', array( LoginController::class, 'logout' ));

    // ----- Admin (authenticated) -----
    $router->get('/admin', array( DashboardController::class, 'index' ), array( AdminAuth::class ));

    // Registrations
    $router->get('/admin/registrations', array( RegistrationsController::class, 'index' ), array( AdminAuth::class ));
    $router->get('/admin/registrations/new', array( RegistrationsController::class, 'newForm' ), array( AdminAuth::class ));
    $router->post('/admin/registrations', array( RegistrationsController::class, 'create' ), array( AdminAuth::class, CsrfVerify::class ));
    $router->get('/admin/registrations/{id}', array( RegistrationsController::class, 'view' ), array( AdminAuth::class ));
    $router->get('/admin/registrations/{id}/edit', array( RegistrationsController::class, 'edit' ), array( AdminAuth::class ));
    $router->post('/admin/registrations/{id}', array( RegistrationsController::class, 'update' ), array( AdminAuth::class, CsrfVerify::class ));
    $router->post('/admin/registrations/{id}/delete', array( RegistrationsController::class, 'delete' ), array( AdminAuth::class, CsrfVerify::class ));
    $router->post('/admin/registrations/{id}/restore', array( RegistrationsController::class, 'restore' ), array( AdminAuth::class, CsrfVerify::class ));
    $router->post('/admin/registrations/bulk-status', array( RegistrationsController::class, 'bulkStatus' ), array( AdminAuth::class, CsrfVerify::class ));

    // Alumni
    $router->get('/admin/alumni', array( RegistrationsController::class, 'alumni' ), array( AdminAuth::class ));

    // Alerts
    $router->get('/admin/alerts', array( AlertsController::class, 'index' ), array( AdminAuth::class ));

    // Contacts
    $router->get('/admin/contacts', array( ContactsController::class, 'index' ), array( AdminAuth::class ));
    $router->post('/admin/contacts/{id}/delete', array( ContactsController::class, 'delete' ), array( AdminAuth::class, CsrfVerify::class ));

    // Applications (OJT + Trainer)
    $router->get('/admin/applications/{type}', array( ApplicationsController::class, 'index' ), array( AdminAuth::class ));
    $router->post('/admin/applications/{type}/{id}/delete', array( ApplicationsController::class, 'delete' ), array( AdminAuth::class, CsrfVerify::class ));

    // Custom endpoints
    $router->get('/admin/custom-endpoints', array( CustomEndpointsController::class, 'index' ), array( AdminAuth::class ));
    $router->get('/admin/custom-endpoints/new', array( CustomEndpointsController::class, 'newForm' ), array( AdminAuth::class ));
    $router->post('/admin/custom-endpoints', array( CustomEndpointsController::class, 'create' ), array( AdminAuth::class, CsrfVerify::class ));
    $router->get('/admin/custom-endpoints/{id}/edit', array( CustomEndpointsController::class, 'edit' ), array( AdminAuth::class ));
    $router->post('/admin/custom-endpoints/{id}', array( CustomEndpointsController::class, 'update' ), array( AdminAuth::class, CsrfVerify::class ));
    $router->post('/admin/custom-endpoints/{id}/delete', array( CustomEndpointsController::class, 'delete' ), array( AdminAuth::class, CsrfVerify::class ));
    $router->get('/admin/custom-endpoints/{id}/submissions', array( CustomEndpointsController::class, 'submissions' ), array( AdminAuth::class ));

    // Form routes (Settings sub-section)
    $router->post('/admin/form-routes/add', array( FormRouteController::class, 'add' ), array( AdminAuth::class, CsrfVerify::class ));
    $router->post('/admin/form-routes/delete', array( FormRouteController::class, 'delete' ), array( AdminAuth::class, CsrfVerify::class ));

    // Users
    $router->get('/admin/users', array( UsersController::class, 'index' ), array( AdminAuth::class ));
    $router->get('/admin/users/new', array( UsersController::class, 'newForm' ), array( AdminAuth::class ));
    $router->post('/admin/users', array( UsersController::class, 'create' ), array( AdminAuth::class, CsrfVerify::class ));
    $router->get('/admin/users/{id}/edit', array( UsersController::class, 'edit' ), array( AdminAuth::class ));
    $router->post('/admin/users/{id}', array( UsersController::class, 'update' ), array( AdminAuth::class, CsrfVerify::class ));
    $router->post('/admin/users/{id}/delete', array( UsersController::class, 'delete' ), array( AdminAuth::class, CsrfVerify::class ));

    // Profile
    $router->get('/admin/profile', array( ProfileController::class, 'show' ), array( AdminAuth::class ));
    $router->post('/admin/profile', array( ProfileController::class, 'update' ), array( AdminAuth::class, CsrfVerify::class ));

    // Settings
    $router->get('/admin/settings', array( SettingsController::class, 'show' ), array( AdminAuth::class ));
    $router->post('/admin/settings', array( SettingsController::class, 'save' ), array( AdminAuth::class, CsrfVerify::class ));
    $router->post('/admin/settings/test-email', array( SettingsController::class, 'testEmail' ), array( AdminAuth::class, CsrfVerify::class ));

    // CSV exports
    $router->get('/admin/export/registrations.csv', array( ExportController::class, 'stream' ), array( AdminAuth::class ));
    $router->get('/admin/export/contacts.csv', array( ExportController::class, 'stream' ), array( AdminAuth::class ));
    $router->get('/admin/export/applications-ojt.csv', array( ExportController::class, 'stream' ), array( AdminAuth::class ));
    $router->get('/admin/export/applications-trainer.csv', array( ExportController::class, 'stream' ), array( AdminAuth::class ));

    // Outbox (Phase 5)
    $router->get('/admin/outbox', array( OutboxController::class, 'index' ), array( AdminAuth::class ));
    $router->post('/admin/outbox/process', array( OutboxController::class, 'process' ), array( AdminAuth::class, CsrfVerify::class ));
    $router->post('/admin/outbox/{id}/retry', array( OutboxController::class, 'retry' ), array( AdminAuth::class, CsrfVerify::class ));

    // Resume download (Phase 5)
    $router->get('/admin/registrations/{id}/resume', array( RegistrationsController::class, 'downloadResume' ), array( AdminAuth::class ));
};
