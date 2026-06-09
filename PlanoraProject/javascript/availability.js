(function () {
  var tabButtons = Array.prototype.slice.call(document.querySelectorAll(".tab-btn"));
  var sections = Array.prototype.slice.call(document.querySelectorAll(".listings-section"));
  var listingCards = Array.prototype.slice.call(document.querySelectorAll(".listing-card"));
  var syncButton = document.getElementById("syncCalendarBtn");
  var syncNotice = document.getElementById("syncNotice");
  var monthNames = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"
  ];

  function pad(value) {
    return value < 10 ? "0" + value : String(value);
  }

  function dateKey(year, month, day) {
    return year + "-" + pad(month + 1) + "-" + pad(day);
  }

  function parseBookings(card) {
    try {
      return JSON.parse(card.getAttribute("data-bookings") || "{}");
    } catch (error) {
      return {};
    }
  }

  function dominantStatus(dayBookings) {
    if (!dayBookings) {
      return "available";
    }

    if (dayBookings.confirmed > 0) {
      return "confirmed";
    }

    if (dayBookings.pending > 0) {
      return "pending";
    }

    if (dayBookings.unavailable > 0) {
      return "unavailable";
    }

    return "available";
  }

  function addStatusDots(dayElement, dayBookings) {
    var dots = document.createElement("div");

    dots.className = "day-dots";

    ["confirmed", "pending", "unavailable"].forEach(function (status) {
      if (dayBookings && dayBookings[status] > 0) {
        var dot = document.createElement("span");
        dot.className = "dot " + status;
        dot.title = status === "unavailable"
          ? "Not available"
          : dayBookings[status] + " " + status + " booking" + (dayBookings[status] > 1 ? "s" : "");
        dots.appendChild(dot);
      }
    });

    dayElement.appendChild(dots);
  }

  function renderCalendar(card) {
    var calendar = card.querySelector("[data-calendar]");
    var label = card.querySelector("[data-month-label]");
    var daysContainer = card.querySelector("[data-days]");
    var bookings = parseBookings(card);
    var today = new Date();
    var currentYear = parseInt(card.getAttribute("data-year"), 10);
    var currentMonth = parseInt(card.getAttribute("data-month"), 10);
    var firstDay;
    var daysInMonth;
    var leadingDay;
    var day;

    if (!calendar || !label || !daysContainer) {
      return;
    }

    if (isNaN(currentYear) || isNaN(currentMonth)) {
      currentYear = today.getFullYear();
      currentMonth = today.getMonth();
      card.setAttribute("data-year", currentYear);
      card.setAttribute("data-month", currentMonth);
    }

    firstDay = new Date(currentYear, currentMonth, 1).getDay();
    daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

    label.textContent = monthNames[currentMonth] + " " + currentYear;
    daysContainer.innerHTML = "";

    for (leadingDay = 0; leadingDay < firstDay; leadingDay += 1) {
      var blank = document.createElement("div");
      blank.className = "day outside-month";
      daysContainer.appendChild(blank);
    }

    for (day = 1; day <= daysInMonth; day += 1) {
      var key = dateKey(currentYear, currentMonth, day);
      var dayBookings = bookings[key];
      var status = dominantStatus(dayBookings);
      var dayElement = document.createElement("div");
      var number = document.createElement("span");

      dayElement.className = "day " + status;
      dayElement.setAttribute("data-date", key);

      if (status !== "available") {
        dayElement.classList.add("has-booking");
      }

      if (
        today.getFullYear() === currentYear &&
        today.getMonth() === currentMonth &&
        today.getDate() === day
      ) {
        dayElement.classList.add("today");
      }

      number.className = "day-number";
      number.textContent = day;
      dayElement.appendChild(number);
      addStatusDots(dayElement, dayBookings);

      dayElement.addEventListener("click", function () {
        var selectedDay = this;
        var currentStatus = selectedDay.classList.contains("unavailable") ? "unavailable" : "available";

        if (selectedDay.classList.contains("confirmed")) {
          currentStatus = "confirmed";
        }

        if (selectedDay.classList.contains("pending")) {
          currentStatus = "pending";
        }

        toggleAvailabilityDate(card, selectedDay.getAttribute("data-date"), currentStatus);
      });

      daysContainer.appendChild(dayElement);
    }
  }

  function setDayStatus(bookings, key, status) {
    if (!bookings[key]) {
      bookings[key] = {
        confirmed: 0,
        pending: 0,
        unavailable: 0,
        blocked: 0
      };
    }

    if (status === "unavailable") {
      bookings[key].blocked = 1;
      bookings[key].unavailable = Math.max(1, bookings[key].unavailable || 0);
    } else {
      bookings[key].unavailable = Math.max(0, (bookings[key].unavailable || 0) - (bookings[key].blocked || 0));
      bookings[key].blocked = 0;
    }

    if (
      bookings[key].confirmed < 1 &&
      bookings[key].pending < 1 &&
      bookings[key].unavailable < 1
    ) {
      delete bookings[key];
    }
  }

  function toggleAvailabilityDate(card, key, currentStatus) {
    var listingId = card.getAttribute("data-listing-id");
    var isEditing = card.classList.contains("editing");
    var formData = new FormData();
    var action = currentStatus === "unavailable" ? "unblock" : "block";
    var bookings = parseBookings(card);
    var dayBookings = bookings[key] || {};

    if (!isEditing || !listingId) {
      return;
    }

    if (currentStatus === "confirmed" || currentStatus === "pending") {
      alert("This date already has a booking request. Change the booking status from the Bookings page.");
      return;
    }

    if (currentStatus === "unavailable" && !(dayBookings.blocked > 0)) {
      alert("This date is not available because of an existing booking record.");
      return;
    }

    formData.append("listing_id", listingId);
    formData.append("date", key);
    formData.append("action", action);

    fetch("../php/updateAvailabilityDate.php", {
      method: "POST",
      body: formData,
      credentials: "same-origin"
    })
      .then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok || !data.success) {
            throw new Error(data.message || "Could not update availability.");
          }

          setDayStatus(bookings, key, data.status);
          card.setAttribute("data-bookings", JSON.stringify(bookings));
          renderCalendar(card);
        });
      })
      .catch(function (error) {
        alert(error.message);
      });
  }

  function renderAllCalendars() {
    listingCards.forEach(function (card) {
      renderCalendar(card);
    });
  }

  tabButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      var tabId = button.getAttribute("data-tab");

      tabButtons.forEach(function (item) {
        item.classList.remove("active");
      });

      sections.forEach(function (section) {
        section.classList.remove("active");
      });

      button.classList.add("active");

      if (document.getElementById(tabId)) {
        document.getElementById(tabId).classList.add("active");
      }
    });
  });

  listingCards.forEach(function (card) {
    var today = new Date();
    var prev = card.querySelector(".prev-month");
    var next = card.querySelector(".next-month");
    var editButton = card.querySelector("[data-edit-availability]");

    card.setAttribute("data-year", today.getFullYear());
    card.setAttribute("data-month", today.getMonth());

    if (prev) {
      prev.addEventListener("click", function () {
        var month = parseInt(card.getAttribute("data-month"), 10) - 1;
        var year = parseInt(card.getAttribute("data-year"), 10);

        if (month < 0) {
          month = 11;
          year -= 1;
        }

        card.setAttribute("data-month", month);
        card.setAttribute("data-year", year);
        renderCalendar(card);
      });
    }

    if (next) {
      next.addEventListener("click", function () {
        var month = parseInt(card.getAttribute("data-month"), 10) + 1;
        var year = parseInt(card.getAttribute("data-year"), 10);

        if (month > 11) {
          month = 0;
          year += 1;
        }

        card.setAttribute("data-month", month);
        card.setAttribute("data-year", year);
        renderCalendar(card);
      });
    }

    if (editButton) {
      editButton.addEventListener("click", function () {
        var isEditing = card.classList.toggle("editing");

        editButton.innerHTML = isEditing
          ? '<i class="fa-solid fa-check"></i> Done Editing'
          : '<i class="fa-solid fa-pen-to-square"></i> Edit Calendar';
      });
    }
  });

  if (syncButton) {
    syncButton.addEventListener("click", function () {
      syncButton.classList.add("syncing");
      syncButton.innerHTML = '<i class="fa-solid fa-rotate fa-spin"></i> Syncing';

      setTimeout(function () {
        window.location.href = "availability.php?synced=1";
      }, 350);
    });
  }

  if (syncNotice && !syncNotice.hidden) {
    setTimeout(function () {
      syncNotice.hidden = true;
    }, 2800);
  }

  renderAllCalendars();
})();
