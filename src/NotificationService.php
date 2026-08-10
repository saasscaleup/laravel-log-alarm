<?php

namespace Saasscaleup\LogAlarm;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class NotificationService
{

    /**
     * Send notification to Slack and email.
     *
     * @param string $message The message to be sent.
     * @return void
     */
    public static function send($message){
        // Send Slack notification
        /**
         * Send a notification to Slack.
         *
         * @param string $message The message to be sent.
         * @return void
         */
        self::sendSlackNotification($message);

        // Send Discord notification
        /**
         * Send a notification to Discord.
         *
         * @param string $message The message to be sent.
         * @return void
         */
        self::sendDiscordNotification($message);
        
        // Send email notification
        /**
         * Send a notification to email.
         *
         * @param string $message The message to be sent.
         * @return void
         */
        self::sendEmailNotification($message);

        // Send Telegram notification
        /**
         * Send a notification to telegram.
         *
         * @param string $message The message to be sent.
         * @return void
         */
        self::sendTelegramNotification($message);
    }


    /**
     * Send a notification to Slack.
     *
     * This function sends a message to a Slack channel via a webhook URL.
     *
     * @param string $message The message to be sent.
     * @return void
     */
    public static function sendSlackNotification($message)
    {
        // Get the webhook URL from the configuration
        $webhookUrl = config('log-alarm.slack_webhook_url');

        // If the webhook URL is not configured, return without sending the notification
        if (empty($webhookUrl)) {
            return;
        }

        // Prepare the data for the request
        $data = [
            'text' => config('log-alarm.notification_email_subject') ,
            'attachments' => [
                [
                    'title' => config('log-alarm.notification_email_subject'),
                    'text' => $message,
                    'color' => '#FF0000',
                    'fields' => [
                        [
                            'title' => 'Priority',
                            'value' => 'High',
                            'short' => true
                        ]
                    ]
                ]
            ]
        ];

        // Initialize the cURL session
        $ch = curl_init($webhookUrl);

        // Set the request method, request body, and options
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ]);

        // Execute the request and get the response
        $result = curl_exec($ch);

        // Close the cURL session
        curl_close($ch);

        // If the request failed, log the error
        if ($result === false) {
            Log::info("LogAlarm::sendSlackNotification->error: " . curl_error($ch));
        }
    }

    /**
     * Send a notification via email.
     *
     * This function sends a message via email to the recipients specified in the configuration.
     *
     * @param string $message The message to be sent.
     * @return void
     */
    public static function sendEmailNotification($message)
    {
        // Get the email addresses to send the notification to
        $to = config('log-alarm.notification_email');

        // If no email addresses are configured, return without sending the notification
        if (empty($to)) {
            return;
        }

        // Split the email addresses into an array
        $to_emails = explode(',', $to);

        try {
            // Send the email notification
            Mail::raw($message, function ($msg) use ($to_emails) {
                // Set the recipients and subject of the email
                $msg->to($to_emails)->subject(config('log-alarm.notification_email_subject'));
            });
        } catch (\Exception $e) {
            // If the email sending fails, log the error
            Log::info("LogAlarm::sendEmailNotification->error: " . $e->getMessage());
        }
    }

    /**
     * Send a notification to Discord.
     *
     * This function sends a message to a Discord channel via a webhook URL.
     *
     * @param string $message The message to be sent.
     * @return void
     */
    public static function sendDiscordNotification($message)
    {
        // Get the webhook URL from the configuration
        $webhookUrl = config('log-alarm.discord_webhook_url');

        // If the webhook URL is not configured, return without sending the notification
        if (empty($webhookUrl)) {
            return;
        }

        // Prepare the data for the request
        $data = [
            'embeds' => [
                [
                    'title' => config('log-alarm.notification_email_subject'),
                    'description' => $message,
                    'color' => 16711680, // Red color in decimal
                    'fields' => [
                        [
                            'name' => 'Priority',
                            'value' => 'High',
                            'inline' => true
                        ]
                    ]
                ]
            ]
        ];

        try {
            // Send the request using HTTP facade
            $response = Http::post($webhookUrl, $data);

            if (!$response->successful()) {
                Log::info("LogAlarm::sendDiscordNotification->error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::info("LogAlarm::sendDiscordNotification->error: " . $e->getMessage());
        }
    }

     /**
     * Send a notification to Telegram.
     *
     * This function sends a message to a Telegram chat via the Telegram Bot API.
     *
     * @param string $message The message to be sent.
     * @return void
     */
    public static function sendTelegramNotification($message)
    {
        // Get the bot token and chat ID from the configuration
        $botToken = config('log-alarm.telegram_bot_token');
        $chatId = config('log-alarm.telegram_chat_id');
        $messageThreadId = config('log-alarm.telegram_message_thread_id');


        // If either the bot token or chat ID is not configured, return without sending the notification
        if (empty($botToken) || empty($chatId)) {
            return;
        }

        // Prepare the message text with formatting
        $text = "*" . config('log-alarm.notification_email_subject') . "*\n\n" .
                $message . "\n\n" .
                "*Priority:* High";

        // Prepare the data for the request
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true
        ];

        if ($messageThreadId) {
            $data['message_thread_id'] = $messageThreadId;
        }

        // Construct the API URL
        $apiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";

        try {
            // Send the request using HTTP facade
            $response = Http::post($apiUrl, $data);

            if (!$response->successful()) {
                Log::info("LogAlarm::sendTelegramNotification->error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::info("LogAlarm::sendTelegramNotification->error: " . $e->getMessage());
        }
    }
}