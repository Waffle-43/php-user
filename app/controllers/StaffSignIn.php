<?php

namespace App\Controllers;

use App\Models\Staff;
use Valitron\Validator;
use Utils\Helper;

class StaffSignIn
{
    public static function signIn()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            exit;
        }
        
        $token = $_POST['csrf_token'] ?? null;

        if (!Helper::verifyCsrfToken($token)) {
            http_response_code(403);
            die("CSRF validation failed!");
        }
        
        $v = new Validator($_POST);
        $v->rule('required', ['username', 'staff-email', 'password'])->message('{field} is required');
        $v->rule('username', 'username')->message('Invalid username');
        $v->rule('email', 'staff-email')->message('Invalid email format');
        $v->rule('lengthMin', 'password', 6)->message('Password must be at least 6 characters');

        if (!$v->validate()) {
            $_SESSION['errors'] = $v->errors();
            $_SESSION['old'] = $_POST;
            Helper::redirect('staff-signin');
            exit;
        }

        $username = $_POST['username'];
        $email = $_POST['staff-email'];
        $password = $_POST['password'];

        $staff = new Staff();
        $staffMember = $staff->findByEmail($email);
        
        if ($staffMember) {
            if (password_verify($password, $staffMember->password)) {
                // Check if staff account is approved
                if ($staffMember->status !== 'approved') {
                self::showError('general', 'Your staff account is pending approval', 'staff-signin');
                echo "Staff status: " . $staffMember->status . "\n";
                exit;
            }

            $_SESSION['staff'] = [
                    'id' => $staffMember->id,
                    'username' => $staffMember->username,
                    'email' => $staffMember->email,
                    'email_encrypted' => Helper::encryptEmail($staffMember->email),
                    'name' => $staffMember->name,
                    'role' => $staffMember->role,
                    'permissions' => $staffMember->permissions, 
                    'last_activity' => time(),
                ];
                
                if (!empty($_POST['remember_me'])) {
                    setcookie("staff_remember", base64_encode($staffMember->id), time() + (30 * 24 * 60 * 60), "/", "", true, true);
                }
                
                Helper::redirect("staff-dashboard");
            } else {
                self::showError('general', 'Wrong login details', 'staff-signin');
            }
        } else {
            self::showError('staff-email', "Email address not found", "staff-signin");
        }
    }

    private static function showError(string $field, string $message, string $redirect)
    {
        $_SESSION['errors']["{$field}"][] = $message;
        $_SESSION['old'] = $_POST;
        Helper::redirect($redirect);
        exit;
    }
}
