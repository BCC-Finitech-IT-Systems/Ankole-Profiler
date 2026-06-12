<?php

namespace App\Console\Commands;

use App\Models\CommunicationTemplate;
use App\Models\Person;
use App\Services\AfricasTalkingSmsService;
use App\Services\CommunicationChannelManager;
use App\Services\EmailTemplateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestSmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sms:test 
                            {recipient : Phone number to send test SMS to}
                            {--message= : Custom message to send (optional)}
                            {--template= : Template ID to use (optional)}
                            {--person= : Person ID for template testing (optional)}';

    /**
     * The console command description.
     */
    protected $description = 'Test SMS functionality with Africa\'s Talking';

    /**
     * Execute the console command.
     */
    public function handle(
        AfricasTalkingSmsService $smsService,
        CommunicationChannelManager $channelManager,
        EmailTemplateService $templateService
    ): int {
        if (app()->environment("production")) {
            $this->error("This SMS test command is disabled in production.");
            return 1;
        }

        $recipient = $this->argument('recipient');
        $customMessage = $this->option('message');
        $templateId = $this->option('template');
        $personId = $this->option('person');

        $this->info('🚀 Testing SMS Communication System');
        $this->newLine();

        // Test 1: Service Availability
        $this->info('1. Testing SMS Channel Availability...');
        $isAvailable = $channelManager->isChannelAvailable('sms');
        
        if ($isAvailable) {
            $this->info('   ✅ SMS Channel is available and configured');
        } else {
            $this->error('   ❌ SMS Channel is not available');
            $this->warn('   Please check your Africa\'s Talking configuration in .env file');
            return Command::FAILURE;
        }

        // Test 2: Phone Number Validation
        $this->info('2. Testing Phone Number Validation...');
        $isValidPhone = $smsService->validatePhoneNumber($recipient);
        
        if ($isValidPhone) {
            $formattedPhone = $smsService->formatPhoneNumber($recipient);
            $this->info("   ✅ Phone number is valid: {$formattedPhone}");
        } else {
            $this->error("   ❌ Phone number format is invalid: {$recipient}");
            return Command::FAILURE;
        }

        // Test 3: Account Balance
        $this->info('3. Checking Account Balance...');
        $balanceInfo = $smsService->getAccountBalance();
        
        if ($balanceInfo['success']) {
            $this->info("   ✅ Account Balance: {$balanceInfo['balance']} {$balanceInfo['currency']}");
        } else {
            $this->warn("   ⚠️  Could not retrieve account balance: {$balanceInfo['error']}");
        }

        // Test 4: Send Test Message
        $this->newLine();
        $this->info('4. Sending Test SMS...');

        $message = $customMessage ?? 'Hello! This is a test message from Ankole Profiler SMS system. 📱';

        if ($templateId && $personId) {
            // Test with template
            $result = $this->testWithTemplate($templateId, $personId, $recipient, $channelManager, $templateService);
        } else {
            // Test direct SMS
            $result = $smsService->sendSms($recipient, $message);
        }

        if ($result->isSuccessful()) {
            $this->info('   ✅ SMS sent successfully!');
            $this->info("   📧 Message ID: {$result->messageId}");
            if ($result->providerMessageId) {
                $this->info("   🏷️  Provider Message ID: {$result->providerMessageId}");
            }
            if (isset($result->metadata['cost'])) {
                $this->info("   💰 Cost: {$result->metadata['cost']}");
            }
        } else {
            $this->error('   ❌ SMS failed to send');
            $this->error("   Error: {$result->errorMessage}");
            return Command::FAILURE;
        }

        // Test 5: Channel Information
        $this->newLine();
        $this->info('5. SMS Channel Information:');
        $channelInfo = $channelManager->getChannelInfo()['sms'] ?? null;
        
        if ($channelInfo) {
            $this->table(['Property', 'Value'], [
                ['Type', $channelInfo['type']],
                ['Available', $channelInfo['available'] ? 'Yes' : 'No'],
                ['Max Message Length', $channelInfo['max_message_length']],
                ['Bulk Messaging', $channelInfo['capabilities']['bulk_messaging'] ?? 'Unknown'],
                ['Delivery Reports', $channelInfo['capabilities']['delivery_reports'] ?? 'Unknown'],
                ['Max Recipients (Bulk)', $channelInfo['capabilities']['max_recipients_per_bulk'] ?? 'Unknown'],
            ]);
        }

        $this->newLine();
        $this->info('🎉 SMS testing completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Test SMS with template
     */
    private function testWithTemplate(
        string $templateId,
        string $personId,
        string $recipient,
        CommunicationChannelManager $channelManager,
        EmailTemplateService $templateService
    ) {
        try {
            $template = CommunicationTemplate::findOrFail($templateId);
            $person = Person::where('person_id', $personId)->firstOrFail();

            $this->info("   Using template: {$template->name}");
            $this->info("   For person: {$person->full_name} ({$person->person_id})");

            // Create message from template
            $message = $templateService->createMessageFromTemplate(
                template: $template,
                person: $person,
                channel: 'sms',
                extraVariables: [
                    'current_datetime' => now()->format('F j, Y \a\t g:i A'),
                    'action_url' => url('/dashboard'),
                ]
            );

            // Send via channel manager
            return $channelManager->send($message);

        } catch (\Exception $e) {
            $this->error("   ❌ Template test failed: {$e->getMessage()}");
            
            // Fallback to direct SMS
            $fallbackMessage = "Test SMS from Ankole Profiler - Template test failed, using fallback message.";
            $smsService = app(AfricasTalkingSmsService::class);
            return $smsService->sendSms($recipient, $fallbackMessage);
        }
    }
}