<?php

namespace App\Console\Commands;

use App\Services\WebSocketService;
use Illuminate\Console\Command;

class StartWebSocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the WebSocket server';

    /**
     * Execute the console command.
     */
    public function handle(WebSocketService $webSocketService)
    {
        $this->info("Starting WebSocket server...");
        $webSocketService->start();
    }
}
