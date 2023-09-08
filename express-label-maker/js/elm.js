//adresnica
jQuery(document).ready(function ($) {
  $("body").append(
    '<div class="elm_loading_panel"><div class="elm_spinner"></div></div>'
  );

  $("body").on("click", ".elm_open_modal", function (e) {
    e.preventDefault();

    var order_id = $(this).data("order-id");
    let courier = $(this).data("courier");

    console.log(courier, "KURIR");

    $(".elm_loading_panel").fadeIn(300);
    $(".elm_loading_panel").css("display", "flex");

    $.ajax({
      url: elm_ajax.ajax_url,
      type: "POST",
      data: {
        action: "elm_show_confirm_modal",
        security: elm_ajax.nonce,
        order_id: order_id,
      },
      success: function (response) {
        if (response.success) {
          $("body").append(response.data);
          $(".elm_modal_wrapper").fadeIn(300);
          $(".elm_modal_wrapper").css("display", "flex");
          $("#hiddenCourier").val(courier);
        } else {
          console.error("Error: ", response.data);
        }
        $(".elm_loading_panel").fadeOut(300);
      },
      error: function () {
        $(".elm_loading_panel").fadeOut(300);
      },
    });
  });
});

jQuery("body").on("click", ".elm_cancel_action", function () {
  jQuery(".elm_modal_wrapper").fadeOut(300, function () {
    jQuery(this).remove();
  });
});

jQuery("body").on("click", ".elm_cancel_action", function (e) {
  e.preventDefault();
});

//print label
jQuery(document).ready(function ($) {
  $("body").append(
    '<div class="elm_loading_panel"><div class="elm_spinner"></div></div>'
  );
  $(document).on("click", ".elm_confirm_action", function (e) {
    e.preventDefault();

    $(".elm_loading_panel").fadeIn(300);
    $(".elm_loading_panel").css({
      display: "flex",
      "z-index": "9999999",
    });

    /*         let parcelData = $('#elm_order_details_form').serializeArray().reduce(function(obj, item) {
            obj[item.name] = item.value;
            return obj;
        }, {}); */
    var courier = $("#hiddenCourier").val();
    var form = $("#elm_order_details_form");
    var orderId = $("#hiddenOrderId").val();

    switch (courier) {
      case "dpd":
        var parcelData = setDPDParcelData(form);
        break;
    }

    console.log(parcelData, "parceldata");
    console.log(courier, "courier");
    console.log(orderId, "orderId");

    $.ajax({
      url: elm_ajax.ajax_url,
      method: "POST",
      data: {
        action: "elm_print_label",
        parcel: parcelData,
        security: elm_ajax.nonce,
        chosenCourier: courier,
        orderId: orderId
      },
      success: function (response) {
        if (response.success) {
          console.log(response, "success");
          $(".elm_loading_panel").fadeOut(300);

          if (response.data.file_path) {
            window.open(response.data.file_path, "_blank");
          } else if (response.data.pdf_data) {
            let pdfData = atob(response.data.pdf_data);
            let uint8Array = new Uint8Array(pdfData.length);
            for (let i = 0; i < pdfData.length; i++) {
              uint8Array[i] = pdfData.charCodeAt(i);
            }

            let blob = new Blob([uint8Array], { type: "application/pdf" });
            let url = URL.createObjectURL(blob);
            window.open(url, "_blank");
          }

          jQuery(".elm_modal_wrapper").fadeOut(300, function () {
            jQuery(this).remove();
          });
        } else {
          alert("Error sending to API");
          console.error(response);
          $(".elm_loading_panel").fadeOut(300);
        }
      },
    });
  });

  function setDPDParcelData(form) {
    return {
      cod_amount: form.find('input[name="cod_amount"]').val(),
      name1: form.find('input[name="customer_name"]').val(),
      street: form.find('input[name="customer_address"]').val(),
      rPropNum: form.find('input[name="house_number"]').val(),
      city: form.find('input[name="city"]').val(),
      country: form.find('input[name="country"]').val(),
      pcode: form.find('input[name="zip_code"]').val(),
      email: form.find('input[name="email"]').val(),
      sender_remark: form.find('textarea[name="note"]').val(),
      weight: form.find('input[name="weight"]').val(),
      order_number: form.find('input[name="reference"]').val(),
      cod_purpose: form.find('input[name="reference"]').val(),
      parcel_type:
        form.find('input[name="parcel_type"]:checked').val() === "cod"
          ? "D-COD"
          : "D",
      num_of_parcel: form.find('input[name="package_number"]').val(),
    };
  }
});
