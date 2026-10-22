<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$admin = \App\Models\User::factory()->create(['role' => 'admin']);
$token = $admin->createToken('test')->plainTextToken;

echo "Admin ID: {$admin->id}\n";
echo "Token: {$token}\n";

// Test creating a course
$client = new \GuzzleHttp\Client(['base_uri' => 'http://127.0.0.1:8000']);
try {
    $response = $client->post('/api/courses', [
        'headers' => [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'course_code' => 'TEST',
            'name' => 'Test Course',
            'department' => 'Testing',
        ],
    ]);
    $body = json_decode($response->getBody(), true);
    echo "Create response: " . json_encode($body, JSON_PRETTY_PRINT) . "\n";
    
    $courseId = $body['id'] ?? null;
    echo "Course ID: {$courseId}\n";
    
    if ($courseId) {
        $response = $client->get("/api/courses/{$courseId}", [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json',
            ],
        ]);
        $body = json_decode($response->getBody(), true);
        echo "Get response: " . json_encode($body, JSON_PRETTY_PRINT) . "\n";
    }
} catch (\GuzzleHttp\Exception\ClientException $e) {
    echo "Error: " . $e->getResponse()->getBody() . "\n";
}