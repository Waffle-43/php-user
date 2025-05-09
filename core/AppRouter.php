<?php

namespace Core;

use App\Controllers\ResetPassword;
use App\Controllers\VerifyEmail;
use App\Controllers\SignUp;
use App\Controllers\SignIn;
use App\Controllers\UpdateProfile;
use App\Controllers\AdminDashboard;
use App\Controllers\StaffAuth;
use App\Controllers\StaffSignIn;
use App\Controllers\StaffSignUp;
use App\Auth\RolePermission;
use Utils\Helper;

use Bramus\Router\Router;

class AppRouter
{
    private static $router;

    public static function init()
    {
        if (!self::$router) {
            self::$router = new Router();
        }
        return self::$router;
    }

    public static function defineRoutes()
    {
        $router = self::init();

        // Home route
        $router->get('/', function () {
            Middleware::requireAuth();
            require __DIR__ . '/../integration_tests/integrated_homepage.php';
        });
        $router->get('/home', function () {
            Middleware::requireAuth();
              require __DIR__ . '/../integration_tests/integrated_homepage.php';
        });


        // Authentication routes
        $router->get('/sign-in', function () {
            require __DIR__ . '/../pages/auth/signin.php';
            Middleware::guestOnly();
        });

        $router->get('/sign-up', function () {
            require __DIR__ . '/../pages/auth/signup.php';
            Middleware::guestOnly();
        });

        $router->get('/staff-signin', function () {
            require __DIR__ . '/../pages/auth/staff-signin.php';
            Middleware::guestOnly();
        });
        $router->get('/staff-signup', function () {
            require __DIR__ . '/../pages/auth/staff-signup.php';
            Middleware::guestOnly();
        });

        $router->get('/verify-email', function () {
            require __DIR__ . '/../pages/auth/verify.php';
            Middleware::guestOnly();
        });

        $router->get('/reset-password', function () {
            Middleware::guestOnly();
            $require_verification = Config::get('auth.require_verification');
            if ($require_verification) {
                require __DIR__ . '/../pages/auth/recover.php';
            } else {
                $_SESSION['errors']["general"] = "Enable email verification on config";
                Helper::redirect('sign-in');
            }
        });

        $router->get('/logout', function () {
            Middleware::logout();
        });

        $router->get('/email-confirmation', function () {
            Middleware::guestOnly();
            require __DIR__ . '/../pages/auth/email-confirmation.php';
        });

        //service routes
        $router->get('/services', function () {
            Middleware::requireAuth();
            require __DIR__ . '/../services/SerCus.php';
        });
        $router->get('/manage-services', function () {
            Middleware::requireAuth();
            require __DIR__ . '/../services/service_provider_manage_services.php';
        });
        $router->get('/add-services', function () {
            Middleware::requireAuth();
            require __DIR__ . '/../services/addService.php';
        });
        $router->get('/list-services', function () {
            Middleware::requireAuth();
            require __DIR__ . '/../services/list_services.php';
        });
        $router->get('/reschedule-services', function () {
            Middleware::requireAuth();
            require __DIR__ . '/../services/service_provider_reschedule.php';
        });

        //appointment routes
        $router->get('/appointments', function () {
            Middleware::requireAuth();
            require __DIR__ . '/..//appointments/appointment.php';
        });
        $router->get('/all-appointments', function () {
            Middleware::requireAuth();
            require __DIR__ . '/../appointments/service_provider_all_appointments.php';
        });
        $router->get('/manage-appointment', function () {
            Middleware::requireAuth();
            require __DIR__ . '/../appointments/service_provider_manage_appointment.php';
        });
        $router->get('/add-appointments', function () {
            Middleware::requireAuth();
            require __DIR__ . '/../appointments/service_provider_add_appointment.php';
        });


        //stylist routes
        
        //notification routes
        $router->get('/all-notifications', function () {
            Middleware::requireAuth();
            require __DIR__ . '/../notifications/all_notifications.php';
        });
        $router->get('/notification', function () {
            Middleware::requireAuth();
            require __DIR__ . '/../notifications/notification.php';
        });
        //profile route
        $router->get('/profile', function () {
            Middleware::requireAuth();
            require __DIR__ . '/../pages/user/profile.php';
        });
        $router->post('update-profile', [UpdateProfile::class, 'updateProfile']);

        $router->post('/upload-pfp', function () {
            require __DIR__ . '/../pages/user/upload-pfp.php';
        });


        //post routes

        $router->post('/sign-up', function () {
            SignUp::signUp();
        });
        $router->post('/sign-in', function () {
            SignIn::signIn();
        });
        $router->post('/reset-password', function () {
            $controller = new ResetPassword();
            $controller->handleRequest();
        });
        $router->post('/update-profile', function () {
            UpdateProfile::updateProfile();
        });

        //staff routes
        $router->get('/staff-dashboard', function () {
            StaffAuth::requireStaffLogin();
            AdminDashboard::index();
        });
         $router->get('/service-dashboard', function () {
            require __DIR__ . '/../unified_dashboard.php';
        });

        $router->get('/staff-manage', function () {
            StaffAuth::requirePermission(RolePermission::PERMISSION_MANAGE_STAFF);
            AdminDashboard::manageStaff();
        });

        $router->post('/staff-confirmation', function () {
            StaffAuth::requirePermission(RolePermission::PERMISSION_MANAGE_STAFF);
            AdminDashboard::approveStaff();
        });
        
        $router->post('/staff-update', function () {
            StaffAuth::requirePermission(RolePermission::PERMISSION_MANAGE_STAFF);
            AdminDashboard::updateStaff();
        });
        
        $router->post('/staff-add', function () {
            StaffAuth::requirePermission(RolePermission::PERMISSION_MANAGE_STAFF);
            AdminDashboard::addStaff();
        });
        
        $router->post('/staff-delete', function () {
            StaffAuth::requirePermission(RolePermission::PERMISSION_MANAGE_STAFF);
            AdminDashboard::deleteStaff();
        });

        $router->get('/staff-logout', function () {
            StaffAuth::logout();
        });


        $router->post('/verify-email', function () {
            $controller = new VerifyEmail();
            $controller->handleRequest();
        });


        //worldcard
        $router->set404(function () {
            header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
            echo "404 - Page Not Found!";
        });
    }

    public static function run()
    {
        self::defineRoutes();
        self::$router->run();
    }
}
