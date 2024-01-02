<?php
/*
Plugin Name: TNN Registration
Description: A custom registration form.
Version: 1.0
Author: Your Name
*/

// Enqueue scripts and localize data for AJAX requests
add_action('wp_enqueue_scripts', 'tnn_reg_enqueue_scripts');

function tnn_reg_enqueue_scripts() {
    wp_enqueue_script('tnn-reg-scripts', plugin_dir_url(__FILE__) . 'tnn-reg-scripts.js', array('jquery'), '1.0', true);

    // Enqueue your CSS file
    wp_enqueue_style('tnn-reg-styles', plugin_dir_url(__FILE__) . 'tnn-reg-styles.css');

    // Pass the admin-ajax URL to the script
    wp_localize_script('tnn-reg-scripts', 'tnn_reg_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}

register_activation_hook(__FILE__, 'tnn_reg_activate');
register_deactivation_hook(__FILE__, 'tnn_reg_deactivate');

function tnn_reg_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tnn_registration';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        firstname varchar(255) NOT NULL,
        surname varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        phone varchar(11) NOT NULL UNIQUE,  -- 11 digits
        dob date NOT NULL,
        occupation varchar(255) NOT NULL,
        state varchar(255) NOT NULL,
        local_government varchar(255) NOT NULL,
        unique_id varchar(255) NOT NULL,
        photo_path varchar(255),
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function tnn_reg_deactivate() {
    // Deactivation tasks, if any.
}

function tnn_reg_form_shortcode($atts) {
    $selected_local_government = isset($atts['local_government']) ? $atts['local_government'] : '';
    ob_start();
    include(plugin_dir_path(__FILE__) . 'registration-form.php');
    return ob_get_clean();
}
add_shortcode('tnn_reg_form', 'tnn_reg_form_shortcode');


function get_local_governments_by_state($state_id) {
    global $wpdb;
    $local_governments_table = $wpdb->prefix . 'local_governments';
    
    $local_governments = $wpdb->get_col(
        $wpdb->prepare("SELECT local_government_name FROM $local_governments_table WHERE state_id = %d", $state_id)
    );

    return $local_governments;
}

add_action('wp_ajax_get_local_governments', 'get_local_governments');
add_action('wp_ajax_nopriv_get_local_governments', 'get_local_governments');

function get_local_governments() {
    if (isset($_POST['state_id'])) {
        $state_id = intval($_POST['state_id']);
        global $wpdb;
        $local_governments_table = $wpdb->prefix . 'local_governments';

        $local_governments = $wpdb->get_col(
            $wpdb->prepare("SELECT local_government_name FROM $local_governments_table WHERE state_id = %d", $state_id)
        );

        $options = '<option value="">Select a Local Government</option>';
        foreach ($local_governments as $lg) {
            $options .= '<option value="' . esc_attr($lg) . '">' . esc_html($lg) . '</option>';
        }

        echo $options;
    }

    wp_die();
}

function tnn_reg_handle_form_submission() {
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit"])) {
        $firstname = sanitize_text_field($_POST["firstname"]);
        $surname = sanitize_text_field($_POST["surname"]);
        $email = sanitize_email($_POST["email"]);
        $phone = sanitize_text_field($_POST["phone"]);
        $dob = sanitize_text_field($_POST["dob"]);
        $occupation = sanitize_text_field($_POST["occupation"]);
        $state_id = intval($_POST["state"]);
        $local_government = sanitize_text_field($_POST["local_government"]);

        // Validate phone number (11 digits)
        if (!preg_match('/^\d{11}$/', str_replace(' ', '', $phone))) {
            // Check if the form data contains any errors (e.g., invalid phone number)
            echo '<script>alert("Invalid phone number. Please enter an 11-digit phone number.");</script>';
            // Prevent the form from submitting
            echo '<script>event.preventDefault();</script>';
            return; // Exit the function to prevent further processing
        }

        // Check if the phone number already exists in the database
        if (tnn_reg_phone_exists($phone)) {
            // Redirect to a specific page (change the URL to the page you want)
            wp_redirect(home_url('/phone-exists-page'));
            exit;
        }

        // Handle photo upload
        $upload_dir = wp_upload_dir();
        $file = $_FILES['photo'];

        if ($file) {
            $file_name = sanitize_file_name($file['name']);
            $file_path = $upload_dir['path'] . '/' . $file_name;
            move_uploaded_file($file['tmp_name'], $file_path);

            // Store the image URL in the database
            $file_url = $upload_dir['url'] . '/' . $file_name;
        } else {
            $file_path = '';
            $file_url = '';
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'tnn_registration';

        // Retrieve state_name from the states table
        $state_name = $wpdb->get_var(
            $wpdb->prepare("SELECT state_name FROM {$wpdb->prefix}states WHERE state_id = %d", $state_id)
        );

        // Generate a unique ID with the format: TNN/state_abv/local_government_abv/serialnumber
        $state_abv = get_state_abv($state_id);
        $local_government_abv = get_local_government_abv($local_government);
        $new_serial_number = tnn_reg_generate_unique_number();
        $unique_id = 'TNN/' . $state_abv . '/' . $local_government_abv . '/' .$new_serial_number;

        $data = array(
            'firstname' => $firstname,
            'surname' => $surname,
            'email' => $email,
            'phone' => $phone,
            'dob' => $dob,
            'occupation' => $occupation,
            'state' => $state_name,
            'local_government' => $local_government,
            'unique_id' => $unique_id,
            'photo_path' => $file_url
        );

        $wpdb->insert($table_name, $data);
$state_name = $wpdb->get_var(
        $wpdb->prepare("SELECT state_name FROM {$wpdb->prefix}states WHERE state_id = %d", $state_id)
    );
        
       //This is what sends
       
        $to = "info@teamnewnigeria.com"; // this is your Email address
    $from = $_POST['email']; // this is the sender's Email address
        $subject = "Form submission";
        $subject2 = "Registration Form";
        $message = " Your  registration number is " . $unique_id. ". Your participation is greatly appreciated, and we are excited to have you join us. Please find below the details you provided during the registration process:" . "\n\n"; // Include other form fields in the message

$state_name = $wpdb->get_var(
    $wpdb->prepare("SELECT state_name FROM {$wpdb->prefix}states WHERE state_id = %d", $state_id)
);

// Define an array of fields you want to include in the email
$fields_to_include = array(
    'unique_id' => 'TNN Number',
    'firstname' => 'First Name',
    'surname' => 'Last Name',
    'email' => 'Email',
    'phone' => 'Phone',
    'dob' => 'Date of Birth',
    'occupation' => 'Occupation',
    'state' => 'State',
    'local_government' => 'Local Government',

);

  // Constructing the email content with HTML styling
        $message = "
        <html>
        <head>
          <title>Registration Details</title>
          <style>
            /* Define your custom styles here */
            body {
              font-family: Arial, sans-serif;
              background-color: #f4f4f4;
              padding: 20px;
            }
            .container {
              max-width: 600px;
              margin: 0 auto;
              background-color: #fff;
              padding: 20px;
              border-radius: 8px;
              box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
            h1 {
              color: #333;
            }
            table {
              width: 100%;
              border-collapse: collapse;
              margin-top: 20px;
            }
            th, td {
              padding: 8px;
              text-align: left;
              border-bottom: 1px solid #ddd;
            }
          </style>
        </head>
        <body>
          <div class='container'>
            <h1>Registration Details</h1>
            <p>Your registration number is <strong>{$unique_id}</strong>. Your participation is greatly appreciated, and we are excited to have you join us. Below are the details you provided during the registration process:</p>
            <table>
              <tr>
                <th></th>
                <th></th>
              </tr>";

        // Loop through form fields to include in the HTML email
        foreach ($fields_to_include as $field_key => $field_label) {
            // Fetch field values (adjusted for state and unique ID)
            if ($field_key === 'state') {
                $field_value = $state_name; // Use the retrieved state name
            } elseif ($field_key === 'unique_id') {
                $field_value = $unique_id; // Include the unique_id field
            } else {
                $field_value = isset($_POST[$field_key]) ? $_POST[$field_key] : ''; // Fetch other form field values
            }

            // Add each field and its value to the HTML table
            $message .= "<tr><td>{$field_label}</td><td>{$field_value}</td></tr>";
        }

        $message .= "
            </table>
            <p>Thank you for registering with us!</p>
          </div>
        </body>
        </html>";

        // Send email with HTML content and custom styling
        $to = "info@teamnewnigeria.com"; // Replace with recipient's email address
        $from = $_POST['email']; // Replace with sender's email address
        $subject = "Form submission";
        $headers = "From:" . $to . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        // Send email to recipient
        mail($to, $subject, $message, $headers);

        // Send a copy of the message to the sender
        mail($from, $subject, $message, $headers);

        wp_redirect(home_url('/thank-you'));
        exit;
    }
}


function tnn_reg_phone_exists($phone) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tnn_registration';
    $existing_phone = $wpdb->get_var($wpdb->prepare("SELECT phone FROM $table_name WHERE phone = %s", $phone));
    return !empty($existing_phone);
}


function get_state_abv($state_id) {
    global $wpdb;
    $states_table = $wpdb->prefix . 'states';

    $state_abv = $wpdb->get_var(
        $wpdb->prepare("SELECT state_abv FROM $states_table WHERE state_id = %d", $state_id)
    );

    return $state_abv;
}

function get_local_government_abv($local_government_name) {
    global $wpdb;
    $local_governments_table = $wpdb->prefix . 'local_governments';

    $local_government_abv = $wpdb->get_var(
        $wpdb->prepare("SELECT local_government_abv FROM $local_governments_table WHERE local_government_name = %s", $local_government_name)
    );

    return $local_government_abv;
}

function tnn_reg_generate_unique_number() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tnn_registration';

    // Count the number of existing records in the registration table
    $count = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

    $serial_number = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

    $unique_number = "$serial_number";

    return $unique_number;
}

add_action('init', 'tnn_reg_handle_form_submission');
