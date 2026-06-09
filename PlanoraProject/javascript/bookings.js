(function () {
  var rows = Array.prototype.slice.call(document.querySelectorAll(".booking-row"));
  var searchInput = document.getElementById("bookingSearch");
  var typeFilter = document.getElementById("bookingTypeFilter");
  var filterButtons = Array.prototype.slice.call(document.querySelectorAll(".filter-btn"));
  var emptyRow = document.getElementById("emptyBookingsRow");
  var footerText = document.getElementById("bookingFooterText");
  var pageButtons = document.getElementById("pageButtons");
  var prevPage = document.getElementById("prevPage");
  var nextPage = document.getElementById("nextPage");
  var bookingModal = document.getElementById("bookingModal");
  var closeModal = document.getElementById("closeModal");
  var rowsPerPage = 8;
  var activeStatus = "all";
  var activeType = "all";
  var currentPage = 1;
  var activeModalRow = null;
  var activeModalDetails = {};

  if (emptyRow) {
    emptyRow.removeAttribute("hidden");
  }

  function readDetails(button) {
    if (!button) {
      return {};
    }

    try {
      return JSON.parse(button.getAttribute("data-details") || "{}");
    } catch (error) {
      return {};
    }
  }

  function text(value, fallback) {
    if (value === null || value === undefined || value === "") {
      return fallback || "Not set";
    }

    return String(value);
  }

  function setText(selector, value, root) {
    var element = (root || document).querySelector(selector);

    if (element) {
      element.textContent = text(value);
    }
  }

  function setImage(selector, value, root) {
    var element = (root || document).querySelector(selector);

    if (element && value) {
      element.setAttribute("src", value);
    }
  }

  function escapeHtml(value) {
    return text(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function normalizeVisibleText() {
    rows.forEach(function (row) {
      var details = readDetails(row.querySelector(".js-view-booking"));
      var price = row.querySelector(".price");
      var status = row.querySelector(".status");

      if (price && details.totalPrice) {
        price.textContent = details.totalPrice;
      }

      if (status && details.status) {
        status.textContent = "\u2022 " + details.status;
      }
    });
  }

  function filteredRows() {
    var query = searchInput ? searchInput.value.trim().toLowerCase() : "";

    return rows.filter(function (row) {
      var rowStatus = row.getAttribute("data-status") || "";
      var rowType = row.getAttribute("data-type") || "";
      var rowSearch = row.getAttribute("data-search") || "";
      var statusMatches = activeStatus === "all" || rowStatus === activeStatus;
      var typeMatches = activeType === "all" || rowType === activeType;
      var searchMatches = query === "" || rowSearch.indexOf(query) !== -1;

      return statusMatches && typeMatches && searchMatches;
    });
  }

  function renderPagination(totalRows) {
    if (!pageButtons || !prevPage || !nextPage) {
      return;
    }

    var totalPages = Math.max(1, Math.ceil(totalRows / rowsPerPage));
    var page;

    if (currentPage > totalPages) {
      currentPage = totalPages;
    }

    pageButtons.innerHTML = "";

    for (page = 1; page <= totalPages; page += 1) {
      (function (pageNumber) {
        var button = document.createElement("button");
        button.type = "button";
        button.textContent = pageNumber;

        if (pageNumber === currentPage) {
          button.classList.add("active");
        }

        button.addEventListener("click", function () {
          currentPage = pageNumber;
          renderTable();
        });

        pageButtons.appendChild(button);
      })(page);
    }

    prevPage.disabled = currentPage === 1;
    nextPage.disabled = currentPage === totalPages;
  }

  function renderTable() {
    var matchingRows = filteredRows();
    var totalRows = matchingRows.length;
    var startIndex = (currentPage - 1) * rowsPerPage;
    var endIndex = startIndex + rowsPerPage;
    var visibleRows = matchingRows.slice(startIndex, endIndex);

    rows.forEach(function (row) {
      row.style.display = "none";
    });

    visibleRows.forEach(function (row) {
      row.style.display = "";
    });

    if (emptyRow) {
      emptyRow.style.display = totalRows === 0 ? "" : "none";
    }

    if (footerText) {
      if (totalRows === 0) {
        footerText.textContent = "Showing 0 requests";
      } else {
        footerText.textContent = "Showing " + (startIndex + 1) + " to " + (startIndex + visibleRows.length) + " of " + totalRows + " requests";
      }
    }

    renderPagination(totalRows);
  }

  function fillModal(details, row) {
    var statusBadge;
    var clientCard;
    var clientLines;
    var summaryItems;
    var listingCards;
    var firstListing;
    var secondListing;
    var inclusions;
    var totalRows;
    var confirmBtn;
    var completeBtn;
    var declineBtn;
    var messageBtn;
    var notesBox;
    var statusClass;

    if (!bookingModal) {
      return;
    }

    activeModalRow = row || null;
    activeModalDetails = details || {};

    statusBadge = bookingModal.querySelector(".status-badge");
    clientCard = bookingModal.querySelector(".modal-left .modal-card .client-info");
    summaryItems = bookingModal.querySelectorAll(".summary-item");
    listingCards = bookingModal.querySelectorAll(".modal-center .modal-card");
    firstListing = listingCards[0];
    secondListing = listingCards[1];
    inclusions = bookingModal.querySelector(".inclusions");
    totalRows = bookingModal.querySelectorAll(".total-row");
    confirmBtn = bookingModal.querySelector(".confirm-btn");
    completeBtn = bookingModal.querySelector(".complete-btn");
    declineBtn = bookingModal.querySelector(".decline-btn");
    messageBtn = bookingModal.querySelector(".message-btn");
    notesBox = bookingModal.querySelector("textarea");
    statusClass = text(details.statusClass, "pending").toLowerCase();

    setText(".modal-title p", "Requested on " + text(details.requestedAt), bookingModal);

    if (statusBadge) {
      statusBadge.className = "status-badge " + text(details.statusClass, "pending");
      statusBadge.textContent = "\u2022 " + text(details.status, "Pending");
    }

    if (clientCard) {
      setImage("img", details.clientImage, clientCard);
      setText("h4", details.clientName, clientCard);
      clientLines = clientCard.querySelectorAll("p");

      if (clientLines[0]) {
        clientLines[0].innerHTML = '<i class="fa-solid fa-phone"></i> ' + escapeHtml(details.clientPhone);
      }

      if (clientLines[1]) {
        clientLines[1].innerHTML = '<i class="fa-regular fa-envelope"></i> ' + escapeHtml(details.clientEmail);
      }
    }

    if (summaryItems[0]) setText("h4", details.eventName, summaryItems[0]);
    if (summaryItems[1]) setText("h4", text(details.eventDate) + (details.eventTime && details.eventTime !== "Not set" ? " \u2022 " + details.eventTime : ""), summaryItems[1]);
    if (summaryItems[2]) {
      setText("h4", details.venue, summaryItems[2]);
      setText("p", details.venueNote || "", summaryItems[2]);

      if (summaryItems[2].querySelector("p")) {
        summaryItems[2].querySelector("p").hidden = !details.venueNote;
      }
    }
    if (summaryItems[3]) setText("h4", details.guestCount, summaryItems[3]);
    if (summaryItems[4]) setText(".client-message", details.message, summaryItems[4]);

    if (firstListing) {
      setText("h3", text(details.listingType, "Service") + " LISTING", firstListing);
      setImage(".listing-item img", details.listingImage, firstListing);
      setText(".listing-content h4", details.serviceTitle, firstListing);
      setText(".listing-content p", details.listingDescription, firstListing);
      setText(".listing-tag", details.listingType, firstListing);
      setText(".listing-bottom h5", details.basePrice, firstListing);
    }

    if (secondListing) {
      secondListing.style.display = "none";
    }

    if (inclusions) {
      setText("h3", "BOOKING DETAILS", inclusions.closest(".modal-card"));
      inclusions.innerHTML =
        '<div><i class="fa-solid fa-circle-check"></i>Category: ' + escapeHtml(details.serviceCategory || "Service") + "</div>" +
        '<div><i class="fa-solid fa-circle-check"></i>Type: ' + escapeHtml(details.listingType || "Listing") + "</div>" +
        '<div><i class="fa-solid fa-circle-check"></i>Day: ' + escapeHtml(details.eventDay || "Not set") + "</div>" +
        '<div><i class="fa-solid fa-circle-check"></i>Status: ' + escapeHtml(details.status || "Pending") + "</div>";
    }

    if (totalRows[0]) {
      totalRows[0].querySelector("span:first-child").textContent = "Service Price";
      totalRows[0].querySelector("span:last-child").textContent = text(details.basePrice, "PHP 0.00");
    }

    if (totalRows[1]) {
      totalRows[1].querySelector("span:first-child").textContent = "Additional Fees";
      totalRows[1].querySelector("span:last-child").textContent = text(details.logisticsFee, "PHP 0.00");
    }

    if (totalRows[2]) {
      totalRows[2].querySelector("span:first-child").textContent = "Status";
      totalRows[2].querySelector("span:last-child").textContent = text(details.status);
    }

    setText(".grand-total h2", details.totalPrice, bookingModal);

    if (notesBox) {
      notesBox.value = "";
    }

    if (confirmBtn) {
      confirmBtn.disabled = statusClass === "confirmed" || statusClass === "completed";
      confirmBtn.classList.toggle("is-disabled", statusClass === "confirmed" || statusClass === "completed");
      confirmBtn.innerHTML = '<i class="fa-solid fa-check"></i>' + (statusClass === "confirmed" ? " Booking Confirmed" : " Confirm Booking");
    }

    if (completeBtn) {
      completeBtn.disabled = !details.canComplete;
      completeBtn.classList.toggle("is-disabled", !details.canComplete);
      completeBtn.title = details.canComplete ? "" : "Bookings can only be completed after a confirmed event date is over.";
      completeBtn.innerHTML = '<i class="fa-solid fa-circle-check"></i>' + (statusClass === "completed" ? " Booking Completed" : " Mark Completed");
    }

    if (declineBtn) {
      declineBtn.disabled = statusClass === "cancelled" || statusClass === "completed";
      declineBtn.classList.toggle("is-disabled", statusClass === "cancelled" || statusClass === "completed");
      declineBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>' + (statusClass === "cancelled" ? " Request Declined" : " Decline Request");
    }

    if (messageBtn) {
      messageBtn.disabled = !details.clientEmail || details.clientEmail === "Not set";
    }

    bookingModal.classList.add("active");
  }

  function closeRowMenus(exceptMenu) {
    rows.forEach(function (row) {
      var menu = row.querySelector(".row-menu");
      var button = row.querySelector(".js-more-booking");

      if (menu && menu !== exceptMenu) {
        menu.classList.remove("open");
      }

      if (button && (!exceptMenu || button.closest(".row-menu") !== exceptMenu)) {
        button.setAttribute("aria-expanded", "false");
      }
    });
  }

  function updateBookingStatus(row, status, trigger) {
    var bookingId = row ? row.getAttribute("data-id") : "";
    var currentStatus = row ? (row.getAttribute("data-status") || "").toLowerCase() : "";
    var formData = new FormData();

    if (!bookingId) {
      alert("Open a booking first before changing its status.");
      return;
    }

    if (currentStatus === "completed") {
      alert("This booking is already completed, so its request status can no longer be changed.");
      return;
    }

    if (status === "completed" && row.getAttribute("data-can-complete") !== "1") {
      alert("You can only mark a confirmed booking as completed after the event date is over.");
      return;
    }

    if (currentStatus === status) {
      alert("This booking is already marked as " + status + ".");
      return;
    }

    formData.append("booking_id", bookingId);
    formData.append("status", status);

    if (trigger) {
      trigger.disabled = true;
    }

    fetch("../php/updateBookingStatus.php", {
      method: "POST",
      body: formData,
      credentials: "same-origin"
    })
      .then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok || !data.success) {
            throw new Error(data.message || "Could not update booking status.");
          }

          window.location.reload();
        });
      })
      .catch(function (error) {
        alert(error.message);

        if (trigger) {
          trigger.disabled = false;
        }
      });
  }

  function sendClientMessage() {
    var details = activeModalDetails || {};
    var email = text(details.clientEmail, "").trim();
    var notes = bookingModal ? bookingModal.querySelector("textarea") : null;
    var noteText = notes ? notes.value.trim() : "";
    var subject;
    var body;

    if (!email || email === "Not set") {
      alert("This booking does not have a client email address.");
      return;
    }

    subject = "Planora booking #" + text(details.bookingId, "") + " - " + text(details.serviceTitle, "Booking request");
    body = "Hi " + text(details.clientName, "there") + ",\n\n" +
      "Regarding your Planora booking request:\n" +
      "Service: " + text(details.serviceTitle, "Service") + "\n" +
      "Event: " + text(details.eventName, "Event") + "\n" +
      "Date: " + text(details.eventDate, "Not set") + (details.eventTime && details.eventTime !== "Not set" ? " at " + details.eventTime : "") + "\n" +
      "Venue: " + text(details.venue, "Not set") + "\n\n" +
      (noteText ? noteText + "\n\n" : "") +
      "Thank you.";

    window.location.href = "mailto:" + email + "?subject=" + encodeURIComponent(subject) + "&body=" + encodeURIComponent(body);
  }

  filterButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      filterButtons.forEach(function (item) {
        item.classList.remove("active");
      });

      button.classList.add("active");
      activeStatus = button.getAttribute("data-status") || "all";
      currentPage = 1;
      renderTable();
    });
  });

  if (searchInput) {
    var initialSearch = "";
    var searchMatch = window.location.search.match(/[?&]search=([^&]+)/);

    if (searchMatch) {
      initialSearch = decodeURIComponent(searchMatch[1].replace(/\+/g, " "));
    }

    if (initialSearch) {
      searchInput.value = initialSearch;
    }

    searchInput.addEventListener("input", function () {
      currentPage = 1;
      renderTable();
    });
  }

  if (typeFilter) {
    typeFilter.addEventListener("change", function () {
      activeType = typeFilter.value || "all";
      currentPage = 1;
      renderTable();
    });
  }

  if (prevPage) {
    prevPage.addEventListener("click", function () {
      if (currentPage > 1) {
        currentPage -= 1;
        renderTable();
      }
    });
  }

  if (nextPage) {
    nextPage.addEventListener("click", function () {
      var totalPages = Math.max(1, Math.ceil(filteredRows().length / rowsPerPage));

      if (currentPage < totalPages) {
        currentPage += 1;
        renderTable();
      }
    });
  }

  rows.forEach(function (row) {
    var button = row.querySelector(".js-view-booking");
    var moreButton = row.querySelector(".js-more-booking");
    var statusButtons = Array.prototype.slice.call(row.querySelectorAll(".js-status-action"));

    if (button) {
      button.addEventListener("click", function () {
        fillModal(readDetails(button), row);
      });
    }

    if (moreButton) {
      moreButton.addEventListener("click", function (event) {
        var menu = moreButton.closest(".row-menu");
        var isOpen = menu && menu.classList.contains("open");

        event.stopPropagation();
        closeRowMenus(menu);

        if (menu) {
          menu.classList.toggle("open", !isOpen);
          moreButton.setAttribute("aria-expanded", !isOpen ? "true" : "false");
        }
      });
    }

    statusButtons.forEach(function (statusButton) {
      statusButton.addEventListener("click", function () {
        updateBookingStatus(row, statusButton.getAttribute("data-status"), statusButton);
      });
    });
  });

  document.addEventListener("click", function (event) {
    if (!event.target.closest(".row-menu")) {
      closeRowMenus();
    }
  });

  if (closeModal) {
    closeModal.addEventListener("click", function () {
      bookingModal.classList.remove("active");
    });
  }

  if (bookingModal) {
    var modalConfirmBtn = bookingModal.querySelector(".confirm-btn");
    var modalCompleteBtn = bookingModal.querySelector(".complete-btn");
    var modalDeclineBtn = bookingModal.querySelector(".decline-btn");
    var modalMessageBtn = bookingModal.querySelector(".message-btn");

    if (modalConfirmBtn) {
      modalConfirmBtn.addEventListener("click", function () {
        updateBookingStatus(activeModalRow, "confirmed", modalConfirmBtn);
      });
    }

    if (modalCompleteBtn) {
      modalCompleteBtn.addEventListener("click", function () {
        if (window.confirm("Mark this booking as completed?")) {
          updateBookingStatus(activeModalRow, "completed", modalCompleteBtn);
        }
      });
    }

    if (modalDeclineBtn) {
      modalDeclineBtn.addEventListener("click", function () {
        if (window.confirm("Decline this booking request?")) {
          updateBookingStatus(activeModalRow, "cancelled", modalDeclineBtn);
        }
      });
    }

    if (modalMessageBtn) {
      modalMessageBtn.addEventListener("click", sendClientMessage);
    }

    bookingModal.addEventListener("click", function (event) {
      if (event.target === bookingModal) {
        bookingModal.classList.remove("active");
      }
    });
  }

  normalizeVisibleText();
  renderTable();
})();
