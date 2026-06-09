// SIDEBAR ACTIVE EFFECT

const menuItems = document.querySelectorAll(".menu-item");

menuItems.forEach(function (item) {
  item.addEventListener("click", function () {
    menuItems.forEach(function (nav) {
      nav.classList.remove("active");
    });

    item.classList.add("active");
  });
});

// TAB SWITCH

const tabs = document.querySelectorAll(".tab");
const payoutCard = document.getElementById("payout-info");

tabs.forEach(function (tab) {
  tab.addEventListener("click", function () {
    tabs.forEach(function (item) {
      item.classList.remove("active");
    });

    tab.classList.add("active");

    if (tab.textContent.trim().toLowerCase().includes("payout") && payoutCard) {
      payoutCard.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  });
});

// EARNINGS OVERVIEW CHART

const overviewData = window.earningsOverviewData || { monthly: [], yearly: [] };
const overviewGraph = document.getElementById("earningsOverviewGraph");
const overviewPeriod = document.getElementById("overviewPeriod");
const metricButtons = Array.prototype.slice.call(document.querySelectorAll("[data-chart-metric]"));
const visibleMetrics = {
  earnings: true,
  bookings: true
};

function peso(value) {
  return "PHP " + Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

function renderOverviewChart() {
  if (!overviewGraph) {
    return;
  }

  const period = overviewPeriod ? overviewPeriod.value : "monthly";
  const rows = overviewData[period] || [];
  const showEarnings = visibleMetrics.earnings;
  const showBookings = visibleMetrics.bookings;
  const maxEarnings = Math.max.apply(null, rows.map(function (row) {
    return Number(row.earnings) || 0;
  }).concat([0]));
  const maxBookings = Math.max.apply(null, rows.map(function (row) {
    return Number(row.bookings) || 0;
  }).concat([0]));

  overviewGraph.innerHTML = "";

  if (!rows.length || (!maxEarnings && !maxBookings)) {
    const empty = document.createElement("p");
    empty.className = "empty-chart";
    empty.textContent = "No overview data yet.";
    overviewGraph.appendChild(empty);
    return;
  }

  rows.forEach(function (row) {
    const group = document.createElement("div");
    group.className = "bar-group";

    const pair = document.createElement("div");
    pair.className = "bar-pair";

    if (showEarnings) {
      const earnings = Number(row.earnings) || 0;
      const earningsHeight = maxEarnings > 0 ? Math.max(8, Math.round((earnings / maxEarnings) * 220)) : 0;
      const bar = document.createElement("div");
      bar.className = "bar earnings-bar";
      bar.style.height = earningsHeight + "px";
      bar.title = "Earnings: " + peso(earnings);
      bar.setAttribute("aria-label", bar.title);
      pair.appendChild(bar);
    }

    if (showBookings) {
      const bookings = Number(row.bookings) || 0;
      const bookingsHeight = maxBookings > 0 ? Math.max(8, Math.round((bookings / maxBookings) * 220)) : 0;
      const bar = document.createElement("div");
      bar.className = "bar bookings-bar";
      bar.style.height = bookingsHeight + "px";
      bar.title = "Bookings: " + bookings;
      bar.setAttribute("aria-label", bar.title);
      pair.appendChild(bar);
    }

    const label = document.createElement("span");
    label.textContent = row.label;

    group.appendChild(pair);
    group.appendChild(label);
    overviewGraph.appendChild(group);
  });
}

metricButtons.forEach(function (button) {
  button.addEventListener("click", function () {
    const metric = button.getAttribute("data-chart-metric");

    if (!metric || !(metric in visibleMetrics)) {
      return;
    }

    Object.keys(visibleMetrics).forEach(function (key) {
      visibleMetrics[key] = key === metric;
    });

    metricButtons.forEach(function (item) {
      const itemMetric = item.getAttribute("data-chart-metric");
      item.classList.toggle("active", visibleMetrics[itemMetric]);
    });

    renderOverviewChart();
  });
});

if (overviewPeriod) {
  overviewPeriod.addEventListener("change", renderOverviewChart);
}

renderOverviewChart();

// PAYOUT METHOD MODAL

const payoutModal = document.getElementById("payoutMethodModal");
const openPayoutModal = document.getElementById("openPayoutMethodModal");
const closePayoutModal = document.getElementById("closePayoutMethodModal");
const cancelPayoutModal = document.getElementById("cancelPayoutMethod");

function setPayoutModal(open) {
  if (!payoutModal) {
    return;
  }

  payoutModal.classList.toggle("show", open);
  payoutModal.setAttribute("aria-hidden", open ? "false" : "true");
  document.body.classList.toggle("modal-open", open);

  if (open) {
    const firstInput = payoutModal.querySelector("input[name='bank_name']");
    if (firstInput) {
      firstInput.focus();
    }
  }
}

if (openPayoutModal) {
  openPayoutModal.addEventListener("click", function () {
    setPayoutModal(true);
  });
}

[closePayoutModal, cancelPayoutModal].forEach(function (button) {
  if (button) {
    button.addEventListener("click", function () {
      setPayoutModal(false);
    });
  }
});

if (payoutModal) {
  payoutModal.addEventListener("click", function (event) {
    if (event.target === payoutModal) {
      setPayoutModal(false);
    }
  });
}

document.addEventListener("keydown", function (event) {
  if (event.key === "Escape") {
    setPayoutModal(false);
  }
});

// VIEW ALL

const viewAllBtn = document.querySelector(".view-all-btn");

if (viewAllBtn) {
  viewAllBtn.addEventListener("click", function () {
    const recentPayout = document.querySelector(".recent-payout");
    if (recentPayout) {
      recentPayout.scrollIntoView({ behavior: "smooth", block: "center" });
    }
  });
}
