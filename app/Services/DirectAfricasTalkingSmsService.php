<?php

namespace App\Services;

use App\Contracts\Communication\CommunicationResult;
use App\Contracts\Communication\CommunicationStatus;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery\CountValidator\Exception;

class DirectAfricasTalkingSmsService
{
    protected GuzzleClient $client;
    protected array $config;
    protected string $baseUrl;

    protected $sms;
    protected string $username;
    protected string $environment;
    protected string $apiKey;

    public function __construct()
    {
        $this->config = config('africastalking');

        $this->username = $this->config['username'];
        $this->apiKey = $this->config['api_key'];
        $this->environment = $this->config['environment'];

        // Validate required configuration
        if (empty($this->config['username'])) {
            throw new \InvalidArgumentException('AT_USERNAME is required in .env file');
        }

        if (empty($this->config['api_key'])) {
            throw new \InvalidArgumentException('AT_API_KEY is required in .env file');
        }

        // Set base URL based on environment
        if ($this->config['environment'] === 'production') {
            $this->baseUrl = 'https://api.africastalking.com/version1/';
        } else {
            $this->baseUrl = 'https://api.sandbox.africastalking.com/version1/';
        }

        // Initialize Guzzle client with proper configuration
        $clientConfig = [
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'headers' => [
                'apikey' => $this->config['api_key'],
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json'
            ]
        ];

        // TLS verification: use the configured CA bundle when present; the
        // disable flag is only honoured outside production.
        if (($this->config['disable_ssl_verification'] ?? false) && !app()->isProduction()) {
            Log::warning('SSL verification disabled for Africa\'s Talking API - non-production only!');
            $clientConfig['verify'] = false;
        } else {
            $caBundle = $this->config['ca_bundle'] ?? null;
            $clientConfig['verify'] = ($caBundle && is_file($caBundle)) ? $caBundle : true;
        }

        $this->client = new GuzzleClient($clientConfig);

        Log::info('Direct Africa\'s Talking SMS Service initialized', [
            'username' => $this->config['username'],
            'environment' => $this->config['environment'],
            'base_url' => $this->baseUrl,
        ]);
    }

    private function getErrorMessage(int $statusCode, string $status): string
    {
        $errorMessages = [
            400 => 'Invalid phone number',
            401 => 'Invalid sender ID',
            402 => 'Insufficient balance',
            403 => 'Invalid API key or unauthorized access',
            404 => 'Invalid endpoint or resource not found',
            405 => 'Method not allowed',
            406 => 'Invalid request format',
            407 => 'Missing required parameters',
            409 => 'Duplicate message',
            500 => 'Internal server error',
            501 => 'Gateway error',
            502 => 'Service temporarily unavailable'
        ];

        $baseMessage = $errorMessages[$statusCode] ?? "SMS delivery failed (Code: {$statusCode})";

        // Include the original status message if available and different
        if ($status && $status !== 'Unknown' && !str_contains($baseMessage, $status)) {
            $baseMessage .= " - {$status}";
        }

        return $baseMessage;
    }

    // Send a single SMS message
    public function sendSms(string $recipient, string $message, array $options = []): CommunicationResult
    {
        try {
            // Format the phone number properly
            $formattedRecipient = $this->formatPhoneNumber($recipient);

            Log::info('Attempting to send SMS via Africa\'s Talking', [
                'original_recipient' => $recipient,
                'formatted_recipient' => $formattedRecipient,
                'message_length' => strlen($message),
                'environment' => $this->environment,
                'username' => $this->username
            ]);

            // Validate phone number format
            if (!$this->validatePhoneNumber($formattedRecipient)) {
                throw new Exception("Invalid phone number format: {$formattedRecipient}");
            }

            // Send SMS through Africa's Talking API
            $response = $this->client->post('messaging', [
                'form_params' => [
                    'username' => $this->config['username'],
                    'to' => $formattedRecipient,
                    'message' => $message,
                    'deliveryReports' => 1,
                    'callbackUrl' => route('sms.delivery.webhook')
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $responseData = json_decode($body, true);

            Log::info('Africa\'s Talking API Response', [
                'response' => $responseData,
                'recipient' => $formattedRecipient
            ]);

            // Parse the response
            if ($statusCode === 201 && isset($responseData['SMSMessageData'])) {
                $recipientData = $responseData['SMSMessageData']['Recipients'][0];
                $statusCode = $recipientData['statusCode'] ?? 0;
                $status = $recipientData['status'] ?? 'Unknown';

                $status = $recipientData['status'] ?? 'Unknown';
                $messageId = $recipientData['messageId'] ?? null;
                $cost = $recipientData['cost'] ?? 'Unknown';

                Log::info('SMS Status Details', [
                    'recipient' => $formattedRecipient,
                    'status_code' => $statusCode,
                    'status' => $status,
                    'message_id' => $messageId,
                    'cost' => $cost
                ]);

                // Check status codes according to Africa's Talking documentation
                // 100 = Success, 101 = Queued, 102 = Sent
                // 400+ = Various failure codes
                if (in_array($statusCode, [100, 101, 102])) {
                    return CommunicationResult::success(
                        messageId: $messageId ?: Str::uuid()->toString(),
                        recipient: $formattedRecipient,
                        channel: 'sms',
                        metadata: [
                            'status_code' => $statusCode,
                            'status' => $status,
                            'cost' => $cost,
                            'provider' => 'africastalking',
                            'environment' => $this->environment
                        ]
                    );
                } else {
                    // Handle specific error codes
                    $errorMessage = $this->getErrorMessage($statusCode, $status);

                    return CommunicationResult::failure(
                        messageId: $messageId ?: Str::uuid()->toString(),
                        recipient: $formattedRecipient,
                        channel: 'sms',
                        errorMessage: $errorMessage,
                        metadata: [
                            'status_code' => $statusCode,
                            'status' => $status,
                            'provider' => 'africastalking',
                            'environment' => $this->environment
                        ]
                    );
                }
            } else {
                // Invalid response structure
                throw new Exception('Invalid response structure from Africa\'s Talking API');
            }
        } catch (Exception $e) {
            Log::error('SMS sending failed', [
                'recipient' => $recipient,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return CommunicationResult::failure(
                messageId: Str::uuid()->toString(),
                recipient: $recipient,
                channel: 'sms',
                errorMessage: 'SMS sending error: ' . $e->getMessage(),
                metadata: [
                    'provider' => 'africastalking',
                    'environment' => $this->environment,
                    'error_type' => 'exception'
                ]
            );
        }
    }


    // Send bulk SMS messages
    public function sendBulkSms(array $recipients, string $message, array $options = []): array
    {
        $results = [];

        foreach ($recipients as $recipient) {
            $results[] = $this->sendSms($recipient, $message, $options);
        }

        return $results;
    }

    /**
     * Get account balance
     */
    public function getAccountBalance(): array
    {
        try {
            $response = $this->client->get("user?username={$this->config['username']}", [
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if ($statusCode === 200 && isset($data['UserData'])) {
                return [
                    'success' => true,
                    'balance' => $data['UserData']['balance'],
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to retrieve balance: ' . $body,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // Validate phone number format
    public function validatePhoneNumber(string $phoneNumber): bool
    {
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^+\d]/', '', $phoneNumber);

        // Check if it starts with + and has at least 10 digits
        if (preg_match('/^\+\d{10,15}$/', $cleaned)) {
            return true;
        }

        return false;
    }

    // Format phone number to international format
    public function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any spaces, dashes, or other formatting
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);

        // If it doesn't start with +, add +
        if (!str_starts_with($cleaned, '+')) {
            // Assume Uganda if no country code and starts with 7 or 0
            if (str_starts_with($cleaned, '7') || str_starts_with($cleaned, '07')) {
                $cleaned = '+256' . ltrim($cleaned, '0');
            } elseif (str_starts_with($cleaned, '256')) {
                $cleaned = '+' . $cleaned;
            } else {
                $cleaned = '+' . $cleaned;
            }
        }

        return $cleaned;
    }


    // Extract message parts count from API response
    private function extractMessageParts(string $message): int
    {
        if (preg_match('/Message parts: (\d+)/', $message, $matches)) {
            return (int) $matches[1];
        }
        return 1;
    }

    /**
     * Test connection to Africa's Talking API
     */
    public function testConnection(): array
    {
        try {
            $balance = $this->getAccountBalance();

            if ($balance['success']) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'balance' => $balance['balance'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Connection failed: ' . ($balance['error'] ?? 'Unknown error'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get delivery status of a message
     */
    public function getDeliveryStatus(string $messageId): CommunicationStatus
    {
        // Note: Africa's Talking API doesn't provide a direct delivery status endpoint
        // This would typically require webhook implementation for real-time status updates
        // For now, return a pending status
        return new CommunicationStatus(
            messageId: $messageId,
            status: 'pending',
            timestamp: new \DateTime(),
            details: 'Delivery status not available via API - use webhooks for real-time updates'
        );
    }

    /**
     * Get configuration for debugging
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
                'base_url' => $this->baseUrl,
                'api_key_set' => !empty($this->config['api_key']),
                'api_key_length' => strlen($this->config['api_key'] ?? ''),
            ]
        ];
    }
}
