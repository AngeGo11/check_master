document.addEventListener("DOMContentLoaded", function () {
  const sidebar = document.getElementById("sidebar");
  const sidebarToggle = document.getElementById("sidebarToggle");
  const sidebarOverlay = document.getElementById("sidebarOverlay");


  sidebarToggle.addEventListener("click", function () {
    sidebar.classList.toggle("active");
    sidebarOverlay.classList.toggle("active");

    if (sidebar.classList.contains("active")) {
      sidebarToggle.innerHTML = '<i class="fa-solid fa-xmark"></i>';
    } else {
      sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
    }
  });

  sidebarOverlay.addEventListener("click", function () {
    sidebar.classList.remove("active");
    sidebarOverlay.classList.remove("active");
  });

  function handleResize() {
    if (window.innerWidth > 768) {
      sidebar.classList.remove("active");
      sidebarOverlay.classList.remove("active");
    }
  }

  window.addEventListener("resize", handleResize);

  const menuItems = document.querySelectorAll(".sidebar-menu a");
  menuItems.forEach((item) => {
    item.addEventListener("click", function () {
      if (window.innerWidth <= 768) {
        sidebar.classList.remove("active");
        sidebarOverlay.classList.remove("active");
      }
    });
  });
});
