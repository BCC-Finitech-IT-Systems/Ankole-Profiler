<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Contracts\Communication\CommunicationStatus;
use App\Models\CommunicationMessage;
use App\Models\SMSDeliveryReport;

class SMSWebhookController extends Controller
{
    public function handleDeliveryReport(Request $request)
    {
        try {
            // Log the incoming webhook data
            Log::info('SMS Delivery Report Webhook Received', [
                'payload' => $request->all(),
            ]);

            // Validate required fields
            $messageId = $request->input('id');
            $status = $request->input('status');
            $phoneNumber = $request->input('phoneNumber');

            if (!$messageId || !$status || !$phoneNumber) {
                // Africa's Talking posts other callback shapes (e.g. incoming
                // SMS) to the same URL in some configurations — acknowledge
                // them so the provider does not keep retrying.
                Log::info('SMS webhook payload is not a delivery report - ignoring', [
                    'payload' => $request->all()
                ]);
                return response()->json(['status' => 'ignored']);
            }

            // Store delivery report
            SMSDeliveryReport::updateOrCreate(
                ['message_id' => $messageId],
                [
                    'phone_number' => $phoneNumber,
                    'status' => $status,
                    'network_code' => $request->input('networkCode'),
                    'failure_reason' => $request->input('failureReason'),
                    'retry_count' => $request->input('retryCount', 0),
                    'delivered_at' => now(),
                    'webhook_payload' => $request->all()
                ]
            );

            // Update communication history
            $this->updateCommunicationHistory($messageId, $status, $request->input('failureReason'));

            Log::info('SMS Delivery Report Processed', [
                'message_id' => $messageId,
                'status' => $status,
                'phone_number' => $phoneNumber
            ]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Error processing SMS delivery report', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    private function updateCommunicationHistory($messageId, $status, ?string $failureReason): void
    {
        try {
            $message = CommunicationMessage::where('provider_message_id', $messageId)->first();

            if (!$message) {
                Log::info('No communication message found for delivery report', [
                    'message_id' => $messageId,
                ]);
                return;
            }

            $communicationStatus = $this->mapAfricasTalkingStatus($status);

            if ($communicationStatus) {
                $message->updateStatus($communicationStatus, $failureReason);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update communication history', [
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function mapAfricasTalkingStatus(string $status): ?CommunicationStatus
    {
        return match ($status) {
            'Buffered', 'Sent' => CommunicationStatus::SENT,
            'Success' => CommunicationStatus::DELIVERED,
            'Failed', 'Rejected' => CommunicationStatus::FAILED,
            default => null,
        };
    }
}
