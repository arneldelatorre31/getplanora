// SIDEBAR ACTIVE EFFECT

const menuItems = document.querySelectorAll(".menu-item");

menuItems.forEach(item => {

  item.addEventListener("click", () => {

    menuItems.forEach(nav => nav.classList.remove("active"));

    item.classList.add("active");

  });

});

// BUTTONS

const buttons = document.querySelectorAll("button");

buttons.forEach(button => {

  button.addEventListener("click", () => {

    console.log(button.innerText + " clicked");

  });

});