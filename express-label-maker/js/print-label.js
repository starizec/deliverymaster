jQuery(document).ready(function ($) {
  // Dodaj loader samo ako ne postoji
  if (!$(".explm-loading-panel").length) {
    $("body").append(
      '<div class="explm-loading-panel"><div class="explm-spinner"></div></div>'
    );
  }

  function showLoader() {
    $(".explm-loading-panel")
      .fadeIn(300)
      .css({ display: "flex", "z-index": "9999999" });
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

    $.post(
      explm_ajax.ajax_url,
      {
        action: "explm_show_confirm_modal",
        security: explm_ajax.nonce,
        order_id: orderId,
        courier: courier,
      },
      function (response) {
        if (response.success) {
          $("body").append(response.data);
          $(".explm-modal-wrapper").fadeIn(300).css("display", "flex");
          $("#hiddenCourier").val(courier);
        } else {
          showErrorPopup(response.data.error_id, response.data.error_message);
        }
        hideLoader();
      }
    ).fail(hideLoader);
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
      case "hp":
        parcelData = setHPParcelData(form);
        break;
      case "gls":
        parcelData = setGLSParcelData(form);
        break;
      default:
        parcelData = {};
    }

    $.post(
      explm_ajax.ajax_url,
      {
        action: "explm_print_label",
        parcel: parcelData,
        security: explm_ajax.nonce,
        chosenCourier: courier,
        orderId: orderId,
      },
      function (response) {
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
          explm.showErrorsPopup(response, { title: 'Errors while creating label' });
        }
      }
    ).fail(hideLoader);
  });


function setDPDParcelData(form) {
    const isCod =
      form.find('input[name="parcel_type"]:checked').val() === "cod";
    const parcelLockerId =
      form.find('input[name="dpd_parcel_locker_location_id"]').val() || "";
    const parcelLockerType =
      form.find('input[name="dpd_parcel_locker_type"]').val() || "";

    const dpdNote = (explm_ajax.dpd_note || "").trim();
    const customerNote = (
      form.find('textarea[name="note"]').val() || ""
    ).trim();

    let sender_remark = dpdNote !== "" ? dpdNote : customerNote;

    if (sender_remark.length > 50) {
      sender_remark = sender_remark.substring(0, 47) + "...";
    }

    const data = {
      recipient_name: form.find('input[name="customer_name"]').val() || "",
      recipient_phone: form.find('input[name="phone"]').val() || "",
      recipient_email: form.find('input[name="email"]').val() || "",
      recipient_adress:
        (form.find('input[name="customer_address"]').val() || "") +
        " " +
        (form.find('input[name="house_number"]').val() || ""),
      recipient_city: form.find('input[name="city"]').val() || "",
      recipient_postal_code: form.find('input[name="zip_code"]').val() || "",
      recipient_country: form.find('input[name="country"]').val() || "",

      sender_name: explm_ajax.dpd_sender_name || "",
      sender_phone: explm_ajax.dpd_sender_phone || "",
      sender_email: explm_ajax.dpd_sender_email || "",
      sender_adress:
        (explm_ajax.dpd_sender_street || "") +
        " " +
        (explm_ajax.dpd_sender_number || ""),
      sender_city: explm_ajax.dpd_sender_city || "",
      sender_postal_code: explm_ajax.dpd_sender_postcode || "",
      sender_country: explm_ajax.dpd_sender_country || "",

      order_number: form.find('input[name="reference"]').val() || "",
      parcel_weight: form.find('input[name="weight"]').val() || "2.00",
      parcel_remark: sender_remark,
      parcel_value: form.find('input[name="order_total"]').val() || "",

      parcel_size: form.find('select[name="parcel_size"]').val() || "",
      parcel_count: form.find('select[name="package_number"]').val() || 1,

      cod_amount: isCod
        ? form.find('input[name="cod_amount"]').val()
        : "",
      cod_currency: isCod ? form.find('input[name="hiddenCurrency"]').val() : "",

      value: "",

      delivery_service: form.find('select[name="delivery_service"]').val() || "",

      location_id: parcelLockerId,
      location_type: parcelLockerType,
    };

    return data;
  }

  function setOverseasParcelData(form) {
    const isCod =
      form.find('input[name="parcel_type"]:checked').val() === "cod";
    const parcelLockerId =
      form.find('input[name="overseas_parcel_locker_location_id"]').val() || "";
    const parcelLockerType =
      form.find('input[name="overseas_parcel_locker_type"]').val() || "";

    const overseasNote = (explm_ajax.overseas_note || "").trim();
    const customerNote = (
      form.find('textarea[name="note"]').val() || ""
    ).trim();

    let sender_remark = overseasNote !== "" ? overseasNote : customerNote;

    if (sender_remark.length > 35) {
      sender_remark = sender_remark.substring(0, 32) + "...";
    }

    const data = {
      recipient_name: form.find('input[name="customer_name"]').val() || "",
      recipient_phone: form.find('input[name="phone"]').val() || "",
      recipient_email: form.find('input[name="email"]').val() || "",
      recipient_adress:
        (form.find('input[name="customer_address"]').val() || "") +
        " " +
        (form.find('input[name="house_number"]').val() || ""),
      recipient_city: form.find('input[name="city"]').val() || "",
      recipient_postal_code: form.find('input[name="zip_code"]').val() || "",
      recipient_country: form.find('input[name="country"]').val() || "",

      sender_name: explm_ajax.overseas_sender_name || "",
      sender_phone: explm_ajax.overseas_sender_phone || "",
      sender_email: explm_ajax.overseas_sender_email || "",
      sender_adress:
        (explm_ajax.overseas_sender_street || "") +
        " " +
        (explm_ajax.overseas_sender_number || ""),
      sender_city: explm_ajax.overseas_sender_city || "",
      sender_postal_code: explm_ajax.overseas_sender_postcode || "",
      sender_country: explm_ajax.overseas_sender_country || "",

      order_number: form.find('input[name="reference"]').val() || "",
      parcel_weight: form.find('input[name="weight"]').val() || "2.00",
      parcel_remark: sender_remark,
      parcel_value: form.find('input[name="order_total"]').val() || "",

      parcel_size: form.find('select[name="parcel_size"]').val() || "",
      parcel_count: form.find('select[name="package_number"]').val() || 1,

      cod_amount: isCod
        ? form.find('input[name="cod_amount"]').val()
        : "",
      cod_currency: isCod ? form.find('input[name="hiddenCurrency"]').val() : "",

      value: "",

      location_id: parcelLockerId,
      location_type: parcelLockerType,
    };

    return data;
  }

  function setHPParcelData(form) {
    const isCod =
      form.find('input[name="parcel_type"]:checked').val() === "cod";
    const parcelLockerId =
      form.find('input[name="hp_parcel_locker_location_id"]').val() || "";
    const parcelLockerType =
      form.find('input[name="hp_parcel_locker_type"]').val() || "";

    const hpNote = (explm_ajax.hp_note || "").trim();
    const customerNote = (
      form.find('textarea[name="note"]').val() || ""
    ).trim();

    let sender_remark = hpNote !== "" ? hpNote : customerNote;
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

    const data = {
      recipient_name: form.find('input[name="customer_name"]').val() || "",
      recipient_phone: form.find('input[name="phone"]').val() || "",
      recipient_email: form.find('input[name="email"]').val() || "",
      recipient_adress:
        (form.find('input[name="customer_address"]').val() || "") +
        " " +
        (form.find('input[name="house_number"]').val() || ""),
      recipient_city: form.find('input[name="city"]').val() || "",
      recipient_postal_code: form.find('input[name="zip_code"]').val() || "",
      recipient_country: form.find('input[name="country"]').val() || "",

      sender_name: explm_ajax.hp_sender_name || "",
      sender_phone: explm_ajax.hp_sender_phone || "",
      sender_email: explm_ajax.hp_sender_email || "",
      sender_adress:
        (explm_ajax.hp_sender_street || "") +
        " " +
        (explm_ajax.hp_sender_number || ""),
      sender_city: explm_ajax.hp_sender_city || "",
      sender_postal_code: explm_ajax.hp_sender_postcode || "",
      sender_country: explm_ajax.hp_sender_country || "",

      order_number: form.find('input[name="reference"]').val() || "",
      parcel_weight: form.find('input[name="weight"]').val() || "2.00",
      parcel_remark: sender_remark,
      parcel_value: form.find('input[name="order_total"]').val() || "",

      parcel_size: form.find('select[name="parcel_size"]').val() || "",
      parcel_count: form.find('select[name="package_number"]').val() || 1,

      cod_amount: isCod
        ? form.find('input[name="cod_amount"]').val()
        : "",
      cod_currency: isCod ? form.find('input[name="hiddenCurrency"]').val() : "",

      value: insuredChecked ? form.find('input[name="order_total"]').val() : "",

      additional_services: additional_services,
      delivery_service: form.find('select[name="delivery_service"]').val() || "",

      location_id: parcelLockerId,
      location_type: parcelLockerType,
    };

    return data;
  }

  function setGLSParcelData(form) {
    const isCod =
      form.find('input[name="parcel_type"]:checked').val() === "cod";
    const parcelLockerId =
      form.find('input[name="gls_parcel_locker_location_id"]').val() || "";
    const parcelLockerType =
      form.find('input[name="gls_parcel_locker_type"]').val() || "";

    const glsNote = (explm_ajax.gls_note || "").trim();
    const customerNote = (
      form.find('textarea[name="note"]').val() || ""
    ).trim();

    let sender_remark = glsNote !== "" ? glsNote : customerNote;
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

    const data = {
      recipient_name: form.find('input[name="customer_name"]').val() || "",
      recipient_phone: form.find('input[name="phone"]').val() || "",
      recipient_email: form.find('input[name="email"]').val() || "",
      recipient_adress:
        (form.find('input[name="customer_address"]').val() || "") +
        " " +
        (form.find('input[name="house_number"]').val() || ""),
      recipient_city: form.find('input[name="city"]').val() || "",
      recipient_postal_code: form.find('input[name="zip_code"]').val() || "",
      recipient_country: form.find('input[name="country"]').val() || "",

      sender_name: explm_ajax.gls_sender_name || "",
      sender_phone: explm_ajax.gls_sender_phone || "",
      sender_email: explm_ajax.gls_sender_email || "",
      sender_adress:
        (explm_ajax.gls_sender_street || "") +
        " " +
        (explm_ajax.gls_sender_number || ""),
      sender_city: explm_ajax.gls_sender_city || "",
      sender_postal_code: explm_ajax.gls_sender_postcode || "",
      sender_country: explm_ajax.gls_sender_country || "",

      order_number: form.find('input[name="reference"]').val() || "",
      parcel_weight: form.find('input[name="weight"]').val() || "2.00",
      parcel_remark: sender_remark,
      parcel_value: form.find('input[name="order_total"]').val() || "",

      parcel_size: form.find('select[name="parcel_size"]').val() || "",
      parcel_count: form.find('select[name="package_number"]').val() || 1,

      cod_amount: isCod
        ? form.find('input[name="cod_amount"]').val()
        : "",
      cod_currency: isCod ? form.find('input[name="hiddenCurrency"]').val() : "",

      value: "",

      additional_services: additional_services,
      printer_type: form.find('select[name="printer_type"]').val() || "",
      print_position: form.find('select[name="print_position"]').val() || "",

      location_id: parcelLockerId,
      location_type: parcelLockerType,
    };

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

  function showErrorPopup(errorId, errorMessage) {
    Swal.fire({
      icon: "error",
      title: "Request failed",
      html: `<b>Error ID:</b> ${errorId}<br><b>Message:</b> ${errorMessage}`,
      confirmButtonText: "OK",
      customClass: {
        popup: "explm-swal-scroll",
        title: "explm-swal-title",
        confirmButton: "explm-swal-button",
      },
    });
  }
});
