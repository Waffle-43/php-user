<?php

namespace App\Controllers;

use App\Models\Staff;
use Valitron\Validator;
use Utils\Helper;

class StaffSignUp
{
    public static function signUp()
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
        $v->rule('required', ['name', 'username', 'email', 'password', 'confirm_password'])->message('{field} is required');
        $v->rule('username', 'username')->message('Invalid username');
        $v->rule('alphaNum', 'username');
        $v->rule('email', 'email')->message('Invalid email format');
        $v->rule('lengthMin', 'password', 6)->message('Password must be at least 6 characters');
        $v->rule('equals', 'password', 'confirm_password')->message('Passwords do not match');
        $v->rule('regex', 'name', '/^[A-Za-z\s]+$/')->message('Full name must contain only letters and spaces');
        
        // Validate work email domain if needed
        // $v->rule('emailDomain', 'email', 'companyname.com')->message('Please use your company email address');

        if (!$v->validate()) {
            $_SESSION['errors'] = $v->errors();
            $_SESSION['old'] = $_POST;
            Helper::redirect('staff-signup');
            exit;
        }

        // Get validated inputs
        $username = $_POST['username'];
        $full_name = $_POST['name'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $email = $_POST['email'];
        
        // Store staff in database
        $staff = new Staff();
        if ($staff->emailExists($email)) {
            $_SESSION['errors']['email'][] = "Email is already registered!";
            $_SESSION['old'] = $_POST;
            Helper::redirect('staff-signup');
            exit;
        }

        // Set default status to 'pending' for admin approval
        $created = $staff->createStaff($full_name, $username, $email, $password, 'pending');

        if ($created) {
            $_SESSION['success'] = "Staff account request submitted successfully! An administrator will review your request.";
            
            // Optionally notify admin about new staff registration
            self::notifyAdmin($full_name, $email);
            
            Helper::redirect("staff-confirmation");
        } else {
            $_SESSION['errors']['general'][] = "Something went wrong!";
            Helper::redirect("staff-signup");
        }
        exit;
    }
    
    private static function notifyAdmin($name, $email)
    {
        // Implement admin notification logic here
        // This could email the administrator about a new staff registration
        
        // Example implementation (assuming you have a MailService like in your SignUp class)
        // $mailService = new \Core\MailService();
        // $adminEmail = \Core\Config::get('admin.email');
        // $subject = "New Staff Registration Request";
        // $message = "A new staff registration request has been submitted by $name ($email). Please review and approve or reject this request.";
        // $mailService->sendEmail($adminEmail, $subject, $message);
    }
}