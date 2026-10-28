<?php
/**
 * Email Sender for Automated Emails
 */

if ( ! defined('ABSPATH')) {
    exit;
}

class SIMESC_Email_Sender {
    
    /**
     * Send automated email
     *
     * @param int   $email_id Email post ID.
     * @param array $args {
     *     Optional. Extra options.
     *     @type bool  $ignore_status Skip active-status check (for test sends).
     *     @type array $recipients    Override recipient list.
     *     @type bool  $update_last   Whether to update last-sent meta. Default true.
     *     @type bool  $is_test       Mark as test in subject/log. Default false.
     * }
     * @return bool
     */
    public function send_email($email_id, $args = array()) {
        $args = wp_parse_args(
            $args,
            array(
                'ignore_status' => false,
                'recipients'    => null,
                'update_last'   => true,
                'is_test'       => false,
            )
        );

        $email_post = get_post($email_id);
        
        if ( ! $email_post || $email_post->post_type !== SIMESC_POST_TYPE ) {
            return false;
        }
        
        // Get email settings
        $subject = get_post_meta($email_id, '_simesc_subject', true);
        $status = get_post_meta($email_id, '_simesc_status', true);
        $preheader = get_post_meta($email_id, '_simesc_preheader', true);
        
        // Check if active
        if ( ! $args['ignore_status'] && $status !== 'active') {
            return false;
        }
        
        // Parse recipients
        if ( is_array( $args['recipients'] ) ) {
            $recipients = $args['recipients'];
        } else {
            $recipients_json = get_post_meta($email_id, '_simesc_recipients', true);
            $recipients = json_decode($recipients_json, true);
        }
        if (!is_array($recipients) || empty($recipients)) {
            return false;
        }
        
        // Get email content
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Using WordPress core filter
        $content = apply_filters('the_content', $email_post->post_content);
        $content = $this->prepend_preheader( $content, $preheader );
        
        // If no subject, use post title
        if (empty($subject)) {
            $subject = $email_post->post_title;
        }
        
        // Strip HTML tags from subject (WordPress best practice)
        $subject = wp_strip_all_tags($subject);
        if ( ! empty( $args['is_test'] ) ) {
            $subject = '[TEST] ' . $subject;
        }
        
        // Apply filters before sending (WordPress best practice)
        do_action('simesc_email_send_before', $email_id, $recipients, $subject, $content);
        
        // Set up email filters (following EDD and WordPress best practices)
        add_filter('wp_mail_from', array($this, 'get_from_email'));
        add_filter('wp_mail_from_name', array($this, 'get_from_name'));
        add_filter('wp_mail_content_type', array($this, 'get_content_type'));
        
        // Prepare email headers
        $headers = array();
        
        $sent_count = 0;
        $failed_count = 0;
        
        // Send to each recipient
        foreach ($recipients as $recipient_email) {
            if (!is_email($recipient_email)) {
                $this->log_email($email_id, $recipient_email, 'failed', 'Invalid email address');
                $failed_count++;
                continue;
            }
            
            // Send email
            $sent = wp_mail($recipient_email, $subject, $content, $headers);
            
            if ($sent) {
                $this->log_email(
                    $email_id,
                    $recipient_email,
                    'sent',
                    ! empty( $args['is_test'] ) ? 'Test email' : ''
                );
                $sent_count++;
            } else {
                $this->log_email($email_id, $recipient_email, 'failed', 'wp_mail() returned false');
                $failed_count++;
            }
        }
        
        // Remove filters after sending (WordPress best practice)
        remove_filter('wp_mail_from', array($this, 'get_from_email'));
        remove_filter('wp_mail_from_name', array($this, 'get_from_name'));
        remove_filter('wp_mail_content_type', array($this, 'get_content_type'));
        
        // Apply filters after sending (WordPress best practice)
        do_action('simesc_email_send_after', $email_id, $recipients, $subject, $content, $sent_count, $failed_count);
        
        // Update last sent date
        if ($sent_count > 0 && ! empty( $args['update_last'] ) && empty( $args['is_test'] ) ) {
            update_post_meta($email_id, '_simesc_last_sent', current_time('mysql'));
        }
        
        return $sent_count > 0;
    }

    /**
     * Prepend hidden inbox preview / preheader text for email clients.
     *
     * @param string $content   HTML body.
     * @param string $preheader Preview text.
     * @return string
     */
    public function prepend_preheader( $content, $preheader ) {
        $preheader = trim( wp_strip_all_tags( (string) $preheader ) );
        if ( '' === $preheader ) {
            return $content;
        }

        // Hidden preheader that many clients show as inbox preview under the subject.
        $hidden = sprintf(
            '<div style="display:none!important;visibility:hidden;opacity:0;color:transparent;height:0;width:0;max-height:0;max-width:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;">%1$s%2$s</div>',
            esc_html( $preheader ),
            // Spacer so body text does not leak into the preview.
            str_repeat( '&nbsp;&zwnj;', 30 )
        );

        return $hidden . $content;
    }
    
    /**
     * Log email sending
     */
    private function log_email($email_id, $recipient_email, $status, $error_message = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'simesc_email_logs';
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table requires direct query
        $wpdb->insert(
            $table_name,
            array(
                'email_id' => $email_id,
                'recipient_email' => $recipient_email,
                'sent_at' => current_time('mysql'),
                'status' => $status,
                'error_message' => $error_message
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get from email address (filter callback)
     */
    public function get_from_email($from_email = '') {
        $email = get_option('admin_email');
        return apply_filters('simesc_email_from_address', $email, $from_email);
    }
    
    /**
     * Get from name (filter callback)
     */
    public function get_from_name($from_name = '') {
        $name = get_bloginfo('name');
        return apply_filters('simesc_email_from_name', $name, $from_name);
    }
    
    /**
     * Get content type (filter callback)
     */
    public function get_content_type($content_type = '') {
        return apply_filters('simesc_email_content_type', 'text/html', $content_type);
    }
    
    /**
     * Manually trigger email send (for testing / preview).
     *
     * @param int         $email_id       Email post ID.
     * @param string|null $test_recipient Override recipient.
     * @return bool
     */
    public function send_test_email($email_id, $test_recipient = null) {
        if ( empty( $test_recipient ) || ! is_email( $test_recipient ) ) {
            return false;
        }

        return $this->send_email(
            $email_id,
            array(
                'ignore_status' => true,
                'recipients'    => array( $test_recipient ),
                'update_last'   => false,
                'is_test'       => true,
            )
        );
    }
}


