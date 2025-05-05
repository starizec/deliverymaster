jQuery(document).ready(function ($) {
    $("body").append(
      '<div class="explm-loading-panel"><div class="explm-spinner"></div></div>'
    );
  
    $("body").on("click", "#explm_collection_request", function (e) {
      e.preventDefault();
  
      var order_id = $(this).data("order-id");
  
      $(".explm-loading-panel").fadeIn(300);
      $(".explm-loading-panel").css("display", "flex");
  
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
            $(".explm-modal-wrapper").fadeIn(300);
            $(".explm-modal-wrapper").css("display", "flex");
          } else {
            alert(
              "Error ID: " +
                (response.data.error_id ? response.data.error_id : "null") +
                "\nMessage: " +
                (response.data.error_message
                  ? response.data.error_message
                  : "null")
            );
          }
          $(".explm-loading-panel").fadeOut(300);
        },
        error: function () {
          $(".explm-loading-panel").fadeOut(300);
        },
      });
    });
  });
  
  jQuery("body").on("click", ".explm-cancel-action", function () {
    jQuery(".explm-modal-wrapper").fadeOut(300, function () {
      jQuery(this).remove();
    });
  });
  
  jQuery("body").on("click", ".explm-cancel-action", function (e) {
    e.preventDefault();
  });
  
  //SEND COLLECTION REQUEST
  jQuery(document).ready(function ($) {
    $("body").append(
      '<div class="explm-loading-panel"><div class="explm-spinner"></div></div>'
    );
    $(document).on("click", ".explm_confirm_collection_action", function (e) {
      e.preventDefault();
  
      $(".explm-loading-panel").fadeIn(300);
      $(".explm-loading-panel").css({
        display: "flex",
        "z-index": "9999999",
      });
  
      var courier = $("#collection_courier").val();
      var form = $("#explm-collection-order-details-form");
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
          country: country,
        },
        success: function (response) {
          if (response.success) {
            jQuery(".explm-modal-wrapper").fadeOut(300, function () {
              jQuery(this).remove();
            });
        
            Swal.fire({
              icon: "success",
              title: "Collection request sent",
              html:
                "<b>Code:</b> " + response.data.code + "<br>" +
                "<b>Reference:</b> " + response.data.reference,
              confirmButtonText: "OK",
              customClass: {
                popup: 'explm-swal-scroll',
                title: 'explm-swal-title',
                confirmButton: 'explm-swal-button'
              }
            })
        
          } else {
            jQuery(".explm-modal-wrapper").fadeOut(300, function () {
              jQuery(this).remove();
            });
        
            let errorsHtml = '';
        
            if (Array.isArray(response.data.errors)) {
              if (response.data.errors.length === 1) {
                errorsHtml =
                "<b>Order number:</b> " + error.order_number + "<br>" +
                "<b>Error code:</b> " + (error.error_code || "unknown") + "<br>" +
                "<b>Message:</b> " + error.error_message;
              } else {
                response.data.errors.forEach(function (error, index) {
                  errorsHtml +=
                  "<b>Error " + (index + 1) + ":</b><br>" +
                  "<b>Order number:</b> " + error.order_number + "<br>" +
                  "<b>Error code:</b> " + (error.error_code || "unknown") + "<br>" +
                  "<b>Message:</b> " + error.error_message + "<br><br>";
                });
              }
            } else {
              errorsHtml = "<b>Unknown error occurred.</b>";
            }
        
            Swal.fire({
              icon: "error",
              title: "Collection request failed",
              html: errorsHtml,
              confirmButtonText: "OK",
              customClass: {
                popup: 'explm-swal-scroll',
                title: 'explm-swal-title',
                confirmButton: 'explm-swal-button'
              },
              didOpen: () => {
                const htmlContainer = Swal.getHtmlContainer();
                if (htmlContainer) {
                  htmlContainer.style.maxHeight = '50vh';
                  htmlContainer.style.overflowY = 'auto';
                }
              }
            });
          }
        }
      });
    });
  
    function setDPDCollectionData(form) {
      var rawDate = form.find('input[name="collection_pickup_date"]').val();
      var formattedDate = rawDate.split("-").join("");
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
        rname: form
          .find('input[name="collection_company_or_personal_name"]')
          .val(),
        rname2: form.find('input[name="collection_contact_person"]').val(),
        rstreet: form.find('input[name="collection_street"]').val(),
        rPropertyNumber: form
          .find('input[name="collection_property_number"]')
          .val(),
        rcity: form.find('input[name="collection_city"]').val(),
        rpostal: form.find('input[name="collection_postal_code"]').val(),
        rcountry: form.find('select[name="collection_country"]').val(),
        rphone: form.find('input[name="collection_phone"]').val(),
        remail: form.find('input[name="collection_email"]').val(),
        pickup_date: formattedDate,
      };
    }
  });