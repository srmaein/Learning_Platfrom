<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/UserModel.php';

class UserController extends BaseController {
    private $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    public function login() {
        if ($this->isPost()) {
            $email = $this->getPostData('email');
            $password = $this->getPostData('password');

            $user = $this->userModel->authenticate($email, $password);
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $this->jsonResponse(['success' => true, 'message' => 'Login successful']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid credentials'], 401);
            }
        } else {
            $this->render('user/login');
        }
    }

    public function register() {
        if ($this->isPost()) {
            $data = [
                'email' => $this->getPostData('email'),
                'password' => password_hash($this->getPostData('password'), PASSWORD_DEFAULT),
                'name' => $this->getPostData('name')
            ];

            try {
                $userId = $this->userModel->create($data);
                $this->jsonResponse(['success' => true, 'message' => 'Registration successful', 'user_id' => $userId]);
            } catch (Exception $e) {
                $this->jsonResponse(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()], 400);
            }
        } else {
            $this->render('user/register');
        }
    }

    public function logout() {
        session_destroy();
        $this->redirect('/login');
    }

    public function profile() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $user = $this->userModel->find($_SESSION['user_id']);
        $this->render('user/profile', ['user' => $user]);
    }
}
?> 