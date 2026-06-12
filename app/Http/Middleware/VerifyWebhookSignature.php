<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates inbound provider webhooks. Verification is enforced when the
 * relevant credential/secret is configured; otherwise the request is allowed
 * through with a warning so existing deployments keep working until the
 * secrets are provisioned.
 */
class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next, string $provider): Response
    {
        $verified = match ($provider) {
            'twilio' => $this->verifyTwilio($request),
            'africastalking' => $this->verifySharedToken($request, config('services.sms.providers.africas_talking.webhook_token')),
            'meta' => $this->verifyMeta($request),
            default => false,
        };

        if (!$verified) {
            Log::warning('Webhook signature verification failed', [
                'provider' => $provider,
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json(['error' => 'invalid signature'], 403);
        }

        return $next($request);
    }

    /**
     * Validate Twilio's X-Twilio-Signature header (HMAC-SHA1 of the full URL
     * concatenated with the sorted POST parameters, keyed by the auth token).
     */
    private function verifyTwilio(Request $request): bool
    {
        $authToken = config('services.sms.providers.twilio.auth_token');

        if (!$authToken) {
            return $this->allowUnconfigured('twilio');
        }

        $signature = $request->header('X-Twilio-Signature');

        if (!$signature) {
            return false;
        }

        $data = $request->fullUrl();
        $params = $request->post();
        ksort($params);

        foreach ($params as $key => $value) {
            $data .= $key . $value;
        }

        $expected = base64_encode(hash_hmac('sha1', $data, $authToken, true));

        return hash_equals($expected, $signature);
    }

    /**
     * Validate Meta's X-Hub-Signature-256 header (HMAC-SHA256 of the raw
     * request body, keyed by the app secret). GET subscription handshakes
     * carry no signature; the controller validates the verify token instead.
     */
    private function verifyMeta(Request $request): bool
    {
        if ($request->isMethod('GET')) {
            return true;
        }

        $appSecret = config('services.whatsapp.providers.meta.app_secret');

        if (!$appSecret) {
            return $this->allowUnconfigured('meta');
        }

        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expected, $signature);
    }

    /**
     * Providers without request signing (Africa's Talking) authenticate with
     * a shared token passed as a query parameter or X-Webhook-Token header.
     */
    private function verifySharedToken(Request $request, ?string $expectedToken): bool
    {
        if (!$expectedToken) {
            return $this->allowUnconfigured('africastalking');
        }

        $token = $request->header('X-Webhook-Token', $request->query('token'));

        return is_string($token) && hash_equals($expectedToken, $token);
    }

    private function allowUnconfigured(string $provider): bool
    {
        Log::warning("Webhook verification secret not configured for {$provider}; accepting request unverified.");

        return true;
    }
}
