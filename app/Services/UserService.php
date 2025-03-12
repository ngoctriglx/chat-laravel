<?php

namespace App\Services;

use App\Models\User;

class UserService {
    public function getUserInformation($user_id) {
        $user = User::find($user_id);
        if (!$user) {
            return null;
        }
        $user->load('userDetail');
        $user = $user->toArray();
        $data = [
            'user_id' => $user['user_id'],
            'user_email' => $user['user_email'],
            'user_phone' => $user['user_phone'],
            'first_name' => $user['user_detail']['first_name'],
            'last_name' => $user['user_detail']['last_name'],
            'gender' => $user['user_detail']['gender'],
            'picture' => $user['user_detail']['picture'],
            'background_image' => $user['user_detail']['background_image'],
            'birth_date' => $user['user_detail']['birth_date'],
            'status_message' => $user['user_detail']['status_message'],
        ];
        return $data;
    }

    public function getUserByEmail($email) {
        return User::where('user_email', $email)->first();
    }

    public function getUserByPhone($phone) {
        return User::where('user_phone', $phone)->first();
    }

    public function getUserById($userId) {
        return User::where('user_id', $userId)->first();
    }

    public function getUserByAny($query) {
        if (filter_var($query, FILTER_VALIDATE_EMAIL)) {
            return $this->getUserByEmail($query);
        } elseif (preg_match('/^\+?[0-9]{10,15}$/', $query)) {
            return  $this->getUserByPhone($query);
        }
        return null;
    }

    public function getStatusByUserId($userId) {
        $user = $this->getUserById($userId);
        if (!$user) {
            return false;
        }
        return $user->user_account_status;
    }
}
