    jQuery(document).ready(function ($) {
    
        $('body').append('<div class="dm_loading_panel"><div class="dm_spinner"></div></div>');

        $('body').on('click', '.dm_open_modal', function (e) {
            e.preventDefault();

            var order_id = $(this).data('order-id');

            // Show the loading panel
            $('.dm_loading_panel').fadeIn(300);
            $('.dm_loading_panel').css('display', 'flex');

            $.ajax({
                url: dm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dm_show_confirm_modal',
                    security: dm_ajax.nonce,
                    order_id: order_id,
                },
                success: function (response) {
                    if (response.success) {
                        $('body').append(response.data);
                        $('.dm_modal_wrapper').fadeIn(300);
                        $('.dm_modal_wrapper').css('display', 'flex');
                    } else {
                        console.error('Error: ', response.data);
                    }
                    $('.dm_loading_panel').fadeOut(300);
                },
                error: function () {
                    $('.dm_loading_panel').fadeOut(300);
                }
            });
        });
    
    $('body').on('click', '.dm_confirm_action', function (e) {
        e.preventDefault();
        var form = $('#dm_order_details_form');
    
        var orderData = {
            reference: form.find('input[name="reference"]').val(),
            customer_name: form.find('input[name="customer_name"]').val(),
            customer_address: form.find('input[name="customer_address"]').val(),
            house_number: form.find('input[name="house_number"]').val(),
            city: form.find('input[name="city"]').val(),
            zip_code: form.find('input[name="zip_code"]').val(),
            country: form.find('input[name="country"]').val(),
            contact_person: form.find('input[name="contact_person"]').val(),
            phone: form.find('input[name="phone"]').val(),
            email: form.find('input[name="email"]').val(),
            payment_method: form.find('input[name="payment_method"]').val(),
            collection_date: form.find('input[name="collection_date"]').val(),
            weight: form.find('input[name="weight"]').val(),
            package_number: form.find('input[name="package_number"]').val(),
            cod_amount: form.find('input[name="cod_amount"]').val(),
            note: form.find('textarea[name="note"]').val(),
        };
    
        console.log(orderData, 'orderData')
    
        // Make your API call here with the updated orderData object
        // For example:
        $.ajax({
            url: `https://easyship.hr/api/parcel/parcel_import?username=${dm_options.username}&password=${dm_options.password}&name1=${orderData.customer_name}&street=${orderData.customer_address}&rPropNum=${orderData.house_number}&city=${orderData.city}&country=${orderData.country}&pcode=${orderData.zip_code}&email=${orderData.email}&phone=${orderData.phone}&sender_remark=${orderData.note}&weight=${orderData.weight}&order_number=${orderData.reference}&parcel_type=D&num_of_parcel=${orderData.package_number}`,
            type: "POST",
            //data: orderData,
            success: function (response) {
              // Handle success
              console.log(response, 'adresnica')
              $.ajax({
                url: `https://easyship.hr/api/parcel/parcel_print?username=${dm_options.username}&password=${dm_options.password}&parcels=${response.pl_number[0]}`,
                type: "POST",
                xhrFields: {
                  responseType: 'blob'
                },
                //data: orderData,
                success: function (res) {
                  console.log(res, 'label');
                  // Handle success
                  var blob = new Blob([res], { type: "application/pdf" });
      
                  // Generate a unique filename for the PDF
                  var filename = "your-plugin-" + Date.now() + ".pdf";
      
                  // Create a temporary link element
                  var link = document.createElement("a");
                  link.href = window.URL.createObjectURL(blob);
                  link.download = filename;
      
                  // Programmatically click the link to trigger the download
                  link.click();
                  // Clean up the temporary link element
                  link.remove();
                },
                error: function (error) {
                  // Handle error
                  console.error("API call failed: ", error);
                },
              });
            },
            error: function (error) {
                // Handle error
                console.error("API call failed: ", error);
            },
        });
    
        $('.dm_modal_wrapper').fadeOut(300, function () {
            $(this).remove();
        });
    });

    $('body').on('click', '.dm_confirm_action, .dm_cancel_action', function () {
        $('.dm_modal_wrapper').fadeOut(300, function () {
            $(this).remove();
        });
    });

    $('body').on('click', '.dm_cancel_action', function (e) {
        e.preventDefault();
    });

    $('#dm-settings-form').on('submit', function (e) {
        e.preventDefault();

        // Get the username and password from the form
        const username = $('input[name="dm_username"]').val();
        const password = $('input[name="dm_password"]').val();

        // Perform the API call
        $.ajax({
            url: 'https://easyship.hr/api/parcel/parcel_import',
            type: 'POST',
            data: {
                username: username,
                password: password,
            },
            success: function (response) {
                // Handle success
                console.log("API call successful: ", response);

                // Proceed with the form submission
                $('#dm-settings-form').off('submit').submit();
            },
            error: function (error) {
                // Handle error
                console.error("API call failed: ", error);
            },
        });
    });
});