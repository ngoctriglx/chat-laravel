<?php

namespace App\Services\UserSearchStrategies;

use Illuminate\Database\Eloquent\Builder;

class PhoneSearchStrategy implements UserSearchStrategy
{
    public function apply(Builder $query, string $value): Builder
    {
        return $query->where('user_phone', $value);
    }
} 