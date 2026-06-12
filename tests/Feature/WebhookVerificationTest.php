<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookVerificationTest extends TestCase
{
    use RefreshDatabase;

    private array $deliveryReportPayload = [
        'id' => 'ATXid_test123',
        'status' => 'Success',
        'phoneNumber' => '+256700000000',
    ];

    public function test_africastalking_webhook_rejected_without_token_when_configured()
    {
        config(['services.sms.providers.africas_talking.webhook_token' => 'secret-token']);

        $this->postJson(route('sms.delivery.webhook'), $this->deliveryReportPayload)
            ->assertForbidden();
    }

    public function test_africastalking_webhook_rejected_with_wrong_token()
    {
        config(['services.sms.providers.africas_talking.webhook_token' => 'secret-token']);

        $this->postJson(route('sms.delivery.webhook') . '?token=wrong', $this->deliveryReportPayload)
            ->assertForbidden();
    }

    public function test_africastalking_webhook_accepted_with_query_token()
    {
        config(['services.sms.providers.africas_talking.webhook_token' => 'secret-token']);

        $this->postJson(route('sms.delivery.webhook') . '?token=secret-token', $this->deliveryReportPayload)
            ->assertOk();

        $this->assertDatabaseHas('sms_delivery_reports', [
            'message_id' => 'ATXid_test123',
            'status' => 'Success',
        ]);
    }

    public function test_africastalking_webhook_accepted_with_header_token()
    {
        config(['services.sms.providers.africas_talking.webhook_token' => 'secret-token']);

        $this->postJson(route('sms.delivery.webhook'), $this->deliveryReportPayload, [
            'X-Webhook-Token' => 'secret-token',
        ])->assertOk();
    }

    public function test_africastalking_webhook_allowed_when_token_not_configured()
    {
        config(['services.sms.providers.africas_talking.webhook_token' => null]);

        $this->postJson(route('sms.delivery.webhook'), $this->deliveryReportPayload)
            ->assertOk();
    }

    public function test_twilio_webhook_rejected_without_signature_when_configured()
    {
        config(['services.sms.providers.twilio.auth_token' => 'twilio-auth-token']);

        $this->post(route('webhooks.communication.twilio'), [
            'MessageSid' => 'SM123',
            'MessageStatus' => 'delivered',
        ])->assertForbidden();
    }

    public function test_twilio_webhook_accepted_with_valid_signature()
    {
        config(['services.sms.providers.twilio.auth_token' => 'twilio-auth-token']);

        $params = [
            'MessageSid' => 'SM123',
            'MessageStatus' => 'delivered',
        ];

        $url = route('webhooks.communication.twilio');
        ksort($params);
        $data = $url;
        foreach ($params as $key => $value) {
            $data .= $key . $value;
        }
        $signature = base64_encode(hash_hmac('sha1', $data, 'twilio-auth-token', true));

        // Middleware passes; controller 404s because no such message exists.
        $this->post($url, $params, ['X-Twilio-Signature' => $signature])
            ->assertNotFound();
    }

    public function test_meta_whatsapp_get_handshake_bypasses_signature_check()
    {
        config([
            'services.whatsapp.providers.meta.app_secret' => 'meta-secret',
            'services.whatsapp.providers.meta.verify_token' => 'verify-me',
        ]);

        $this->get(route('webhooks.communication.meta-whatsapp', [
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'verify-me',
            'hub_challenge' => 'challenge-123',
        ]))->assertOk()->assertSee('challenge-123');
    }

    public function test_meta_whatsapp_post_rejected_without_signature_when_configured()
    {
        config(['services.whatsapp.providers.meta.app_secret' => 'meta-secret']);

        $this->postJson(route('webhooks.communication.meta-whatsapp'), ['entry' => []])
            ->assertForbidden();
    }
}
