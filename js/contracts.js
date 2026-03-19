document.addEventListener("DOMContentLoaded", function () {
  // view contract (read-only)
  document.querySelectorAll(".view-contract").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      var tr = e.currentTarget.closest(".contract-row");
      if (!tr) return;
      var lines = [];
      lines.push(
        "Contract #: " + (tr.getAttribute("data-contract_number") || ""),
      );
      lines.push("Title: " + (tr.getAttribute("data-title") || ""));
      lines.push("Description: " + (tr.getAttribute("data-description") || ""));
      lines.push(
        "Entreprise A ID: " + (tr.getAttribute("data-entrepriseA_id") || ""),
      );
      lines.push(
        "Entreprise B ID: " + (tr.getAttribute("data-entrepriseB_id") || ""),
      );
      lines.push(
        "Value: " +
          (tr.getAttribute("data-value") || "") +
          " " +
          (tr.getAttribute("data-currency") || ""),
      );
      lines.push(
        "Dates: " +
          (tr.getAttribute("data-start_date") || "") +
          " / " +
          (tr.getAttribute("data-end_date") || ""),
      );
      lines.push("Status: " + (tr.getAttribute("data-status") || ""));
      alert(lines.join("\n"));
    });
  });

  // ensure modal select displays current value when fillForm used
  // fillForm helper is provided by modal.js
});
