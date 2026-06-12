<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AfricasTalkingSmsService;

class SmsDebugCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:debug {--detailed : Show detailed configuration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug SMS configuration and connectivity';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (app()->environment("production")) {
            $this->error("This SMS test command is disabled in production.");
            return 1;
        }

        $this->info('🔍 SMS Configuration Debug');
        $this->line('=========================');
        
        // Step 1: Environment validation
        $this->info('📋 Step 1: Environment Variables');
        $this->checkEnvironmentVariables();
        
        // Step 2: Service initialization
        $this->info('🔧 Step 2: Service Initialization');
        try {
            $smsService = app(AfricasTalkingSmsService::class);
            $this->info('✅ SMS Service initialized successfully');
            
            // Step 3: Configuration validation
            $this->info('⚙️ Step 3: Configuration Validation');
            $validation = $smsService->validateConfiguration();
            $this->displayValidation($validation);
            
            // Step 4: Show raw config if requested
            if ($this->option('detailed')) {
                $this->info('🔍 Step 4: Detailed Configuration');
                $rawConfig = $smsService->getRawConfig();
                $this->displayRawConfig($rawConfig);
            }
            
            // Step 5: Network connectivity test
            $this->info('🌐 Step 5: Network Connectivity Test');
            $this->testConnectivity($smsService);
            
        } catch (\Exception $e) {
            $this->error('❌ SMS Service initialization failed');
            $this->line('Error: ' . $e->getMessage());
            $this->line('File: ' . $e->getFile() . ':' . $e->getLine());
            
            if (strpos($e->getMessage(), 'cURL error 6') !== false) {
                $this->line('');
                $this->warn('💡 cURL error 6 suggests hostname resolution failure.');
                $this->line('This could be caused by:');
                $this->line('  - Incorrect username format in SDK initialization');
                $this->line('  - Network connectivity issues');
                $this->line('  - Malformed API endpoint URL');
                $this->line('  - DNS resolution problems');
            }
        }
        
        return 0;
    }
    
    private function checkEnvironmentVariables()
    {
        $vars = [
            'AT_USERNAME' => env('AT_USERNAME'),
            'AT_API_KEY' => env('AT_API_KEY'),
            'AT_ENVIRONMENT' => env('AT_ENVIRONMENT', 'sandbox'),
        ];
        
        foreach ($vars as $key => $value) {
            if (empty($value)) {
                $this->error("❌ {$key}: NOT SET");
            } else {
                if ($key === 'AT_API_KEY') {
                    $this->info("✅ {$key}: SET (length: " . strlen($value) . ")");
                } else {
                    $this->info("✅ {$key}: {$value}");
                }
            }
        }
    }
    
    private function displayValidation($validation)
    {
        if ($validation['valid']) {
            $this->info('✅ Configuration is valid');
        } else {
            $this->error('❌ Configuration has issues:');
            foreach ($validation['issues'] as $issue) {
                $this->line("  - {$issue}");
            }
        }
        
        if (!empty($validation['warnings'])) {
            $this->warn('⚠️ Warnings:');
            foreach ($validation['warnings'] as $warning) {
                $this->line("  - {$warning}");
            }
        }
    }
    
    private function displayRawConfig($rawConfig)
    {
        $this->line('Configuration File Values:');
        foreach ($rawConfig['config_file'] as $key => $value) {
            if ($key === 'api_key') {
                $this->line("  {$key}: " . substr($value, 0, 10) . "..." . substr($value, -5));
            } else {
                $this->line("  {$key}: " . (is_array($value) ? json_encode($value) : $value));
            }
        }
        
        $this->line('Environment Values:');
        foreach ($rawConfig['env_values'] as $key => $value) {
            $this->line("  {$key}: " . (is_array($value) ? json_encode($value) : $value));
        }
    }
    
    private function testConnectivity($smsService)
    {
        try {
            // First, let's test the connection without sending
            $this->line('Testing connection to Africa\'s Talking API...');
            
            // Try to get account balance first (simpler call)
            try {
                $balance = $smsService->getAccountBalance();
                $this->info('✅ API connection successful');
                $this->line('Account Balance: ' . json_encode($balance));
            } catch (\Exception $e) {
                $this->error('❌ API connection failed during balance check');
                $this->line('Error: ' . $e->getMessage());
            }
            
            // Now test SMS sending
            $this->line('Testing SMS sending...');
            $result = $smsService->sendSms('+254700000000', 'Debug message from profiler app at ' . now());
            
            if ($result->isSuccessful()) {
                $this->info('✅ Test SMS sent successfully');
                $this->line('Message ID: ' . $result->messageId);
            } else {
                $this->error('❌ Test SMS failed');
                $this->line('Error: ' . $result->errorMessage);
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Network test failed');
            $this->line('Error: ' . $e->getMessage());
            
            // Provide specific guidance for common errors
            if (strpos($e->getMessage(), 'Could not resolve host') !== false) {
                $this->line('');
                $this->warn('💡 cURL error 6 means DNS resolution failed.');
                $this->line('The hostname being resolved appears to be just "messaging" instead of a full URL.');
                $this->line('This suggests an issue with how the Africa\'s Talking SDK is constructing the API endpoint.');
                $this->line('');
                $this->line('Common causes:');
                $this->line('  1. Incorrect username format for SDK initialization');
                $this->line('  2. Missing or malformed base URL in SDK configuration');
                $this->line('  3. SDK version compatibility issue');
                $this->line('');
                $this->line('The sandbox username should be exactly "sandbox" (which you have).');
                
                // Check if we can extract more details from the error
                if (strpos($e->getMessage(), 'messaging') !== false) {
                    $this->warn('The SDK is trying to connect to hostname "messaging" - this is incomplete.');
                    $this->line('Expected hostname should be something like "api.sandbox.africastalking.com"');
                }
            }
        }
    }
}