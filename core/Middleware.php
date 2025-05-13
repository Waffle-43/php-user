<?php

namespace Core;

use App\Auth\RbacService; // Added for RBAC

class Middleware
{
    /**
     * Check if the user is authenticated.
     * Redirect to sign-in page if not logged in.
     */
    public static function requireAuth()
    {
        if (!isset($_SESSION['user'])) {
            // $_SESSION['errors']['auth'][] = "You must be signed in to access this page.";
            header("Location: sign-in");
            exit;
        }

        // Auto logout if inactive for too long
        self::checkSessionTimeout();
    }

    /**
     * Ensure a guest (unauthenticated user) is accessing certain pages.
     * Redirect logged-in users away from sign-in/sign-up pages.
     */
    public static function guestOnly()
    {
        if (isset($_SESSION['user'])) {
            header("Location: home");
            exit;
        }
    }

    /**
     * Auto logout inactive users.
     */
    private static function checkSessionTimeout()
    {
        $timeout_duration = 900; // 15 minutes
        if (
            isset($_SESSION['user']['last_activity']) &&
            (time() - $_SESSION['user']['last_activity']) > $timeout_duration
        ) {
            session_unset();
            session_destroy();
            header("Location: sign-in");
            exit;
        }

        $_SESSION['user']['last_activity'] = time();
    }

    /**
     * Check if the authenticated user has a specific permission.
     * Redirects or shows 403 error if permission is denied.
     *
     * @param string $permissionName The name of the permission to check.
     */
    public static function requirePermission(string $permissionName)
    {
        // First, ensure the user is authenticated.
        self::requireAuth();

        // Assuming requireAuth() ensures $_SESSION['user'] is set.
        $userId = $_SESSION['user']['id'] ?? null;
        $sessionRole = $_SESSION['user']['role'] ?? null;

        if ($userId === null || $sessionRole === null) {
            // This case should ideally be caught by requireAuth, but as a safeguard:
            header("HTTP/1.1 403 Forbidden");
            echo "Access Denied: User information not found in session.";
            exit;
        }

        // Determine userType for RbacService based on session role
        $rbacUserType = '';
        if ($sessionRole === 'user') {
            $rbacUserType = 'customer';
        } elseif ($sessionRole === 'staff' || $sessionRole === 'admin') {
            // Assumes 'admin' session role corresponds to a user in the 'staff' table
            $rbacUserType = 'staff';
        } else {
            // Unknown session role, deny access
            header("HTTP/1.1 403 Forbidden");
            echo "Access Denied: Unknown user role.";
            exit;
        }

        $rbacService = new RbacService();
        if (!$rbacService->userHasPermission($userId, $permissionName, $rbacUserType)) {
            header("HTTP/1.1 403 Forbidden");
            // You might want to redirect to a specific error page or show a nicer message
            echo "Access Denied: You do not have the required permission ({$permissionName}).";
            exit;
        }
    }
    
    public static function logout()
    {
        session_unset();
        session_destroy();
        setcookie("remember_me", "", time() - 3600, "/", "", true, true);
        header("Location: sign-in");
        exit;
    }
}
