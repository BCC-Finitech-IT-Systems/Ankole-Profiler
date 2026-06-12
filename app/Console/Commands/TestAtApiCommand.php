<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client as GuzzleClient;

class TestAtApiCommand extends Command
{
    protected $signature = 'at:test-api';
    protected $description = 'Test direct API connection to Africa\'s Talking';

    public function handle()
    {
        if (app()->environment("production")) {
            $this->error("This SMS test command is disabled in production.");
            return 1;
        }

        $this->info('🔍 Testing Direct Africa\'s Talking API Connection');
        $this->line('=================================================');
        
        $username = env('AT_USERNAME', 'sandbox');
        $apiKey = env('AT_API_KEY');
        
        if (empty($apiKey)) {
            $this->error('❌ AT_API_KEY not found in environment');
            return 1;
        }
        
        // Test 1: Basic connectivity to API endpoint
        $this->info('📡 Test 1: Basic connectivity to API endpoint');
        $baseUrl = 'https://api.sandbox.africastalking.com/version1/';
        $this->testBasicConnectivity($baseUrl);
        
        // Test 2: Account balance check via direct API call
        $this->info('💰 Test 2: Account balance check');
        $this->testAccountBalance($username, $apiKey, $baseUrl);
        
        // Test 3: SMS sending via direct API call  
        $this->info('📱 Test 3: SMS sending');
        $this->testSmsSending($username, $apiKey, $baseUrl);
        
        return 0;
    }
    
    private function testBasicConnectivity(string $baseUrl): void
    {
        try {
            $client = new GuzzleClient([
                'verify' => false, // Disable SSL verification for development
                'timeout' => 10,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ],
            ]);
            
            // Just test if we can reach the host
            $response = $client->get($baseUrl, [
                'http_errors' => false,
            ]);
            
            $this->info("✅ Successfully connected to {$baseUrl}");
            $this->line("Status Code: {$response->getStatusCode()}");
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to connect to {$baseUrl}");
            $this->line("Error: {$e->getMessage()}");
        }
    }
    
    private function testAccountBalance(string $username, string $apiKey, string $baseUrl): void
    {
        try {
            $client = new GuzzleClient([
                'base_uri' => $baseUrl,
                'verify' => false,
                'timeout' => 10,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ],
                'headers' => [
                    'apikey' => $apiKey,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json'
                ]
            ]);
            
            $response = $client->get("user?username={$username}", [
                'http_errors' => false,
            ]);
            
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            
            $this->info("✅ Account balance API call successful");
            $this->line("Status Code: {$response->getStatusCode()}");
            $this->line("Response: " . json_encode($data, JSON_PRETTY_PRINT));
            
        } catch (\Exception $e) {
            $this->error("❌ Account balance check failed");
            $this->line("Error: {$e->getMessage()}");
        }
    }
    
    private function testSmsSending(string $username, string $apiKey, string $baseUrl): void
    {
        try {
            $client = new GuzzleClient([
                'base_uri' => $baseUrl,
                'verify' => false,
                'timeout' => 10,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ],
                'headers' => [
                    'apikey' => $apiKey,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json'
                ]
            ]);
            
            $response = $client->post('messaging', [
                'form_params' => [
                    'username' => $username,
                    'to' => '+254700000000',
                    'message' => 'Test message from Profiler App at ' . now(),
                ],
                'http_errors' => false,
            ]);
            
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            
            $this->info("✅ SMS sending API call successful");
            $this->line("Status Code: {$response->getStatusCode()}");
            $this->line("Response: " . json_encode($data, JSON_PRETTY_PRINT));
            
        } catch (\Exception $e) {
            $this->error("❌ SMS sending failed");
            $this->line("Error: {$e->getMessage()}");
        }
    }
}