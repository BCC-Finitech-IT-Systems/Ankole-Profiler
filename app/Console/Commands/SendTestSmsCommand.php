<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DirectAfricasTalkingSmsService;

class SendTestSmsCommand extends Command
{
    protected $signature = 'sms:send {phone} {message?}';
    protected $description = 'Send a test SMS to a specific phone number';

    public function handle()
    {
        if (app()->environment("production")) {
            $this->error("This SMS test command is disabled in production.");
            return 1;
        }

        $phone = $this->argument('phone');
        $message = $this->argument('message') ?: 'Hello! This is a test message from your Profiler App sent on ' . now()->format('M j, Y \a\t g:i A');
        
        $this->info('📱 Sending Test SMS');
        $this->line('===================');
        $this->line("To: {$phone}");
        $this->line("Message: {$message}");
        $this->line('');
        
        try {
            $smsService = new DirectAfricasTalkingSmsService();
            
            // Format the phone number properly
            $formattedPhone = $this->formatPhoneNumber($phone);
            $this->line("Formatted number: {$formattedPhone}");
            
            // Send the SMS
            $result = $smsService->sendSms($formattedPhone, $message);
            
            if ($result->isSuccessful()) {
                $this->info('✅ SMS sent successfully!');
                $this->line('Message ID: ' . $result->messageId);
                $this->line('Provider Message ID: ' . ($result->providerMessageId ?? 'N/A'));
                
                if ($result->metadata) {
                    $this->line('Cost: ' . ($result->metadata['cost'] ?? 'N/A'));
                    $this->line('Message Parts: ' . ($result->metadata['message_parts'] ?? 'N/A'));
                }
                
                $this->line('');
                $this->info('🎉 Check your phone for the message!');
                
            } else {
                $this->error('❌ SMS sending failed');
                $this->line('Error: ' . $result->errorMessage);
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to send SMS');
            $this->line('Error: ' . $e->getMessage());
        }
        
        return 0;
    }
    
    private function formatPhoneNumber(string $phone): string
    {
        // Remove any spaces, hyphens, parentheses
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        
        // If it starts with 256 (Uganda country code), add +
        if (preg_match('/^256/', $cleaned)) {
            return '+' . $cleaned;
        }
        
        // If it starts with 0, replace with +256
        if (preg_match('/^0/', $cleaned)) {
            return '+256' . substr($cleaned, 1);
        }
        
        // If it doesn't start with +, assume it's a Uganda number and add +256
        if (!preg_match('/^\+/', $cleaned)) {
            return '+256' . $cleaned;
        }
        
        return $cleaned;
    }
}