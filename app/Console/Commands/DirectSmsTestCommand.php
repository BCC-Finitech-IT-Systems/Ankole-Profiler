<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DirectAfricasTalkingSmsService;

class DirectSmsTestCommand extends Command
{
    protected $signature = 'sms:direct-test';
    protected $description = 'Test direct SMS service without SDK';

    public function handle()
    {
        if (app()->environment("production")) {
            $this->error("This SMS test command is disabled in production.");
            return 1;
        }

        $this->info('🔍 Direct SMS Service Test');
        $this->line('============================');
        
        try {
            $smsService = new DirectAfricasTalkingSmsService();
            $this->info('✅ Direct SMS Service initialized successfully');
            
            // Step 1: Configuration validation
            $this->info('⚙️ Step 1: Configuration Validation');
            $validation = $smsService->validateConfiguration();
            $this->displayValidation($validation);
            
            // Step 2: Connection test
            $this->info('🌐 Step 2: Connection Test');
            $connectionTest = $smsService->testConnection();
            if ($connectionTest['success']) {
                $this->info('✅ Connection successful');
                $this->line('Balance: ' . $connectionTest['balance']);
            } else {
                $this->error('❌ Connection failed');
                $this->line('Error: ' . $connectionTest['message']);
            }
            
            // Step 3: SMS sending test
            $this->info('📱 Step 3: SMS Sending Test');
            $result = $smsService->sendSms('+254700000000', 'Direct SMS test from Profiler App at ' . now());
            
            if ($result->isSuccessful()) {
                $this->info('✅ SMS sent successfully');
                $this->line('Message ID: ' . $result->messageId);
                $this->line('Provider Message ID: ' . ($result->providerMessageId ?? 'N/A'));
                if ($result->metadata) {
                    $this->line('Cost: ' . ($result->metadata['cost'] ?? 'N/A'));
                    $this->line('Message Parts: ' . ($result->metadata['message_parts'] ?? 'N/A'));
                }
            } else {
                $this->error('❌ SMS sending failed');
                $this->line('Error: ' . $result->errorMessage);
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Direct SMS Service failed');
            $this->line('Error: ' . $e->getMessage());
            $this->line('File: ' . $e->getFile() . ':' . $e->getLine());
        }
        
        return 0;
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
        
        $this->line('Configuration Details:');
        foreach ($validation['config'] as $key => $value) {
            if ($key === 'api_key_set') {
                $value = $value ? 'Yes' : 'No';
            }
            $this->line("  {$key}: {$value}");
        }
    }
}