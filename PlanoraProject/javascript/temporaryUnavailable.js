const menuItems = document.querySelectorAll(".menu-item");

menuItems.forEach(item => {

  item.addEventListener("click", () => {

    if(item.getAttribute("href") === "temporaryUnavailable.php"){
      item.classList.add("active");
    }

  });

});