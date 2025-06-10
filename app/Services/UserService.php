<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserService
{
    public function getUserInformation($userId)
    {
        return Cache::remember("user_info:{$userId}", now()->addMinutes(1), function () use ($userId) {
            $user = User::find($userId);
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
                'picture' => asset('storage/' . ($user['user_detail']['picture'] ?? 'avatars/f7P4t4u4p5thbDZkRAomCjtRv7c2z92aha9OOZvXENpDrv7LGjCmb9pz3bxz8SwZ.png')),
                'background_image' => asset('storage/' . ($user['user_detail']['background_image'] ?? 'backgrounds/bg-5dsf6fdsfsdfsadfas68fsda5cxz7dfsfsd7fds5f.jpg')),
                'birth_date' => $user['user_detail']['birth_date'],
                'status_message' => $user['user_detail']['status_message'],
            ];
            return $data;
        });
    }

    public function getUserByEmail($email)
    {
        return User::where('user_email', $email)->first();
    }

    public function getUserByPhone($phone)
    {
        return User::where('user_phone', $phone)->first();
    }

    public function getUserById($userId)
    {
        return User::where('user_id', $userId)->first();
    }

    public function getUserByAny($query)
    {
        if (filter_var($query, FILTER_VALIDATE_EMAIL)) {
            return $this->getUserByEmail($query);
        } elseif (preg_match('/^\+?[0-9]{10,15}$/', $query)) {
            return  $this->getUserByPhone($query);
        }
        return null;
    }

    public function getStatusByUserId($userId)
    {
        $user = $this->getUserById($userId);
        if (!$user) {
            return false;
        }
        return $user->user_account_status;
    }

    /**
     * Search users by exact email or phone (returns single result or null)
     * 
     * @param array $filters
     * @return array|null
     */
    public function searchUsers(array $filters = [])
    {
        $query = User::with('userDetail')
            ->where('user_account_status', User::STATUS_ACTIVE);

        // Search by exact email or phone only (security requirement)
        if (!empty($filters['q'])) {
            $searchQuery = $filters['q'];
            
            // Check if it's a valid email or phone format
            if (filter_var($searchQuery, FILTER_VALIDATE_EMAIL)) {
                $query->where('user_email', $searchQuery);
            } elseif (preg_match('/^\+?[0-9]{10,15}$/', $searchQuery)) {
                $query->where('user_phone', $searchQuery);
            } else {
                // Return null if query is not a valid email or phone
                return null;
            }
        }

        // Filter by type (email or phone only)
        if (!empty($filters['type'])) {
            switch ($filters['type']) {
                case 'email':
                    $query->whereNotNull('user_email');
                    break;
                case 'phone':
                    $query->whereNotNull('user_phone');
                    break;
                default:
                    // Invalid type, return null
                    return null;
            }
        }

        // Exclude current user
        if (!empty($filters['exclude_user_id'])) {
            $query->where('user_id', '!=', $filters['exclude_user_id']);
        }

        // Get single user
        $user = $query->first();

        if (!$user) {
            return null;
        }

        // Return user information in the expected format
        return $this->getUserInformation($user->user_id);
    }
}
