document.getElementById("dashboardBtn").onclick = () => {
      console.log("Dashboard Clicked");
    };

    document.getElementById("bookingsBtn").onclick = () => {
      console.log("Bookings Clicked");
    };

    document.getElementById("calendarBtn").onclick = () => {
      console.log("Calendar Clicked");
    };

    document.getElementById("requestsBtn").onclick = () => {
      console.log("Requests Clicked");
    };

    document.getElementById("messagesBtn").onclick = () => {
      console.log("Messages Clicked");
    };

    document.getElementById("servicesBtn").onclick = () => {
      console.log("Services Clicked");
    };

    document.getElementById("earningsBtn").onclick = () => {
      console.log("Earnings Clicked");
    };

    document.getElementById("reviewsBtn").onclick = () => {
      console.log("Reviews Clicked");
    };

    document.getElementById("profileBtn").onclick = () => {
      console.log("Profile Clicked");
    };

    document.getElementById("settingsBtn").onclick = () => {
      console.log("Settings Clicked");
    };

    document.getElementById("newBookingBtn").onclick = () => {
      console.log("New Booking");
    };

    document.getElementById("searchBtn").onclick = () => {
      console.log("Search");
    };

    document.getElementById("notificationBtn").onclick = () => {
      console.log("Notifications");
    };

    document.getElementById("calendarViewBtn").onclick = () => {
      console.log("Calendar View");
    };

    document.getElementById("viewBookingsBtn").onclick = () => {
      console.log("View Bookings");
    };

    document.getElementById("monthDropdownBtn").onclick = () => {
      console.log("Month Dropdown");
    };

    document.getElementById("viewReviewsBtn").onclick = () => {
      console.log("View Reviews");
    };

// SIDEBAR ACTIVE EFFECT

const menuItems = document.querySelectorAll(".menu-item");

menuItems.forEach(item => {

  item.addEventListener("click", () => {

    menuItems.forEach(nav => nav.classList.remove("active"));

    item.classList.add("active");

  });

});
