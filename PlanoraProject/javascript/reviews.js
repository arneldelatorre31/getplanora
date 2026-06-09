const reviewTabs = document.querySelectorAll(".review-tab");
const clientReviews = document.getElementById("clientReviews");
const givenReviews = document.getElementById("givenReviews");

reviewTabs.forEach((tab) => {
  tab.addEventListener("click", () => {
    reviewTabs.forEach((btn) => btn.classList.remove("active"));
    tab.classList.add("active");

    const target = tab.getAttribute("data-target");
    clientReviews.style.display = target === "clients" ? "block" : "none";
    givenReviews.style.display = target === "given" ? "block" : "none";
  });
});

const dropdownToggle = document.querySelector(".dropdown-toggle");
const dropdownMenu = document.getElementById("dropdownMenu");

if (dropdownToggle && dropdownMenu) {
  dropdownToggle.addEventListener("click", (event) => {
    event.stopPropagation();
    dropdownMenu.classList.toggle("show");
  });

  document.addEventListener("click", (event) => {
    if (!event.target.closest(".vendor-dropdown")) {
      dropdownMenu.classList.remove("show");
    }
  });
}

const detailsModal = document.getElementById("reviewDetailsModal");
const sendReviewModal = document.getElementById("sendReviewModal");

function openModal(modal) {
  modal.classList.add("active");
  document.body.classList.add("modal-open");
}

function closeModal(modal) {
  modal.classList.remove("active");
  document.body.classList.remove("modal-open");
}

document.querySelectorAll("[data-close-modal], .review-modal-overlay").forEach((element) => {
  element.addEventListener("click", () => {
    document.querySelectorAll(".review-modal.active").forEach(closeModal);
  });
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    document.querySelectorAll(".review-modal.active").forEach(closeModal);
  }
});

function starsMarkup(rating) {
  let html = "";

  for (let index = 1; index <= 5; index++) {
    html += `<i class="${index <= rating ? "fa-solid" : "fa-regular"} fa-star"></i>`;
  }

  return html;
}

function setText(id, value) {
  const element = document.getElementById(id);

  if (element) {
    element.textContent = value || "Not set";
  }
}

function populateDetails(details) {
  setText("detailsTitle", details.title);
  setText("detailsClientName", details.clientName);
  setText("detailsClientPhone", details.clientPhone);
  setText("detailsClientEmail", details.clientEmail);
  setText("detailsEventDate", details.eventDate);
  setText("detailsCreatedAt", details.createdAt);
  setText("detailsRating", Number(details.rating || 0).toFixed(1));
  setText("detailsServiceName", details.serviceName);
  setText("detailsServiceCategory", details.serviceCategory);
  setText("detailsType", details.type);
  setText("detailsEventTime", details.eventTime);
  setText("detailsVenue", details.venue);
  setText("detailsGuestCount", details.guestCount);
  setText("detailsBookingStatus", details.bookingStatus);
  setText("detailsPaymentStatus", details.paymentStatus);
  setText("detailsListingName", details.serviceName);
  setText("detailsListingDescription", details.listingDescription);
  setText("detailsReviewText", details.reviewText || "No review text.");
  setText("detailsBasePrice", details.basePrice);
  setText("detailsLogisticsFee", details.logisticsFee);
  setText("detailsSecurityDeposit", details.securityDeposit);
  setText("detailsTotalAmount", details.totalAmount);
  setText("detailsSpecialRequest", details.specialRequest);

  const rating = Number(details.rating || 0);
  ["breakdownQuality", "breakdownProfessionalism", "breakdownCommunication", "breakdownValue", "breakdownTimeliness"].forEach((id) => {
    setText(id, rating.toFixed(1));
  });

  const stars = document.getElementById("detailsStars");
  if (stars) {
    stars.innerHTML = starsMarkup(Math.round(rating));
  }

  const clientImage = document.getElementById("detailsClientImage");
  if (clientImage) {
    clientImage.src = details.clientImage || "image/default-profile.png";
  }

  const listingImage = document.getElementById("detailsListingImage");
  if (listingImage) {
    listingImage.src = details.listingImage || "image/planoraLogo.jpg";
  }

  const badge = document.getElementById("detailsSourceBadge");
  if (badge) {
    badge.textContent = details.source === "given" ? "Client" : "From Client";
  }

  const heading = document.getElementById("detailsReviewHeading");
  if (heading) {
    heading.textContent = details.source === "given" ? "Review Given" : "Client Review";
  }

  const listingType = document.getElementById("detailsListingType");
  if (listingType) {
    listingType.textContent = details.type || "Package";
    listingType.className = `type ${String(details.type || "").toLowerCase() === "package" ? "package" : "ala"}`;
  }
}

document.querySelectorAll(".js-view-details").forEach((button) => {
  button.addEventListener("click", () => {
    const details = JSON.parse(button.dataset.details || "{}");
    populateDetails(details);
    openModal(detailsModal);
  });
});

function populateSendReview(data) {
  const mappings = {
    sendClientName: data.clientName,
    sendClientEmail: data.clientEmail,
    sendServiceName: data.serviceName,
    sendServiceCategory: data.serviceCategory,
    sendType: data.type,
    sendEventDate: data.eventDate,
    sendClientImage: data.clientImage,
    sendClientPreviewName: data.clientName,
    sendClientPreviewEmail: data.clientEmail,
    sendClientPreviewService: data.serviceName
  };

  Object.entries(mappings).forEach(([id, value]) => {
    const element = document.getElementById(id);
    if (element) {
      if ("value" in element) {
        element.value = value || "";
      } else {
        element.textContent = value || "Not set";
      }
    }
  });

  const image = document.getElementById("sendClientPreviewImage");
  if (image) {
    image.src = data.clientAvatar || data.clientImage || "image/default-profile.png";
  }
}

document.querySelectorAll(".js-send-review").forEach((button) => {
  button.addEventListener("click", () => {
    const reviewData = JSON.parse(button.dataset.review || "{}");
    populateSendReview(reviewData);
    openModal(sendReviewModal);
  });
});
