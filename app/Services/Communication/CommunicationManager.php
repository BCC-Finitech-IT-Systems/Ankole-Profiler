<?php

namespace App\Services\Communication;

use App\Contracts\Communication\CommunicationChannelInterface;
use App\Contracts\Communication\CommunicationResult;
use App\Contracts\Communication\CommunicationStatus;
use App\Contracts\Communication\CommunicationMessage as CommunicationMessageDTO;
use App\Models\CommunicationMessage;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class CommunicationManager
{
    private array $channels = [];

    public function __construct(
        EmailCommunicationService $emailService,
        SMSCommunicationService $smsService,
        WhatsAppCommunicationService $whatsappService
    ) {
        $this->channels = [
            'email' => $emailService,
            'sms' => $smsService,
            'whatsapp' => $whatsappService,
        ];
    }

    /**
     * Send a single message
     */
    public function send(CommunicationMessageDTO $message): CommunicationResult
    {
        try {
            $channel = $this->getChannel($message->channel);

            if (!$channel->isAvailable()) {
                throw new Exception("Channel {$message->channel} is not available or properly configured");
            }

            // Create database record
            $dbMessage = $this->createDatabaseMessage($message);

            // Send the message
            $result = $channel->send(
                $message->recipient,
                $message->content,
                $this->buildChannelOptions($message)
            );

            // Update database record with result
            $this->updateDatabaseMessage($dbMessage, $result);

            return $result;

        } catch (Exception $e) {
            Log::error('Communication send failed', [
                'channel' => $message->channel,
                'recipient' => $message->recipient,
                'error' => $e->getMessage(),
            ]);

            return CommunicationResult::failure(
                messageId: 'failed_' . time() . '_' . Str::random(8),
                recipient: $message->recipient,
                channel: $message->channel,
                errorMessage: $e->getMessage()
            );
        }
    }

    /**
     * Send bulk messages to multiple recipients
     */
    public function sendBulk(string $channel, array $recipients, string $content, array $options = []): Collection
    {
        $channelService = $this->getChannel($channel);
        $bulkMessageId = 'bulk_' . time() . '_' . Str::random(10);

        // Create database records for all messages
        $dbMessages = collect();
        foreach ($recipients as $recipient) {
            $message = CommunicationMessageDTO::create($channel, $recipient, $content, $options);
            $dbMessage = $this->createDatabaseMessage($message, $bulkMessageId);
            $dbMessages->push($dbMessage);
        }

        try {
            // Send bulk messages
            $results = $channelService->sendBulk($recipients, $content, $options);

            // Update database records with results
            foreach ($results as $index => $result) {
                if (isset($dbMessages[$index])) {
                    $this->updateDatabaseMessage($dbMessages[$index], $result);
                }
            }

            return $results;

        } catch (Exception $e) {
            // Mark all messages as failed
            foreach ($dbMessages as $dbMessage) {
                $dbMessage->updateStatus(CommunicationStatus::FAILED, $e->getMessage());
            }

            Log::error('Bulk communication send failed', [
                'channel' => $channel,
                'recipients_count' => count($recipients),
                'bulk_message_id' => $bulkMessageId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send personalized messages to a collection of persons
     */
    public function sendPersonalized(string $channel, Collection $persons, string $template, array $options = []): Collection
    {
        $channelService = $this->getChannel($channel);
        $bulkMessageId = 'personalized_' . time() . '_' . Str::random(10);

        try {
            // Create database records for personalized messages
            $dbMessages = collect();
            foreach ($persons as $person) {
                $recipient = $this->getPersonContactInfo($person, $channel);
                if ($recipient) {
                    $message = CommunicationMessageDTO::withTemplate(
                        $recipient,
                        $channel,
                        $template,
                        $this->getPersonTemplateData($person),
                        $options
                    );
                    $dbMessage = $this->createDatabaseMessage($message, $bulkMessageId, $person->id);
                    $dbMessages->push($dbMessage);
                }
            }

            // Send personalized messages
            $results = $channelService->sendPersonalized($persons, $template, $options);

            // Update database records
            foreach ($results as $index => $result) {
                if (isset($dbMessages[$index])) {
                    $this->updateDatabaseMessage($dbMessages[$index], $result);
                }
            }

            return $results;

        } catch (Exception $e) {
            Log::error('Personalized communication send failed', [
                'channel' => $channel,
                'persons_count' => $persons->count(),
                'bulk_message_id' => $bulkMessageId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Schedule a message for later delivery
     */
    public function schedule(CommunicationMessageDTO $message, string $scheduledAt): CommunicationMessage
    {
        $dbMessage = $this->createDatabaseMessage($message->scheduleAt($scheduledAt));
        return $dbMessage;
    }

    /**
     * Process scheduled messages that are ready to send
     */
    public function processScheduledMessages(): int
    {
        $readyMessages = CommunicationMessage::readyToSend()->get();
        $processed = 0;

        foreach ($readyMessages as $dbMessage) {
            try {
                $message = $this->createDTOFromDatabaseMessage($dbMessage);
                $result = $this->send($message);

                if ($result->isSuccessful()) {
                    $processed++;
                }
            } catch (Exception $e) {
                Log::error('Failed to process scheduled message', [
                    'message_id' => $dbMessage->message_id,
                    'error' => $e->getMessage(),
                ]);

                $dbMessage->updateStatus(CommunicationStatus::FAILED, $e->getMessage());
            }
        }

        return $processed;
    }

    /**
     * Get delivery status for a message
     */
    public function getDeliveryStatus(string $messageId): ?CommunicationStatus
    {
        $dbMessage = CommunicationMessage::where('message_id', $messageId)->first();

        if (!$dbMessage) {
            return null;
        }

        // Try to get updated status from provider if not in final state
        if (!$dbMessage->isComplete() && $dbMessage->provider_message_id) {
            try {
                $channel = $this->getChannel($dbMessage->channel);
                $providerStatus = $channel->getDeliveryStatus($dbMessage->provider_message_id);

                if ($providerStatus !== $dbMessage->getStatusEnum()) {
                    $dbMessage->updateStatus($providerStatus);
                }
            } catch (Exception $e) {
                Log::warning('Failed to update delivery status from provider', [
                    'message_id' => $messageId,
                    'provider_message_id' => $dbMessage->provider_message_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $dbMessage->getStatusEnum();
    }

    /**
     * Get available communication channels
     */
    public function getAvailableChannels(): array
    {
        $available = [];

        foreach ($this->channels as $name => $channel) {
            if ($channel->isAvailable()) {
                $available[$name] = [
                    'name' => $name,
                    'display_name' => ucfirst($name),
                    'max_length' => $channel->getMaxMessageLength(),
                    'supported_types' => $channel->getSupportedMessageTypes(),
                ];
            }
        }

        return $available;
    }

    /**
     * Check if a specific channel is available and configured
     */
    public function isChannelAvailable(string $channel): bool
    {
        try {
            $channelService = $this->getChannel($channel);
            return $channelService->isAvailable();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get channel configuration requirements
     */
    public function getChannelRequirements(string $channel): array
    {
        return $this->getChannel($channel)->getConfigurationRequirements();
    }

    /**
     * Get bulk message statistics
     */
    public function getBulkMessageStats(string $bulkMessageId): array
    {
        return CommunicationMessage::getBulkDeliveryRate($bulkMessageId);
    }

    /**
     * Get communication history for a person
     */
    public function getPersonHistory(int $personId, ?string $channel = null): Collection
    {
        $query = CommunicationMessage::where('recipient_person_id', $personId)
                                   ->orderBy('created_at', 'desc');

        if ($channel) {
            $query->where('channel', $channel);
        }

        return $query->get();
    }

    /**
     * Get Organization communication statistics
     */
    public function getOrganizationStats(int $OrganizationId, ?string $period = 'month'): array
    {
        $query = CommunicationMessage::where('organization_id', $OrganizationId);

        // Apply time filter
        match ($period) {
            'day' => $query->where('created_at', '>=', now()->startOfDay()),
            'week' => $query->where('created_at', '>=', now()->startOfWeek()),
            'month' => $query->where('created_at', '>=', now()->startOfMonth()),
            'year' => $query->where('created_at', '>=', now()->startOfYear()),
            default => null,
        };

        $messages = $query->get();

        return [
            'total' => $messages->count(),
            'by_channel' => $messages->groupBy('channel')->map->count(),
            'by_status' => $messages->groupBy('status')->map->count(),
            'success_rate' => $messages->count() > 0
                ? round($messages->whereIn('status', ['sent', 'delivered'])->count() / $messages->count() * 100, 2)
                : 0,
        ];
    }

    /**
     * Get the appropriate channel service
     */
    private function getChannel(string $channel): CommunicationChannelInterface
    {
        if (!isset($this->channels[$channel])) {
            throw new Exception("Unsupported communication channel: {$channel}");
        }

        return $this->channels[$channel];
    }

    /**
     * Create database message record
     */
    private function createDatabaseMessage(
        CommunicationMessageDTO $message,
        ?string $bulkMessageId = null,
        ?int $recipientPersonId = null
    ): CommunicationMessage {
        return CommunicationMessage::create([
            'message_id' => 'msg_' . time() . '_' . Str::random(10),
            'sent_by_user_id' => Auth::id(),
            'organization_id' => user_current_organization()?->id ?? 1,
            'recipient_person_id' => $recipientPersonId,
            'recipient_identifier' => $message->recipient,
            'recipient_type' => $message->channel,
            'channel' => $message->channel,
            'subject' => $message->subject,
            'content' => $message->content,
            'message_type' => $message->options['type'] ?? 'text',
            'template_data' => $message->templateData,
            'attachments' => $message->attachments,
            'status' => 'pending',
            'scheduled_at' => $message->scheduledAt,
            'metadata' => $message->metadata,
            'priority' => $message->priority ?? 5,
            'is_bulk_message' => !is_null($bulkMessageId),
            'bulk_message_id' => $bulkMessageId,
        ]);
    }

    /**
     * Update database message with result
     */
    private function updateDatabaseMessage(CommunicationMessage $dbMessage, CommunicationResult $result): void
    {
        $status = $result->isSuccessful() ? CommunicationStatus::SENT : CommunicationStatus::FAILED;

        $dbMessage->update([
            'provider_message_id' => $result->providerMessageId,
            'status' => $status->value,
            'error_message' => $result->errorMessage,
            'sent_at' => $result->sentAt,
            'provider' => $result->metadata['provider'] ?? null,
            'provider_response' => $result->metadata,
        ]);
    }

    /**
     * Build channel-specific options from message DTO
     */
    private function buildChannelOptions(CommunicationMessageDTO $message): array
    {
        $options = $message->options;

        if ($message->subject) {
            $options['subject'] = $message->subject;
        }

        if ($message->attachments) {
            $options['attachments'] = $message->attachments;
        }

        return $options;
    }

    /**
     * Get contact information for a person based on channel
     */
    private function getPersonContactInfo(Person $person, string $channel): ?string
    {
        return match ($channel) {
            'email' => $person->emailAddresses->where('is_primary', true)->first()?->email,
            'sms', 'whatsapp' => $person->phones->where('is_primary', true)->first()?->number,
            default => null,
        };
    }

    /**
     * Get template data for a person
     */
    private function getPersonTemplateData(Person $person): array
    {
        return [
            'first_name' => $person->given_name,
            'last_name' => $person->family_name,
            'full_name' => $person->full_name,
            'email' => $person->emailAddresses->where('is_primary', true)->first()?->email ?? '',
            'phone' => $person->phones->where('is_primary', true)->first()?->number ?? '',
            'organization' => $person->currentAffiliation?->Organization?->display_name ?? '',
        ];
    }

    /**
     * Create DTO from database message
     */
    private function createDTOFromDatabaseMessage(CommunicationMessage $dbMessage): CommunicationMessageDTO
    {
        return new CommunicationMessageDTO(
            recipient: $dbMessage->recipient_identifier,
            content: $dbMessage->content,
            channel: $dbMessage->channel,
            options: [],
            subject: $dbMessage->subject,
            attachments: $dbMessage->attachments,
            template: null,
            templateData: $dbMessage->template_data,
            scheduledAt: $dbMessage->scheduled_at?->toISOString(),
            priority: $dbMessage->priority,
            metadata: $dbMessage->metadata
        );
    }
}
