<?php
require_once 'vendor/autoload.php'; // Install with: composer require ratchet/pawl

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class NotificationServer implements MessageComponentInterface {
    protected $clients;
    protected $pdo;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->initDatabase();
        echo "WebSocket server started on port 8080\n";
    }

    private function initDatabase() {
        try {
            $this->pdo = new PDO(
                "mysql:host=localhost;dbname=your_database_name",
                "your_username",
                "your_password",
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            echo "Database connection failed: " . $e->getMessage() . "\n";
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        switch ($data['type']) {
            case 'auth':
                // Store user info with connection
                $from->user_id = $data['user_id'];
                $from->role = $data['role'];
                echo "User {$data['user_id']} authenticated with role {$data['role']}\n";
                break;
                
            case 'get_notifications':
                $this->sendNotifications($from);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    public function broadcastNewNotification($notification) {
        $message = json_encode([
            'type' => 'notification',
            'notification' => $notification
        ]);

        foreach ($this->clients as $client) {
            if (isset($client->role) && $client->role === 'admin') {
                $client->send($message);
            }
        }
    }

    public function broadcastNotificationUpdate($count) {
        $message = json_encode([
            'type' => 'notification_update',
            'count' => $count
        ]);

        foreach ($this->clients as $client) {
            if (isset($client->role) && $client->role === 'admin') {
                $client->send($message);
            }
        }
    }

    private function sendNotifications(ConnectionInterface $conn) {
        if (!$this->pdo) return;

        try {
            $query = "
                SELECT 
                    a.application_id, 
                    a.status, 
                    a.created_at, 
                    u.first_name, 
                    u.surname, 
                    p.address AS property_address, 
                    p.owner AS property_owner,
                    p.property_id
                FROM 
                    rate_clearance_applications a
                JOIN 
                    users u ON a.user_id = u.user_id
                JOIN 
                    properties p ON a.property_id = p.property_id
                ORDER BY 
                    a.created_at DESC
                LIMIT 10
            ";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $message = json_encode([
                'type' => 'notifications_list',
                'notifications' => $notifications
            ]);

            $conn->send($message);
        } catch (Exception $e) {
            echo "Error sending notifications: " . $e->getMessage() . "\n";
        }
    }
}

// Start the server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new NotificationServer()
        )
    ),
    8080
);

$server->run();
?>
