jQuery(document).ready(function ($) {
    // Dodaj loader samo ako ne postoji
    if (!$(".explm-loading-panel").length) {
      $("body").append('<div class="explm-loading-panel"><div class="explm-spinner"></div></div>');
    }
  
    function showLoader() {
      $(".explm-loading-panel").fadeIn(300).css({ display: "flex", "z-index": "9999999" });
    }
  
    function hideLoader() {
      $(".explm-loading-panel").fadeOut(300);
    }
  
    function hideModal() {
      $(".explm-modal-wrapper").fadeOut(300, function () {
        $(this).remove();
      });
    }
  
    // Otvori modal za potvrdu
    $("body").on("click", ".explm-open-modal", function (e) {
      e.preventDefault();
  
      const orderId = $(this).data("order-id");
      const courier = $(this).data("courier");
  
      showLoader();
  
      $.post(explm_ajax.ajax_url, {
        action: "explm_show_confirm_modal",
        security: explm_ajax.nonce,
        order_id: orderId,
        courier: courier
      }, function (response) {
        if (response.success) {
          $("body").append(response.data);
          $(".explm-modal-wrapper").fadeIn(300).css("display", "flex");
          $("#hiddenCourier").val(courier);
        } else {
          showErrorPopup(response.data.error_id, response.data.error_message);
        }
        hideLoader();
      }).fail(hideLoader);
    });
  
    // Cancel modal
    $("body").on("click", ".explm-cancel-action", function (e) {
      e.preventDefault();
      hideModal();
    });
  
    // Confirm print label
    $(document).on("click", ".explm_confirm_action", function (e) {
      e.preventDefault();
  
      showLoader();
  
      const courier = $("#hiddenCourier").val();
      const form = $("#explm-order-details-form");
      const orderId = $("#hiddenOrderId").val();
  
      let parcelData;
      switch (courier) {
        case "dpd":
          parcelData = setDPDParcelData(form);
          break;
        case "overseas":
          parcelData = setOverseasParcelData(form);
          break;
        default:
          parcelData = {};
      }
  
      $.post(explm_ajax.ajax_url, {
        action: "explm_print_label",
        parcel: parcelData,
        security: explm_ajax.nonce,
        chosenCourier: courier,
        orderId: orderId
      }, function (response) {
        hideLoader();
  
        if (response.success) {
          if (response.data.file_path) {
            window.open(response.data.file_path, "_blank");
          } else if (response.data.pdf_data) {
            downloadPdf(response.data.pdf_data, response.data.file_name);
          }
          hideModal();
          location.reload();
        } else {
          hideModal();
          showErrorsPopup(response.data.errors || []);
        }
      }).fail(hideLoader);
    });
  
    function setDPDParcelData(form) {
      const isCod = form.find('input[name="parcel_type"]:checked').val() === "cod";
      const dpdParcelLockerId = form.find('input[name="parcel_locker"]').val();
    
      let parcelType = "";
      if (dpdParcelLockerId) {
        parcelType = "D-B2C-PSD";
      } else if (explm_ajax.serviceType === "DPD Classic") {
        parcelType = isCod ? "D-COD" : "D";
      } else if (explm_ajax.serviceType === "DPD Home") {
        parcelType = isCod ? "D-COD-B2C" : "D-B2C";
      }

      const dpdNote = (explm_ajax.dpd_note || "").trim();
      const customerNote = (form.find('textarea[name="note"]').val() || "").trim();
    
      let sender_remark = dpdNote !== "" ? dpdNote : customerNote;
    
      if (sender_remark.length > 50) {
        sender_remark = sender_remark.substring(0, 47) + '...';
      }
    
      const data = {
        cod_amount: form.find('input[name="cod_amount"]').val(),
        name1: form.find('input[name="customer_name"]').val(),
        street: form.find('input[name="customer_address"]').val(),
        rPropNum: form.find('input[name="house_number"]').val(),
        city: form.find('input[name="city"]').val(),
        country: form.find('input[name="country"]').val(),
        pcode: form.find('input[name="zip_code"]').val(),
        email: form.find('input[name="email"]').val(),
        sender_remark: sender_remark,
        weight: form.find('input[name="weight"]').val(),
        order_number: form.find('input[name="reference"]').val(),
        cod_purpose: form.find('input[name="reference"]').val(),
        parcel_type: parcelType,
        num_of_parcel: form.find('input[name="package_number"]').val(),
        phone: form.find('input[name="phone"]').val(),
        contact: form.find('input[name="contact_person"]').val()
      };
    
      if (dpdParcelLockerId) {
        data.pudo_id = dpdParcelLockerId;
      }
    
      return data;
    }    
  
    function setOverseasParcelData(form) {
      const isCod = form.find('input[name="parcel_type"]:checked').val() === "cod";
      const overseasParcelLockerId = form.find('input[name="parcel_locker"]').val();

      const overseasNote = (explm_ajax.overseas_note || "").trim();
      const customerNote = (form.find('textarea[name="note"]').val() || "").trim();
      
      let sender_remark = overseasNote !== "" ? overseasNote : customerNote;
      
      if (sender_remark.length > 35) {
          sender_remark = sender_remark.substring(0, 32) + '...';
      }
  
      const data = {
        cod_amount: isCod ? form.find('input[name="cod_amount"]').val() : null,
        name1: form.find('input[name="customer_name"]').val(),
        rPropNum: form.find('input[name="customer_address"]').val() + " " + form.find('input[name="house_number"]').val(),
        city: form.find('input[name="city"]').val(),
        pcode: form.find('input[name="zip_code"]').val(),
        email: form.find('input[name="email"]').val(),
        sender_remark: sender_remark,
        order_number: form.find('input[name="reference"]').val(),
        num_of_parcel: form.find('input[name="package_number"]').val(),
        phone: form.find('input[name="phone"]').val(),
        pudo_id: overseasParcelLockerId
      };

      if (overseasParcelLockerId) {
        data.pudo_id = overseasParcelLockerId;
      }
    
      return data;
    }
  
    function downloadPdf(base64data, fileName) {
      const binary = atob(base64data);
      const array = new Uint8Array(binary.length);
      for (let i = 0; i < binary.length; i++) {
        array[i] = binary.charCodeAt(i);
      }
      const blob = new Blob([array], { type: "application/pdf" });
      const url = URL.createObjectURL(blob);
      window.open(url, "_blank");
  
      const a = document.createElement("a");
      a.href = url;
      a.download = fileName;
      document.body.appendChild(a);
      a.click();
      URL.revokeObjectURL(url);
      document.body.removeChild(a);
    }
  
    function showErrorsPopup(errors) {
      let html = "";
  
      if (errors.length === 1) {
        html = `<b>Order number:</b> ${errors[0].order_number}<br>` +
               `<b>Error code:</b> ${errors[0].error_code || "unknown"}<br>` +
               `<b>Message:</b> ${errors[0].error_message}`;
    } else {
        errors.forEach((error, idx) => {
            html += `<b>Error ${idx + 1}:</b><br>` +
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
  
    function showErrorPopup(errorId, errorMessage) {
      Swal.fire({
        icon: "error",
        title: "Request failed",
        html: `<b>Error ID:</b> ${errorId}<br><b>Message:</b> ${errorMessage}`,
        confirmButtonText: "OK",
        customClass: {
          popup: 'explm-swal-scroll',
          title: 'explm-swal-title',
          confirmButton: 'explm-swal-button'
        },
      });
    }
  });  