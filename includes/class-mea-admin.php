<?php
/**
 * Admin Interface for Managing Automated Emails
 */

if ( ! defined('ABSPATH')) {
    exit;
}

class SIMESC_Admin {
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        // Save meta data
        add_action( 'save_post_' . SIMESC_POST_TYPE, array( $this, 'save_meta_data' ) );
        // Add custom columns
        add_filter( 'manage_' . SIMESC_POST_TYPE . '_posts_columns', array( $this, 'add_custom_columns' ) );
        add_action( 'manage_' . SIMESC_POST_TYPE . '_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
        // Add quick edit support
        add_action('quick_edit_custom_box', array($this, 'quick_edit_fields'), 10, 2);
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        // AJAX handlers for saved recipients
        add_action('wp_ajax_simesc_save_recipients', array($this, 'ajax_save_recipients'));
        add_action('wp_ajax_simesc_load_recipients', array($this, 'ajax_load_recipients'));
        add_action('wp_ajax_simesc_delete_recipients', array($this, 'ajax_delete_recipients'));
        add_action('wp_ajax_simesc_send_test_email', array($this, 'ajax_send_test_email'));
    }
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=' . SIMESC_POST_TYPE,
            __('Recipient Lists', 'simple-email-scheduler'),
            __('Recipient Lists', 'simple-email-scheduler'),
            'manage_options',
            'simesc-recipient-lists',
            array($this, 'render_recipient_lists_page')
        );
    }
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'simesc_email_settings',
            __('Email Settings', 'simple-email-scheduler'),
            array($this, 'render_email_settings_meta_box'),
            SIMESC_POST_TYPE,
            'normal',
            'high'
        );
        add_meta_box(
            'simesc_email_logs',
            __('Email Logs', 'simple-email-scheduler'),
            array($this, 'render_logs_meta_box'),
            SIMESC_POST_TYPE,
            'side',
            'default'
        );
    }
    /**
     * Render email settings meta box
     */
    public function render_email_settings_meta_box($post) {
        wp_nonce_field('simesc_save_meta', 'simesc_meta_nonce');
        // Verify post object
        if (!is_object($post) || !isset($post->ID)) {
            return;
        }
        $recipients = get_post_meta($post->ID, '_simesc_recipients', true);
        $subject = get_post_meta($post->ID, '_simesc_subject', true);
        $preheader = get_post_meta($post->ID, '_simesc_preheader', true);
        // If no subject set, use post title as default
        if (empty($subject)) {
            $subject = $post->post_title;
        }
        $status = get_post_meta($post->ID, '_simesc_status', true);
        $current_user = wp_get_current_user();
        $default_test_email = ( $current_user && is_email( $current_user->user_email ) ) ? $current_user->user_email : get_option( 'admin_email' );
        // Default to 'active' if status is empty (new post)
        if (empty($status)) {
            $status = 'active';
        }
        $last_sent = get_post_meta($post->ID, '_simesc_last_sent', true);
        // Get schedule settings
        $day_of_month = get_post_meta($post->ID, '_simesc_day_of_month', true);
        $send_time = get_post_meta($post->ID, '_simesc_send_time', true);
        if (empty($day_of_month)) {
            $day_of_month = 1;
        }
        if (empty($send_time)) {
            $send_time = '09:00';
        }
        // Parse recipients
        $recipients_array = array();
        if (!empty($recipients)) {
            $recipients_array = json_decode($recipients, true);
            if (!is_array($recipients_array)) {
                $recipients_array = array();
            }
        }
        ?>
        <table class="form-table">
            <tr>
                <th><label for="simesc_subject"><?php esc_html_e('Email Subject', 'simple-email-scheduler'); ?> <span style="color: red;">*</span></label></th>
                <td>
                    <input type="text" id="simesc_subject" name="simesc_subject" value="<?php echo esc_attr($subject); ?>" class="regular-text" required />
                    <p class="description"><?php esc_html_e('Subject line for the email. This is required and will be used as the email subject when sending.', 'simple-email-scheduler'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="simesc_preheader"><?php esc_html_e('Inbox Preview Text', 'simple-email-scheduler'); ?></label></th>
                <td>
                    <input type="text" id="simesc_preheader" name="simesc_preheader" value="<?php echo esc_attr( $preheader ); ?>" class="regular-text" maxlength="150" />
                    <p class="description"><?php esc_html_e('Optional. Shown under the subject in many email inboxes (Gmail, Outlook, Apple Mail). Leave blank to skip.', 'simple-email-scheduler'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="simesc_test_email"><?php esc_html_e('Send Test Email', 'simple-email-scheduler'); ?></label></th>
                <td>
                    <input type="email" id="simesc_test_email" value="<?php echo esc_attr( $default_test_email ); ?>" class="regular-text" placeholder="you@example.com" />
                    <button type="button" class="button" id="simesc-send-test-email" data-post-id="<?php echo esc_attr( (string) $post->ID ); ?>">
                        <?php esc_html_e( 'Send Test / Preview', 'simple-email-scheduler' ); ?>
                    </button>
                    <span id="simesc-test-email-status" style="margin-left:8px;"></span>
                    <p class="description"><?php esc_html_e('Saves are not required first if the post already exists. Uses current saved subject, preview text, and content. Subject is prefixed with [TEST].', 'simple-email-scheduler'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="simesc_recipients"><?php esc_html_e('Recipients', 'simple-email-scheduler'); ?></label></th>
                <td>
                    <div style="margin-bottom: 10px;">
                        <label for="mea-recipient-list-select" style="display: block; margin-bottom: 5px;">
                            <strong><?php esc_html_e('Select Saved List:', 'simple-email-scheduler'); ?></strong>
                        </label>
                        <select id="mea-recipient-list-select" style="width: 100%; max-width: 400px;">
                            <option value=""><?php esc_html_e('-- Select a saved list or add individual recipients --', 'simple-email-scheduler'); ?></option>
                            <?php
                            $saved_lists = get_option('simesc_saved_recipients', array());
                            foreach ($saved_lists as $key => $list): ?>
                                <option value="<?php echo esc_attr($key); ?>" data-recipients="<?php echo esc_attr(wp_json_encode($list['recipients'])); ?>">
                                    <?php echo esc_html($list['name']); ?> (<?php echo count($list['recipients']); ?> recipients)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description" style="margin-top: 5px;">
                            <?php esc_html_e('Select a saved recipient list, or add individual recipients below.', 'simple-email-scheduler'); ?>
                            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . SIMESC_POST_TYPE . '&page=simesc-recipient-lists' ) ); ?>"><?php esc_html_e('Manage lists', 'simple-email-scheduler'); ?></a>
                        </p>
                    </div>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                        <label style="display: block; margin-bottom: 5px;">
                            <strong><?php esc_html_e('Individual Recipients:', 'simple-email-scheduler'); ?></strong>
                        </label>
                        <div id="mea-recipients-container">
                            <?php if (!empty($recipients_array)): ?>
                                <?php foreach ($recipients_array as $index => $email): ?>
                                    <div class="mea-recipient-row" style="margin-bottom: 5px;">
                                        <input type="email" name="simesc_recipients[]" value="<?php echo esc_attr($email); ?>" class="regular-text" placeholder="email@example.com" />
                                        <button type="button" class="button mea-remove-recipient"><?php esc_html_e('Remove', 'simple-email-scheduler'); ?></button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="mea-recipient-row" style="margin-bottom: 5px;">
                                    <input type="email" name="simesc_recipients[]" value="" class="regular-text" placeholder="email@example.com" />
                                    <button type="button" class="button mea-remove-recipient"><?php esc_html_e('Remove', 'simple-email-scheduler'); ?></button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button" id="mea-add-recipient"><?php esc_html_e('Add Recipient', 'simple-email-scheduler'); ?></button>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label for="simesc_status"><?php esc_html_e('Status', 'simple-email-scheduler'); ?></label></th>
                <td>
                    <select id="simesc_status" name="simesc_status">
                        <option value="active" <?php selected($status, 'active'); ?>><?php esc_html_e('Active', 'simple-email-scheduler'); ?></option>
                        <option value="inactive" <?php selected($status, 'inactive'); ?>><?php esc_html_e('Inactive', 'simple-email-scheduler'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Only active emails will be sent automatically.', 'simple-email-scheduler'); ?></p>
                </td>
            </tr>
            <?php if (!empty($last_sent)): ?>
            <tr>
                <th><?php esc_html_e('Last Sent', 'simple-email-scheduler'); ?></th>
                <td>
                    <strong><?php echo esc_html($last_sent); ?></strong>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <hr style="margin: 20px 0;">
        <h3 style="margin-top: 0;"><?php esc_html_e('Schedule Settings', 'simple-email-scheduler'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="simesc_day_of_month"><?php esc_html_e('Day of Month', 'simple-email-scheduler'); ?></label></th>
                <td>
                    <input type="number" id="simesc_day_of_month" name="simesc_day_of_month" value="<?php echo esc_attr($day_of_month); ?>" min="1" max="31" step="1" class="small-text" style="width: 80px;" />
                    <p class="description"><?php esc_html_e('Day of the month to send this email (1-31).', 'simple-email-scheduler'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="simesc_send_time"><?php esc_html_e('Send Time', 'simple-email-scheduler'); ?></label></th>
                <td>
                    <input type="time" id="simesc_send_time" name="simesc_send_time" value="<?php echo esc_attr($send_time); ?>" />
                    <p class="description"><?php esc_html_e('Time of day to send the email (24-hour format).', 'simple-email-scheduler'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    /**
     * Render schedule meta box
     */
    public function render_schedule_meta_box($post) {
        $day_of_month = get_post_meta($post->ID, '_simesc_day_of_month', true);
        $send_time = get_post_meta($post->ID, '_simesc_send_time', true);
        if (empty($day_of_month)) {
            $day_of_month = 1;
        }
        if (empty($send_time)) {
            $send_time = '09:00';
        }
        ?>
        <table class="form-table">
            <tr>
                <th><label for="simesc_day_of_month"><?php esc_html_e('Day of Month', 'simple-email-scheduler'); ?></label></th>
                <td>
                    <input type="number" id="simesc_day_of_month" name="simesc_day_of_month" value="<?php echo esc_attr($day_of_month); ?>" min="1" max="31" step="1" class="small-text" style="width: 80px;" />
                    <p class="description"><?php esc_html_e('Day of the month to send this email (1-31).', 'simple-email-scheduler'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="simesc_send_time"><?php esc_html_e('Send Time', 'simple-email-scheduler'); ?></label></th>
                <td>
                    <input type="time" id="simesc_send_time" name="simesc_send_time" value="<?php echo esc_attr($send_time); ?>" />
                    <p class="description"><?php esc_html_e('Time of day to send the email (24-hour format).', 'simple-email-scheduler'); ?></p>
                </td>
            </tr>
        </table>
        <p class="description">
            <strong><?php esc_html_e('Note:', 'simple-email-scheduler'); ?></strong><br>
            <?php esc_html_e('The email will be sent on the specified day and time each month. If the day doesn\'t exist in a month (e.g., Feb 30), it will be sent on the last day of that month.', 'simple-email-scheduler'); ?>
        </p>
        <?php
    }
    /**
     * Render logs meta box
     */
    public function render_logs_meta_box($post) {
        global $wpdb;
        // Verify post object
        if (!is_object($post) || !isset($post->ID)) {
            return;
        }

        // Sanitize table name and the queried email ID before building the query
        $table_name = esc_sql( $wpdb->prefix . 'simesc_email_logs' );
        $email_id   = absint( $post->ID );
        if ( 0 === $email_id ) {
            return;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $logs = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name cannot be prepared, validated as safe above
            $wpdb->prepare( "SELECT * FROM `{$table_name}` WHERE email_id = %d ORDER BY sent_at DESC LIMIT 20", $email_id )
        );
        ?>
        <div class="mea-logs-container">
            <?php if (empty($logs)): ?>
                <p><?php esc_html_e('No emails sent yet.', 'simple-email-scheduler'); ?></p>
            <?php else: ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Recipient', 'simple-email-scheduler'); ?></th>
                            <th><?php esc_html_e('Sent At', 'simple-email-scheduler'); ?></th>
                            <th><?php esc_html_e('Status', 'simple-email-scheduler'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log->recipient_email); ?></td>
                                <td><?php echo esc_html($log->sent_at); ?></td>
                                <td>
                                    <?php if ($log->status === 'sent'): ?>
                                        <span style="color: green;"><?php esc_html_e('Sent', 'simple-email-scheduler'); ?></span>
                                    <?php else: ?>
                                        <span style="color: red;"><?php esc_html_e('Failed', 'simple-email-scheduler'); ?></span>
                                        <?php if (!empty($log->error_message)): ?>
                                            <br><small><?php echo esc_html($log->error_message); ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    /**
     * Save meta data
     */
    public function save_meta_data($post_id) {
        // Check nonce
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce value, validated by wp_verify_nonce
        if (!isset($_POST['simesc_meta_nonce'])) {
            return;
        }
        $temp_nonce = wp_unslash($_POST['simesc_meta_nonce']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce value, validated by wp_verify_nonce
        $nonce = sanitize_text_field($temp_nonce);
        if (!wp_verify_nonce($nonce, 'simesc_save_meta')) {
            return;
        }
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        // Save recipients
        if (isset($_POST['simesc_recipients']) && is_array($_POST['simesc_recipients'])) {
            $recipients = array();
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated as array and sanitized in loop below
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated as array and sanitized in loop below
            if (isset($_POST['simesc_recipients']) && is_array($_POST['simesc_recipients'])) {
                $temp_recipients = wp_unslash($_POST['simesc_recipients']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated as array in condition above, unslashed on assignment
                $recipients_raw = $temp_recipients; // Already unslashed
            } else {
                $recipients_raw = array();
            } // Validate as array before unslashing
            foreach ($recipients_raw as $email) {
                $email = sanitize_email($email);
                if (!empty($email) && is_email($email)) {
                    $recipients[] = $email;
                }
            }
            update_post_meta($post_id, '_simesc_recipients', wp_json_encode($recipients));
        }
        // Save subject (required field)
        if (isset($_POST['simesc_subject'])) {
            $subject = sanitize_text_field(wp_unslash($_POST['simesc_subject']));
            if (!empty($subject)) {
                update_post_meta($post_id, '_simesc_subject', $subject);
            } else {
                // If empty, use post title as fallback
                $post = get_post($post_id);
                if ($post) {
                    update_post_meta($post_id, '_simesc_subject', $post->post_title);
                }
            }
        } else {
            // If not set, use post title as default
            $post = get_post($post_id);
            if ($post) {
                update_post_meta($post_id, '_simesc_subject', $post->post_title);
            }
        }
        // Save optional inbox preview / preheader text
        if ( isset( $_POST['simesc_preheader'] ) ) {
            $preheader = sanitize_text_field( wp_unslash( $_POST['simesc_preheader'] ) );
            if ( '' === $preheader ) {
                delete_post_meta( $post_id, '_simesc_preheader' );
            } else {
                update_post_meta( $post_id, '_simesc_preheader', $preheader );
            }
        }
        // Save day of month
        if (isset($_POST['simesc_day_of_month'])) {
            $day = absint(wp_unslash($_POST['simesc_day_of_month']));
            if ($day >= 1 && $day <= 31) {
                update_post_meta($post_id, '_simesc_day_of_month', $day);
            }
        }
        // Save send time
        if (isset($_POST['simesc_send_time'])) {
            update_post_meta($post_id, '_simesc_send_time', sanitize_text_field(wp_unslash($_POST['simesc_send_time'])));
        }
        // Save status (default to 'active' if not set)
        if (isset($_POST['simesc_status'])) {
            $status = sanitize_text_field(wp_unslash($_POST['simesc_status']));
            if (in_array($status, array('active', 'inactive'))) {
                update_post_meta($post_id, '_simesc_status', $status);
            }
        } else {
            // Default to 'active' for new posts
            update_post_meta($post_id, '_simesc_status', 'active');
        }
    }
    /**
     * Add custom columns
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['simesc_subject'] = __('Email Subject', 'simple-email-scheduler');
        $new_columns['simesc_recipients'] = __('Recipients', 'simple-email-scheduler');
        $new_columns['simesc_schedule'] = __('Schedule', 'simple-email-scheduler');
        $new_columns['simesc_status'] = __('Status', 'simple-email-scheduler');
        $new_columns['simesc_last_sent'] = __('Last Sent', 'simple-email-scheduler');
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }
    /**
     * Render custom columns
     */
    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'simesc_subject':
                $subject = get_post_meta($post_id, '_simesc_subject', true);
                if (!empty($subject)) {
                    echo esc_html($subject);
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
            case 'simesc_recipients':
                $recipients = get_post_meta($post_id, '_simesc_recipients', true);
                if (!empty($recipients)) {
                    $recipients_array = json_decode($recipients, true);
                    if (is_array($recipients_array)) {
                        // translators: %d is the number of recipients
                        echo esc_html(sprintf(_n('%d recipient', '%d recipients', count($recipients_array), 'simple-email-scheduler'), count($recipients_array)));
                    } else {
                        echo '—';
                    }
                } else {
                    echo '—';
                }
                break;
            case 'simesc_schedule':
                $day = get_post_meta($post_id, '_simesc_day_of_month', true);
                $time = get_post_meta($post_id, '_simesc_send_time', true);
                if (!empty($day) && !empty($time)) {
                    // translators: %1$s is the day of month, %2$s is the time
                    echo esc_html(sprintf(__('Day %1$s at %2$s', 'simple-email-scheduler'), $day, $time));
                } else {
                    echo '—';
                }
                break;
            case 'simesc_status':
                $status = get_post_meta($post_id, '_simesc_status', true);
                if ($status === 'active') {
                    echo '<span style="color: green;">' . esc_html__('Active', 'simple-email-scheduler') . '</span>';
                } else {
                    echo '<span style="color: gray;">' . esc_html__('Inactive', 'simple-email-scheduler') . '</span>';
                }
                break;
            case 'simesc_last_sent':
                $last_sent = get_post_meta($post_id, '_simesc_last_sent', true);
                echo $last_sent ? esc_html($last_sent) : '—';
                break;
        }
    }
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Enqueue for post edit pages
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            global $post_type;
            if ( SIMESC_POST_TYPE === $post_type ) {
                wp_enqueue_script(
                    'simesc-admin-js',
                    SIMESC_PLUGIN_DIR_URL . 'assets/js/admin.js',
                    array('jquery'),
                    SIMESC_VERSION,
                    true
                );
                // Localize script for AJAX (keep object name stable to avoid JS changes)
                wp_localize_script(
                    'simesc-admin-js',
                    'simescAdmin',
                    array(
                        'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
                        'nonce'        => wp_create_nonce( 'simesc_recipients_nonce' ),
                        'testNonce'    => wp_create_nonce( 'simesc_test_email_nonce' ),
                        'i18n'         => array(
                            'sending'      => __( 'Sending…', 'simple-email-scheduler' ),
                            'sendTest'     => __( 'Send Test / Preview', 'simple-email-scheduler' ),
                            'enterEmail'   => __( 'Please enter a valid test email address.', 'simple-email-scheduler' ),
                            'saveFirst'    => __( 'Please save this email first, then send a test.', 'simple-email-scheduler' ),
                            'errorGeneric' => __( 'An error occurred. Please try again.', 'simple-email-scheduler' ),
                        ),
                    )
                );
            }
        }
        // Enqueue for recipient lists page
        if ( SIMESC_POST_TYPE . '_page_simesc-recipient-lists' === $hook ) {
            wp_enqueue_script(
                'simesc-admin-js',
                SIMESC_PLUGIN_DIR_URL . 'assets/js/admin.js',
                array('jquery'),
                SIMESC_VERSION,
                true
            );
            // Localize script for AJAX (keep object name stable to avoid JS changes)
            wp_localize_script(
                'simesc-admin-js',
                'simescAdmin',
                array(
                    'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                    'nonce'     => wp_create_nonce( 'simesc_recipients_nonce' ),
                    'testNonce' => wp_create_nonce( 'simesc_test_email_nonce' ),
                )
            );
        }
    }

    /**
     * AJAX: send a one-off test/preview of the saved email to an address.
     */
    public function ajax_send_test_email() {
        check_ajax_referer( 'simesc_test_email_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'simple-email-scheduler' ) ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
        $to      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

        if ( ! $post_id || SIMESC_POST_TYPE !== get_post_type( $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid email post.', 'simple-email-scheduler' ) ) );
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'simple-email-scheduler' ) ) );
        }

        if ( ! is_email( $to ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a valid test email address.', 'simple-email-scheduler' ) ) );
        }

        $sender = new SIMESC_Email_Sender();
        $sent   = $sender->send_test_email( $post_id, $to );

        if ( $sent ) {
            wp_send_json_success(
                array(
                    /* translators: %s: recipient email */
                    'message' => sprintf( __( 'Test email sent to %s.', 'simple-email-scheduler' ), $to ),
                )
            );
        }

        wp_send_json_error( array( 'message' => __( 'Failed to send test email. Check your mail configuration.', 'simple-email-scheduler' ) ) );
    }
    /**
     * AJAX handler: Save recipients list
     */
    public function ajax_save_recipients() {
        check_ajax_referer('simesc_recipients_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'simple-email-scheduler')));
        }
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated with isset() and sanitized below
        if (isset($_POST['name'])) {
            $temp_name = wp_unslash($_POST['name']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated with isset() above
            $name = sanitize_text_field($temp_name);
        } else {
            $name = '';
        }
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated as array and sanitized in loop below

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated as array and sanitized in loop below
        if (isset($_POST['recipients']) && is_array($_POST['recipients'])) {
                    $temp_recipients = wp_unslash($_POST['recipients']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated as array in condition above, unslashed on assignment
                    $recipients_raw = $temp_recipients; // Already unslashed
        } else {
            $recipients_raw = array();
        }
        if (empty($name)) {
            wp_send_json_error(array('message' => __('List name cannot be empty.', 'simple-email-scheduler')));
        }
        if (!is_array($recipients_raw) || empty($recipients_raw)) {
            wp_send_json_error(array('message' => __('No recipients provided.', 'simple-email-scheduler')));
        }
        // Sanitize recipients
        $recipients = array();
        foreach ($recipients_raw as $email) {
            $email = sanitize_email($email);
            if (!empty($email) && is_email($email)) {
                $recipients[] = $email;
            }
        }
        if (empty($recipients)) {
            wp_send_json_error(array('message' => __('No valid recipients provided.', 'simple-email-scheduler')));
        }
        // Get existing lists
        $saved_lists = get_option('simesc_saved_recipients', array());
        // Generate unique key
        $key = sanitize_key($name);
        $counter = 1;
        while (isset($saved_lists[$key])) {
            $key = sanitize_key($name) . '-' . $counter;
            $counter++;
        }
        // Save list
        $saved_lists[$key] = array(
            'name' => $name,
            'recipients' => $recipients,
        );
        if (update_option('simesc_saved_recipients', $saved_lists)) {
            wp_send_json_success(array('message' => __('Recipient list saved successfully.', 'simple-email-scheduler')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save recipient list.', 'simple-email-scheduler')));
        }
    }
    /**
     * AJAX handler: Load recipients list
     */
    public function ajax_load_recipients() {
        check_ajax_referer('simesc_recipients_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'simple-email-scheduler')));
        }
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated with isset() and sanitized below
        if (isset($_POST['key'])) {
            $temp_key = wp_unslash($_POST['key']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated with isset() above
            $key = sanitize_text_field($temp_key);
        } else {
            $key = '';
        }
        if (empty($key)) {
            wp_send_json_error(array('message' => __('No list selected.', 'simple-email-scheduler')));
        }
        $saved_lists = get_option('simesc_saved_recipients', array());
        if (!isset($saved_lists[$key])) {
            wp_send_json_error(array('message' => __('Recipient list not found.', 'simple-email-scheduler')));
        }
        wp_send_json_success(array(
            'recipients' => $saved_lists[$key]['recipients'],
            'name' => $saved_lists[$key]['name'],
        ));
    }
    /**
     * AJAX handler: Delete recipients list
     */
    public function ajax_delete_recipients() {
        check_ajax_referer('simesc_recipients_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'simple-email-scheduler')));
        }
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated with isset() and sanitized below
        if (isset($_POST['key'])) {
            $temp_key = wp_unslash($_POST['key']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated with isset() above
            $key = sanitize_text_field($temp_key);
        } else {
            $key = '';
        }
        if (empty($key)) {
            wp_send_json_error(array('message' => __('No list selected.', 'simple-email-scheduler')));
        }
        $saved_lists = get_option('simesc_saved_recipients', array());
        if (!isset($saved_lists[$key])) {
            wp_send_json_error(array('message' => __('Recipient list not found.', 'simple-email-scheduler')));
        }
        unset($saved_lists[$key]);
        if (update_option('simesc_saved_recipients', $saved_lists)) {
            wp_send_json_success(array('message' => __('Recipient list deleted successfully.', 'simple-email-scheduler')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete recipient list.', 'simple-email-scheduler')));
        }
    }
    /**
     * Render recipient lists management page
     */
    public function render_recipient_lists_page() {
        // Handle form submission
        if (isset($_POST['simesc_save_list']) && check_admin_referer('simesc_save_recipient_list', 'simesc_recipient_list_nonce')) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated with isset() and sanitized below
            if (isset($_POST['list_name'])) {
                $temp_list_name = wp_unslash($_POST['list_name']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated with isset() above
                $list_name = sanitize_text_field($temp_list_name);
            } else {
                $list_name = '';
            }
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated as array and sanitized in loop below

            if (isset($_POST['recipients']) && is_array($_POST['recipients'])) {
                        $temp_recipients = wp_unslash($_POST['recipients']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated as array in condition above, unslashed on assignment
                        $recipients_raw = $temp_recipients;
            } else {
                $recipients_raw = array();
            }
            if (!empty($list_name) && is_array($recipients_raw) && !empty($recipients_raw)) {
                $recipients = array();
                foreach ($recipients_raw as $email) {
                    $email = sanitize_email($email);
                    if (!empty($email) && is_email($email)) {
                        $recipients[] = $email;
                    }
                }
                if (!empty($recipients)) {
                    $saved_lists = get_option('simesc_saved_recipients', array());
                    $key = sanitize_key($list_name);
                    $counter = 1;
                    while (isset($saved_lists[$key])) {
                        $key = sanitize_key($list_name) . '-' . $counter;
                        $counter++;
                    }
                    $saved_lists[$key] = array(
                        'name' => $list_name,
                        'recipients' => $recipients,
                    );
                    update_option('simesc_saved_recipients', $saved_lists);
                    echo '<div class="notice notice-success"><p>' . esc_html__('Recipient list saved successfully!', 'simple-email-scheduler') . '</p></div>';
                }
            }
        }
        // Handle deletion
        if (isset($_GET['delete']) && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'delete_recipient_list_' . sanitize_text_field(wp_unslash($_GET['delete'])))) {
            $key = sanitize_text_field(wp_unslash($_GET['delete']));
            $saved_lists = get_option('simesc_saved_recipients', array());
            if (isset($saved_lists[$key])) {
                unset($saved_lists[$key]);
                update_option('simesc_saved_recipients', $saved_lists);
                echo '<div class="notice notice-success"><p>' . esc_html__('Recipient list deleted successfully!', 'simple-email-scheduler') . '</p></div>';
            }
        }
        $saved_lists = get_option('simesc_saved_recipients', array());
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Recipient Lists', 'simple-email-scheduler'); ?></h1>
            <p><?php esc_html_e('Manage your saved recipient lists. These lists can be reused across multiple automated emails.', 'simple-email-scheduler'); ?></p>
            <div style="display: flex; gap: 20px; margin-top: 20px;">
                <div style="flex: 1;">
                    <h2><?php esc_html_e('Add New List', 'simple-email-scheduler'); ?></h2>
                    <form method="post" action="">
                        <?php wp_nonce_field('simesc_save_recipient_list', 'simesc_recipient_list_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th><label for="list_name"><?php esc_html_e('List Name', 'simple-email-scheduler'); ?></label></th>
                                <td>
                                    <input type="text" id="list_name" name="list_name" class="regular-text" required />
                                </td>
                            </tr>
                            <tr>
                                <th><label for="recipients"><?php esc_html_e('Recipients', 'simple-email-scheduler'); ?></label></th>
                                <td>
                                    <div id="mea-admin-recipients-container">
                                        <div class="mea-recipient-row" style="margin-bottom: 5px;">
                                            <input type="email" name="recipients[]" class="regular-text" placeholder="email@example.com" />
                                            <button type="button" class="button mea-remove-recipient"><?php esc_html_e('Remove', 'simple-email-scheduler'); ?></button>
                                        </div>
                                    </div>
                                    <button type="button" class="button" id="mea-admin-add-recipient"><?php esc_html_e('Add Recipient', 'simple-email-scheduler'); ?></button>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" name="simesc_save_list" class="button button-primary" value="<?php esc_attr_e('Save List', 'simple-email-scheduler'); ?>" />
                        </p>
                    </form>
                </div>
                <div style="flex: 1;">
                    <h2><?php esc_html_e('Saved Lists', 'simple-email-scheduler'); ?></h2>
                    <?php if (empty($saved_lists)): ?>
                        <p><?php esc_html_e('No recipient lists saved yet.', 'simple-email-scheduler'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('List Name', 'simple-email-scheduler'); ?></th>
                                    <th><?php esc_html_e('Recipients', 'simple-email-scheduler'); ?></th>
                                    <th><?php esc_html_e('Actions', 'simple-email-scheduler'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($saved_lists as $key => $list): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($list['name']); ?></strong></td>
                                        <td>
                                            <?php echo count($list['recipients']); ?> <?php esc_html_e('recipients', 'simple-email-scheduler'); ?>
                                            <details style="margin-top: 5px;">
                                                <summary style="cursor: pointer; color: #0073aa;"><?php esc_html_e('View emails', 'simple-email-scheduler'); ?></summary>
                                                <ul style="margin: 5px 0 0 20px;">
                                                    <?php foreach ($list['recipients'] as $email): ?>
                                                        <li><?php echo esc_html($email); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </details>
                                        </td>
                                        <td>
                                            <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('delete' => $key)), 'delete_recipient_list_' . $key)); ?>" 
                                               class="button button-small" 
                                               onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this list?', 'simple-email-scheduler'); ?>');">
                                                <?php esc_html_e('Delete', 'simple-email-scheduler'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}


