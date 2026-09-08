<?php
require_once __DIR__ . 'BaseModel.php';

class UserModel extends BaseModel {
    protected $table = 'users';

    public function authenticate($email, $password, $userType) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email AND user_type = :user_type";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'email' => $email,
            'user_type' => $userType
        ]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']); // Don't return the password
            return $user;
        }
        
        return false;
    }

    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function saveResetToken($userId, $token, $expiry) {
        $sql = "UPDATE {$this->table} SET 
                reset_token = :token,
                reset_token_expiry = :expiry
                WHERE id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'token' => $token,
            'expiry' => $expiry,
            'user_id' => $userId
        ]);
    }

    public function validateResetToken($token) {
        $sql = "SELECT id FROM {$this->table} 
                WHERE reset_token = :token 
                AND reset_token_expiry > NOW()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function getUserIdByToken($token) {
        $sql = "SELECT id FROM {$this->table} WHERE reset_token = :token";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['token' => $token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null;
    }

    public function updatePassword($userId, $hashedPassword) {
        $sql = "UPDATE {$this->table} SET 
                password = :password,
                reset_token = NULL,
                reset_token_expiry = NULL
                WHERE id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'password' => $hashedPassword,
            'user_id' => $userId
        ]);
    }

    public function clearResetToken($userId) {
        $sql = "UPDATE {$this->table} SET 
                reset_token = NULL,
                reset_token_expiry = NULL
                WHERE id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['user_id' => $userId]);
    }

    public function create($data) {
        // Validate email uniqueness
        if ($this->findByEmail($data['email'])) {
            throw new Exception('Email already exists');
        }

        // Add created_at timestamp
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return parent::create($data);
    }

    protected function validate($data) {
        $errors = [];

        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (empty($data['password'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }

        if (empty($data['name'])) {
            $errors[] = 'Name is required';
        }

        if (!in_array($data['user_type'], ['student', 'teacher', 'admin'])) {
            $errors[] = 'Invalid user type';
        }

        return $errors;
    }
}
?> 