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
    
    $.ajax({
        url: `https://easyship.hr/api/parcel/parcel_import?username=${dm_options.username}&password=${dm_options.password}&name1=${orderData.customer_name}&street=${orderData.customer_address}&rPropNum=${orderData.house_number}&city=${orderData.city}&country=${orderData.country}&pcode=${orderData.zip_code}&email=${orderData.email}&phone=${orderData.phone}&sender_remark=${orderData.note}&weight=${orderData.weight}&order_number=${orderData.reference}&parcel_type=D&num_of_parcel=${orderData.package_number}`,
        type: "POST",
        success: function (response) {
          update_adresnica(orderData.reference, `HR-DPD-${response.pl_number[0]}`);

          $.ajax({
            url: `https://easyship.hr/api/parcel/parcel_print?username=${dm_options.username}&password=${dm_options.password}&parcels=${response.pl_number[0]}`,
            type: "POST",
            xhrFields: {
              responseType: 'blob'
            },
            success: function (res) {
              var blob = new Blob([res], { type: "application/pdf" });
              var filename = `${customer_name}-${response.pl_number[0]}-${Date.now()}.pdf`;
              var link = document.createElement("a");

              link.href = window.URL.createObjectURL(blob);
              link.download = filename;
              link.click();
              link.remove();
            },
            error: function (error) {
              console.error("API call failed: ", error);
            },
          });
        },
        error: function (error) {
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

function update_adresnica(order_id, pl_number) {
    $.ajax({
        url: dm_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'dm_update_adresnica',
            security: dm_ajax.nonce,
            order_id: order_id,
            pl_number: pl_number,
        },
        success: function (response) {
            if (response.success) {
                console.log('Adresnica updated successfully.');
            } else {
                console.error('Error updating Adresnica: ', response.data);
            }
        },
        error: function () {
            console.error('Error updating Adresnica.');
        }
    });
}
});