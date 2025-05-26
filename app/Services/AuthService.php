<?php

namespace App\Services;

class AuthService {
    public function getTypeUserName($emailOrPhone) {
        if (filter_var($emailOrPhone, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        } elseif (preg_match('/^\+?[0-9]{10,15}$/', $emailOrPhone)) {
            return 'phone';
        }
        return false;
    }
}
