<?php

namespace App\Services;

use OpenSwoole\WebSocket\Server as OpenSwooleServer;
use OpenSwoole\Http\Request as OpenSwooleHttpRequest;
use OpenSwoole\WebSocket\Frame as OpenSwooleFrame;

class WebSocketService {

    private $path;

    public function __construct() {
        $this->path = storage_path('websocket/connections.json');
    }

    protected function getConnections() {
        if (file_exists($this->path)) {
            $connections = json_decode(file_get_contents($this->path), true);
            return $connections ?: [];
        }
        return [];
    }

    protected function saveConnections($connections) {
        $directory = dirname($this->path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        file_put_contents($this->path, json_encode($connections, JSON_PRETTY_PRINT));
    }

    public function start() {
        $server = new OpenSwooleServer('0.0.0.0', 9200);

        $server->on('Start', function () {
            echo "OpenSwoole WebSocket Server is started at ws://0.0.0.0:9200\n";
        });

        $server->on('Open', function (OpenSwooleServer $server, OpenSwooleHttpRequest $request) {
            echo "connection open: {$request->fd}\n";
            $fd = $request->fd;

            $userId = $request->get['user_id'] ?? null;
            if (!$userId) {
                $server->push($request->fd, json_encode([
                    'message' => 'User ID is missing.',
                ]));
                $server->close($request->fd);
                return;
            }

            $connections = $this->getConnections();


            $connections[$userId] = $fd;
            echo "connections: " . json_encode($connections) . "\n";
            $this->saveConnections($connections);
        });

        $server->on('Message', function (OpenSwooleServer $server, OpenSwooleFrame $frame) {
            $data = json_decode($frame->data, true);

            $senderId = $data['sender_id'];
            $receiverId = $data['receiver_id'];
            $message = $data['message'];

            $connections = $this->getConnections();

            if (isset($connections[$receiverId])) {
                $server->push($connections[$receiverId], json_encode([
                    'sender_id' => $senderId,
                    'receiver_id' => $receiverId,
                    'message' => $message,
                ]));
            } else {
                $server->push($frame->fd, json_encode([
                    'sender_id' => $senderId,
                    'receiver_id' => $receiverId,
                    'message' => 'User offline',
                ]));
            }
        });

        $server->on('Close', function (OpenSwooleServer $server, int $fd) {
            echo "connection close: {$fd}\n";

            $connections = $this->getConnections();

            foreach ($connections as $userId => $storedFd) {
                if ($storedFd == $fd) {
                    unset($connections[$userId]);
                    echo "Connection closed for user: {$userId}\n";
                }
            }

            $this->saveConnections($connections);
        });

        $server->on('Disconnect', function (OpenSwooleServer $server, int $fd) {
            echo "connection disconnect: {$fd}\n";

            $connections = $this->getConnections();

            foreach ($connections as $userId => $storedFd) {
                if ($storedFd == $fd) {
                    unset($connections[$userId]);
                    echo "Connection closed for user: {$userId}\n";
                }
            }

            $this->saveConnections($connections);
        });

        $server->start();
    }
}
