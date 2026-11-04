<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;

class SmtpSettingController extends Controller
{
    /**
     * Get current SMTP configuration.
     */
    public function getSettings(): JsonResponse
    {
        $settings = Setting::where('group', 'mail')->pluck('value', 'key');
        $hasPassword = !empty($settings['mail_password']);

        return response()->json([
            'settings' => [
                'mail_mailer' => $settings['mail_mailer'] ?? 'smtp',
                'mail_host' => $settings['mail_host'] ?? '',
                'mail_port' => $settings['mail_port'] ?? '587',
                'mail_username' => $settings['mail_username'] ?? '',
                'mail_password' => $hasPassword ? '••••••••••••' : '',
                'mail_encryption' => $settings['mail_encryption'] ?? 'tls',
                'mail_from_address' => $settings['mail_from_address'] ?? 'noreply@hrms.local',
                'mail_from_name' => $settings['mail_from_name'] ?? 'HRMS System',
                'is_configured' => !empty($settings['mail_host']) && !empty($settings['mail_username']),
            ],
        ]);
    }

    /**
     * Update SMTP configuration.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mail_mailer' => ['required', 'string', 'in:smtp,sendmail,log'],
            'mail_host' => ['required_if:mail_mailer,smtp', 'nullable', 'string'],
            'mail_port' => ['required_if:mail_mailer,smtp', 'nullable', 'numeric'],
            'mail_username' => ['nullable', 'string'],
            'mail_password' => ['nullable', 'string'],
            'mail_encryption' => ['required_if:mail_mailer,smtp', 'nullable', 'string', 'in:tls,ssl,none'],
            'mail_from_address' => ['required', 'email'],
            'mail_from_name' => ['required', 'string', 'max:255'],
        ]);

        foreach ($validated as $key => $value) {
            // If password is sent as placeholder dots or empty on edit, do not overwrite existing password
            if ($key === 'mail_password') {
                if (!empty($value) && $value !== '••••••••••••') {
                    Setting::set($key, $value, 'mail');
                }
                continue;
            }

            Setting::set($key, (string) ($value ?? ''), 'mail');
        }

        return response()->json([
            'message' => 'SMTP mail configurations saved successfully.',
        ]);
    }

    /**
     * Send a test email to verify SMTP configuration.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        $settings = Setting::where('group', 'mail')->pluck('value', 'key');

        $host = $settings['mail_host'] ?? '';
        $port = (int) ($settings['mail_port'] ?? 587);
        $username = $settings['mail_username'] ?? '';
        $password = $settings['mail_password'] ?? '';
        $encryption = $settings['mail_encryption'] ?? 'tls';
        $fromAddress = $settings['mail_from_address'] ?? 'noreply@hrms.local';
        $fromName = $settings['mail_from_name'] ?? 'HRMS System';

        if (empty($host)) {
            return response()->json([
                'message' => 'SMTP Host is not configured. Please save your SMTP settings first.',
            ], 422);
        }

        try {
            // Dynamically configure mailer at runtime
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $host,
                'mail.mailers.smtp.port' => $port,
                'mail.mailers.smtp.encryption' => $encryption === 'none' ? null : $encryption,
                'mail.mailers.smtp.username' => $username,
                'mail.mailers.smtp.password' => $password,
                'mail.mailers.smtp.timeout' => 15,
                'mail.from.address' => $fromAddress,
                'mail.from.name' => $fromName,
            ]);

            Mail::purge('smtp');

            $recipient = $request->input('test_email');
            $sentAt = now()->toDateTimeString();

            Mail::raw(
                "Hello,\n\nThis is a test email sent from HRMS Enterprise System to confirm your SMTP configuration.\n\nConnection Details:\nHost: {$host}\nPort: {$port}\nEncryption: {$encryption}\nSent At: {$sentAt}\n\nIf you received this message, your SMTP credentials are functioning properly.",
                function ($message) use ($recipient, $fromAddress, $fromName) {
                    $message->to($recipient)
                            ->from($fromAddress, $fromName)
                            ->subject('[HRMS] SMTP Configuration Test Successful');
                }
            );

            return response()->json([
                'message' => "Test email successfully dispatched to {$recipient}.",
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'SMTP Connection failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
