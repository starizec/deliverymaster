//ADRESNICA
jQuery(document).ready(function ($) {
  $("body").append(
    '<div class="explm_loading_panel"><div class="explm_spinner"></div></div>'
  );

  $("body").on("click", ".explm_open_modal", function (e) {
    e.preventDefault();

    var order_id = $(this).data("order-id");
    let courier = $(this).data("courier");

    $(".explm_loading_panel").fadeIn(300);
    $(".explm_loading_panel").css("display", "flex");

    $.ajax({
      url: explm_ajax.ajax_url,
      type: "POST",
      data: {
        action: "explm_show_confirm_modal",
        security: explm_ajax.nonce,
        order_id: order_id,
        courier: courier
      },
      success: function (response) {
        if (response.success) {
          $("body").append(response.data);
          $(".explm_modal_wrapper").fadeIn(300);
          $(".explm_modal_wrapper").css("display", "flex");
          $("#hiddenCourier").val(courier);
        } else {
          alert(
            "Error ID: " +
              (response.data.error_id ? response.data.error_id : "null") +
              "\nMessage: " +
              (response.data.error_message ? response.data.error_message : "null")
          );
        }
        $(".explm_loading_panel").fadeOut(300);
      },
      error: function () {
        $(".explm_loading_panel").fadeOut(300);
      },
    });
  });
});

jQuery("body").on("click", ".explm_cancel_action", function () {
  jQuery(".explm_modal_wrapper").fadeOut(300, function () {
    jQuery(this).remove();
  });
});

jQuery("body").on("click", ".explm_cancel_action", function (e) {
  e.preventDefault();
});

//PRINT LABEL
jQuery(document).ready(function ($) {
  $("body").append(
    '<div class="explm_loading_panel"><div class="explm_spinner"></div></div>'
  );
  $(document).on("click", ".explm_confirm_action", function (e) {
    e.preventDefault();

    $(".explm_loading_panel").fadeIn(300);
    $(".explm_loading_panel").css({
      display: "flex",
      "z-index": "9999999",
    });

    var courier = $("#hiddenCourier").val();
    var form = $("#explm_order_details_form");
    var orderId = $("#hiddenOrderId").val();

    switch (courier) {
      case "dpd": //DODATI KURIRE
        var parcelData = setDPDParcelData(form);
        break;
        case "overseas":
        var parcelData = setOverseasParcelData(form);
        break;
    }

    $.ajax({
      url: explm_ajax.ajax_url,
      method: "POST",
      data: {
        action: "explm_print_label",
        parcel: parcelData,
        security: explm_ajax.nonce,
        chosenCourier: courier,
        orderId: orderId,
      },
      success: function (response) {
        if (response.success) {
          $(".explm_loading_panel").fadeOut(300);
          if (response.data.file_path) {
            window.open(response.data.file_path, "_blank");
          } else if (response.data.pdf_data) {
            let binaryString = atob(response.data.pdf_data);
            let uint8Array = new Uint8Array(binaryString.length);
            for (let i = 0; i < binaryString.length; i++) {
                uint8Array[i] = binaryString.charCodeAt(i);
            }
            
            let blob = new Blob([uint8Array], { type: 'application/pdf' });
            let url = URL.createObjectURL(blob);
            window.open(url, '_blank');
            

            let a = document.createElement("a");
            a.href = url;
            a.download = response.data.file_name;
            document.body.appendChild(a);
            a.click();
            URL.revokeObjectURL(url);
            document.body.removeChild(a);
          }
          

          jQuery(".explm_modal_wrapper").fadeOut(300, function () {
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
          $(".explm_loading_panel").fadeOut(300);
        }
      },
    });
  });

  function setDPDParcelData(form) {
    var isCod = form.find('input[name="parcel_type"]:checked').val() === "cod";
    var parcelType;
    var dpdParcelLockerId = $("#dpdParcelLockerId").val();
  
    if (dpdParcelLockerId) {
      parcelType = "D-B2C-PSD";
    } else if (explm_ajax.serviceType === 'DPD Classic') {
      parcelType = isCod ? "D-COD" : "D";
    } else if (explm_ajax.serviceType === 'DPD Home') {
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
      pudo_id: dpdParcelLockerId
    };
  }

  function setOverseasParcelData(form) {
    var isCod = form.find('input[name="parcel_type"]:checked').val() === "cod";
    var overseasParcelLockerId = $("#overseasParcelLockerId").val();
  
    return {
      cod_amount: isCod ? form.find('input[name="cod_amount"]').val() : null,
      name1: form.find('input[name="customer_name"]').val(),
      rPropNum: form.find('input[name="customer_address"]').val() + ' ' + form.find('input[name="house_number"]').val(),
      city: form.find('input[name="city"]').val(),
      pcode: form.find('input[name="zip_code"]').val(),
      email: form.find('input[name="email"]').val(),
      sender_remark: form.find('textarea[name="note"]').val(),
      order_number: form.find('input[name="reference"]').val(),
      num_of_parcel: form.find('input[name="package_number"]').val(),
      phone: form.find('input[name="phone"]').val(),
      pudo_id: overseasParcelLockerId
    };
  }

});

//PRINT LABELS
jQuery(document).ready(function ($) {
  $("body").append('<div class="explm_loading_panel"><div class="explm_spinner"></div></div>');
  var orderFilterForm = $('#posts-filter, #wc-orders-filter');
  orderFilterForm.on('submit', function(e) {
      const actionValue = $(this).find('select[name="action"]').val();

      console.log(actionValue, 'action')

      const supportedActionValues = ['explm_dpd_print_label', 'explm_gls_print_label', 'explm_overseas_print_label', 'explm_hp_print_label'];

      if (supportedActionValues.includes(actionValue)) {
        e.preventDefault();
        
          $(".explm_loading_panel").fadeIn(300);
          $(".explm_loading_panel").css({
              display: "flex",
              "z-index": "9999999",
          });

          var checkedPostIds = $('input[name="post[]"]:checked, input[name="id[]"]:checked').map(function() {
            return $(this).val();
          }).get();

          if (checkedPostIds.length === 0) {
            alert('No orders selected!');
            $(".explm_loading_panel").fadeOut(300);
            return;
          }


          $.ajax({
            url: explm_ajax.ajax_url,
            method: "POST",
            data: {
              action: "explm_print_labels",
              security: explm_ajax.nonce,
              post_ids: checkedPostIds,
              actionValue: actionValue
            },
              success: function (response) {
                console.log(response, 'resp')
                  if (response.success) {
                      $(".explm_loading_panel").fadeOut(300);

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

                      jQuery(".explm_modal_wrapper").fadeOut(300, function () {
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
                      $(".explm_loading_panel").fadeOut(300);
                  }
              },
          });
      }
  });
});

//STATUS
function update_parcel_status(order_id, pl_status) {
  jQuery.ajax({
    url: explm_ajax.ajax_url,
    type: "POST",
    data: {
      action: "explm_parcel_statuses",
      security: explm_ajax.nonce,
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

function getStatusText(r, meta) {
  //DODATI KURIRE
  const statusHandlers = {
      '_dpd_': () => r.parcel_status || 'No status available',
      '_overseas_': () => (r.data && r.data.Events && r.data.Events.length > 0) ? r.data.Events[r.data.Events.length - 1].StatusDescription : 'No status available'
  };

  for (let key in statusHandlers) {
      if (meta.includes(key)) {
          return statusHandlers[key]();
      }
  }

  return 'No status available';
}

jQuery(document).ready(function ($) {
  const urlParams = new URLSearchParams(window.location.search);

  const isOrdersPage = urlParams.get("post_type") === 'shop_order' || 
                    window.location.href.includes('wc-orders');

  if (!isOrdersPage) {
      return;
  }
  const pagination_page =
    urlParams.get("paged") != undefined ? urlParams.get("paged") : "1";

  const limit = 20;
  const offset = +pagination_page * limit - limit;

  $.ajax({
    url: explm_ajax.ajax_url,
    type: "POST",
    data: {
      action: "get_orders",
      security: explm_ajax.nonce,
      limit: limit,
      offset: offset,
    },
    success: function (response) {
      if (response.success) {
        const orders = response.data;

        $.each(orders, function (index, order) {
          const pl_number = order.pl_number.split("-").pop();

          if (order.pl_number != "") {
            const parcel_status_element = $(`tr#post-${order.order_id}, tr#order-${order.order_id}`).find(
              "td.explm_parcel_status"
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
          
                  const statusText = getStatusText(r, order.pl_number_meta);
          
                  update_parcel_status(order.order_id, statusText);
                  const spanElement = $('<span title="' + statusText + '">' + statusText + '</span>');
                  if (statusText.length > 30) {
                      spanElement.text(statusText.substring(0, 30) + '...');
                      spanElement.attr('title', statusText);
                  }
          
                  applyStatusClass(spanElement, statusText);
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
    case 'PRINTED': //DPD
      element.addClass('elm-status-printed');
      break;
    case 'DELIVERED': //DPD
      element.addClass('elm-status-delivered');
      break;
    case 'CANCELLED': //DPD
      element.addClass('elm-status-cancelled');
      break;
    case 'Otkupnina plaćena gotovinom.': //OVERSEAS
      element.addClass('elm-status-delivered');
      break;  
    default:
      element.addClass('elm-status-rest');
      break;
    }
  }
}

jQuery(document).ready(function($) {
$('td.explm_parcel_status span').each(function() {
  var status = $(this).text();
  applyStatusClass($(this), status);
});
});

//START TRIAL

jQuery(document).ready(function ($) {
  $("body").append(
    '<div class="explm_loading_panel"><div class="explm_spinner"></div></div>'
  );
  $(document).on("click", ".elm-start-trial-btn", function (e) {
    e.preventDefault();

    $(".explm_loading_panel").fadeIn(300);
    $(".explm_loading_panel").css({
      display: "flex",
      "z-index": "9999999",
    });

    $.ajax({
      url: explm_ajax.ajax_url,
      type: "POST",
      data: {
        action: "explm_start_trial",
        security: explm_ajax.nonce,
        email: $('#explm_email').val(),
        domain: window.location.hostname,
        licence: 'trial'
    },
      success: function (response) {
        if (response.success) {
          $(".explm_loading_panel").fadeOut(300);
          $('#explm_email').val(response.data.email);
          $('#explm_licence_key').val(response.data.licence);
          $(".elm-start-trial-btn").hide();
          $("#explm_submit_btn").prop("disabled", false);
          alert("Your license has been generated and your trial has started.");
          jQuery(".explm_modal_wrapper").fadeOut(300, function () {
            jQuery(this).remove();
          });
        } else {
          alert(
            "Error ID: " +
              response.data.error_id +
              "\nMessage: " +
              response.data.error_message
          );
          $(".explm_loading_panel").fadeOut(300);
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

  if (!explm_ajax.email || !explm_ajax.licence) {
    return;
  }
    $.ajax({
      url: explm_ajax.ajax_url,
      type: "POST",
      data: {
        action: "explm_licence_check",
        security: explm_ajax.nonce,
        domain: window.location.hostname,
    },
      success: function (response) {
        if (response.success) {
          $('#explm_valid_from').val(response.data.valid_from);
          $('#explm_valid_until').val(response.data.valid_until);
          $('#explm_usage_limit').val(response.data.usage_limit);
          $('#explm_usage').val(response.data.usage);

          const usage = parseInt(response.data.usage, 10);
          if (!isNaN(usage) && usage >= 1) {
            const totalMinutes = usage * 5;
            const days = Math.floor(totalMinutes / 1440);
            const hours = Math.floor((totalMinutes % 1440) / 60);
            const minutes = totalMinutes % 60;
          
            const message = explm_ajax.savedLabelTime
              .replace('%1$d', totalMinutes)
              .replace('%2$d', hours)
              .replace('%3$d', minutes)
              .replace('%4$d', days);
          
            const output = $('<p style="margin-top:20px; font-weight:bold;">' + message + '</p>');
            $('#explm_usage').closest('table').after(output);
          }

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

//LICENCE JS

document.addEventListener('DOMContentLoaded', function() {
  const url = window.location.search;

  if (url !== "?page=express_label_maker&tab=licence" && (url !== "?page=express_label_maker")) {
      return;
  }
  var emailInput = document.getElementById('explm_email');
  var licenceKeyInput = document.getElementById('explm_licence_key');
  var countrySelect = document.getElementById('explm_country');
  var startTrialButton = document.getElementById('start-trial-btn');
  var submitButton = document.getElementById('explm_submit_btn');

  function toggleStartTrialButton() {
    console.log('RADIII')
      startTrialButton.style.display = licenceKeyInput.value.trim() === '' ? 'inline-block' : 'none';
  }

  function toggleSubmitButton() {
      submitButton.disabled = emailInput.value.trim() === '' || licenceKeyInput.value.trim() === '' || countrySelect.value.trim() === '';
  }

  toggleStartTrialButton();
  toggleSubmitButton();

  licenceKeyInput.addEventListener('input', function() {
      toggleStartTrialButton();
      toggleSubmitButton();
  });

  emailInput.addEventListener('input', toggleSubmitButton);
  countrySelect.addEventListener('change', toggleSubmitButton);
});


//COLLECTION REQUEST

jQuery(document).ready(function ($) {
  $("body").append(
    '<div class="explm_loading_panel"><div class="explm_spinner"></div></div>'
  );

  $("body").on("click", "#explm_collection_request", function (e) {
    e.preventDefault();

    var order_id = $(this).data("order-id");

    $(".explm_loading_panel").fadeIn(300);
    $(".explm_loading_panel").css("display", "flex");

    $.ajax({
      url: explm_ajax.ajax_url,
      type: "POST",
      data: {
        action: "explm_show_collection_modal",
        security: explm_ajax.nonce,
        order_id: order_id,
      },
      success: function (response) {
        if (response.success) {
          $("body").append(response.data);
          $(".explm_modal_wrapper").fadeIn(300);
          $(".explm_modal_wrapper").css("display", "flex");
        } else {
          alert(
            "Error ID: " +
              (response.data.error_id ? response.data.error_id : "null") +
              "\nMessage: " +
              (response.data.error_message ? response.data.error_message : "null")
          );
        }
        $(".explm_loading_panel").fadeOut(300);
      },
      error: function () {
        $(".explm_loading_panel").fadeOut(300);
      },
    });
  });
});

jQuery("body").on("click", ".explm_cancel_action", function () {
  jQuery(".explm_modal_wrapper").fadeOut(300, function () {
    jQuery(this).remove();
  });
});

jQuery("body").on("click", ".explm_cancel_action", function (e) {
  e.preventDefault();
});

//SEND COLLECTION REQUEST
jQuery(document).ready(function ($) {
  $("body").append(
    '<div class="explm_loading_panel"><div class="explm_spinner"></div></div>'
  );
  $(document).on("click", ".explm_confirm_collection_action", function (e) {
    e.preventDefault();

    $(".explm_loading_panel").fadeIn(300);
    $(".explm_loading_panel").css({
      display: "flex",
      "z-index": "9999999",
    });

    var courier = $("#collection_courier").val();
    var form = $("#explm_collection_order_details_form");
    var orderId = $("#hiddenOrderId").val();
    var country = $("#hiddenCountry").val();

    //DODATI KURIRE
    switch (courier) {
      case "dpd":
        var parcelData = setDPDCollectionData(form);
        break;
    }

    $.ajax({
      url: explm_ajax.ajax_url,
      method: "POST",
      data: {
        action: "explm_collection_request",
        parcel: parcelData,
        security: explm_ajax.nonce,
        chosenCourier: courier,
        orderId: orderId,
        country: country
      },
      success: function (response) {
        if (response.success) {
          $(".explm_loading_panel").fadeOut(300);
          alert(
            "Collection request successfully sent.\n" +
            "Code: " +
              response.data.code +
              "\nReference: " +
              response.data.reference
          );

          jQuery(".explm_modal_wrapper").fadeOut(300, function () {
            jQuery(this).remove();
          });
        } else {
          alert(
            "Error ID: " +
              response.data.error_id +
              "\nMessage: " +
              response.data.error_message
          );
          $(".explm_loading_panel").fadeOut(300);
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

jQuery(document).ready(function($) {
  function togglePaketomatSelect() {
      if ($('#enable_paketomat').is(':checked')) {
          $('#paketomat_shipping_method_row').show();
      } else {
          $('#paketomat_shipping_method_row').hide();
      }
  }
  togglePaketomatSelect();

  $('#enable_paketomat').on('change', togglePaketomatSelect);
});

jQuery(document).ready(function($) {
  const usageField = $('#explm_usage');
  console.log(usageField, 'usageField')
  const outputContainer = $('<p id="explm_saving_summary" style="font-weight:bold; margin-top: 20px;"></p>');

  if (usageField.length) {
    const usage = parseInt(usageField.val(), 10);
    if (!isNaN(usage)) {
      const totalMinutes = usage * 5;
      const days = Math.floor(totalMinutes / 1440);
      const hours = Math.floor((totalMinutes % 1440) / 60);
      const minutes = totalMinutes % 60;

      const message = `Uštedili ste ${minutes} minuta, ${hours} sati i ${days} dana na ispisu naljepnica.`;
      outputContainer.text(message);

      // Dodaj ispod .form-table gdje su inputi
      usageField.closest('table').after(outputContainer);
    }
  }
});
