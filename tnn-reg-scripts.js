jQuery(document).ready(function($) {
    $("#state").change(function() {
        var selectedStateID = $(this).val();
        console.log('Selected State ID: ' + selectedStateID); // Log the selected state ID
        if (selectedStateID !== "") {
            $.ajax({
                type: 'POST',
                url: tnn_reg_ajax.ajax_url,
                data: {
                    action: 'get_local_governments',
                    state_id: selectedStateID
                },
                success: function(response) {
                    console.log('AJAX Response: ' + response); // Log the AJAX response
                    $("#local_government").html(response);
                }
            });
        } else {
            $("#local_government").html('<option value="">Select a Local Government</option>');
        }
    });
});
