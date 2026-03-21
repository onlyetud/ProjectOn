// devis.js - dynamic articles, totals, and modal population
(function () {
  "use strict";

  function money(v) {
    return parseFloat(v || 0).toFixed(2);
  }

  function calcRowTotals(row) {
    const q = parseFloat(row.querySelector(".a-qty").value || 0);
    const unit = parseFloat(row.querySelector(".a-unit").value || 0);
    const tva = parseFloat(row.querySelector(".a-tva").value || 0);
    const tht = q * unit;
    const ttva = tht * (tva / 100);
    const tttc = tht + ttva;
    row.querySelector(".a-tht").value = money(tht);
    row.querySelector(".a-ttva").value = money(ttva);
    row.querySelector(".a-tttc").value = money(tttc);
    return { tht, ttva, tttc };
  }

  function recalc(container, totalsTargets) {
    const rows = container.querySelectorAll(".article-row");
    let tht = 0,
      ttva = 0,
      tttc = 0;
    rows.forEach((r) => {
      const t = calcRowTotals(r);
      tht += t.tht;
      ttva += t.ttva;
      tttc += t.tttc;
    });
    totalsTargets.total_ht.value = money(tht);
    totalsTargets.total_tva.value = money(ttva);
    totalsTargets.total_ttc.value = money(tttc);
  }

  function createArticleRow(values) {
    const div = document.createElement("div");
    div.className = "article-row";
    div.innerHTML = `
      <div class="article-fields">
        <input name="article_name[]" class="a-name" placeholder="Article name" value="${values.name || ""}">
        <input name="article_description[]" class="a-desc" placeholder="Description" value="${values.desc || ""}">
        <input name="quantity[]" class="a-qty" placeholder="Quantity" value="${values.qty || 0}" type="number" step="0.01">
        <input name="unit_price[]" class="a-unit" placeholder="Unit price" value="${values.unit || 0}" type="number" step="0.01">
        <input name="tva_rate[]" class="a-tva" placeholder="TVA %" value="${values.tva || 0}" type="number" step="0.01">
        <input name="article_total_ht[]" class="a-tht" readonly value="0.00">
        <input name="article_total_tva[]" class="a-ttva" readonly value="0.00">
        <input name="article_total_ttc[]" class="a-tttc" readonly value="0.00">
      </div>
      <div class="article-actions"><button type="button" class="btn ghost remove-article"><i class="fa-solid fa-trash"></i></button></div>
    `;
    // add listeners
    [".a-qty", ".a-unit", ".a-tva"].forEach((sel) => {
      div.querySelector(sel).addEventListener("input", function () {
        const container = div.closest(".modal-body") || document;
        const totalsTargets = {
          total_ht: container.querySelector('input[name="total_ht"]'),
          total_tva: container.querySelector('input[name="total_tva"]'),
          total_ttc: container.querySelector('input[name="total_ttc"]'),
        };
        recalc(div.parentNode, totalsTargets);
      });
    });

    div.querySelector(".remove-article").addEventListener("click", function () {
      div.parentNode.removeChild(div);
      const container = div.closest(".modal-body") || document;
      const totalsTargets = {
        total_ht: container.querySelector('input[name="total_ht"]'),
        total_tva: container.querySelector('input[name="total_tva"]'),
        total_ttc: container.querySelector('input[name="total_ttc"]'),
      };
      recalc(div.parentNode, totalsTargets);
    });
    return div;
  }

  document.addEventListener("DOMContentLoaded", function () {
    const addBtn = document.getElementById("addArticleBtn");
    const container = document.getElementById("articlesContainer");
    const totalsTargets = {
      total_ht: document.querySelector('#addDevisModal input[name="total_ht"]'),
      total_tva: document.querySelector(
        '#addDevisModal input[name="total_tva"]',
      ),
      total_ttc: document.querySelector(
        '#addDevisModal input[name="total_ttc"]',
      ),
    };
    if (addBtn && container)
      addBtn.addEventListener("click", function () {
        container.appendChild(createArticleRow({}));
      });

    const editAddBtn = document.getElementById("editAddArticleBtn");
    const editContainer = document.getElementById("editArticlesContainer");
    if (editAddBtn && editContainer)
      editAddBtn.addEventListener("click", function () {
        editContainer.appendChild(createArticleRow({}));
      });

    // Populate functions used by the page
    window.populateEditDevis = function (tr) {
      const form = document.getElementById("editDevisForm");
      form.id.value = tr.getAttribute("data-id");
      form.devis_number.value = tr.getAttribute("data-devis_number");
      form.title.value = tr.getAttribute("data-title");
      form.description.value = tr.getAttribute("data-description");
      form.contract_id.value = tr.getAttribute("data-contract_id");
      form.status.value = tr.getAttribute("data-status");
      form.issue_date.value = tr.getAttribute("data-issue_date");
      form.expiry_date.value = tr.getAttribute("data-expiry_date");
      form.notes.value = tr.getAttribute("data-notes");
      // clear articles
      editContainer.innerHTML = "";
      try {
        const arts = JSON.parse(tr.getAttribute("data-articles")) || [];
        arts.forEach((a) => {
          const r = createArticleRow({
            name: a.article_name || "",
            desc: a.description || "",
            qty: a.quantity || 0,
            unit: a.unit_price || 0,
            tva: a.tva_rate || 0,
          });
          editContainer.appendChild(r);
        });
      } catch (e) {}
      // compute totals
      const totalsTargetsEdit = {
        total_ht: form.querySelector('input[name="total_ht"]'),
        total_tva: form.querySelector('input[name="total_tva"]'),
        total_ttc: form.querySelector('input[name="total_ttc"]'),
      };
      recalc(editContainer, totalsTargetsEdit);
      // set values from row totals
      totalsTargetsEdit.total_ht.value =
        tr.getAttribute("data-total_ht") || totalsTargetsEdit.total_ht.value;
      totalsTargetsEdit.total_tva.value =
        tr.getAttribute("data-total_tva") || totalsTargetsEdit.total_tva.value;
      totalsTargetsEdit.total_ttc.value =
        tr.getAttribute("data-total_ttc") || totalsTargetsEdit.total_ttc.value;
    };

    window.populateViewDevis = function (tr) {
      const body = document.getElementById("viewDevisBody");
      const articles = JSON.parse(tr.getAttribute("data-articles") || "[]");
      let html =
        "<p><strong>Devis #:</strong> " +
        tr.getAttribute("data-devis_number") +
        "</p>";
      html +=
        "<p><strong>Title:</strong> " + tr.getAttribute("data-title") + "</p>";
      html +=
        "<p><strong>Contract:</strong> " +
        tr.querySelector("td:nth-child(4)").textContent +
        "</p>";
      html +=
        "<p><strong>Status:</strong> " +
        tr.getAttribute("data-status") +
        " — <strong>Issue:</strong> " +
        tr.getAttribute("data-issue_date") +
        " — <strong>Expiry:</strong> " +
        tr.getAttribute("data-expiry_date") +
        "</p>";
      html += "<h4>Articles</h4>";
      if (articles.length === 0)
        html += '<div class="muted">No articles.</div>';
      else {
        html +=
          '<table class="devis-articles" style="width:100%;border-collapse:collapse">';
        html +=
          "<thead><tr><th>Article</th><th>Qty</th><th>Unit</th><th>TVA%</th><th>Total TTC</th></tr></thead><tbody>";
        articles.forEach((a) => {
          html +=
            "<tr><td>" +
            a.article_name +
            "</td><td>" +
            a.quantity +
            "</td><td>" +
            parseFloat(a.unit_price).toFixed(2) +
            "</td><td>" +
            parseFloat(a.tva_rate).toFixed(2) +
            "</td><td>" +
            parseFloat(a.total_ttc).toFixed(2) +
            "</td></tr>";
        });
        html += "</tbody></table>";
      }
      html +=
        '<div style="margin-top:12px"><strong>Totals:</strong> HT ' +
        tr.getAttribute("data-total_ht") +
        " — TVA " +
        tr.getAttribute("data-total_tva") +
        " — TTC " +
        tr.getAttribute("data-total_ttc") +
        "</div>";
      html +=
        '<div style="margin-top:12px"><button class="btn ghost" data-close="true">Close</button></div>';
      body.innerHTML = html;
    };

    // intercept form submits to ensure totals values are set
    document
      .getElementById("devisForm")
      ?.addEventListener("submit", function (e) {
        const totalsTargets = {
          total_ht: this.querySelector('input[name="total_ht"]'),
          total_tva: this.querySelector('input[name="total_tva"]'),
          total_ttc: this.querySelector('input[name="total_ttc"]'),
        };
        recalc(container, totalsTargets);
      });

    document
      .getElementById("editDevisForm")
      ?.addEventListener("submit", function (e) {
        const totalsTargets = {
          total_ht: this.querySelector('input[name="total_ht"]'),
          total_tva: this.querySelector('input[name="total_tva"]'),
          total_ttc: this.querySelector('input[name="total_ttc"]'),
        };
        recalc(editContainer, totalsTargets);
      });
  });
})();
