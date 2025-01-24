<?php
/**
 * Plugin Name: Contact Form Manager
 * Description: A custom plugin for managing contact form entries, sending summary emails, and deleting entries.
 * Version: 1.0
 * Author: Your Name
 * License: GPL2
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Register the cron job
function cfm_register_daily_contact_summary_cron() {
    if (!wp_next_scheduled('cfm_daily_contact_summary_cron')) {
        wp_schedule_event(time(), 'daily', 'cfm_daily_contact_summary_cron');
    }
}
add_action('wp', 'cfm_register_daily_contact_summary_cron');

// Handle the daily summary email
add_action('cfm_daily_contact_summary_cron', 'cfm_send_daily_contact_summary');

function cfm_send_daily_contact_summary() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_entries';

    // Get yesterday's date
    $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));

    // Fetch entries submitted in the last 24 hours
    $new_entries = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_name WHERE submitted_at > %s", $yesterday)
    );

    // If no new entries, exit
    if (empty($new_entries)) {
        return;
    }

    // Prepare the email
    $to = get_option('admin_email');
    $subject = 'Daily Contact Form Summary';
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    $body = '<h1>New Contact Form Entries</h1>';
    $body .= '<table border="1" cellspacing="0" cellpadding="5">';
    $body .= '<thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Message</th>
                    <th>Submitted At</th>
                </tr>
              </thead>
              <tbody>';
    foreach ($new_entries as $entry) {
        $body .= '<tr>
                    <td>' . esc_html($entry->name) . '</td>
                    <td>' . esc_html($entry->email) . '</td>
                    <td>' . esc_html($entry->message) . '</td>
                    <td>' . esc_html($entry->submitted_at) . '</td>
                  </tr>';
    }
    $body .= '</tbody></table>';

    // Send the email
    wp_mail($to, $subject, $body, $headers);
}

// Create the contact entries table
function cfm_create_contact_entries_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_entries';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        submitted_at DATETIME NOT NULL
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'cfm_create_contact_entries_table');

// Clean up the cron job on plugin deactivation
function cfm_unregister_daily_contact_summary_cron() {
    $timestamp = wp_next_scheduled('cfm_daily_contact_summary_cron');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'cfm_daily_contact_summary_cron');
    }
}
register_deactivation_hook(__FILE__, 'cfm_unregister_daily_contact_summary_cron');

// Display the contact entries in admin
function cfm_display_contact_entries() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_entries';
    $entries = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submitted_at DESC");

    echo '<div class="wrap"><h1>Contact Form Entries</h1>';
    echo '<table class="widefat fixed" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Message</th>
                <th>Submitted At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($entries as $entry) {
        $delete_url = wp_nonce_url(
            admin_url('admin-post.php?action=cfm_delete_contact_entry&id=' . $entry->id),
            'cfm_delete_contact_entry_' . $entry->id
        );

        echo '<tr>
            <td>' . esc_html($entry->id) . '</td>
            <td>' . esc_html($entry->name) . '</td>
            <td>' . esc_html($entry->email) . '</td>
            <td>' . esc_html($entry->message) . '</td>
            <td>' . esc_html($entry->submitted_at) . '</td>
            <td><a href="' . esc_url($delete_url) . '" class="button button-small" onclick="return confirm(\'Are you sure you want to delete this entry?\')">X</a></td>
        </tr>';
    }
    
    echo '</tbody></table></div>';
}

// Handle the delete action
add_action('admin_post_cfm_delete_contact_entry', 'cfm_delete_contact_entry');

function cfm_delete_contact_entry() {
    // Verify nonce for security
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'cfm_delete_contact_entry_' . $id)) {
        wp_die('Unauthorized request.');
    }

    if ($id > 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contact_entries';

        // Delete the entry
        $wpdb->delete($table_name, ['id' => $id], ['%d']);
    }

    // Redirect back to the admin page
    wp_redirect(admin_url('admin.php?page=cfm-contact-entries'));
    exit;
}

// Add the admin menu for the contact entries page
function cfm_register_contact_entries_page() {
    add_menu_page(
        'Contact Form Entries',
        'Contact Entries',
        'manage_options',
        'cfm-contact-entries',
        'cfm_display_contact_entries',
        'dashicons-email',
        20
    );
}
add_action('admin_menu', 'cfm_register_contact_entries_page');

// Add the admin styles (optional)
function cfm_admin_styles() {
    echo '<style>
        a.button-small {
            background-color: #d63638;
            color: #fff;
            border-color: #d63638;
        }
        a.button-small:hover {
            background-color: #a6282b;
            border-color: #a6282b;
        }
    </style>';
}
add_action('admin_head', 'cfm_admin_styles');
register_activation_hook(__FILE__, 'cfm_create_contact_entries_table');

function cfm_enqueue_admin_styles() {
  wp_enqueue_style('cfm-admin-style', plugin_dir_url(__FILE__) . 'admin.css');
}
add_action('admin_enqueue_scripts', 'cfm_enqueue_admin_styles');

// Contact Form Shortcode
function cfm_contact_form_shortcode() {
  // Check if the form is submitted
  if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cfm_contact_form_submitted'])) {
      cfm_handle_form_submission();
  }

  ob_start(); // Start capturing the output for the shortcode

  ?>
  <form method="POST" action="">
      <p>
          <label for="name">Name <span style="color:red">*</span></label><br>
          <input type="text" name="cfm_name" id="name" required>
      </p>
      <p>
          <label for="email">Email <span style="color:red">*</span></label><br>
          <input type="email" name="cfm_email" id="email" required>
      </p>
      <p>
          <label for="message">Message <span style="color:red">*</span></label><br>
          <textarea name="cfm_message" id="message" rows="5" required></textarea>
      </p>
      <p>
          <input type="submit" value="Send Message">
      </p>
      <input type="hidden" name="cfm_contact_form_submitted" value="1">
  </form>

  <?php
  // Output a success message if the form was submitted successfully
  if (isset($GLOBALS['cfm_submission_success']) && $GLOBALS['cfm_submission_success']) {

      wp_safe_redirect('/thank-you');
      die();
  }

  // Output a failure message if there was an error
  if (isset($GLOBALS['cfm_submission_error']) && $GLOBALS['cfm_submission_error']) {
      echo '<p><strong style="color:red">There was an error submitting the form. Please try again.</strong></p>';
  }

  return ob_get_clean(); // Return the captured output
}
add_shortcode('cfm_contact_form', 'cfm_contact_form_shortcode');
// Handle the form submission and save the data
function cfm_handle_form_submission() {
  // Sanitize and validate inputs
  $name    = sanitize_text_field($_POST['cfm_name']);
  $email   = sanitize_email($_POST['cfm_email']);
  $message = sanitize_textarea_field($_POST['cfm_message']);

  // Check if the inputs are valid
  if (empty($name) || empty($email) || empty($message) || !is_email($email)) {
      $GLOBALS['cfm_submission_error'] = true;
      return;
  }

  // Insert the entry into the database
  global $wpdb;
  $table_name = $wpdb->prefix . 'contact_entries';
  $wpdb->insert(
      $table_name,
      [
          'name'        => $name,
          'email'       => $email,
          'message'     => $message,
          'submitted_at' => current_time('mysql')
      ],
      ['%s', '%s', '%s', '%s']
  );

  // If the data is inserted successfully, set success flag
  if ($wpdb->insert_id) {
      $GLOBALS['cfm_submission_success'] = true;
  } else {
      $GLOBALS['cfm_submission_error'] = true;
  }
}