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
        showErrorPopup(response);
      },
    });
  }
  
  function displayError(error) {
    $(".explm-error").text(error);
    $(".explm-error").removeAttr("style");
  }
  
  function getStatusText(r, meta) {
    const statusHandlers = {
      _dpd_: () => r.parcel_status || "No status available",
      _overseas_: () =>
        r.data && r.data.Events && r.data.Events.length > 0
          ? r.data.Events[r.data.Events.length - 1].StatusDescription
          : "No status available",
    };
  
    for (let key in statusHandlers) {
      if (meta.includes(key)) {
        return statusHandlers[key]();
      }
    }
  
    return "No status available";
  }
  
  jQuery(document).ready(function ($) {
    const urlParams = new URLSearchParams(window.location.search);
    const isOrdersPage =
      urlParams.get("post_type") === "shop_order" ||
      window.location.href.includes("wc-orders");
  
    if (!isOrdersPage) {
      return;
    }
  
    const pagination_page =
      urlParams.get("paged") != undefined ? urlParams.get("paged") : "1";
  
    const order_rows = $("tr.type-shop_order").length;
    const limit = order_rows > 0 ? order_rows : 20;
    const offset = (pagination_page - 1) * limit;
  
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
              const parcel_status_element = $(
                `tr#post-${order.order_id}, tr#order-${order.order_id}`
              ).find("td.explm_parcel_status");
              parcel_status_element.html(
                '<img src="/wp-content/plugins/express-label-maker/assets/statusloading.gif" alt="loading">'
              );
  
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
                  const spanElement = $(
                    '<span title="' + statusText + '">' + statusText + "</span>"
                  );
                  
                  spanElement.attr("title", statusText);
  
                  applyStatusClass(spanElement, statusText);
                  parcel_status_element.html(spanElement);
                },
                error: function (response) {
                  showErrorPopup(response);
                },
              });
            }
          });
        } else {
          showErrorPopup(response);
        }
      },
      error: function (response) {
        showErrorPopup(response);
      },
    });
  });
  
  function applyStatusClass(element, status) {
    if (status) {
      element.addClass("explm-package-status order-status");
      switch (status) {
        case "PRINTED": //DPD
          element.addClass("explm-status-printed");
          break;
        case "DELIVERED": //DPD
          element.addClass("explm-status-delivered");
          break;
          case "DELIVERED_BY_DRIVER_TO_DPD_PARCELSHOP ": //DPD
          element.addClass("explm-status-delivered");
          break;
        case "CANCELLED": //DPD
          element.addClass("explm-status-cancelled");
          break;
        case "Otkupnina plaćena gotovinom.": //OVERSEAS
          element.addClass("explm-status-delivered");
          break;
        case "Prijevoz/pouzeće je naplaćen.": //OVERSEAS
          element.addClass("explm-status-delivered");
          break;
        case "Pošiljka je isporučena.": //OVERSEAS
          element.addClass("explm-status-delivered");
          break;
        case "Pošiljka je isporučena originalnom pošiljatelju.": //OVERSEAS
          element.addClass("explm-status-delivered");
          break;
        default:
          element.addClass("explm-status-rest");
          break;
      }
    }
  }
  
  jQuery(document).ready(function ($) {
    $("td.explm_parcel_status span").each(function () {
      var status = $(this).text();
      applyStatusClass($(this), status);
    });
  });
  
  function showErrorPopup(response) {
    let errorId = response?.data?.error_id ?? "null";
    let errorMessage = response?.data?.error_message ?? "null";
  
    Swal.fire({
      icon: "error",
      title: "Error",
      html:
        "<b>Error ID:</b> " + errorId + "<br>" +
        "<b>Message:</b> " + errorMessage,
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