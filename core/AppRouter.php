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
use App\Controllers\RolePermission;
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
            require __DIR__ . '/../pages/home.php';
        });
        $router->get('/home', function () {
            Middleware::requireAuth();
            require __DIR__ . '/../pages/home.php';
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
