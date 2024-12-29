<?php

return [

    // enable or disable LOG ALARM
    'enabled' => env('LA_ENABLED', true),

    // log listener for specific log type
    'log_type' => env('LA_LOG_TYPE', 'error'), // also possible: 'error,warning,debug'

    // log time frame - log time frame to listen - in minutes
    "log_time_frame" => env('LA_LOG_TIME_FRAME', 1),

    // log per time frame - How many log to count per time frame until alarm trigger 
    "log_per_time_frame" => env('LA_LOG_PER_TIME_FRAME', 5),

    // delay between alarms in minutes - How many minutes to delay between alarms
    'delay_between_alarms' => env('LA_DELAY_BETWEEN_ALARMS', 5),

    // log listener for specific word inside log messages
    'specific_string' => env('LA_SPECIFIC_STRING', ''), // also possible: 'table lock' or 'foo' or 'bar' or leave empty '' to enable any word

    // notification message for log alarm
    'notification_message' => env('LA_NOTIFICATION_MESSAGE', ''), // Leave empty '' to enable error log triggered alarm
    
    // Slack webhook url for log alarm
    'slack_webhook_url' => env('LA_SLACK_WEBHOOK_URL', ''),

    
    // Discord webhook url for log alarm
    'discord_webhook_url' => env('LA_DISCORD_WEBHOOK_URL', ''),

    // notification email address for log alarm
    'notification_email' => env('LA_NOTIFICATION_EMAIL', 'admin@example.com'), // also possible: 'admin@example.com,admin2@example.com'

    // notification email subject for log alarm
    'notification_email_subject' => env('LA_NOTIFICATION_EMAIL_SUBJECT', 'Log Alarm Notification'),
];
