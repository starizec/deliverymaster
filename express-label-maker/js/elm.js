// elm.js
(function ($, w) {
  'use strict';

  // ---- Admin UI: Paketomat toggle
  function togglePaketomatSelect() {
    if ($("#enable_paketomat").is(":checked")) {
      $("#paketomat_shipping_method_row").show();
    } else {
      $("#paketomat_shipping_method_row").hide();
    }
  }
  $(function () {
    // DOM ready
    if ($("#enable_paketomat").length && $("#paketomat_shipping_method_row").length) {
      togglePaketomatSelect();
      $("#enable_paketomat").on("change", togglePaketomatSelect);
    }
  });

  // ---- Namespace
  const explm = w.explm = w.explm || {};

  /**
   * Centralizirani popup za greške.
   * @param {any} respOrErrors - može biti cijeli response, response.data, response.data.errors ili array error objekata
   * @param {Object} opts - { title?: string, fallbackMessage?: string }
   * @returns {Promise} Promise od Swal.fire (ili resolved Promise ako Swal nije dostupan)
   */
  explm.showErrorsPopup = function (respOrErrors, opts = {}) {
    const title = opts.title || 'Error';
    const fallbackMessage = opts.fallbackMessage || 'Unknown error occurred.';

    // 1) Izvuci errors bez obzira na oblik
    let errors = [];
    if (Array.isArray(respOrErrors)) {
      errors = respOrErrors;
    } else if (respOrErrors?.data?.errors) {
      errors = respOrErrors.data.errors;
    } else if (respOrErrors?.errors) {
      errors = respOrErrors.errors;
    } else if (respOrErrors?.response?.data?.errors) {
      // Axios error
      errors = respOrErrors.response.data.errors;
    } else if (respOrErrors?.data && respOrErrors?.success === false) {
      // ponekad backend vraća { success:false, data:{...} } bez errors polja
      errors = [respOrErrors.data];
    }

    // 2) Ako dođe single objekt umjesto niza
    if (!Array.isArray(errors) && errors && typeof errors === 'object') {
      errors = [errors];
    }

    // 3) Normalizacija
    const normalized = (errors || []).map(e => ({
      order: e?.order_number ?? e?.order ?? null,
      code: e?.error_code ?? e?.code ?? 'unknown',
      message: e?.error_message ?? e?.message ?? 'unknown',
    }));

    // 4) HTML (Order number samo ako postoji)
    const render = (err, i, many) => {
      return (
        (many ? `<b>Error ${i + 1}:</b><br>` : '') +
        (err.order ? `<b>Order number:</b> ${String(err.order)}<br>` : '') +
        `<b>Error code:</b> ${String(err.code)}<br>` +
        `<b>Message:</b> ${String(err.message)}`
      );
    };

    let html;
    if (!normalized.length) {
      html = `<b>${fallbackMessage}</b>`;
    } else if (normalized.length === 1) {
      html = render(normalized[0], 0, false);
    } else {
      html = normalized.map((e, i) => render(e, i, true)).join('<br><br>');
    }

    // 5) Prikaz
    if (w.Swal && typeof w.Swal.fire === 'function') {
      return Swal.fire({
        icon: 'error',
        title,
        html,
        confirmButtonText: 'OK',
        customClass: {
          popup: 'explm-swal-scroll',
          title: 'explm-swal-title',
          confirmButton: 'explm-swal-button',
        },
        didOpen: () => {
          const el = Swal.getHtmlContainer();
          if (el) { el.style.maxHeight = '50vh'; el.style.overflowY = 'auto'; }
        }
      });
    } else {
      // Fallback (bez SweetAlert2)
      alert(title + '\n\n' + $(html).text());
      return Promise.resolve();
    }
  };

})(jQuery, window);