<?php

namespace App\Services\UserSearchStrategies;

use Illuminate\Database\Eloquent\Builder;

class EmailSearchStrategy implements UserSearchStrategy
{
    public function apply(Builder $query, string $value): Builder
    {
        return $query->where('user_email', $value);
    }
} 