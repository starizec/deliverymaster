jQuery(document).ready(function ($) {
  $("body").append(
    '<div class="dm_loading_panel"><div class="dm_spinner"></div></div>'
  );

  $("body").on("click", ".dm_open_modal", function (e) {
    e.preventDefault();

    var order_id = $(this).data("order-id");

    // Show the loading panel
    $(".dm_loading_panel").fadeIn(300);
    $(".dm_loading_panel").css("display", "flex");

    $.ajax({
      url: dm_ajax.ajax_url,
      type: "POST",
      data: {
        action: "dm_show_confirm_modal",
        security: dm_ajax.nonce,
        order_id: order_id,
      },
      success: function (response) {
        if (response.success) {
          $("body").append(response.data);
          $(".dm_modal_wrapper").fadeIn(300);
          $(".dm_modal_wrapper").css("display", "flex");
        } else {
          console.error("Error: ", response.data);
        }
        $(".dm_loading_panel").fadeOut(300);
      },
      error: function () {
        $(".dm_loading_panel").fadeOut(300);
      },
    });
  });

  $("body").on("click", ".dm_confirm_action", function (e) {
    e.preventDefault();
    var form = $("#dm_order_details_form");

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
      parcel_type: form.find('input[name="parcel_type"]:checked').val(),
    };

    console.log(orderData);
    const parcel_type = orderData.parcel_type === "cod" ? "D-COD" : "D";

    $.ajax({
      url: `https://easyship.hr/api/parcel/parcel_import?username=${dm_options.username}&password=${dm_options.password}&cod_amount=${orderData.cod_amount}&name1=${orderData.customer_name}&street=${orderData.customer_address}&rPropNum=${orderData.house_number}&city=${orderData.city}&country=${orderData.country}&pcode=${orderData.zip_code}&email=${orderData.email}&phone=${orderData.phone}&sender_remark=${orderData.note}&weight=${orderData.weight}&order_number=${orderData.reference}&cod_purpose=${orderData.reference}&parcel_type=${parcel_type}&num_of_parcel=${orderData.package_number}`,
      type: "POST",
      success: function (response) {
        if (response.status != "ok") {
          displayError(response.errlog);
          return;
        }

        update_adresnica(
          orderData.reference,
          `HR-DPD-${response.pl_number[0]}`
        );

        $.ajax({
          url: `https://easyship.hr/api/parcel/parcel_print?username=${dm_options.username}&password=${dm_options.password}&parcels=${response.pl_number[0]}`,
          type: "POST",
          xhrFields: {
            responseType: "blob",
          },
          success: function (res) {
            if (response.status === "err") {
              displayError(res.errlog);
              return;
            }

            var blob = new Blob([res], { type: "application/pdf" });
            var filename = `${orderData.customer_name}-${response.pl_number[0]}}.pdf`;
            var link = document.createElement("a");

            link.href = window.URL.createObjectURL(blob);
            link.download = filename;
            link.click();
            link.remove();

            $.ajax({
              url: `https://easyship.hr/api/parcel/parcel_status?secret=FcJyN7vU7WKPtUh7m1bx&parcel_number=${response.pl_number[0]}`,
              type: "POST",
              success: function (r) {
                if (r.status === "err") {
                  displayError(r.errlog);
                  return;
                }
                console.log(r);
                update_parcel_status(orderData.reference, r.parcel_status);
              },
              error: function (error) {
                console.error("API call failed: ", error);
                displayError(error);
              },
            });

            $(".dm_modal_wrapper").fadeOut(300, function () {
              $(this).remove();
            });
          },
          error: function (error) {
            console.error("API call failed: ", error);
            displayError(error);
          },
        });
      },
      error: function (error) {
        console.error("API call failed: ", error);
        displayError(error);
      },
    });
  });

  $("body").on("click", ".dm_cancel_action", function () {
    $(".dm_modal_wrapper").fadeOut(300, function () {
      $(this).remove();
    });
  });

  $("body").on("click", ".dm_cancel_action", function (e) {
    e.preventDefault();
  });

  function update_adresnica(order_id, pl_number) {
    $.ajax({
      url: dm_ajax.ajax_url,
      type: "POST",
      data: {
        action: "dm_update_adresnica",
        security: dm_ajax.nonce,
        order_id: order_id,
        pl_number: pl_number,
      },
      success: function (response) {
        if (response.success) {
          console.log("Adresnica updated successfully.");
        } else {
          console.error("Error updating Adresnica: ", response.data);
        }
      },
      error: function () {
        console.error("Error updating Adresnica.");
      },
    });
  }

  function update_parcel_status(order_id, pl_status) {
    console.log(order_id, pl_status);
    $.ajax({
      url: dm_ajax.ajax_url,
      type: "POST",
      data: {
        action: "dm_update_parcel_status",
        security: dm_ajax.nonce,
        order_id: order_id,
        pl_status: pl_status,
      },
      success: function (response) {
        console.log(response);
        if (response.success) {
          console.log("Parcel status updated successfully.");
        } else {
          console.error("Error updating Status: ", response.data);
        }
      },
      error: function (error) {
        console.error("Error updating Status.", error);
      },
    });
  }

  function displayError(error) {
    $(".dm-error").text(error);
    $(".dm-error").removeAttr("style");
  }

  jQuery(document).ready(function ($) {
    const urlParams = new URLSearchParams(window.location.search);

    const orders_page = urlParams.get("post_type");
    const pagination_page =
      urlParams.get("paged") != undefined ? urlParams.get("paged") : "1";

    if (orders_page != "shop_order") {
      return;
    }

    const limit = 20;
    const offset = +pagination_page * limit - limit;

    $.ajax({
      url: ajaxurl, // WordPress AJAX endpoint
      type: "POST",
      data: {
        action: "get_orders",
        limit: limit,
        offset: offset,
      },
      success: function (response) {
        if (response.success) {
          const orders = response.data;

          $.each(orders, function (index, order) {
            const pl_number = order.pl_number.split("-").pop();

            if (order.pl_number != "") {
              const parcel_status_element = $(`tr#post-${order.order_id}`).find(
                "td.dm_parcel_status"
              );
              parcel_status_element.html('<img src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/images/loading.gif" alt="GIF Image">');

              $.ajax({
                url: `https://easyship.hr/api/parcel/parcel_status?secret=FcJyN7vU7WKPtUh7m1bx&parcel_number=${pl_number}`,
                type: "POST",
                success: function (r) {
                  if (r.status === "err") {
                    displayError(r.errlog);
                    return;
                  }
                  update_parcel_status(order.order_id, r.parcel_status);
                  const spanElement = $('<span>' + r.parcel_status + '</span>');
                  applyStatusClass(spanElement, r.parcel_status);
                  parcel_status_element.html(spanElement);

                },
                error: function (error) {
                  console.error("API call failed: ", error);
                },
              });
            }
          });
        } else {
          // Handle error response
          console.error(response.data);
        }
      },
      error: function (error) {
        // Handle AJAX error
        console.error(error);
      },
    });
  });
});

function applyStatusClass(element, status) {
  if (status) {
    element.addClass('dm-package-status order-status');
    switch (status) {
      case 'PRINTED':
        element.addClass('dm-status-printed');
        break;
      case 'DELIVERED':
        element.addClass('dm-status-delivered');
        break;
      case 'CANCELLED':
        element.addClass('dm-status-cancelled');
        break;
      default:
        element.addClass('dm-status-rest');
        break;
    }
  }
}

jQuery(document).ready(function($) {
  $('td.dm_parcel_status span').each(function() {
    var status = $(this).text();
    applyStatusClass($(this), status);
  });
});