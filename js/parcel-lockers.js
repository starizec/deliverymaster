var dpdMap;
var dpdLockers;
var overseasMap;
var overseasLockers;
var hpMap;
var hpLockers;
var glsMap;
var glsLockers;

jQuery(document).ready(function ($) {
  function geocodePostcodeAndZoom(postcode) {
    
    if (!postcode) return;

    const city = $('input[name="billing_city"]').val() || "";

    const country = $('input[name="billing_country"]').val() || "";

    const query = `${postcode} ${city} ${country}`.trim();

    $.getJSON("https://nominatim.openstreetmap.org/search", {
      format: "json",
      q: query,
      limit: 1,
    }).done(function (data) {
      if (data.length > 0 && dpdMap) {
        const lat = parseFloat(data[0].lat);
        const lon = parseFloat(data[0].lon);
        dpdMap.setView([lat, lon], 14);
      }
      if (data.length > 0 && overseasMap) {
        const lat = parseFloat(data[0].lat);
        const lon = parseFloat(data[0].lon);
        overseasMap.setView([lat, lon], 14);
      }
      if (data.length > 0 && hpMap) {
        const lat = parseFloat(data[0].lat);
        const lon = parseFloat(data[0].lon);
        hpMap.setView([lat, lon], 14);
      }
      if (data.length > 0 && glsMap) {
        const lat = parseFloat(data[0].lat);
        const lon = parseFloat(data[0].lon);
        glsMap.setView([lat, lon], 14);
      }
    });
  }

  // DPD
  $(document).on("click", "#select-dpd-parcel-locker", function (e) {
    e.preventDefault();
    var $dpdButton = $(this);
    $dpdButton.prop("disabled", true).text(parcel_locker_i18n.loading);

    var dpdModal = $(`
      <div class="parcel-locker-modal dpd-parcel-locker-modal">
          <div class="parcel-locker-modal-content">
              <span class="parcel-locker-close dpd-parcel-locker-close">&times;</span>
              <div style="overflow-y: auto; overflow-x: hidden;">
              <div id="dpd-parcel-locker-map-container" style="position: relative;">
                  <div id="dpd-parcel-locker-map"></div>
                  <div id="map-loading" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
                        background: rgba(255,255,255,0.8); display: flex; align-items: center; justify-content: center; 
                        z-index: 9999;">
                      <div class="loader"></div>
                  </div>
              </div>
              <div class="parcel-locker-list-container">
                  <h3>${parcel_locker_i18n.choose_locker}</h3>
                  <div class="parcel-locker-search">
                      <input type="text" id="dpd-parcel-locker-search" placeholder="${parcel_locker_i18n.choose_locker}...">
                  </div>
                  <div class="parcel-locker-list"></div>
              </div>
              </div>
          </div>
      </div>
    `);

    $("body").append(dpdModal);
    dpdModal.fadeIn();

    $.ajax({
      url: dpd_parcel_lockers_vars.ajax_url,
      type: "POST",
      data: {
        action: "get_dpd_parcel_lockers",
        nonce: dpd_parcel_lockers_vars.nonce,
      },
      success: function (response) {
        if (response.success) {
          dpdLockers = response.data.lockers;
          initDpdMap(dpdLockers);
          populateDpdLockerList(dpdLockers);

          $("#dpd-parcel-locker-search").on("keyup", function () {
            var value = $(this).val().toLowerCase();
            $(".dpd-parcel-locker-item").filter(function () {
              $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
            if (dpdLockers && dpdMap) {
              var matchingLockers = dpdLockers.filter(function (locker) {
                return (
                  locker.name.toLowerCase().indexOf(value) > -1 ||
                  locker.address.toLowerCase().indexOf(value) > -1
                );
              });
              if (matchingLockers.length > 0) {
                var bounds = L.latLngBounds([]);
                matchingLockers.forEach(function (locker) {
                  bounds.extend([locker.lat, locker.lng]);
                });
                var computedZoom = dpdMap.getBoundsZoom(bounds);
                if (computedZoom > 16) {
                  dpdMap.setView(bounds.getCenter(), 16);
                } else {
                  dpdMap.fitBounds(bounds, { padding: [50, 50], maxZoom: 16 });
                }
              }
            }
          });
        }
      },
      complete: function () {
        $dpdButton
          .prop("disabled", false)
          .text(parcel_locker_i18n.choose_locker);
      },
    });

    dpdModal.find(".dpd-parcel-locker-close").on("click", function () {
      dpdModal.fadeOut(function () {
        $(this).remove();
      });
    });
  });

  // DPD
  function initDpdMap(lockers) {
    if (!Array.isArray(lockers) || lockers.length === 0) {
      $("#map-loading").fadeOut();
      return;
    }
    dpdMap = L.map("dpd-parcel-locker-map").setView(
      [
        dpd_parcel_lockers_vars.default_lat,
        dpd_parcel_lockers_vars.default_lng,
      ],
      8
    );
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution:
        '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(dpdMap);
    var markers = L.markerClusterGroup();
    lockers.forEach(function (locker) {
      var marker = L.marker([locker.lat, locker.lng]).bindPopup(
        `<strong>${locker.name}</strong><br>${locker.address}`
      );
      marker.on("mouseover", function () {
        this.openPopup();
      });
      marker.on("mouseout", function () {
        this.closePopup();
      });
      marker.on("click", function () {
        selectDpdLocker(locker);
      });
      markers.addLayer(marker);
    });
    dpdMap.addLayer(markers);

    setTimeout(function () {
      const postcode =
        $('input[name="billing_postcode"]').val();
      geocodePostcodeAndZoom(postcode);
    }, 500);

    if (lockers.length > 0) {
      dpdMap.fitBounds(markers.getBounds());
    }
    $("#map-loading").fadeOut();
  }

  // DPD
  function populateDpdLockerList(lockers) {
    var list = $(".parcel-locker-list");
    list.empty();

    $(".explm-no-lockers").remove();

    if (lockers.length === 0) {
      list.after(
        `<div class="explm-no-lockers">${parcel_locker_i18n.no_parcel_lockers}</div>`
      );
      return;
    }

    lockers.forEach(function (locker) {
      var item = $(`
        <div class="dpd-parcel-locker-item parcel-locker-item" data-locker-id="${locker.id}">
          <h4>${locker.name}</h4>
          <p>${locker.address}</p>
        </div>
      `);
      item.on("click", function () {
        selectDpdLocker(locker);
      });
      list.append(item);
    });
    $("#dpd-parcel-locker-search").on("keyup", function () {
      var visibleCount = $(
        ".parcel-locker-list .parcel-locker-item:visible"
      ).length;
      $(".explm-no-lockers").remove();
      if (visibleCount === 0) {
        list.append(
          `<div class="explm-no-lockers">${ parcel_locker_i18n.no_parcel_lockers }</div>`
        );
      }
    });
  }

  // Odabir DPD paketomata
  function selectDpdLocker(locker) {
    $("#dpd_parcel_locker_location_id").val(locker.id);
    $("#dpd_parcel_locker_name").val(locker.name);
    $("#dpd_parcel_locker_type").val(locker.type);
    $("#dpd_parcel_locker_address").val(locker.address);
    $("#dpd_parcel_locker_street").val(locker.street);
    $("#dpd_parcel_locker_house_number").val(locker.house_number);
    $("#dpd_parcel_locker_postal_code").val(locker.postal_code);
    $("#dpd_parcel_locker_city").val(locker.city);
    $("#selected-dpd-parcel-locker-info")
      .html(
        `<strong>${parcel_locker_i18n.selected_locker}</strong><p>${locker.name}<br>${locker.address}</p>`
      )
      .show();
    $("#clear-dpd-parcel-locker").show();
    $(".parcel-locker-modal").fadeOut(function () {
      $(this).remove();
    });
  }

  function clearDpdLockerSelection() {
    $("#dpd_parcel_locker_location_id").val("");
    $("#dpd_parcel_locker_name").val("");
    $("#dpd_parcel_locker_type").val("");
    $("#dpd_parcel_locker_address").val("");
    $("#dpd_parcel_locker_street").val("");
    $("#dpd_parcel_locker_house_number").val("");
    $("#dpd_parcel_locker_postal_code").val("");
    $("#dpd_parcel_locker_city").val("");
    $("#selected-dpd-parcel-locker-info").html("").hide();
    $("#clear-dpd-parcel-locker").hide();
  }

  $(document).on("click", "#clear-dpd-parcel-locker", function (e) {
    e.preventDefault();
    clearDpdLockerSelection();
  });

  // Overseas
  $(document).on("click", "#select-overseas-parcel-locker", function (e) {
    e.preventDefault();
    var $overseasButton = $(this);
    $overseasButton.prop("disabled", true).text(parcel_locker_i18n.loading);

    var overseasModal = $(`
      <div class="parcel-locker-modal overseas-parcel-locker-modal">
          <div class="parcel-locker-modal-content">
              <span class="parcel-locker-close overseas-parcel-locker-close">&times;</span>
              <div style="overflow-y: auto; overflow-x: hidden;">
              <div id="overseas-parcel-locker-map-container" style="position: relative;">
                  <div id="overseas-parcel-locker-map"></div>
                  <div id="map-loading" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
                        background: rgba(255,255,255,0.8); display: flex; align-items: center; justify-content: center; 
                        z-index: 9999;">
                      <div class="loader"></div>
                  </div>
              </div>
              <div class="parcel-locker-list-container">
                  <h3>${parcel_locker_i18n.choose_locker}</h3>
                  <div class="parcel-locker-search">
                      <input type="text" id="overseas-parcel-locker-search" placeholder="${parcel_locker_i18n.choose_locker}...">
                  </div>
                  <div class="parcel-locker-list"></div>
              </div>
              </div>
          </div>
      </div>
    `);

    $("body").append(overseasModal);
    overseasModal.fadeIn(400, function () {
      $.ajax({
        url: overseas_parcel_lockers_vars.ajax_url,
        type: "POST",
        data: {
          action: "get_overseas_parcel_lockers",
          nonce: overseas_parcel_lockers_vars.nonce,
        },
        success: function (response) {
          if (response.success) {
            overseasLockers = response.data.lockers;
            initOverseasMap(overseasLockers);
            populateOverseasLockerList(overseasLockers);

            $("#overseas-parcel-locker-search").on("keyup", function () {
              var value = $(this).val().toLowerCase();
              $(".overseas-parcel-locker-item").filter(function () {
                $(this).toggle(
                  $(this).text().toLowerCase().indexOf(value) > -1
                );
              });

              if (overseasLockers && overseasMap) {
                var matchingLockers = overseasLockers.filter(function (locker) {
                  return (
                    locker.name.toLowerCase().indexOf(value) > -1 ||
                    locker.address.toLowerCase().indexOf(value) > -1
                  );
                });

                if (matchingLockers.length > 0) {
                  var bounds = L.latLngBounds([]);
                  matchingLockers.forEach(function (locker) {
                    bounds.extend([locker.lat, locker.lng]);
                  });

                  var computedZoom = overseasMap.getBoundsZoom(bounds);
                  if (computedZoom > 16) {
                    overseasMap.setView(bounds.getCenter(), 16);
                  } else {
                    overseasMap.fitBounds(bounds, {
                      padding: [50, 50],
                      maxZoom: 16,
                    });
                  }
                }
              }
            });
          }
        },
        complete: function () {
          $overseasButton
            .prop("disabled", false)
            .text(parcel_locker_i18n.choose_locker);
        },
      });
    });

    overseasModal
      .find(".overseas-parcel-locker-close")
      .on("click", function () {
        overseasModal.fadeOut(function () {
          $(this).remove();
        });
      });
  });

  // Overseas
  function initOverseasMap(lockers) {
    if (!Array.isArray(lockers) || lockers.length === 0) {
      $("#map-loading").fadeOut();
      return;
    }
    overseasMap = L.map("overseas-parcel-locker-map").setView(
      [
        overseas_parcel_lockers_vars.default_lat,
        overseas_parcel_lockers_vars.default_lng,
      ],
      8
    );

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution:
        '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(overseasMap);

    var markers = L.markerClusterGroup();

    lockers.forEach(function (locker) {
      var marker = L.marker([locker.lat, locker.lng]).bindPopup(
        `<strong>${locker.name}</strong><br>${locker.address}`
      );
      marker.on("mouseover", function () {
        this.openPopup();
      });
      marker.on("mouseout", function () {
        this.closePopup();
      });
      marker.on("click", function () {
        selectOverseasLocker(locker);
      });
      markers.addLayer(marker);
    });

    overseasMap.addLayer(markers);

    setTimeout(function () {
      const postcode =
        $('input[name="billing_postcode"]').val();
      geocodePostcodeAndZoom(postcode);
    }, 500);

    if (lockers.length > 0) {
      overseasMap.fitBounds(markers.getBounds());
    }

    $("#map-loading").fadeOut();
  }

  // Overseas
  function populateOverseasLockerList(lockers) {
    var list = $(".parcel-locker-list");
    list.empty();
    $(".explm-no-lockers").remove();

    if (lockers.length === 0) {
      list.after(
        `<div class="explm-no-lockers">${parcel_locker_i18n.no_parcel_lockers}</div>`
      );
      return;
    }
    lockers.forEach(function (locker) {
      var item = $(`
        <div class="overseas-parcel-locker-item parcel-locker-item" data-locker-id="${locker.id}">
          <h4>${locker.name}</h4>
          <p>${locker.address}</p>
        </div>
      `);
      item.on("click", function () {
        selectOverseasLocker(locker);
      });
      list.append(item);
    });
    $("#overseas-parcel-locker-search").on("keyup", function () {
      var visibleCount = $(
        ".parcel-locker-list .parcel-locker-item:visible"
      ).length;
      $(".explm-no-lockers").remove();
      if (visibleCount === 0) {
        list.append(
          `<div class="explm-no-lockers">${parcel_locker_i18n.no_parcel_lockers}</div>`
        );
      }
    });
  }

  function selectOverseasLocker(locker) {
    $("#overseas_parcel_locker_location_id").val(locker.id);
    $("#overseas_parcel_locker_name").val(locker.name);
    $("#overseas_parcel_locker_type").val(locker.type);
    $("#overseas_parcel_locker_address").val(locker.address);
    $("#overseas_parcel_locker_street").val(locker.street);
    $("#overseas_parcel_locker_house_number").val(locker.house_number);
    $("#overseas_parcel_locker_postal_code").val(locker.postal_code);
    $("#overseas_parcel_locker_city").val(locker.city);
    $("#selected-overseas-parcel-locker-info")
      .html(
        `<strong>${parcel_locker_i18n.selected_locker}</strong><p>${locker.name}<br>${locker.address}</p>`
      )
      .show();
    $("#clear-overseas-parcel-locker").show();
    $(".parcel-locker-modal").fadeOut(function () {
      $(this).remove();
    });
  }

  function clearOverseasLockerSelection() {
    $("#overseas_parcel_locker_location_id").val("");
    $("#overseas_parcel_locker_name").val("");
    $("#overseas_parcel_locker_type").val("");
    $("#overseas_parcel_locker_address").val("");
    $("#overseas_parcel_locker_street").val("");
    $("#overseas_parcel_locker_house_number").val("");
    $("#overseas_parcel_locker_postal_code").val("");
    $("#overseas_parcel_locker_city").val("");
    $("#selected-overseas-parcel-locker-info").html("").hide();
    $("#clear-overseas-parcel-locker").hide();
  }

  $(document).on("click", "#clear-overseas-parcel-locker", function (e) {
    e.preventDefault();
    clearOverseasLockerSelection();
  });

  // HP
  $(document).on("click", "#select-hp-parcel-locker", function (e) {
    e.preventDefault();
    var $hpButton = $(this);
    $hpButton.prop("disabled", true).text(parcel_locker_i18n.loading);

    var hpModal = $(`
      <div class="parcel-locker-modal hp-parcel-locker-modal">
          <div class="parcel-locker-modal-content">
              <span class="parcel-locker-close hp-parcel-locker-close">&times;</span>
              <div style="overflow-y: auto; overflow-x: hidden;">
              <div id="hp-parcel-locker-map-container" style="position: relative;">
                  <div id="hp-parcel-locker-map"></div>
                  <div id="map-loading" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
                        background: rgba(255,255,255,0.8); display: flex; align-items: center; justify-content: center; 
                        z-index: 9999;">
                      <div class="loader"></div>
                  </div>
              </div>
              <div class="parcel-locker-list-container">
                  <h3>${parcel_locker_i18n.choose_locker}</h3>
                  <div class="parcel-locker-search">
                      <input type="text" id="hp-parcel-locker-search" placeholder="${parcel_locker_i18n.choose_locker}...">
                  </div>
                  <div class="parcel-locker-list"></div>
              </div>
              </div>
          </div>
      </div>
    `);

    $("body").append(hpModal);
    hpModal.fadeIn();

    $.ajax({
      url: hp_parcel_lockers_vars.ajax_url,
      type: "POST",
      data: {
        action: "get_hp_parcel_lockers",
        nonce: hp_parcel_lockers_vars.nonce,
      },
      success: function (response) {
        if (response.success) {
          hpLockers = response.data.lockers;
          initHpMap(hpLockers);
          populateHpLockerList(hpLockers);

          $("#hp-parcel-locker-search").on("keyup", function () {
            var value = $(this).val().toLowerCase();
            $(".hp-parcel-locker-item").filter(function () {
              $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
            if (hpLockers && hpMap) {
              var matchingLockers = hpLockers.filter(function (locker) {
                return (
                  locker.name.toLowerCase().indexOf(value) > -1 ||
                  locker.address.toLowerCase().indexOf(value) > -1
                );
              });
              if (matchingLockers.length > 0) {
                var bounds = L.latLngBounds([]);
                matchingLockers.forEach(function (locker) {
                  bounds.extend([locker.lat, locker.lng]);
                });
                var computedZoom = hpMap.getBoundsZoom(bounds);
                if (computedZoom > 16) {
                  hpMap.setView(bounds.getCenter(), 16);
                } else {
                  hpMap.fitBounds(bounds, { padding: [50, 50], maxZoom: 16 });
                }
              }
            }
          });
        }
      },
      complete: function () {
        $hpButton
          .prop("disabled", false)
          .text(parcel_locker_i18n.choose_locker);
      },
    });

    hpModal.find(".hp-parcel-locker-close").on("click", function () {
      hpModal.fadeOut(function () {
        $(this).remove();
      });
    });
  });

  // hp
  function initHpMap(lockers) {
    if (!Array.isArray(lockers) || lockers.length === 0) {
      $("#map-loading").fadeOut();
      return;
    }
    hpMap = L.map("hp-parcel-locker-map").setView(
      [hp_parcel_lockers_vars.default_lat, hp_parcel_lockers_vars.default_lng],
      8
    );
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution:
        '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(hpMap);
    var markers = L.markerClusterGroup();
    lockers.forEach(function (locker) {
      var marker = L.marker([locker.lat, locker.lng]).bindPopup(
        `<strong>${locker.name}</strong><br>${locker.address}`
      );
      marker.on("mouseover", function () {
        this.openPopup();
      });
      marker.on("mouseout", function () {
        this.closePopup();
      });
      marker.on("click", function () {
        selectHpLocker(locker);
      });
      markers.addLayer(marker);
    });
    hpMap.addLayer(markers);

    setTimeout(function () {
      const postcode =
        $('input[name="billing_postcode"]').val();
      geocodePostcodeAndZoom(postcode);
    }, 500);

    if (lockers.length > 0) {
      hpMap.fitBounds(markers.getBounds());
    }
    $("#map-loading").fadeOut();
  }

  // hp
  function populateHpLockerList(lockers) {
    var list = $(".parcel-locker-list");
    list.empty();

    $(".explm-no-lockers").remove();

    if (lockers.length === 0) {
      list.after(
        `<div class="explm-no-lockers">${parcel_locker_i18n.no_parcel_lockers}</div>`
      );
      return;
    }

    lockers.forEach(function (locker) {
      var item = $(`
        <div class="hp-parcel-locker-item parcel-locker-item" data-locker-id="${locker.id}">
          <h4>${locker.name}</h4>
          <p>${locker.address}</p>
        </div>
      `);
      item.on("click", function () {
        selectHpLocker(locker);
      });
      list.append(item);
    });
    $("#hp-parcel-locker-search").on("keyup", function () {
      var visibleCount = $(
        ".parcel-locker-list .parcel-locker-item:visible"
      ).length;
      $(".explm-no-lockers").remove();
      if (visibleCount === 0) {
        list.append(
          `<div class="explm-no-lockers">${ parcel_locker_i18n.no_parcel_lockers }</div>`
        );
      }
    });
  }

  // Odabir hp paketomata
  function selectHpLocker(locker) {
    $("#hp_parcel_locker_location_id").val(locker.id);
    $("#hp_parcel_locker_name").val(locker.name);
    $("#hp_parcel_locker_type").val(locker.type);
    $("#hp_parcel_locker_address").val(locker.address);
    $("#hp_parcel_locker_street").val(locker.street);
    $("#hp_parcel_locker_house_number").val(locker.house_number);
    $("#hp_parcel_locker_postal_code").val(locker.postal_code);
    $("#hp_parcel_locker_city").val(locker.city);
    $("#selected-hp-parcel-locker-info")
      .html(
        `<strong>${parcel_locker_i18n.selected_locker}</strong><p>${locker.name}<br>${locker.address}</p>`
      )
      .show();
    $("#clear-hp-parcel-locker").show();
    $(".parcel-locker-modal").fadeOut(function () {
      $(this).remove();
    });
  }

  function clearHpLockerSelection() {
    $("#hp_parcel_locker_location_id").val("");
    $("#hp_parcel_locker_name").val("");
    $("#hp_parcel_locker_type").val("");
    $("#hp_parcel_locker_address").val("");
    $("#hp_parcel_locker_street").val("");
    $("#hp_parcel_locker_house_number").val("");
    $("#hp_parcel_locker_postal_code").val("");
    $("#hp_parcel_locker_city").val("");
    $("#selected-hp-parcel-locker-info").html("").hide();
    $("#clear-hp-parcel-locker").hide();
  }

  $(document).on("click", "#clear-hp-parcel-locker", function (e) {
    e.preventDefault();
    clearHpLockerSelection();
  });


 // GLS
  $(document).on("click", "#select-gls-parcel-locker", function (e) {
    e.preventDefault();
    var $glsButton = $(this);
    $glsButton.prop("disabled", true).text(parcel_locker_i18n.loading);

    var glsModal = $(`
      <div class="parcel-locker-modal gls-parcel-locker-modal">
          <div class="parcel-locker-modal-content">
              <span class="parcel-locker-close gls-parcel-locker-close">&times;</span>
              <div style="overflow-y: auto; overflow-x: hidden;">
              <div id="gls-parcel-locker-map-container" style="position: relative;">
                  <div id="gls-parcel-locker-map"></div>
                  <div id="map-loading" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
                        background: rgba(255,255,255,0.8); display: flex; align-items: center; justify-content: center; 
                        z-index: 9999;">
                      <div class="loader"></div>
                  </div>
              </div>
              <div class="parcel-locker-list-container">
                  <h3>${parcel_locker_i18n.choose_locker}</h3>
                  <div class="parcel-locker-search">
                      <input type="text" id="gls-parcel-locker-search" placeholder="${parcel_locker_i18n.choose_locker}...">
                  </div>
                  <div class="parcel-locker-list"></div>
              </div>
              </div>
          </div>
      </div>
    `);

    $("body").append(glsModal);
    glsModal.fadeIn();

    $.ajax({
      url: gls_parcel_lockers_vars.ajax_url,
      type: "POST",
      data: {
        action: "get_gls_parcel_lockers",
        nonce: gls_parcel_lockers_vars.nonce,
      },
      success: function (response) {
        if (response.success) {
          glsLockers = response.data.lockers;
          initGlsMap(glsLockers);
          populateGlsLockerList(glsLockers);

          $("#gls-parcel-locker-search").on("keyup", function () {
            var value = $(this).val().toLowerCase();
            $(".gls-parcel-locker-item").filter(function () {
              $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
            if (glsLockers && glsMap) {
              var matchingLockers = glsLockers.filter(function (locker) {
                return (
                  locker.name.toLowerCase().indexOf(value) > -1 ||
                  locker.address.toLowerCase().indexOf(value) > -1
                );
              });
              if (matchingLockers.length > 0) {
                var bounds = L.latLngBounds([]);
                matchingLockers.forEach(function (locker) {
                  bounds.extend([locker.lat, locker.lng]);
                });
                var computedZoom = glsMap.getBoundsZoom(bounds);
                if (computedZoom > 16) {
                  glsMap.setView(bounds.getCenter(), 16);
                } else {
                  glsMap.fitBounds(bounds, { padding: [50, 50], maxZoom: 16 });
                }
              }
            }
          });
        }
      },
      complete: function () {
        $glsButton
          .prop("disabled", false)
          .text(parcel_locker_i18n.choose_locker);
      },
    });

    glsModal.find(".gls-parcel-locker-close").on("click", function () {
      glsModal.fadeOut(function () {
        $(this).remove();
      });
    });
  });

  // GLS
  function initGlsMap(lockers) {
    if (!Array.isArray(lockers) || lockers.length === 0) {
      $("#map-loading").fadeOut();
      return;
    }
    glsMap = L.map("gls-parcel-locker-map").setView(
      [gls_parcel_lockers_vars.default_lat, gls_parcel_lockers_vars.default_lng],
      8
    );
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution:
        '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(glsMap);
    var markers = L.markerClusterGroup();
    lockers.forEach(function (locker) {
      var marker = L.marker([locker.lat, locker.lng]).bindPopup(
        `<strong>${locker.name}</strong><br>${locker.address}`
      );
      marker.on("mouseover", function () {
        this.openPopup();
      });
      marker.on("mouseout", function () {
        this.closePopup();
      });
      marker.on("click", function () {
        selectGlsLocker(locker);
      });
      markers.addLayer(marker);
    });
    glsMap.addLayer(markers);

    setTimeout(function () {
      const postcode =
        $('input[name="billing_postcode"]').val();
      geocodePostcodeAndZoom(postcode);
    }, 500);

    if (lockers.length > 0) {
      glsMap.fitBounds(markers.getBounds());
    }
    $("#map-loading").fadeOut();
  }

  // GLS
  function populateGlsLockerList(lockers) {
    var list = $(".parcel-locker-list");
    list.empty();

    $(".explm-no-lockers").remove();

    if (lockers.length === 0) {
      list.after(
        `<div class="explm-no-lockers">${parcel_locker_i18n.no_parcel_lockers}</div>`
      );
      return;
    }

    lockers.forEach(function (locker) {
      var item = $(`
        <div class="gls-parcel-locker-item parcel-locker-item" data-locker-id="${locker.id}">
          <h4>${locker.name}</h4>
          <p>${locker.address}</p>
        </div>
      `);
      item.on("click", function () {
        selectGlsLocker(locker);
      });
      list.append(item);
    });
    $("#gls-parcel-locker-search").on("keyup", function () {
      var visibleCount = $(
        ".parcel-locker-list .parcel-locker-item:visible"
      ).length;
      $(".explm-no-lockers").remove();
      if (visibleCount === 0) {
        list.append(
          `<div class="explm-no-lockers">${ parcel_locker_i18n.no_parcel_lockers }</div>`
        );
      }
    });
  }

  // Odabir GLS paketomata
  function selectGlsLocker(locker) {
    $("#gls_parcel_locker_location_id").val(locker.id);
    $("#gls_parcel_locker_name").val(locker.name);
    $("#gls_parcel_locker_type").val(locker.type);
    $("#gls_parcel_locker_address").val(locker.address);
    $("#gls_parcel_locker_street").val(locker.street);
    $("#gls_parcel_locker_house_number").val(locker.house_number);
    $("#gls_parcel_locker_postal_code").val(locker.postal_code);
    $("#gls_parcel_locker_city").val(locker.city);
    $("#selected-gls-parcel-locker-info")
      .html(
        `<strong>${parcel_locker_i18n.selected_locker}</strong><p>${locker.name}<br>${locker.address}</p>`
      )
      .show();
    $("#clear-gls-parcel-locker").show();
    $(".parcel-locker-modal").fadeOut(function () {
      $(this).remove();
    });
  }

  function clearGlsLockerSelection() {
    $("#gls_parcel_locker_location_id").val("");
    $("#gls_parcel_locker_name").val("");
    $("#gls_parcel_locker_type").val("");
    $("#gls_parcel_locker_address").val("");
    $("#gls_parcel_locker_street").val("");
    $("#gls_parcel_locker_house_number").val("");
    $("#gls_parcel_locker_postal_code").val("");
    $("#gls_parcel_locker_city").val("");
    $("#selected-gls-parcel-locker-info").html("").hide();
    $("#clear-gls-parcel-locker").hide();
  }

  $(document).on("click", "#clear-gls-parcel-locker", function (e) {
    e.preventDefault();
    clearGlsLockerSelection();
  });
});