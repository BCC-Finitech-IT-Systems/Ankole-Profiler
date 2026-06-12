<?php

namespace App\Console\Commands;

use App\Services\AfricasTalkingSmsService;
use App\Services\CommunicationChannelManager;
use Illuminate\Console\Command;

class DebugSmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sms:debug 
                            {--test-phone= : Phone number to use for testing}
                            {--send-test : Actually send a test SMS (sandbox only)}';

    /**
     * The console command description.
     */
    protected $description = 'Debug and test Africa\'s Talking SMS configuration and connectivity';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (app()->environment("production")) {
            $this->error("This SMS test command is disabled in production.");
            return 1;
        }

        $this->info('🔍 Africa\'s Talking SMS Debug & Test Tool');
        $this->line('===========================================');
        $this->newLine();

        $smsService = app(AfricasTalkingSmsService::class);
        $channelManager = app(CommunicationChannelManager::class);

        // Test 1: Configuration
        $this->runConfigurationTest($smsService);

        // Test 2: Connection Test
        $this->runConnectionTest($smsService);

        // Test 3: Account Balance
        $this->runAccountBalanceTest($smsService);

        // Test 4: Phone Number Validation
        $this->runPhoneValidationTest($smsService);

        // Test 5: Channel Manager Test
        $this->runChannelManagerTest($channelManager);

        // Test 6: Optional SMS Sending Test
        if ($this->option('send-test')) {
            $this->runSmsTest($smsService);
        }

        $this->newLine();
        $this->info('🎉 Debug tests completed!');
        
        return Command::SUCCESS;
    }

    /**
     * Test configuration loading and validation
     */
    private function runConfigurationTest(AfricasTalkingSmsService $smsService): void
    {
        $this->info('1. 📋 Configuration Test');
        $this->line('   ========================');

        $config = config('africastalking');
        $debugInfo = $smsService->getDebugInfo();

        // Display configuration
        $this->table(['Setting', 'Value', 'Status'], [
            ['Username', $debugInfo['config']['username'] ?: 'NOT SET', $debugInfo['config']['username'] ? '✅' : '❌'],
            ['API Key', $debugInfo['config']['api_key_preview'], $debugInfo['config']['has_api_key'] ? '✅' : '❌'],
            ['Environment', $debugInfo['config']['environment'], '✅'],
            ['App Environment', $debugInfo['config']['app_environment'], '✅'],
            ['Sender ID', $debugInfo['config']['sender_id'], '✅'],
            ['Max Length', $debugInfo['config']['max_length'], '✅'],
            ['Logging', $debugInfo['config']['logging_enabled'] ? 'Enabled' : 'Disabled', '✅'],
            ['SSL Verification', $debugInfo['config']['ssl_verification_disabled'] ? 'DISABLED (Dev Only)' : 'Enabled', 
                $debugInfo['config']['ssl_verification_disabled'] && $debugInfo['config']['app_environment'] === 'local' ? '⚠️' : '✅'],
        ]);

        // Check for potential issues
        if (!$debugInfo['config']['username']) {
            $this->warn('   ⚠️  AT_USERNAME not set in .env file');
        }
        if (!$debugInfo['config']['has_api_key']) {
            $this->warn('   ⚠️  AT_API_KEY not set in .env file');
        }

        $this->newLine();
    }

    /**
     * Test connection to Africa's Talking API
     */
    private function runConnectionTest(AfricasTalkingSmsService $smsService): void
    {
        $this->info('2. 🔌 Connection Test');
        $this->line('   ===================');

        $connectionTest = $smsService->testConnection();

        if ($connectionTest['success']) {
            $this->info('   ✅ Successfully connected to Africa\'s Talking API');
            
            if (isset($connectionTest['response']['UserData'])) {
                $userData = $connectionTest['response']['UserData'];
                $this->line('   User ID: ' . ($userData['id'] ?? 'Unknown'));
                $this->line('   Username: ' . ($userData['username'] ?? 'Unknown'));
            }
        } else {
            $this->error('   ❌ Failed to connect to Africa\'s Talking API');
            $this->error('   Error: ' . $connectionTest['error']);
            
            // Show suggestions if available
            if (!empty($connectionTest['suggestions'])) {
                $this->warn('   💡 Suggestions:');
                foreach ($connectionTest['suggestions'] as $suggestion) {
                    $this->warn('      • ' . $suggestion);
                }
            }
        }

        $this->newLine();
    }

    /**
     * Test account balance retrieval
     */
    private function runAccountBalanceTest(AfricasTalkingSmsService $smsService): void
    {
        $this->info('3. 💰 Account Balance Test');
        $this->line('   ========================');

        $balance = $smsService->getAccountBalance();

        if ($balance['success']) {
            $this->info('   ✅ Account balance retrieved successfully');
            $this->line('   Balance: ' . $balance['balance'] . ' ' . $balance['currency']);
        } else {
            $this->error('   ❌ Failed to retrieve account balance');
            $this->error('   Error: ' . ($balance['error'] ?? 'Unknown error'));
        }

        $this->newLine();
    }

    /**
     * Test phone number validation and formatting
     */
    private function runPhoneValidationTest(AfricasTalkingSmsService $smsService): void
    {
        $this->info('4. 📱 Phone Number Validation Test');
        $this->line('   =================================');

        $testNumbers = [
            '0760081801' => 'Uganda local format',
            '+256760081801' => 'Uganda international format',
            '0711123456' => 'Kenya local format',
            '+254711123456' => 'Kenya international format',
            '0621234567' => 'Tanzania local format',
            '+255621234567' => 'Tanzania international format',
            'invalid' => 'Invalid format',
            '123' => 'Too short',
        ];

        $results = [];
        foreach ($testNumbers as $number => $description) {
            $isValid = $smsService->validatePhoneNumber($number);
            $formatted = $smsService->formatPhoneNumber($number);
            
            $results[] = [
                $number,
                $description,
                $isValid ? 'Valid' : 'Invalid',
                $formatted ?: 'N/A'
            ];
        }

        $this->table(['Number', 'Description', 'Valid', 'Formatted'], $results);
        $this->newLine();
    }

    /**
     * Test communication channel manager
     */
    private function runChannelManagerTest(CommunicationChannelManager $channelManager): void
    {
        $this->info('5. 🔗 Channel Manager Test');
        $this->line('   ========================');

        $isAvailable = $channelManager->isChannelAvailable('sms');
        $channelInfo = $channelManager->getChannelInfo();

        $this->line('   SMS Channel Available: ' . ($isAvailable ? 'YES ✅' : 'NO ❌'));

        if (isset($channelInfo['sms'])) {
            $smsInfo = $channelInfo['sms'];
            
            $this->table(['Property', 'Value'], [
                ['Type', $smsInfo['type']],
                ['Available', $smsInfo['available'] ? 'Yes' : 'No'],
                ['Max Message Length', $smsInfo['max_message_length']],
                ['Bulk Messaging', $smsInfo['capabilities']['bulk_messaging'] ?? 'Unknown'],
                ['Delivery Reports', $smsInfo['capabilities']['delivery_reports'] ?? 'Unknown'],
                ['Max Recipients (Bulk)', $smsInfo['capabilities']['max_recipients_per_bulk'] ?? 'Unknown'],
            ]);
        }

        $this->newLine();
    }

    /**
     * Test actual SMS sending (only in sandbox)
     */
    private function runSmsTest(AfricasTalkingSmsService $smsService): void
    {
        $this->info('6. 📤 SMS Sending Test');
        $this->line('   ====================');

        $environment = config('africastalking.environment');
        
        if ($environment !== 'sandbox') {
            $this->warn('   ⚠️  SMS sending test only runs in sandbox mode');
            $this->warn('   Current environment: ' . $environment);
            return;
        }

        $testPhone = $this->option('test-phone') ?: '+254711XXXYYY';
        $testMessage = 'Test SMS from Ankole Profiler Debug Tool - ' . now()->format('H:i:s');

        $this->line('   Environment: ' . $environment);
        $this->line('   Test Phone: ' . $testPhone);
        $this->line('   Message: ' . $testMessage);
        $this->newLine();

        if ($this->confirm('   Send test SMS? (This will use API credits in sandbox)')) {
            $result = $smsService->sendSms($testPhone, $testMessage);

            if ($result->isSuccessful()) {
                $this->info('   ✅ SMS sent successfully!');
                $this->line('   Message ID: ' . $result->messageId);
                
                if ($result->providerMessageId) {
                    $this->line('   Provider Message ID: ' . $result->providerMessageId);
                }
                
                if (isset($result->metadata['cost'])) {
                    $this->line('   Cost: ' . $result->metadata['cost']);
                }
            } else {
                $this->error('   ❌ SMS sending failed');
                $this->error('   Error: ' . $result->errorMessage);
            }
        } else {
            $this->line('   ⏭️  SMS test skipped by user');
        }

        $this->newLine();
    }
}