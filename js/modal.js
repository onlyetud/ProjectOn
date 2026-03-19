// modal.js - simple modal and sidebar toggle behaviors
(function () {
  "use strict";

  window.openModal = function (id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.setAttribute("aria-hidden", "false");
    modal.classList.add("open");
    document.body.classList.add("no-scroll");
  };

  window.closeModal = function (id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.setAttribute("aria-hidden", "true");
    modal.classList.remove("open");
    document.body.classList.remove("no-scroll");
  };

  document.addEventListener("click", function (e) {
    const close = e.target.closest("[data-close]");
    if (close) {
      const modal = close.closest(".modal");
      if (modal) closeModal(modal.id);
    }
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      document.querySelectorAll(".modal.open").forEach(function (m) {
        closeModal(m.id);
      });
    }
  });

  // Click on overlay closes modal
  document.addEventListener("click", function (e) {
    if (e.target.classList && e.target.classList.contains("modal-overlay")) {
      const modal = e.target.closest(".modal");
      if (modal) closeModal(modal.id);
    }
  });

  // Sidebar toggle for responsive
  document.addEventListener("DOMContentLoaded", function () {
    const toggle = document.getElementById("sidebarToggle");
    const sidebar = document.getElementById("sidebar");
    if (toggle && sidebar) {
      toggle.addEventListener("click", function () {
        const isOpen = sidebar.classList.toggle("collapsed");
        // aria-expanded true when sidebar is visible (collapsed class opens on small screens)
        toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
      });
    }
  });

  // Fill modal forms from data attributes helper
  window.fillForm = function (modalId, values) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    Object.keys(values).forEach(function (k) {
      const field = modal.querySelector('[name="' + k + '"]');
      if (field) field.value = values[k];
    });
  };

  // Toast helper
  window.showToast = function (message, type = "info", duration = 4500) {
    const container = document.getElementById("toast-container");
    if (!container) return;
    const t = document.createElement("div");
    t.className =
      "toast " +
      (type === "error" ? "error" : type === "success" ? "success" : "info");
    t.textContent = message;
    container.appendChild(t);
    // auto-remove
    setTimeout(function () {
      t.style.transition = "opacity 240ms ease, transform 240ms ease";
      t.style.opacity = "0";
      t.style.transform = "translateY(8px)";
      setTimeout(function () {
        try {
          container.removeChild(t);
        } catch (e) {}
      }, 260);
    }, duration);
  };

  // On DOM ready, convert any inline .alert messages to toasts
  document.addEventListener("DOMContentLoaded", function () {
    // find alerts and show as toasts
    document.querySelectorAll(".alert").forEach(function (a) {
      const cls = a.classList.contains("success")
        ? "success"
        : a.classList.contains("errors")
          ? "error"
          : "info";
      // collect text lines
      let text = Array.from(a.childNodes)
        .map(function (n) {
          return n.nodeType === Node.TEXT_NODE
            ? n.textContent.trim()
            : (n.textContent || "").trim();
        })
        .filter(Boolean)
        .join("\n");
      if (!text) text = a.textContent.trim();
      if (text) showToast(text, cls);
      // hide the original alert
      a.style.display = "none";
    });
  });
})();
