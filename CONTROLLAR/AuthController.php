<?php
require_once __DIR__ . 'BaseController.php';
require_once __DIR__ . '../MODELS/UserModel.php';

class AuthController extends BaseController {
    private $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    public function login() {
        if ($this->isPost()) {
            $email = $this->getPostData()['email'] ?? '';
            $password = $this->getPostData()['password'] ?? '';
            $userType = $this->getPostData()['user_type'] ?? 'student'; // student, teacher, or admin

            try {
                $user = $this->userModel->authenticate($email, $password, $userType);
                
                if ($user) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_type'] = $userType;
                    $_SESSION['user_name'] = $user['name'];
                    
                    // Set session timeout (2 hours)
                    $_SESSION['last_activity'] = time();
                    
                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Login successful',
                        'redirect' => $this->getDashboardUrl($userType)
                    ]);
                } else {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Invalid credentials'
                    ], 401);
                }
            } catch (Exception $e) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Login failed: ' . $e->getMessage()
                ], 500);
            }
        } else {
            $this->render('auth/login');
        }
    }

    public function register() {
        if ($this->isPost()) {
            $userType = $this->getPostData()['user_type'] ?? 'student';
            $data = [
                'email' => $this->getPostData()['email'] ?? '',
                'password' => $this->getPostData()['password'] ?? '',
                'name' => $this->getPostData()['name'] ?? '',
                'user_type' => $userType
            ];

            try {
                // Validate registration data
                $errors = $this->validateRegistration($data);
                if (!empty($errors)) {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $errors
                    ], 400);
                    return;
                }

                // Hash password
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                
                // Create user
                $userId = $this->userModel->create($data);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Registration successful',
                    'user_id' => $userId
                ]);
            } catch (Exception $e) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Registration failed: ' . $e->getMessage()
                ], 500);
            }
        } else {
            $this->render('auth/register');
        }
    }

    public function logout() {
        // Clear all session variables
        $_SESSION = array();

        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destroy the session
        session_destroy();

        $this->redirect('/login.php');
    }

    public function forgotPassword() {
        if ($this->isPost()) {
            $email = $this->getPostData()['email'] ?? '';
            
            try {
                $user = $this->userModel->findByEmail($email);
                if ($user) {
                    // Generate reset token
                    $token = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Save token to database
                    $this->userModel->saveResetToken($user['id'], $token, $expiry);
                    
                    // Send reset email (implement email sending functionality)
                    // $this->sendResetEmail($email, $token);
                    
                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Password reset instructions sent to your email'
                    ]);
                } else {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Email not found'
                    ], 404);
                }
            } catch (Exception $e) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to process request: ' . $e->getMessage()
                ], 500);
            }
        } else {
            $this->render('auth/forgot-password');
        }
    }

    public function resetPassword() {
        if ($this->isPost()) {
            $token = $this->getPostData()['token'] ?? '';
            $password = $this->getPostData()['password'] ?? '';
            
            try {
                if ($this->userModel->validateResetToken($token)) {
                    $userId = $this->userModel->getUserIdByToken($token);
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Update password and clear reset token
                    $this->userModel->updatePassword($userId, $hashedPassword);
                    $this->userModel->clearResetToken($userId);
                    
                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Password has been reset successfully'
                    ]);
                } else {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Invalid or expired reset token'
                    ], 400);
                }
            } catch (Exception $e) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to reset password: ' . $e->getMessage()
                ], 500);
            }
        } else {
            $token = $this->getQueryParams()['token'] ?? '';
            if (empty($token)) {
                $this->redirect('/forgot-password');
            }
            $this->render('auth/reset-password', ['token' => $token]);
        }
    }

    private function validateRegistration($data) {
        $errors = [];

        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (empty($data['password'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        if (empty($data['name'])) {
            $errors[] = 'Name is required';
        }

        if (!in_array($data['user_type'], ['student', 'teacher', 'admin'])) {
            $errors[] = 'Invalid user type';
        }

        return $errors;
    }

    private function getDashboardUrl($userType) {
        switch ($userType) {
            case 'student':
                return '/student/dashboard';
            case 'teacher':
                return '/teacher/dashboard';
            case 'admin':
                return '/admin/dashboard';
            default:
                return '/login';
        }
    }

    private function checkSessionTimeout() {
        $timeout = 7200; // 2 hours in seconds
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            $this->logout();
            return false;
        }
        $_SESSION['last_activity'] = time();
        return true;
    }
}
?> 