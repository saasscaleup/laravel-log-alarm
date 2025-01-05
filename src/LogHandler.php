<?php

namespace Saasscaleup\LogAlarm;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class LogHandler
{
    protected $notification_cache_key       = 'log_alarm_last_notification';
    /**
     * handle
     *
     * This method is called when a new log message is logged. It checks if the log level
     * of the message is one of the log levels specified in the config file, and if the
     * message contains the specific string specified in the config file. If both conditions
     * are met, it calls the logError method to handle the error.
     *
     * @param MessageLogged $event The event object containing the log message.
     * @return void
     */
    public function handle(MessageLogged $event)
    {
        // Get the log levels specified in the config file
        $log_types = explode(',',config('log-alarm.log_type'));

        // Check if the log level of the message is one of the log levels specified in the config file
        if (in_array($event->level,$log_types)) {

            // Check if the message contains the specific string specified in the config file
            if ($this->containsSpecificString($event->message)) {

                // Call the logError method to handle the error
                $this->logError($event);
            }
        }
    }
    
    /**
     * containsSpecificString
     *
     * This method checks if the provided log message contains the specific string
     * specified in the config file. It does this by using the strpos() function
     * to search for the position of the specific string within the message. If the
     * specific string is found, the method returns true. If the specific string is
     * not found or if the message is empty, the method returns false.
     *
     * @param  string $message The log message to search for the specific string.
     * @return bool            Returns true if the specific string is found in the message,
     *                         false otherwise.
     */
    protected function containsSpecificString($message)
    {
        // Get the specific string from the config file. If the specific string is not
        // specified in the config file, an empty string is used.
        $specificString = config('log-alarm.specific_string', '');

        // Check if the specific string is found within the message using the strpos() function.
        // If the specific string is found, strpos() returns the position of the first occurrence
        // of the specific string within the message. If the specific string is not found,
        // strpos() returns false.
        return empty($specificString) ? true : strpos($message, $specificString) !== false;
    }
    
    /**
     * Constructs a detailed log alarm message from a MessageLogged event.
     *
     * This method extracts information such as the log level, log message,
     * file, and line number from the provided MessageLogged event. If an
     * exception is present in the event's context, the file and line number
     * are retrieved from the exception; otherwise, they default to 'Unknown'.
     *
     * @param MessageLogged $event The log event containing details such as
     *                             level, message, and context.
     * @return string A formatted string containing the log level, message,
     *                file, and line number.
     */
    protected function getLogAlarmMessage(MessageLogged $event){

        $log_level = $event->level;
        $log_message = $event->message;
        $log_file = 'Unknown file';
        $log_line = 'Unknown line';

        try {
            // Check if 'exception' exists in the context
            if (isset($event->context['exception']) && $event->context['exception'] instanceof \Exception) {
                $exception = $event->context['exception'];
                $log_file = $exception->getFile();
                $log_line = $exception->getLine();
            }
        } catch (\Exception $e) {
            // Do nothing
        }

        // Return a formatted string containing the log level, message, file, and line number
        // Use spaces instead underscores to avoid formatting issues in Slack, Telegram or any using markdown formatting messages
        return "LOG LEVEL: {$log_level}\r\nLOG MESSAGE: {$log_message}\r\nLOG FILE: {$log_file}\r\nLOG LINE: {$log_line}";
    }
    /**
     * logError
     *
     * This method handles the logging of an error. It retrieves the current error logs
     * from the cache, adds a new log with the current timestamp, filters out logs that are
     * older than the specified time, saves the updated logs back to the cache, checks if
     * there have been a specified number of error logs in the last minute, and if so, sends
     * a notification and updates the time of the last notification.
     *
     * @param  MessageLogged $event The event that triggered the logging of the error.
     * @return void
     */
    protected function logError(MessageLogged $event)
    {
        // Get the log alarm message
        $log_message = $this->getLogAlarmMessage($event);
        
        // Generate a unique cache key based on the log message
        $log_alarm_cache_key_enc = md5($log_message);
        
        // Retrieve the current error logs from the cache or initialize an empty array if no logs exist
        $errorLogs = Cache::get($log_alarm_cache_key_enc, []);
        
        // Add a new log with the current timestamp to the array of error logs
        $errorLogs[] = Carbon::now();
        
        // Get the time in minutes to consider an error log as recent
        $log_time_frame = config('log-alarm.log_time_frame');

        // Get specified number of error logs in time frame
        $log_per_time_frame = config('log-alarm.log_per_time_frame');

        // Filter out logs that are older than the specified time frame
        $errorLogs = array_filter($errorLogs, function ($timestamp) use ($log_time_frame) {
            return $timestamp >=  Carbon::now()->subMinutes($log_time_frame);
        });

        // Save the updated logs back to the cache with an expiration time of 1 minute
        Cache::put($log_alarm_cache_key_enc, $errorLogs, Carbon::now()->addMinutes($log_time_frame)); 

        // Check if there have been a specified number of error logs in time frame (in the last minute for example)
        if (count($errorLogs) >= $log_per_time_frame) {
            
            // Retrieve the time of the last notification from the cache or initialize null if no notification time exists
            $last_notification_time = Cache::get($this->notification_cache_key.'_'.$log_alarm_cache_key_enc);

            // Get the delay between notifications from the config file
            $delay_between_alarms = config('log-alarm.delay_between_alarms');

            // Send notification only if last notification was sent more than 5 minutes ago
            // The Carbon library is used to compare the current time with the time of the last notification
            if (!$last_notification_time || Carbon::now()->diffInMinutes($last_notification_time) >= $delay_between_alarms) {
                
                // Get the message to be sent in the notification from the config file
                $message = empty(config('log-alarm.notification_message')) ? $log_message : config('log-alarm.notification_message');

                $message = "The Error was occurred {$log_per_time_frame} times in the last {$log_time_frame} minutes: \r\n\r\n{$message}";

                // Send the notification
                NotificationService::send($message);

                // Update the time of the last notification in the cache
                // The notification is set to expire in the delay between alarms specified in the config file
                Cache::put($this->notification_cache_key.'_'.$log_alarm_cache_key_enc, Carbon::now(), Carbon::now()->addMinutes($delay_between_alarms)); 
            }
        }
    }
}
