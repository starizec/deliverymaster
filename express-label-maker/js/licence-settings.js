function showErrorsPopup(errors) {
  let html = "";

  if (!errors || errors.length === 0) {
      html = "Unknown error occurred.";
  } else if (errors.length === 1) {
      html = `<b>Error code:</b> ${errors[0].error_code || "unknown"}<br>` +
             `<b>Message:</b> ${errors[0].error_message || "unknown"}`;
  } else {
      errors.forEach((error, idx) => {
          html += `<b>Error ${idx + 1}:</b><br>` +
                  `<b>Error code:</b> ${error.error_code || "unknown"}<br>` +
                  `<b>Message:</b> ${error.error_message || "unknown"}<br><br>`;
      });
  }

  Swal.fire({
      icon: "error",
      title: "Error",
      html: html,
      confirmButtonText: "OK",
      customClass: {
          popup: 'explm-swal-scroll',
          title: 'explm-swal-title',
          confirmButton: 'explm-swal-button'
      },
  });
}

jQuery(document).ready(function ($) {
  $("body").append(
    '<div class="explm-loading-panel"><div class="explm-spinner"></div></div>'
  );

  $(document).on("click", ".explm-start-trial-btn", function (e) {
      e.preventDefault();

      $(".explm-loading-panel").fadeIn(300).css({
          display: "flex",
          "z-index": "9999999",
      });

      $.ajax({
          url: explm_ajax.ajax_url,
          type: "POST",
          data: {
              action: "explm_start_trial",
              security: explm_ajax.nonce,
              email: $("#explm_email").val(),
              domain: window.location.hostname,
              licence: "trial",
          },
          success: function (response) {
              $(".explm-loading-panel").fadeOut(300);
              if (response.success) {
                  $("#explm_email").val(response.data.email);
                  $("#explm_licence_key").val(response.data.licence);
                  $(".explm-start-trial-btn").hide();
                  $("#explm_submit_btn").prop("disabled", false);

                  Swal.fire({
                      icon: "success",
                      title: "Trial Started",
                      text: "Your license has been generated and your trial has started.",
                      confirmButtonText: "OK",
                      customClass: {
                          popup: 'explm-swal-scroll',
                          title: 'explm-swal-title',
                          confirmButton: 'explm-swal-button'
                      },
                  }).then(function() {
                    location.reload();
                });

                  jQuery(".explm-modal-wrapper").fadeOut(300, function () {
                      jQuery(this).remove();
                  });
              } else {
                  showErrorsPopup(response.data.errors || []);
              }
          },
      });
  });
});

// LICENCE CHECK
jQuery(document).ready(function ($) {
  const url = window.location.search;

  if (
      url !== "?page=express_label_maker&tab=licence" &&
      url !== "?page=express_label_maker"
  ) {
      return;
  }

  if (!explm_ajax.email || !explm_ajax.licence) {
      return;
  }

  $(".explm-loading-panel").fadeIn(300).css({
      display: "flex",
      "z-index": "9999999",
  });

  $.ajax({
      url: explm_ajax.ajax_url,
      type: "POST",
      data: {
          action: "explm_licence_check",
          security: explm_ajax.nonce,
          domain: window.location.hostname,
      },
      success: function (response) {
          $(".explm-loading-panel").fadeOut(300);
          if (response.success) {
              $("#explm_valid_from").val(response.data.valid_from);
              $("#explm_valid_until").val(response.data.valid_until);
              $("#explm_usage_limit").val(response.data.usage_limit);
              $("#explm_usage").val(response.data.usage);

              const usage = parseInt(response.data.usage, 10);
              if (!isNaN(usage) && usage >= 1) {
                  const totalMinutes = usage * 5;
                  const days = Math.floor(totalMinutes / 1440);
                  const hours = Math.floor(totalMinutes / 60);
                  const minutes = totalMinutes % 60;   

                  const message = explm_ajax.savedLabelTime
                  .replace("%1$d", totalMinutes)
                  .replace("%2$d", hours)
                  .replace("%3$d", minutes)
                  .replace("%4$d", days);

                  const output = $(`
                      <div style="
                        margin-top: 20px;
                        padding: 25px;
                        background: linear-gradient(135deg, #0477d880, #ffffff);
                        border-radius: 12px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                        text-align: center;
                        font-size: 18px;
                        font-weight: bold;
                        animation: pulse 2s infinite;
                      ">
                        🕒 ${message}
                      </div>

                      <style>
                        @keyframes pulse {
                          0% { box-shadow: 0 0 0 0 #0477d880; }
                          70% { box-shadow: 0 0 0 10px rgba(76, 175, 80, 0); }
                          100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
                        }
                      </style>
                  `);

                  $("#explm_usage").closest("table").after(output);
              }

              let remaining = response.data.usage_limit - response.data.usage;

              if (remaining <= 2) {
                  let message = remaining > 0
                      ? "Only " + remaining + " label(s) left until the limit!"
                      : "All labels have been used up!";
              
                  Swal.fire({
                      icon: "warning",
                      title: "Usage Warning",
                      text: message,
                      confirmButtonText: "OK",
                      customClass: {
                          popup: 'explm-swal-scroll',
                          title: 'explm-swal-title',
                          confirmButton: 'explm-swal-button'
                      },
                  });
              }              

              if (response.data.valid_until != null) {
                  let today = new Date();
                  let validUntil = new Date(response.data.valid_until);

                  let timeDifference = validUntil.getTime() - today.getTime();
                  let dayDifference = timeDifference / (1000 * 3600 * 24);

                  if (dayDifference <= 10) {
                      Swal.fire({
                          icon: "warning",
                          title: "License Expiry Warning",
                          text: "You have " + Math.round(dayDifference) + " day(s) left until your license expires!",
                          confirmButtonText: "OK",
                          customClass: {
                              popup: 'explm-swal-scroll',
                              title: 'explm-swal-title',
                              confirmButton: 'explm-swal-button'
                          },
                      });
                  }
              }
          } else {
              showErrorsPopup(response.data.errors || []);
          }
      },
  });
});

// LICENCE JS
document.addEventListener("DOMContentLoaded", function () {
  const url = window.location.search;

  if (
      url !== "?page=express_label_maker&tab=licence" &&
      url !== "?page=express_label_maker"
  ) {
      return;
  }

  var emailInput = document.getElementById("explm_email");
  var licenceKeyInput = document.getElementById("explm_licence_key");
  var countrySelect = document.getElementById("explm_country");
  var startTrialButton = document.getElementById("start-trial-btn");
  var submitButton = document.getElementById("explm_submit_btn");

  function toggleStartTrialButton() {
      startTrialButton.style.display =
          licenceKeyInput.value.trim() === "" ? "inline-block" : "none";
  }

  function toggleSubmitButton() {
      submitButton.disabled =
          emailInput.value.trim() === "" ||
          licenceKeyInput.value.trim() === "" ||
          countrySelect.value.trim() === "";
  }

  toggleStartTrialButton();
  toggleSubmitButton();

  licenceKeyInput.addEventListener("input", function () {
      toggleStartTrialButton();
      toggleSubmitButton();
  });

  emailInput.addEventListener("input", toggleSubmitButton);
  countrySelect.addEventListener("change", toggleSubmitButton);
});