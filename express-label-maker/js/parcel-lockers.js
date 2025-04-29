var dpdMap;
var dpdLockers;
var overseasMap;
var overseasLockers;

jQuery(document).ready(function ($) {

  // DPD
  $(document).on("click", "#select-dpd-parcel-locker", function (e) {
    e.preventDefault();
    var $dpdButton = $(this);
    $dpdButton.prop("disabled", true).text("Učitavanje...");

    var dpdModal = $(`
      <div class="parcel-locker-modal dpd-parcel-locker-modal">
          <div class="parcel-locker-modal-content">
              <span class="parcel-locker-close dpd-parcel-locker-close">&times;</span>
              <div id="dpd-parcel-locker-map-container" style="position: relative;">
                  <div id="dpd-parcel-locker-map"></div>
                  <div id="map-loading" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
                        background: rgba(255,255,255,0.8); display: flex; align-items: center; justify-content: center; 
                        z-index: 9999;">
                      <div class="loader"></div>
                  </div>
              </div>
              <div class="parcel-locker-list-container">
                  <h3>Odaberite paketomat:</h3>
                  <div class="parcel-locker-search">
                      <input type="text" id="dpd-parcel-locker-search" placeholder="Pretražite paketomate...">
                  </div>
                  <div class="parcel-locker-list"></div>
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
        $dpdButton.prop("disabled", false).text("Odaberite paketomat");
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
      [dpd_parcel_lockers_vars.default_lat, dpd_parcel_lockers_vars.default_lng],
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
      marker.on("mouseover", function () { this.openPopup(); });
      marker.on("mouseout", function () { this.closePopup(); });
      marker.on("click", function () { selectDpdLocker(locker); });
      markers.addLayer(marker);
    });
    dpdMap.addLayer(markers);
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
      list.after('<div class="explm-no-lockers">Nema paketomata za prikaz...</div>');
      return;
    }
  
    lockers.forEach(function (locker) {
      var item = $(`
        <div class="dpd-parcel-locker-item parcel-locker-item" data-locker-id="${locker.location_id}">
          <h4>${locker.name}</h4>
          <p>${locker.address}</p>
        </div>
      `);
      item.on("click", function () { selectDpdLocker(locker); });
      list.append(item);
    });
    $("#dpd-parcel-locker-search").on("keyup", function () {
      var visibleCount = $(".parcel-locker-list .parcel-locker-item:visible").length;
      $(".explm-no-lockers").remove();
      if (visibleCount === 0) {
        list.append(`<div class="explm-no-lockers">Nema paketomata za prikaz.</div>`);
      }
    });
  }
  

  // Odabir DPD paketomata
  function selectDpdLocker(locker) {
    $("#dpd_parcel_locker_location_id").val(locker.location_id);
    $("#dpd_parcel_locker_name").val(locker.name);
    $("#dpd_parcel_locker_address").val(locker.address);
    $("#dpd_parcel_locker_street").val(locker.street);
    $("#dpd_parcel_locker_house_number").val(locker.house_number);
    $("#dpd_parcel_locker_postal_code").val(locker.postal_code);
    $("#dpd_parcel_locker_city").val(locker.city);
    $("#selected-dpd-parcel-locker-info")
      .html(`<strong>Odabrani paketomat:</strong><p>${locker.name}<br>${locker.address}</p>`)
      .show();
    $("#clear-dpd-parcel-locker").show();
    $(document.body).trigger("update_checkout");
    $(".parcel-locker-modal").fadeOut(function () { $(this).remove(); });
  }

  function clearDpdLockerSelection() {
    $("#dpd_parcel_locker_location_id").val("");
    $("#dpd_parcel_locker_name").val("");
    $("#dpd_parcel_locker_address").val("");
    $("#dpd_parcel_locker_street").val("");
    $("#dpd_parcel_locker_house_number").val("");
    $("#dpd_parcel_locker_postal_code").val("");
    $("#dpd_parcel_locker_city").val("");
    $("#selected-dpd-parcel-locker-info").html("").hide();
    $("#clear-dpd-parcel-locker").hide();
    $(document.body).trigger("update_checkout");
  }

  $(document).on("click", "#clear-dpd-parcel-locker", function (e) {
    e.preventDefault();
    clearDpdLockerSelection();
  });


  // Overseas
  $(document).on("click", "#select-overseas-parcel-locker", function (e) {
    e.preventDefault();
    var $overseasButton = $(this);
    $overseasButton.prop("disabled", true).text("Učitavanje...");
  
    var overseasModal = $(`
      <div class="parcel-locker-modal overseas-parcel-locker-modal">
          <div class="parcel-locker-modal-content">
              <span class="parcel-locker-close overseas-parcel-locker-close">&times;</span>
              <div id="overseas-parcel-locker-map-container" style="position: relative;">
                  <div id="overseas-parcel-locker-map"></div>
                  <div id="map-loading" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
                        background: rgba(255,255,255,0.8); display: flex; align-items: center; justify-content: center; 
                        z-index: 9999;">
                      <div class="loader"></div>
                  </div>
              </div>
              <div class="parcel-locker-list-container">
                  <h3>Odaberite paketomat:</h3>
                  <div class="parcel-locker-search">
                      <input type="text" id="overseas-parcel-locker-search" placeholder="Pretražite paketomate...">
                  </div>
                  <div class="parcel-locker-list"></div>
              </div>
          </div>
      </div>
    `);
  
    $("body").append(overseasModal);
    overseasModal.fadeIn(400, function(){
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
                  $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
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
                      overseasMap.fitBounds(bounds, { padding: [50, 50], maxZoom: 16 });
                    }
                  }
                }
              });              
            }
          },
          complete: function () {
            $overseasButton.prop("disabled", false).text("Odaberite paketomat");
          },
        });
    });
  
    overseasModal.find(".overseas-parcel-locker-close").on("click", function () {
      overseasModal.fadeOut(function () { $(this).remove(); });
    });
  });  

  // Overseas
  function initOverseasMap(lockers) {
    if (!Array.isArray(lockers) || lockers.length === 0) {
      $("#map-loading").fadeOut();
      return;
    }
    overseasMap = L.map("overseas-parcel-locker-map").setView(
      [overseas_parcel_lockers_vars.default_lat, overseas_parcel_lockers_vars.default_lng],
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
      marker.on("mouseover", function () { this.openPopup(); });
      marker.on("mouseout", function () { this.closePopup(); });
      marker.on("click", function () { selectOverseasLocker(locker); });
      markers.addLayer(marker);
    });
  
    overseasMap.addLayer(markers);
  
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
      list.after('<div class="explm-no-lockers">Nema paketomata za prikaz...</div>');
      return;
    }
    lockers.forEach(function (locker) {
      var item = $(`
        <div class="overseas-parcel-locker-item parcel-locker-item" data-locker-id="${locker.location_id}">
          <h4>${locker.name}</h4>
          <p>${locker.address}</p>
        </div>
      `);
      item.on("click", function () { selectOverseasLocker(locker); });
      list.append(item);
    });
    $("#overseas-parcel-locker-search").on("keyup", function () {
      var visibleCount = $(".parcel-locker-list .parcel-locker-item:visible").length;
      $(".explm-no-lockers").remove();
      if (visibleCount === 0) {
        list.append(`<div class="explm-no-lockers">Nema paketomata za prikaz.</div>`);
      }
    });
  }

  function selectOverseasLocker(locker) {
    $("#overseas_parcel_locker_location_id").val(locker.location_id);
    $("#overseas_parcel_locker_name").val(locker.name);
    $("#overseas_parcel_locker_address").val(locker.address);
    $("#overseas_parcel_locker_street").val(locker.street);
    $("#overseas_parcel_locker_house_number").val(locker.house_number);
    $("#overseas_parcel_locker_postal_code").val(locker.postal_code);
    $("#overseas_parcel_locker_city").val(locker.city);
    $("#selected-overseas-parcel-locker-info")
      .html(`<strong>Odabrani paketomat:</strong><p>${locker.name}<br>${locker.address}</p>`)
      .show();
    $("#clear-overseas-parcel-locker").show();
    $(document.body).trigger("update_checkout");
    $(".parcel-locker-modal").fadeOut(function () { $(this).remove(); });
  }

  function clearOverseasLockerSelection() {
    $("#overseas_parcel_locker_location_id").val("");
    $("#overseas_parcel_locker_name").val("");
    $("#overseas_parcel_locker_address").val("");
    $("#overseas_parcel_locker_street").val("");
    $("#overseas_parcel_locker_house_number").val("");
    $("#overseas_parcel_locker_postal_code").val("");
    $("#overseas_parcel_locker_city").val("");
    $("#selected-overseas-parcel-locker-info").html("").hide();
    $("#clear-overseas-parcel-locker").hide();
    $(document.body).trigger("update_checkout");
  }

  $(document).on("click", "#clear-overseas-parcel-locker", function (e) {
    e.preventDefault();
    clearOverseasLockerSelection();
  });
});