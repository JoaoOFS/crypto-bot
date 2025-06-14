<?php

namespace App\Policies;

use App\Models\TradingStrategy;
use App\Models\User;

class TradingStrategyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TradingStrategy $strategy): bool
    {
        return $user->id === $strategy->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, TradingStrategy $strategy): bool
    {
        return $user->id === $strategy->user_id;
    }

    public function delete(User $user, TradingStrategy $strategy): bool
    {
        return $user->id === $strategy->user_id;
    }

    public function restore(User $user, TradingStrategy $strategy): bool
    {
        return $user->id === $strategy->user_id;
    }

    public function forceDelete(User $user, TradingStrategy $strategy): bool
    {
        return $user->id === $strategy->user_id;
    }
}
