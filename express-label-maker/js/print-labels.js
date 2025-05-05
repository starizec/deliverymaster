jQuery(document).ready(function ($) {
    $("body").append(
      '<div class="explm-loading-panel"><div class="explm-spinner"></div></div>'
    );
  
    var orderFilterForm = $("#posts-filter, #wc-orders-filter");
  
    orderFilterForm.on("submit", function (e) {
      const actionValue = $(this).find('select[name="action"]').val();
  
      console.log(actionValue, "action");
  
      const supportedActionValues = [
        "explm_dpd_print_label",
        "explm_gls_print_label",
        "explm_overseas_print_label",
        "explm_hp_print_label",
      ];
  
      if (supportedActionValues.includes(actionValue)) {
        e.preventDefault();
  
        $(".explm-loading-panel").fadeIn(300).css({
          display: "flex",
          "z-index": "9999999",
        });
  
        var checkedPostIds = $(
          'input[name="post[]"]:checked, input[name="id[]"]:checked'
        )
          .map(function () {
            return $(this).val();
          })
          .get();
  
        if (checkedPostIds.length === 0) {
          jQuery(".explm-modal-wrapper").fadeOut(300, function () {
            jQuery(this).remove();
          });
          Swal.fire({
            icon: "warning",
            title: "No orders selected",
            text: "Please select at least one order to generate labels.",
            confirmButtonText: "OK",
            customClass: {
              popup: 'explm-swal-scroll',
              title: 'explm-swal-title',
              confirmButton: 'explm-swal-button'
            },
          })
          return;
        }
  
        $.ajax({
          url: explm_ajax.ajax_url,
          method: "POST",
          data: {
            action: "explm_print_labels",
            security: explm_ajax.nonce,
            post_ids: checkedPostIds,
            actionValue: actionValue,
          },
          success: function (response) {
            console.log(response, "resp");
          
            $(".explm-loading-panel").fadeOut(300);
          
            if (response.success) {
          
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

              if (!response.data.errors || response.data.errors.length === 0) {
                location.reload();
              }
  
              if (response.data.errors && response.data.errors.length > 0) {
                let errorsHtml = '';
          
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
  
                jQuery(".explm-modal-wrapper").fadeOut(300, function () {
                  jQuery(this).remove();
                });
          
                Swal.fire({
                  icon: "warning",
                  title: "Some orders could not generate labels",
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
                }).then(() => {
                  location.reload();
                });
              }
                  
            } else {
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
                title: "Errors while creating labels",
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
          },        
          error: function () {
            $(".explm-loading-panel").fadeOut(300);
            Swal.fire({
              icon: "error",
              title: "Server error",
              text: "An error occurred while connecting to the server. Please try again.",
              confirmButtonText: "OK",
              customClass: {
                popup: 'explm-swal-scroll',
                title: 'explm-swal-title',
                confirmButton: 'explm-swal-button'
              },
            });
          }
        });
      }
    });
  });