<?php
// This file should be included when new applications are created
// or when application status changes

function triggerNotificationUpdate($applicationId = null) {
    // Send HTTP request to WebSocket server to trigger notification
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode([
                'type' => 'notification_update',
                'application_id' => $applicationId
            ])
        ]
    ]);
    
    // Try to notify WebSocket server
    @file_get_contents('http://localhost:8080/trigger', false, $context);
}

function broadcastNewApplication($applicationData) {
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode([
                'type' => 'new_application',
                'data' => $applicationData
            ])
        ]
    ]);
    
    @file_get_contents('http://localhost:8080/broadcast', false, $context);
}

// Example usage:
// Include this in your application creation/update scripts:
// triggerNotificationUpdate($newApplicationId);
?>
