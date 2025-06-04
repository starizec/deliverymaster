jQuery(document).ready(function ($) {
    $("body").append(
      '<div class="explm-loading-panel"><div class="explm-spinner"></div></div>'
    );
  
    $("body").on("click", ".explm_collection_request_btn", function (e) {
      e.preventDefault();
  
      var order_id = $(this).data("order-id");
      var courier  = $(this).data("courier");
  
      $(".explm-loading-panel").fadeIn(300);
      $(".explm-loading-panel").css("display", "flex");
  
      $.ajax({
        url: explm_ajax.ajax_url,
        type: "POST",
        data: {
            action: "explm_show_collection_modal",
            security: explm_ajax.nonce,
            order_id: order_id,
            courier: courier
        },
        success: function (response) {
            if (response.success) {
                $("body").append(response.data);
                $(".explm-modal-wrapper").fadeIn(300);
                $(".explm-modal-wrapper").css("display", "flex");
            } else {
                let errorsHtml = '';
    
                if (Array.isArray(response.data.errors)) {
                    if (response.data.errors.length === 1) {
                        let error = response.data.errors[0];
                        errorsHtml =
                            "<b>Error code:</b> " + (error.error_code || "unknown") + "<br>" +
                            "<b>Message:</b> " + (error.error_message || "unknown");
                    } else {
                        response.data.errors.forEach(function (error, index) {
                            errorsHtml +=
                                "<b>Error " + (index + 1) + ":</b><br>" +
                                "<b>Error code:</b> " + (error.error_code || "unknown") + "<br>" +
                                "<b>Message:</b> " + (error.error_message || "unknown") + "<br><br>";
                        });
                    }
                } else {
                    errorsHtml = "<b>Unknown error occurred.</b>";
                }
    
                Swal.fire({
                    icon: "error",
                    title: "Failed to load collection modal",
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
  
      var courier = $("#hiddenCollectionCourier").val();
      var form = $("#explm-collection-order-details-form");
      var orderId = $("#hiddenOrderId").val();
      var country = $("#hiddenCountry").val();
  
      //DODATI KURIRE
      switch (courier) {
        case "dpd":
          var parcelData = setDPDCollectionData(form);
          break;
        case "overseas":
          var parcelData = setOverseasCollectionData(form);
          break;
        case "hp":
          var parcelData = setHPCollectionData(form);
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
          console.log(response, 'res1232132')
          if (response.success) {
            jQuery(".explm-modal-wrapper").fadeOut(300, function () {
              jQuery(this).remove();
            });

            $(".explm-loading-panel").fadeOut(300);
        
            Swal.fire({
              icon: "success",
              title: "Collection request sent",
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

            $(".explm-loading-panel").fadeOut(300);
        
            showErrorsPopup(response.data.errors || []);
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
        weight: form.find('input[name="weight"]').val(),
        num_of_parcel: form.find('input[name="package_number"]').val(),
      };
    }

  function setOverseasCollectionData(form) {
    var combinedSenderStreet = form.find('input[name="customer_address"]').val()
                          + " " + form.find('input[name="house_number"]').val();
    var combinedRecipientStreet = form.find('input[name="collection_street"]').val()
                              + " " + form.find('input[name="collection_property_number"]').val();

    return {
      // Sender
      cname1: form.find('input[name="contact_person"]').val(),
      cpostal: form.find('input[name="zip_code"]').val(),
      ccity: form.find('input[name="city"]').val(),
      cstreet: combinedSenderStreet,
      cphone: form.find('input[name="phone"]').val(),
      cemail: form.find('input[name="email"]').val(),

      // Consignee
      name1: form.find('input[name="collection_company_or_personal_name"]').val(),
      pcode: form.find('input[name="collection_postal_code"]').val(),
      city: form.find('input[name="collection_city"]').val(),
      rPropNum: combinedRecipientStreet,
      phone: form.find('input[name="collection_phone"]').val(),
      email: form.find('input[name="collection_email"]').val(),

      num_of_parcel: form.find('input[name="package_number"]').val(),
      order_number: form.find('input[name="order_number"]').val(),
    };
  }


  function setHPCollectionData(form) {
    const hpNote       = (explm_ajax.hp_note || "").trim();
    const customerNote = (form.find('textarea[name="collection_info_for_sender"]').val() || "").trim();
    let sender_remark  = hpNote !== "" ? hpNote : customerNote;
    if (sender_remark.length > 100) {
      sender_remark = sender_remark.substring(0, 97) + "...";
    }

    const additional_services = form
      .find('input[name="delivery_additional_services[]"]:checked')
      .map(function () {
        return this.value;
      })
      .get()
      .join(",");

    const insuredChecked = form.find('input[name="insured_value"]').is(':checked');

    return {
      recipient_name: form.find('input[name="collection_company_or_personal_name"]').val() || "",
      recipient_phone: form.find('input[name="collection_phone"]').val() || "",
      recipient_email: form.find('input[name="collection_email"]').val() || "",
      recipient_adress:
        (form.find('input[name="collection_street"]').val() || "") +
        " " +
        (form.find('input[name="collection_property_number"]').val() || ""),
      recipient_city: form.find('input[name="collection_city"]').val() || "",
      recipient_postal_code: form.find('input[name="collection_postal_code"]').val() || "",
      recipient_country: form.find('select[name="collection_country"]').val() || "",

      sender_name: form.find('input[name="customer_name"]').val() || "",
      sender_phone: form.find('input[name="phone"]').val() || "",
      sender_email: form.find('input[name="email"]').val() || "",
      sender_adress:
        (form.find('input[name="customer_address"]').val() || "") +
        " " +
        (form.find('input[name="house_number"]').val() || ""),
      sender_city: form.find('input[name="city"]').val() || "",
      sender_postal_code: form.find('input[name="zip_code"]').val() || "",
      sender_country: form.find('input[name="country"]').val() || "",

      order_number: $("#hiddenOrderId").val() || "",
      parcel_weight: form.find('input[name="weight"]').val() || "2.00",
      parcel_remark: sender_remark,
      parcel_value: form.find('input[name="order_total"]').val() || "",

      parcel_size: form.find('select[name="parcel_size"]').val() || "",
      parcel_count: form.find('select[name="package_number"]').val() || 1,

      cod_amount: "", 
      cod_currency: "",

      value: insuredChecked ? form.find('input[name="order_total"]').val() : "",

      additional_services: additional_services,
      delivery_sevice: form.find('select[name="delivery_service"]').val() || "",

      location_id: form.find('input[name="hp_parcel_locker_location_id"]').val() || "",
      location_type: form.find('input[name="hp_parcel_locker_type"]').val() || "",
    };
  }

});

  function showErrorsPopup(errors) {
    let html = "";

    if (errors.length === 1) {
      html =
        `<b>Order number:</b> ${errors[0].order_number}<br>` +
        `<b>Error code:</b> ${errors[0].error_code || "unknown"}<br>` +
        `<b>Message:</b> ${errors[0].error_message}`;
    } else {
      errors.forEach((error, idx) => {
        html +=
          `<b>Error ${idx + 1}:</b><br>` +
          `<b>Order number:</b> ${error.order_number}<br>` +
          `<b>Error code:</b> ${error.error_code || "unknown"}<br>` +
          `<b>Message:</b> ${error.error_message}<br><br>`;
      });
    }

    Swal.fire({
      icon: "error",
      title: "Errors while creating label",
      html: html || "<b>Unknown error occurred.</b>",
      confirmButtonText: "OK",
      customClass: {
        popup: "explm-swal-scroll",
        title: "explm-swal-title",
        confirmButton: "explm-swal-button",
      },
      didOpen: () => {
        const htmlContainer = Swal.getHtmlContainer();
        if (htmlContainer) {
          htmlContainer.style.maxHeight = "50vh";
          htmlContainer.style.overflowY = "auto";
        }
      },
    });
  }