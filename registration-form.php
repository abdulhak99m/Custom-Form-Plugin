<style>
    /* Apply styles to labels and inputs */
    label, input, select {
        font-size: 12px;
    }
</style>


<form id="tnn-registration-form" action="" method="post" enctype="multipart/form-data">
    <label for="firstname">First Name:</label>
    <input type="text" name="firstname" required value="<?php echo esc_attr($firstname); ?>"><br>

    <label for="surname">Surname:</label>
    <input type="text" name="surname" required value="<?php echo esc_attr($surname); ?>"><br>

    <label for="email">Email:</label>
    <input type="email" name="email" required value="<?php echo esc_attr($email); ?>"><br>

    <label for="phone">Phone Number:</label>
    <input type="text" name="phone" required value="<?php echo esc_attr($phone); ?>"><br>

    <label for="dob">Date of Birth:</label>
    <input type="date" name="dob" required value="<?php echo esc_attr($dob); ?>"><br>

    <label for="occupation">Occupation:</label>
    <select name="occupation" id="occupation" required>
        <option value="">Select an Occupation</option>
        <?php
        $occupations = array(
            'Healthcare',
            'Education',
            'Legal and Law Enforcement',
            'Engineering and Technical',
            'Business and Finance',
            'Agriculture',
            'Government and Civil Service',
            'Service and Hospitality',
            'Arts and Culture',
            'Security and Safety',
            'Transportation'
        );

        foreach ($occupations as $occupation) {
            $selected = ($occupation === $selected_occupation) ? 'selected' : ''; // Add selected attribute if the occupation matches the submitted value
            echo "<option value='{$occupation}' $selected>{$occupation}</option>";
        }
        ?>
    </select><br>

    <label for="state">State Of Origin:</label>
    <select name="state" id="state" required>
        <option value="">Select a State</option>
        <?php
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        $states_table = $table_prefix . 'states';

        $states = $wpdb->get_results("SELECT state_id, state_name FROM $states_table", ARRAY_A);

        foreach ($states as $state) {
            $selected = ($state['state_id'] === $selected_state) ? 'selected' : ''; // Add selected attribute if the state ID matches the submitted value
            echo "<option value='{$state['state_id']}' $selected>{$state['state_name']}</option>";
        }
        ?>
    </select><br>

    <label for="local_government">Local Government:</label>
    <select name="local_government" id="local_government" required>
        <option value="">Select a Local Government</option>
        <?php
        $selected_local_government = isset($_POST['local_government']) ? $_POST['local_government'] : ''; // Get the selected value from the form submission

        foreach ($local_governments as $lg) {
            $selected = ($lg === $selected_local_government) ? 'selected' : ''; // Check if the option should be selected
            echo "<option value='{$lg}' $selected>{$lg}</option>";
        }
        ?>
    </select><br>

    <label for="photo">Photo:</label>
    <input type="file" name="photo" accept="image/*" required><br>

    <input type="submit" name="submit" value="Submit">
</form>


<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit"]) && !preg_match('/^\d{11}$/', str_replace(' ', '', $phone))) {
    echo '<div id="error-alert" style="color: red;">Invalid phone number. Please enter an 11-digit phone number.</div>';
}
?>

<script>
    document.getElementById('tnn-registration-form').addEventListener('submit', function (event) {
        var phoneInput = document.getElementsByName('phone')[0];
        var phoneValue = phoneInput.value.replace(/\s/g, ''); // Remove white spaces

        if (!(/^\d{11}$/.test(phoneValue))) {
            event.preventDefault(); // Prevent form submission
            var errorAlert = document.getElementById('error-alert');
            if (errorAlert) {
                errorAlert.style.display = 'block';
            }
            return false;
        }
    });
</script>

<script>
    // Remove the error alert message after a short delay
    setTimeout(function () {
        var errorAlert = document.getElementById('error-alert');
        if (errorAlert) {
            errorAlert.style.display = 'none';
        }
    }, 5000); // Adjust the delay time (in milliseconds) as needed
</script>

