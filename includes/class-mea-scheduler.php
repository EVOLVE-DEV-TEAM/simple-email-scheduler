<?php
/**
 * Scheduler for Email Scheduler
 */

if ( ! defined('ABSPATH')) {
    exit;
}

class SIMESC_Scheduler {
    
    public function init() {
        // Register cron hook
        add_action('simesc_monthly_email_check', array($this, 'check_and_send_emails'));
        
        // Schedule if not already scheduled
        if (!wp_next_scheduled('simesc_monthly_email_check')) {
            $this->schedule_monthly_check();
        }
    }
    
    /**
     * Schedule monthly check
     */
    public function schedule_monthly_check() {
        // Schedule to run daily at 1 AM to check for emails that need to be sent
        if (!wp_next_scheduled('simesc_monthly_email_check')) {
            wp_schedule_event(strtotime('tomorrow 1:00'), 'daily', 'simesc_monthly_email_check');
        }
    }
    
    /**
     * Unschedule monthly check
     */
    public function unschedule_monthly_check() {
        $timestamp = wp_next_scheduled('simesc_monthly_email_check');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'simesc_monthly_email_check');
        }
    }
    
    /**
     * Check and send emails that are due
     */
    public function check_and_send_emails() {
        // Get all active automated emails
        $emails = get_posts(array(
            'post_type' => SIMESC_POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for filtering active emails
            'meta_query' => array(
                array(
                    'key' => '_simesc_status',
                    'value' => 'active',
                    'compare' => '='
                )
            )
        ));
        
        if (empty($emails)) {
            return;
        }
        
        // Use WordPress timezone-aware functions
        $current_day = (int) current_time('j');
        $current_hour = (int) current_time('H');
        $current_minute = (int) current_time('i');
        
        $email_sender = new SIMESC_Email_Sender();
        
        foreach ($emails as $email_post) {
            $day_of_month = (int) get_post_meta($email_post->ID, '_simesc_day_of_month', true);
            $send_time = get_post_meta($email_post->ID, '_simesc_send_time', true);
            $last_sent = get_post_meta($email_post->ID, '_simesc_last_sent', true);
            
            // Skip if no day or time set
            if (empty($day_of_month) || empty($send_time)) {
                continue;
            }
            
            // Parse scheduled time
            $send_time_parts = explode(':', $send_time);
            $send_hour = (int) $send_time_parts[0];
            $send_minute = isset($send_time_parts[1]) ? (int) $send_time_parts[1] : 0;
            $send_time_minutes = ($send_hour * 60) + $send_minute;
            $current_time_minutes = ($current_hour * 60) + $current_minute;
            
            // Check if it's the right day
            if ($current_day === $day_of_month) {
                // It's the scheduled day - check if time has passed
                if ($current_time_minutes >= $send_time_minutes) {
                    // Time has passed, send it
                    if ($this->should_send_today($email_post->ID, $last_sent, $send_time)) {
                        $email_sender->send_email($email_post->ID);
                    }
                }
            } elseif ($current_day === $day_of_month + 1) {
                // It's the day after scheduled day - send as catch-up if we haven't sent this month
                // This handles cases where cron runs at 1 AM and email was scheduled for previous day
                if ($this->should_send_today($email_post->ID, $last_sent, $send_time)) {
                    $email_sender->send_email($email_post->ID);
                }
            } else {
                // Handle edge case: if day is 31 and current month doesn't have 31 days
                // Send on last day of month
                $last_day_of_month = (int) current_time('t');
                if ($day_of_month > $last_day_of_month && $current_day === $last_day_of_month) {
                    // This is the last day, and the scheduled day is beyond this month
                    // Check if we should send
                    if ($this->should_send_today($email_post->ID, $last_sent, $send_time)) {
                        $email_sender->send_email($email_post->ID);
                    }
                }
            }
        }
    }
    
    /**
     * Check if email should be sent today
     */
    private function should_send_today($email_id, $last_sent, $send_time) {
        // If never sent, send it
        if (empty($last_sent)) {
            return true;
        }
        
        // Parse last sent timestamp (assume it's in WordPress timezone)
        $last_sent_timestamp = strtotime($last_sent);
        if (!$last_sent_timestamp) {
            return true; // Invalid timestamp, send it
        }
        
        // Check if last sent was today (using WordPress timezone)
        $last_sent_date = current_time('Y-m-d', false, $last_sent_timestamp);
        $current_date = current_time('Y-m-d');
        
        // If last sent was today, don't send again
        if ($last_sent_date === $current_date) {
            return false;
        }
        
        // Check if last sent was this month but on a different day
        $last_sent_month = current_time('Y-m', false, $last_sent_timestamp);
        $current_month = current_time('Y-m');
        
        // If last sent was in a different month, send it
        if ($last_sent_month !== $current_month) {
            return true;
        }
        
        // If last sent was earlier this month but not today, send it
        return true;
    }
}


