<?php

namespace App\Services\UserSearchStrategies;

use Illuminate\Database\Eloquent\Builder;

interface UserSearchStrategy
{
    public function apply(Builder $query, string $value): Builder;
} 