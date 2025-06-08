<?php

namespace App\Http\Controllers\API\Chat;

use App\Events\UserPresence;
use App\Http\Controllers\API\ApiController;
use App\Services\PresenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PresenceController extends ApiController
{
    protected $presenceService;

    public function __construct(PresenceService $presenceService)
    {
        parent::__construct();
        $this->presenceService = $presenceService;
    }

    public function setOnline()
    {
        try {
            $user = Auth::user();
            $this->presenceService->setOnline($user);
            
            broadcast(new UserPresence($user, 'online'));
            
            return $this->success('Online status updated');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function setOffline()
    {
        try {
            $user = Auth::user();
            $this->presenceService->setOffline($user);
            
            broadcast(new UserPresence($user, 'offline', now()));
            
            return $this->success('Offline status updated');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
} 