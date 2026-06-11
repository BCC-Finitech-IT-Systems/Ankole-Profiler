<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use App\Contracts\Communication\CommunicationResult;
use App\Contracts\Communication\CommunicationStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use GuzzleHttp\Client as GuzzleClient;

class AfricasTalkingSmsService
{
    protected AfricasTalking $gateway;
    protected array $config;

    public function __construct()
    {
        $this->config = config('africastalking');

        // Validate required configuration
        if (empty($this->config['username'])) {
            throw new \InvalidArgumentException('AT_USERNAME is required in .env file');
        }

        if (empty($this->config['api_key'])) {
            throw new \InvalidArgumentException('AT_API_KEY is required in .env file');
        }

        // Log initialization for debugging
        Log::info('Initializing Africa\'s Talking SMS Service', [
            'username' => $this->config['username'],
            'environment' => $this->config['environment'],
            'has_api_key' => !empty($this->config['api_key']),
            'api_key_length' => strlen($this->config['api_key']),
        ]);

        // Initialize Africa's Talking SDK
        try {
            // Create a custom gateway with SSL configuration
            $this->gateway = $this->createGatewayWithSSLConfig();

            Log::info('Africa\'s Talking SDK initialized successfully');

        } catch (\Exception $e) {
            Log::error('Failed to initialize Africa\'s Talking SDK', [
                'error' => $e->getMessage(),
                'username' => $this->config['username'],
                'environment' => $this->config['environment'],
            ]);
            throw $e;
        }

    }

    /**
     * Get the correct base URL for SMS API calls
     */
    protected function getSmsBaseUrl(string $type = 'premium'): string
    {
        $isProduction = $this->config['environment'] === 'production';
        if ($type === 'bulk') {
            if ($isProduction) {
                return 'https://api.africastalking.com/version1/messaging/bulk';
            } else {
                // Sandbox bulk endpoint (coming soon)
                return 'https://api.sandbox.africastalking.com/version1/messaging/bulk';
            }
        } else {
            // Premium SMS
            if ($isProduction) {
                return 'https://content.africastalking.com/version1/messaging';
            } else {
                return 'https://api.sandbox.africastalking.com/version1/messaging';
            }
        }
    }

    /**
     * Create AfricasTalking gateway with custom SSL configuration
     */
    private function createGatewayWithSSLConfig(): AfricasTalking
    {
        // Create gateway instance
        $gateway = new AfricasTalking(
            $this->config['username'],
            $this->config['api_key']
        );

        // The SDK has no setEnvironment(); it selects the sandbox endpoints
        // when the username is "sandbox" and production otherwise.

        $this->configureGatewayTls($gateway);

        return $gateway;
    }

    /**
     * Resolve the Guzzle 'verify' option: the configured CA bundle when it
     * exists, otherwise the system default. Verification can only be turned
     * off outside production.
     */
    protected function resolveTlsVerify(): bool|string
    {
        if (($this->config['disable_ssl_verification'] ?? false) && !app()->isProduction()) {
            Log::warning('SSL verification disabled for Africa\'s Talking API - non-production only!');

            return false;
        }

        $caBundle = $this->config['ca_bundle'] ?? null;

        return ($caBundle && is_file($caBundle)) ? $caBundle : true;
    }

    /**
     * Apply the resolved TLS settings to the SDK's internal Guzzle clients
     */
    private function configureGatewayTls(AfricasTalking $gateway): void
    {
        $verify = $this->resolveTlsVerify();

        if ($verify === true) {
            return; // SDK default already verifies against the system bundle
        }

        try {
            // Use reflection to access and modify the private client properties
            $reflection = new \ReflectionClass($gateway);

            // Get all client properties
            $clientProperties = [
                'client',
                'contentClient',
                'voiceClient',
                'tokenClient',
                'mobileDataClient'
            ];

            foreach ($clientProperties as $property) {
                if ($reflection->hasProperty($property)) {
                    $prop = $reflection->getProperty($property);
                    $prop->setAccessible(true);
                    $client = $prop->getValue($gateway);

                    if ($client instanceof GuzzleClient) {
                        $config = $client->getConfig();
                        $config['verify'] = $verify;

                        $newClient = new GuzzleClient($config);
                        $prop->setValue($gateway, $newClient);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not configure SSL settings for Africa\'s Talking clients', [
                'error' => $e->getMessage()
            ]);
            // Don't throw - let it continue with default configuration
        }
    }

    /**
     * Validate configuration and provide detailed diagnostics
     */
    public function validateConfiguration(): array
    {
        $issues = [];
        $warnings = [];

        // Check username
        if (empty($this->config['username'])) {
            $issues[] = 'AT_USERNAME is not set in .env file';
        } elseif ($this->config['username'] === 'sandbox') {
            // This is correct for sandbox
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $this->config['username'])) {
            $warnings[] = 'Username format might be incorrect: ' . $this->config['username'];
        }

        // Check API key
        if (empty($this->config['api_key'])) {
            $issues[] = 'AT_API_KEY is not set in .env file';
        } elseif (strlen($this->config['api_key']) < 10) {
            $warnings[] = 'API key seems too short: ' . strlen($this->config['api_key']) . ' characters';
        }

        // Check environment
        if (!in_array($this->config['environment'], ['sandbox', 'production'])) {
            $warnings[] = 'Invalid environment: ' . $this->config['environment'];
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'warnings' => $warnings,
            'config' => [
                'username' => $this->config['username'],
                'environment' => $this->config['environment'],
                'api_key_set' => !empty($this->config['api_key']),
                'api_key_length' => strlen($this->config['api_key'] ?? ''),
            ]
        ];
    }

    /**
     * Get raw configuration for debugging
     */
    public function getRawConfig(): array
    {
        return [
            'config_file' => $this->config,
            'env_values' => [
                'AT_USERNAME' => env('AT_USERNAME'),
                'AT_API_KEY' => env('AT_API_KEY') ? 'SET (length: ' . strlen(env('AT_API_KEY')) . ')' : 'NOT SET',
                'AT_ENVIRONMENT' => env('AT_ENVIRONMENT'),
            ]
        ];
    }

    /**
     * Send a single SMS message
     */
   public function sendSms(string $recipient, string $message, array $options = []): CommunicationResult
{
    $messageId = $this->generateMessageId();

    try {
        // Validate and format phone number
        $formattedRecipient = $this->formatPhoneNumber($recipient);

        if (!$formattedRecipient) {
            return CommunicationResult::failure(
                messageId: $messageId,
                recipient: $recipient,
                channel: 'sms',
                errorMessage: 'Invalid phone number format'
            );
        }

        // Validate message length
        if (strlen($message) > $this->config['sms']['max_length']) {
            return CommunicationResult::failure(
                messageId: $messageId,
                recipient: $recipient,
                channel: 'sms',
                errorMessage: 'Message exceeds maximum length of ' . $this->config['sms']['max_length'] . ' characters'
            );
        }

        // Prepare SMS data
        $smsData = [
            'to' => $formattedRecipient,
            'message' => $message,
            'from' => $options['sender_id'] ?? $this->config['sms']['sender_id'],
        ];

        // Add delivery reports if enabled
        if ($this->config['sms']['delivery_reports']) {
            $smsData['enqueue'] = 1;  // Changed from 'deliveryReports'
        }

        $baseUrl = $this->getSmsBaseUrl('premium');
        $smsData['base_url'] = $baseUrl; // For debugging/logging
        $this->logRequest('send_sms', $smsData);

        // Send SMS via Africa's Talking (SDK uses its own endpoint, but we log the correct one)
        $sms = $this->gateway->sms();
        $response = $sms->send($smsData);

        $this->logResponse('send_sms', $response);

        // FIXED: Correct response structure for Africa's Talking SDK
        if (isset($response['SMSMessageData']['Recipients'][0])) {
            $recipient_data = $response['SMSMessageData']['Recipients'][0];

            if (isset($recipient_data['status']) &&
                in_array($recipient_data['status'], ['Success', 'Sent'])) {

                return CommunicationResult::success(
                    messageId: $messageId,
                    recipient: $formattedRecipient,
                    channel: 'sms',
                    providerMessageId: $recipient_data['messageId'] ?? null,
                    metadata: [
                        'provider_response' => $response,
                        'cost' => $recipient_data['cost'] ?? null,
                        'status_code' => $recipient_data['statusCode'] ?? null,
                    ]
                );
            } else {
                return CommunicationResult::failure(
                    messageId: $messageId,
                    recipient: $formattedRecipient,
                    channel: 'sms',
                    errorMessage: $recipient_data['status'] ?? 'Unknown error from provider',
                    metadata: ['provider_response' => $response]
                );
            }
        } else {
            return CommunicationResult::failure(
                messageId: $messageId,
                recipient: $formattedRecipient,
                channel: 'sms',
                errorMessage: 'Invalid response format from provider',
                metadata: ['provider_response' => $response]
            );
        }

    } catch (\Exception $e) {
        $this->logError('send_sms', $e, ['recipient' => $recipient, 'message' => $message]);

        return CommunicationResult::failure(
            messageId: $messageId,
            recipient: $recipient,
            channel: 'sms',
            errorMessage: 'Failed to send SMS: ' . $e->getMessage(),
            metadata: ['exception' => $e->getMessage()]
        );
    }
}

    /**
     * Send bulk SMS messages
     */
    public function sendBulkSms(array $recipients, string $message, array $options = []): array
    {
        $results = [];

        // Validate recipients count
        if (count($recipients) > $this->config['sms']['max_recipients']) {
            // Split into chunks
            $chunks = array_chunk($recipients, $this->config['sms']['max_recipients']);

            foreach ($chunks as $chunk) {
                $chunkResults = $this->processBulkSmsChunk($chunk, $message, $options);
                $results = array_merge($results, $chunkResults);
            }
        } else {
            $results = $this->processBulkSmsChunk($recipients, $message, $options);
        }

        return $results;
    }

    /**
     * Process a chunk of bulk SMS recipients
     */
    protected function processBulkSmsChunk(array $recipients, string $message, array $options): array
    {
        $results = [];
        $messageId = $this->generateMessageId();

        try {
            // Format all phone numbers
            $formattedRecipients = [];
            foreach ($recipients as $recipient) {
                $formatted = $this->formatPhoneNumber($recipient);
                if ($formatted) {
                    $formattedRecipients[] = $formatted;
                } else {
                    $results[] = CommunicationResult::failure(
                        messageId: $this->generateMessageId(),
                        recipient: $recipient,
                        channel: 'sms',
                        errorMessage: 'Invalid phone number format'
                    );
                }
            }

            if (empty($formattedRecipients)) {
                return $results;
            }

            // Prepare bulk SMS data
            $smsData = [
                'to' => implode(',', $formattedRecipients),
                'message' => $message,
                'from' => $options['sender_id'] ?? $this->config['sms']['sender_id'],
                'bulkSMSMode' => $this->config['sms']['bulk_sms_mode'] ? 1 : 0,
            ];

            if ($this->config['sms']['delivery_reports']) {
                $smsData['deliveryReports'] = 1;
            }

            $baseUrl = $this->getSmsBaseUrl('bulk');
            $smsData['base_url'] = $baseUrl; // For debugging/logging
            $this->logRequest('send_bulk_sms', $smsData);

            // Send bulk SMS via SDK (SDK uses its own endpoint, but we log the correct one)
            $sms = $this->gateway->sms();
            $response = $sms->send($smsData);

            $this->logResponse('send_bulk_sms', $response);

            // Parse bulk response
            if (isset($response['SMSMessageData']['Recipients'])) {
                foreach ($response['SMSMessageData']['Recipients'] as $recipientData) {
                    $recipient = $recipientData['number'] ?? 'unknown';

                    if (isset($recipientData['status']) &&
                        in_array($recipientData['status'], ['Success', 'Sent'])) {

                        $results[] = CommunicationResult::success(
                            messageId: $this->generateMessageId(),
                            recipient: $recipient,
                            channel: 'sms',
                            providerMessageId: $recipientData['messageId'] ?? null,
                            metadata: [
                                'provider_response' => $recipientData,
                                'cost' => $recipientData['cost'] ?? null,
                                'status_code' => $recipientData['statusCode'] ?? null,
                            ]
                        );
                    } else {
                        $results[] = CommunicationResult::failure(
                            messageId: $this->generateMessageId(),
                            recipient: $recipient,
                            channel: 'sms',
                            errorMessage: $recipientData['status'] ?? 'Unknown error from provider',
                            metadata: ['provider_response' => $recipientData]
                        );
                    }
                }
            }

        } catch (\Exception $e) {
            $this->logError('send_bulk_sms', $e, ['recipients' => $recipients, 'message' => $message]);

            // Create failure results for all recipients
            foreach ($recipients as $recipient) {
                $results[] = CommunicationResult::failure(
                    messageId: $this->generateMessageId(),
                    recipient: $recipient,
                    channel: 'sms',
                    errorMessage: 'Bulk SMS failed: ' . $e->getMessage(),
                    metadata: ['exception' => $e->getMessage()]
                );
            }
        }

        return $results;
    }

    /**
     * Get delivery status of a message
     */
    public function getDeliveryStatus(string $messageId): CommunicationStatus
    {
        try {
            // Note: Africa's Talking doesn't have a direct delivery status API
            // Status is typically received via delivery reports (webhooks)
            // For now, we'll return a pending status
            return CommunicationStatus::PENDING;

        } catch (\Exception $e) {
            $this->logError('get_delivery_status', $e, ['message_id' => $messageId]);
            return CommunicationStatus::FAILED;
        }
    }

    /**
     * Validate phone number format
     */
    public function validatePhoneNumber(string $phoneNumber): bool
    {
        $patterns = $this->config['validation']['phone_patterns'];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $phoneNumber)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format phone number to international format
     */
    public function formatPhoneNumber(string $phoneNumber): ?string
    {
        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);

        if (empty($cleaned)) {
            return null;
        }

        // If starts with +, assume it's already international
        if (str_starts_with($cleaned, '+')) {
            return $this->validatePhoneNumber($cleaned) ? $cleaned : null;
        }

        // If starts with 0, replace with default country code
        if (str_starts_with($cleaned, '0')) {
            $formatted = $this->config['validation']['default_country_code'] . substr($cleaned, 1);
            return $this->validatePhoneNumber($formatted) ? $formatted : null;
        }

        // If doesn't start with + or 0, add default country code
        $formatted = $this->config['validation']['default_country_code'] . $cleaned;
        return $this->validatePhoneNumber($formatted) ? $formatted : null;
    }

    /**
     * Check account balance
     */
   public function getAccountBalance(): array
{
    try {
        $account = $this->gateway->application();
        $response = $account->fetchApplicationData();

        // FIXED: Correct response structure
        return [
            'success' => true,
            'balance' => $response['UserData']['balance'] ?? 'Unknown',
            'currency' => $response['UserData']['currency'] ?? 'USD',
        ];
    } catch (\Exception $e) {
        $this->logError('get_account_balance', $e);

        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

    /**
     * Generate unique message ID
     */
    protected function generateMessageId(): string
    {
        return 'SMS_' . strtoupper(Str::random(8)) . '_' . time();
    }

    /**
     * Log API request
     */
    protected function logRequest(string $operation, array $data): void
    {
        if ($this->config['logging']['enabled'] && $this->config['logging']['log_requests']) {
            Log::info("AfricasTalking SMS Request: {$operation}", [
                'operation' => $operation,
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    /**
     * Log API response
     */
    protected function logResponse(string $operation, array $response): void
    {
        if ($this->config['logging']['enabled'] && $this->config['logging']['log_responses']) {
            Log::info("AfricasTalking SMS Response: {$operation}", [
                'operation' => $operation,
                'response' => $response,
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    /**
     * Test connection and configuration
     */
    public function testConnection(): array
    {
        try {
            Log::info('Testing Africa\'s Talking connection', [
                'username' => $this->config['username'],
                'environment' => $this->config['environment'],
                'has_api_key' => !empty($this->config['api_key']),
            ]);

            $account = $this->gateway->application();
            $response = $account->fetchApplicationData();

            return [
                'success' => true,
                'config' => [
                    'username' => $this->config['username'],
                    'environment' => $this->config['environment'],
                    'has_api_key' => !empty($this->config['api_key']),
                    'api_key_length' => strlen($this->config['api_key'] ?? ''),
                    'ssl_verification_disabled' => $this->config['disable_ssl_verification'] ?? false,
                    'app_environment' => app()->environment(),
                ],
                'response' => $response,
            ];
        } catch (\Exception $e) {
            $this->logError('test_connection', $e);

            $error = $e->getMessage();
            $suggestions = [];

            // Provide helpful suggestions based on error type
            if (str_contains($error, 'SSL') || str_contains($error, 'certificate') || str_contains($error, 'cURL error 60')) {
                $suggestions[] = 'SSL certificate issue detected. Try setting AT_DISABLE_SSL_VERIFICATION=true in .env for development.';
            }
            if (str_contains($error, 'Unauthorized') || str_contains($error, '401')) {
                $suggestions[] = 'Check your AT_API_KEY and AT_USERNAME in .env file.';
            }
            if (str_contains($error, 'Connection refused') || str_contains($error, 'timeout')) {
                $suggestions[] = 'Check your internet connection and firewall settings.';
            }

            return [
                'success' => false,
                'config' => [
                    'username' => $this->config['username'],
                    'environment' => $this->config['environment'],
                    'has_api_key' => !empty($this->config['api_key']),
                    'ssl_verification_disabled' => $this->config['disable_ssl_verification'] ?? false,
                    'app_environment' => app()->environment(),
                ],
                'error' => $error,
                'suggestions' => $suggestions,
                'trace' => $e->getTraceAsString(),
            ];
        }
    }

    /**
     * Get comprehensive debug information
     */
    public function getDebugInfo(): array
    {
        return [
            'config' => [
                'username' => $this->config['username'],
                'environment' => $this->config['environment'],
                'has_api_key' => !empty($this->config['api_key']),
                'api_key_preview' => $this->config['api_key'] ?
                    substr($this->config['api_key'], 0, 6) . '...' : 'NOT SET',
                'sender_id' => $this->config['sms']['sender_id'],
                'max_length' => $this->config['sms']['max_length'],
                'logging_enabled' => $this->config['logging']['enabled'],
                'ssl_verification_disabled' => $this->config['disable_ssl_verification'] ?? false,
                'app_environment' => app()->environment(),
            ],
            'validation' => [
                'default_country_code' => $this->config['validation']['default_country_code'],
                'supported_patterns' => array_keys($this->config['validation']['phone_patterns']),
            ],
            'sdk_info' => [
                'gateway_class' => get_class($this->gateway),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
        ];
    }

    /**
     * Log errors
     */
    protected function logError(string $operation, \Exception $exception, array $context = []): void
    {
        if ($this->config['logging']['enabled']) {
            Log::error("AfricasTalking SMS Error: {$operation}", [
                'operation' => $operation,
                'error' => $exception->getMessage(),
                'context' => $context,
                'trace' => $exception->getTraceAsString(),
                'timestamp' => now()->toISOString(),
            ]);
        }
    }
}
