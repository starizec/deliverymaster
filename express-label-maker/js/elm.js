
jQuery(document).ready(function ($) {
  function togglePaketomatSelect() {
    if ($("#enable_paketomat").is(":checked")) {
      $("#paketomat_shipping_method_row").show();
    } else {
      $("#paketomat_shipping_method_row").hide();
    }
  }
  togglePaketomatSelect();

  $("#enable_paketomat").on("change", togglePaketomatSelect);
});