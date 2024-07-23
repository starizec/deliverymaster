//ADRESNICA
jQuery(document).ready(function ($) {
  $("body").append(
    '<div class="elm_loading_panel"><div class="elm_spinner"></div></div>'
  );

  $("body").on("click", ".elm_open_modal", function (e) {
    e.preventDefault();

    var order_id = $(this).data("order-id");
    let courier = $(this).data("courier");

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
          alert(
            "Error ID: " +
              (response.data.error_id ? response.data.error_id : "null") +
              "\nMessage: " +
              (response.data.error_message ? response.data.error_message : "null")
          );
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

//PRINT LABEL
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

    var courier = $("#hiddenCourier").val();
    var form = $("#elm_order_details_form");
    var orderId = $("#hiddenOrderId").val();

    switch (courier) {
      case "dpd":
        var parcelData = setDPDParcelData(form);
        break;
    }

    $.ajax({
      url: elm_ajax.ajax_url,
      method: "POST",
      data: {
        action: "elm_print_label",
        parcel: parcelData,
        security: elm_ajax.nonce,
        chosenCourier: courier,
        orderId: orderId,
      },
      success: function (response) {
        if (response.success) {
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

            let a = document.createElement("a");
            a.href = url;
            a.download = response.data.file_name;
            document.body.appendChild(a);
            a.click();
            URL.revokeObjectURL(url);
            document.body.removeChild(a);
          }
          

          jQuery(".elm_modal_wrapper").fadeOut(300, function () {
            jQuery(this).remove();
          });
          location.reload();
        } else {
          alert(
            "Error ID: " +
              response.data.error_id +
              "\nMessage: " +
              response.data.error_message
          );
          $(".elm_loading_panel").fadeOut(300);
        }
      },
    });
  });

  function setDPDParcelData(form) {
    var isCod = form.find('input[name="parcel_type"]:checked').val() === "cod";
    var parcelType;
  
    // Određivanje tipa paketa na temelju vrste usluge
    if (elm_ajax.serviceType === 'DPD Classic') {
      parcelType = isCod ? "D-COD" : "D";
    } else if (elm_ajax.serviceType === 'DPD Home') {
      parcelType = isCod ? "D-COD-B2C" : "D-B2C";
    }
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
      parcel_type: parcelType,
      num_of_parcel: form.find('input[name="package_number"]').val(),
      phone: form.find('input[name="phone"]').val(),
      contact: form.find('input[name="contact_person"]').val(),
    };
  }
});

//PRINT LABELS
jQuery(document).ready(function ($) {
  $("body").append('<div class="elm_loading_panel"><div class="elm_spinner"></div></div>');

  $('#posts-filter').on('submit', function (e) {
      const actionValue = $(this).find('select[name="action"]').val();

      const supportedActionValues = ['elm_dpd_print_label', 'elm_gls_print_label', 'elm_overseas_print_label', 'elm_hp_print_label'];

      if (supportedActionValues.includes(actionValue)) {
        e.preventDefault();
        
          $(".elm_loading_panel").fadeIn(300);
          $(".elm_loading_panel").css({
              display: "flex",
              "z-index": "9999999",
          });

          var checkedPostIds = $('input[name="post[]"]:checked').map(function () {
            return $(this).val();
        }).get();


          $.ajax({
            url: elm_ajax.ajax_url,
            method: "POST",
            data: {
              action: "elm_print_labels",
              security: elm_ajax.nonce,
              post_ids: checkedPostIds,
              actionValue: actionValue
            },
              success: function (response) {
                  if (response.success) {
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

                          let a = document.createElement("a");
                          a.href = url;
                          a.download = response.data.file_name;
                          document.body.appendChild(a);
                          a.click();
                          URL.revokeObjectURL(url);
                          document.body.removeChild(a);
                      }

                      jQuery(".elm_modal_wrapper").fadeOut(300, function () {
                          jQuery(this).remove();
                      });
                      location.reload();
                  } else {
                      alert(
                          "Error ID: " +
                          response.data.error_id +
                          "\nMessage: " +
                          response.data.error_message
                      );
                      $(".elm_loading_panel").fadeOut(300);
                  }
              },
          });
      }
  });
});

//STATUS
function update_parcel_status(order_id, pl_status) {
  jQuery.ajax({
    url: elm_ajax.ajax_url,
    type: "POST",
    data: {
      action: "elm_parcel_statuses",
      security: elm_ajax.nonce,
      order_id: order_id,
      pl_status: pl_status,
    },
    success: function (response) {
      if (response.success) {
        console.log("Parcel status updated successfully.");
      }
    },
    error: function (response) {
      alert(
        "Error ID: " +
          (response.data.error_id ? response.data.error_id : "null") +
          "\nMessage: " +
          (response.data.error_message ? response.data.error_message : "null")
      );
    },
  });
}

function displayError(error) {
  $(".elm-error").text(error);
  $(".elm-error").removeAttr("style");
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
    url: elm_ajax.ajax_url,
    type: "POST",
    data: {
      action: "get_orders",
      security: elm_ajax.nonce,
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
              "td.elm_parcel_status"
            );
            parcel_status_element.html('<img src="/wp-content/plugins/express-label-maker/assets/statusloading.gif" alt="loading">');

            $.ajax({
              url: order.pl_parcels.url,
              type: "POST",
              success: function (r) {
                  if (r.status === "err") {
                      displayError(r.errlog);
                      return;
                  }
                  update_parcel_status(order.order_id, r.parcel_status);
          
                  const spanElement = $('<span title="' + r.parcel_status + '">' + r.parcel_status + '</span>');
          
                  if (r.parcel_status.length > 30) {
                      spanElement.text(r.parcel_status.substring(0, 30) + '...');
                      spanElement.attr('title', r.parcel_status);
                  }
          
                  applyStatusClass(spanElement, r.parcel_status);
                  parcel_status_element.html(spanElement);
              },
              error: function (response) {
                alert(
                  "Error ID: " +
                    (response.data.error_id ? response.data.error_id : "null") +
                    "\nMessage: " +
                    (response.data.error_message ? response.data.error_message : "null")
                );
              },
            });
          }
        });
      } else {
        alert(
          "Error ID: " +
            (response.data.error_id ? response.data.error_id : "null") +
            "\nMessage: " +
            (response.data.error_message ? response.data.error_message : "null")
        );
      }
    },
    error: function (response) {
      alert(
        "Error ID: " +
          (response.data.error_id ? response.data.error_id : "null") +
          "\nMessage: " +
          (response.data.error_message ? response.data.error_message : "null")
      );
    },
  });
});

function applyStatusClass(element, status) {
if (status) {
  element.addClass('elm-package-status order-status');
  switch (status) {
    case 'PRINTED':
      element.addClass('elm-status-printed');
      break;
    case 'DELIVERED':
      element.addClass('elm-status-delivered');
      break;
    case 'CANCELLED':
      element.addClass('elm-status-cancelled');
      break;
    default:
      element.addClass('elm-status-rest');
      break;
  }
}
}

jQuery(document).ready(function($) {
$('td.elm_parcel_status span').each(function() {
  var status = $(this).text();
  applyStatusClass($(this), status);
});
});

//START TRIAL

jQuery(document).ready(function ($) {
  $("body").append(
    '<div class="elm_loading_panel"><div class="elm_spinner"></div></div>'
  );
  $(document).on("click", ".elm-start-trial-btn", function (e) {
    e.preventDefault();

    $(".elm_loading_panel").fadeIn(300);
    $(".elm_loading_panel").css({
      display: "flex",
      "z-index": "9999999",
    });

    $.ajax({
      url: elm_ajax.ajax_url,
      type: "POST",
      data: {
        action: "elm_start_trial",
        security: elm_ajax.nonce,
        email: $('#elm_email').val(),
        domain: window.location.hostname,
        licence: 'trial'
    },
      success: function (response) {
        if (response.success) {
          $(".elm_loading_panel").fadeOut(300);
          $('#elm_email').val(response.data.email);
          $('#elm_licence_key').val(response.data.licence);
          $(".elm-start-trial-btn").hide();
          $("#elm_submit_btn").prop("disabled", false);
          alert("Your license has been generated and your trial has started.");
          jQuery(".elm_modal_wrapper").fadeOut(300, function () {
            jQuery(this).remove();
          });
        } else {
          alert(
            "Error ID: " +
              response.data.error_id +
              "\nMessage: " +
              response.data.error_message
          );
          $(".elm_loading_panel").fadeOut(300);
        }
      },
    });
  });
});

//LICENCE CHECK

jQuery(document).ready(function ($) {
  const urlParams = new URLSearchParams(window.location.search);

  const page = urlParams.get("page");
  /* const tab = urlParams.get("tab"); */

  if (/* tab !== "licence" &&  */page !== "express_label_maker") {
      return;
  }

  if (!elm_ajax.email || !elm_ajax.licence) {
    return;
  }
    $.ajax({
      url: elm_ajax.ajax_url,
      type: "POST",
      data: {
        action: "elm_licence_check",
        security: elm_ajax.nonce,
        domain: window.location.hostname,
    },
      success: function (response) {
        if (response.success) {
          $('#elm_valid_from').val(response.data.valid_from);
          $('#elm_valid_until').val(response.data.valid_until);
          $('#elm_usage_limit').val(response.data.usage_limit);
          $('#elm_usage').val(response.data.usage);

          if ((response.data.usage_limit - response.data.usage) <= 2) {
            alert("Only " + (response.data.usage_limit - response.data.usage) + "label(s) to the limit!");
          }

          if (response.data.valid_until != null) {
          let today = new Date();
          let validUntil = new Date(response.data.valid_until);

          let timeDifference = validUntil.getTime() - today.getTime();
          let dayDifference = timeDifference / (1000 * 3600 * 24);

          if (dayDifference <= 10) {
            alert("You have " + Math.round(dayDifference) + " day(s) left until your license expires!!");
          }
        }
        } else {
          alert(
            "Error ID: " +
              response.data.error_id +
              "\nMessage: " +
              response.data.error_message
          );
        }
      },
    });
  });


//COLLECTION REQUEST

jQuery(document).ready(function ($) {
  $("body").append(
    '<div class="elm_loading_panel"><div class="elm_spinner"></div></div>'
  );

  $("body").on("click", "#elm_collection_request", function (e) {
    e.preventDefault();

    var order_id = $(this).data("order-id");

    $(".elm_loading_panel").fadeIn(300);
    $(".elm_loading_panel").css("display", "flex");

    $.ajax({
      url: elm_ajax.ajax_url,
      type: "POST",
      data: {
        action: "elm_show_collection_modal",
        security: elm_ajax.nonce,
        order_id: order_id,
      },
      success: function (response) {
        if (response.success) {
          $("body").append(response.data);
          $(".elm_modal_wrapper").fadeIn(300);
          $(".elm_modal_wrapper").css("display", "flex");
        } else {
          alert(
            "Error ID: " +
              (response.data.error_id ? response.data.error_id : "null") +
              "\nMessage: " +
              (response.data.error_message ? response.data.error_message : "null")
          );
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

//SEND COLLECTION REQUEST
jQuery(document).ready(function ($) {
  $("body").append(
    '<div class="elm_loading_panel"><div class="elm_spinner"></div></div>'
  );
  $(document).on("click", ".elm_confirm_collection_action", function (e) {
    e.preventDefault();

    $(".elm_loading_panel").fadeIn(300);
    $(".elm_loading_panel").css({
      display: "flex",
      "z-index": "9999999",
    });

    var courier = $("#collection_courier").val();
    var form = $("#elm_collection_order_details_form");
    var orderId = $("#hiddenOrderId").val();
    var country = $("#hiddenCountry").val();

    switch (courier) {
      case "dpd":
        var parcelData = setDPDCollectionData(form);
        break;
    }

    $.ajax({
      url: elm_ajax.ajax_url,
      method: "POST",
      data: {
        action: "elm_collection_request",
        parcel: parcelData,
        security: elm_ajax.nonce,
        chosenCourier: courier,
        orderId: orderId,
        country: country
      },
      success: function (response) {
        if (response.success) {
          $(".elm_loading_panel").fadeOut(300);
          alert(
            "Collection request successfully sent.\n" +
            "Code: " +
              response.data.code +
              "\nReference: " +
              response.data.reference
          );

          jQuery(".elm_modal_wrapper").fadeOut(300, function () {
            jQuery(this).remove();
          });
        } else {
          alert(
            "Error ID: " +
              response.data.error_id +
              "\nMessage: " +
              response.data.error_message
          );
          $(".elm_loading_panel").fadeOut(300);
        }
      },
    });
  });

  function setDPDCollectionData(form) {
    var rawDate = form.find('input[name="collection_pickup_date"]').val();
    var formattedDate = rawDate.split('-').join('');
    /* var country = $("#hiddenCountry").val().toUpperCase(); */
    return {
      cname: form.find('input[name="customer_name"]').val(),
      cname1: form.find('input[name="contact_person"]').val(),
      cstreet: form.find('input[name="customer_address"]').val(),
      cPropertyNumber: form.find('input[name="house_number"]').val(),
      ccity: form.find('input[name="city"]').val(),
      cpostal: form.find('input[name="zip_code"]').val(),
      ccountry: form.find('input[name="country"]').val(),
      cphone: form.find('input[name="phone"]').val(),
      cemail: form.find('input[name="email"]').val(),
      info1: form.find('input[name="collection_info_for_sender"]').val(),
      info2: form.find('input[name="collection_info_for_courier"]').val(),
      rname: form.find('input[name="collection_company_or_personal_name"]').val(),
      rname2: form.find('input[name="collection_contact_person"]').val(),
      rstreet: form.find('input[name="collection_street"]').val(),
      rPropertyNumber: form.find('input[name="collection_property_number"]').val(),
      rcity: form.find('input[name="collection_city"]').val(),
      rpostal: form.find('input[name="collection_postal_code"]').val(),
      rcountry: form.find('select[name="collection_country"]').val(),
      rphone: form.find('input[name="collection_phone"]').val(),
      remail: form.find('input[name="collection_email"]').val(),
      pickup_date: formattedDate,
    };
  }
});