<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Communication Settings') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 md:px-8">
        <div class="w-full">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Communication Provider Configuration</h3>

                    <div class="space-y-8">
                        {{-- Email Configuration --}}
                        <div class="border-b border-gray-200 pb-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">📧 Email Configuration</h4>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600 mb-2">Configure your email settings in the <code>.env</code> file:</p>
                                <pre class="text-xs bg-gray-800 text-green-400 p-3 rounded overflow-x-auto">
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host.com
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=your-email@domain.com
MAIL_FROM_NAME="Your App Name"</pre>
                            </div>
                        </div>

                        {{-- SMS Configuration --}}
                        <div class="border-b border-gray-200 pb-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">📱 SMS Configuration</h4>

                            <div class="space-y-4">
                                <div>
                                    <h5 class="font-medium text-gray-800">Africa's Talking</h5>
                                    <div class="bg-gray-50 p-4 rounded-lg mt-2">
                                        <pre class="text-xs bg-gray-800 text-green-400 p-3 rounded overflow-x-auto">
SMS_PROVIDER=africas_talking
AFRICAS_TALKING_API_KEY=your-api-key
AFRICAS_TALKING_USERNAME=your-username
AFRICAS_TALKING_SENDER_ID=your-sender-id</pre>
                                    </div>
                                </div>

                                <div>
                                    <h5 class="font-medium text-gray-800">Twilio</h5>
                                    <div class="bg-gray-50 p-4 rounded-lg mt-2">
                                        <pre class="text-xs bg-gray-800 text-green-400 p-3 rounded overflow-x-auto">
SMS_PROVIDER=twilio
TWILIO_ACCOUNT_SID=your-account-sid
TWILIO_AUTH_TOKEN=your-auth-token
TWILIO_FROM_NUMBER=+1234567890</pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- WhatsApp Configuration --}}
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">💬 WhatsApp Configuration</h4>

                            <div class="space-y-4">
                                <div>
                                    <h5 class="font-medium text-gray-800">Twilio WhatsApp</h5>
                                    <div class="bg-gray-50 p-4 rounded-lg mt-2">
                                        <pre class="text-xs bg-gray-800 text-green-400 p-3 rounded overflow-x-auto">
WHATSAPP_PROVIDER=twilio
TWILIO_WHATSAPP_FROM=whatsapp:+1234567890</pre>
                                    </div>
                                </div>

                                <div>
                                    <h5 class="font-medium text-gray-800">Meta WhatsApp Business API</h5>
                                    <div class="bg-gray-50 p-4 rounded-lg mt-2">
                                        <pre class="text-xs bg-gray-800 text-green-400 p-3 rounded overflow-x-auto">
WHATSAPP_PROVIDER=meta
META_WHATSAPP_ACCESS_TOKEN=your-access-token
META_WHATSAPP_PHONE_NUMBER_ID=your-phone-number-id
META_WHATSAPP_APP_ID=your-app-id
META_WHATSAPP_VERIFY_TOKEN=your-verify-token</pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Webhook URLs --}}
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h4 class="text-md font-medium text-blue-900 mb-3">🔗 Webhook URLs</h4>
                            <p class="text-sm text-blue-800 mb-3">Configure these webhook URLs in your provider dashboards:</p>
                            <div class="space-y-2 text-sm">
                                <div>
                                    <strong>Twilio:</strong> <code class="bg-blue-100 px-2 py-1 rounded">{{ url('/api/webhooks/communication/twilio') }}</code>
                                </div>
                                <div>
                                    <strong>Africa's Talking:</strong> <code class="bg-blue-100 px-2 py-1 rounded">{{ url('/api/webhooks/communication/africas-talking') }}</code>
                                </div>
                                <div>
                                    <strong>Meta WhatsApp:</strong> <code class="bg-blue-100 px-2 py-1 rounded">{{ url('/api/webhooks/communication/meta-whatsapp') }}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
