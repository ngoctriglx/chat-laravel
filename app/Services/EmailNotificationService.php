<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationMail;
use App\Models\UserDetail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EmailNotificationService {
    /**
     * Send email notification to user
     *
     * @param string $action The action type (login|register|forgot-password)
     * @param User $user The user model instance
     * @param string $token The verification token
     * @return void
     * @throws \Exception
     */
    public function sendEmailNotification(string $action, User $user, string $token): void
    {
        try {
            // Validate email
            $validator = Validator::make(['email' => $user->user_email], [
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                throw new \InvalidArgumentException('Invalid email address');
            }

            $userDetail = $user->userDetail()->first();
            $emailTemplates = [
                'login' => [
                    'template' => 'emails.user_2fa_login',
                    'subject' => 'Verify User Login',
                    'data' => ['name' => $userDetail ? $userDetail->first_name : $user->user_name, 'verificationCode' => $token]
                ],
                'register' => [
                    'template' => 'emails.user_2fa_register',
                    'subject' => 'Verify User Register',
                    'data' => ['verificationCode' => $token]
                ],
                'forgot-password' => [
                    'template' => 'emails.user_2fa_forgot_password',
                    'subject' => 'Verify User Forgot Password',
                    'data' => ['name' => $userDetail ? $userDetail->first_name : $user->user_name, 'verificationCode' => $token]
                ],
            ];

            if (!isset($emailTemplates[$action])) {
                throw new \Exception('Invalid action type.');
            }

            $emailData = $emailTemplates[$action];

            Mail::to($user->user_email)->send(new NotificationMail([
                'subject' => $emailData['subject'],
                'template' => $emailData['template'],
                'data' => $emailData['data']
            ]));

            Log::info('Email notification sent successfully', [
                'action' => $action,
                'user_id' => $user->id,
                'email' => $user->user_email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'action' => $action,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
