// devis.js - dynamic articles, totals, and modal population
(function () {
  "use strict";

  function money(v) {
    return parseFloat(v || 0).toFixed(2);
  }
  function formatNumber(v) {
    const n = Number(v || 0);
    return n.toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }
  function recalcSubtotal(tbody, subtotalSpanId) {
    const rows = tbody.querySelectorAll("tr");
    let subtotal = 0;
    rows.forEach((r) => {
      const ttcInput =
        r.querySelector('input[name="article_total_ttc[]"]') ||
        r.querySelector(".a-ttc");
      const val = parseFloat(ttcInput?.value || 0) || 0;
      subtotal += val;
    });
    const span = document.getElementById(subtotalSpanId);
    if (span) span.textContent = formatNumber(subtotal);
    // also update hidden total_ttc in tfoot if present
    const hidden = tbody
      .closest("table")
      ?.querySelector('input[name="total_ttc"]');
    if (hidden) hidden.value = money(subtotal);
  }

  function computeRowTTC(tr) {
    try {
      const qIn = tr.querySelector('input[name="quantity[]"]');
      const unitIn = tr.querySelector('input[name="unit_price[]"]');
      const tvaIn = tr.querySelector('input[name="tva_rate[]"]');
      const ttcIn = tr.querySelector('input[name="article_total_ttc[]"]');
      const q = parseFloat(qIn?.value || 0) || 0;
      const unit = parseFloat(unitIn?.value || 0) || 0;
      const tva = parseFloat(tvaIn?.value || 0) || 0;
      const ht = q * unit;
      const tax = ht * (tva / 100);
      const ttc = ht + tax;
      if (ttcIn) ttcIn.value = money(ttc);
      return ttc;
    } catch (e) {
      return 0;
    }
  }

  function createArticleRow(values) {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td><input type="text" name="article_name[]" class="a-name" placeholder="Product name" required value=${values.name || ""}></td>
      <td><input type="text" name="article_description[]" class="a-desc item-description" placeholder="Details..." value=${values.desc || ""}></td>
      <td style='text-align:center'>
        <select name="article_um[]" class="input-box w-12">
          <option value="">—</option>
            <option value="KG" ${(values.um || "").toString().trim().toUpperCase() === "KG" ? "selected" : ""}>KG</option>
            <option value="U" ${(values.um || "").toString().trim().toUpperCase() === "U" ? "selected" : ""}>U</option>
            <option value="ML" ${(values.um || "").toString().trim().toUpperCase() === "ML" ? "selected" : ""}>ML</option>
            <option value="M2" ${(values.um || "").toString().trim().toUpperCase() === "M2" ? "selected" : ""}>M2</option>
            <option value="M3" ${(values.um || "").toString().trim().toUpperCase() === "M3" ? "selected" : ""}>M3</option>
            <option value="L" ${(values.um || "").toString().trim().toUpperCase() === "L" ? "selected" : ""}>L</option>
            <option value="M" ${(values.um || "").toString().trim().toUpperCase() === "M" ? "selected" : ""}>m</option>
            <option value="CM" ${(values.um || "").toString().trim().toUpperCase() === "CM" ? "selected" : ""}>cm</option>
            <option value="PCS" ${(values.um || "").toString().trim().toUpperCase() === "PCS" ? "selected" : ""}>pcs</option>
        </select>
      </td>
      <td style='text-align:right'><input type="number" name="quantity[]" class="input-box w-16 item-calc" min="1" step="0.01" value=${values.qty !== undefined ? values.qty : 1}></td>
      <td style='text-align:right'><input type="number" name="unit_price[]" class="input-box w-24 item-calc" min="0" step="0.01" value=${values.unit !== undefined ? values.unit : 0}></td>
      <td style='text-align:right'><input type="number" name="tva_rate[]" class="input-box w-16 item-calc" min="0" step="0.01" value=${values.tva || 0}></td>
      <td style='text-align:right'><input type="number" name="article_total_ttc[]" class="text-right item-ttc" step="0.01" value=${values.ttc || 0}></td>
      <td style="text-align:center"><button type="button" class="btn-remove" title="Remove"><i class="fa-solid fa-trash"></i></button></td>
    `;

    // attach listeners: auto-calc TTC when quantity/unit/tva change
    [
      'input[name="quantity[]"]',
      'input[name="unit_price[]"]',
      'input[name="tva_rate[]"]',
    ].forEach((sel) => {
      const inp = tr.querySelector(sel);
      if (!inp) return;
      inp.addEventListener("input", function () {
        computeRowTTC(tr);
        const tbody = tr.parentNode;
        const id =
          tbody.closest("table").id === "articlesTable"
            ? "articlesSubtotal"
            : "editArticlesSubtotal";
        recalcSubtotal(tbody, id);
      });
    });
    // if user edits TTC directly, just update subtotal
    const ttcManual = tr.querySelector('input[name="article_total_ttc[]"]');
    if (ttcManual)
      ttcManual.addEventListener("input", function () {
        const tbody = tr.parentNode;
        const id =
          tbody.closest("table").id === "articlesTable"
            ? "articlesSubtotal"
            : "editArticlesSubtotal";
        recalcSubtotal(tbody, id);
      });

    tr.querySelector(".btn-remove")?.addEventListener("click", function () {
      const tbody = tr.parentNode;
      tbody.removeChild(tr);
      const id =
        tbody.closest("table").id === "articlesTable"
          ? "articlesSubtotal"
          : "editArticlesSubtotal";
      recalcSubtotal(tbody, id);
    });

    return tr;
  }

  document.addEventListener("DOMContentLoaded", function () {
    const addBtn = document.getElementById("addArticleBtn");
    const addTbody = document.querySelector("#articlesTable tbody");
    if (addBtn && addTbody)
      addBtn.addEventListener("click", function () {
        const tr = createArticleRow({});
        addTbody.appendChild(tr);
        computeRowTTC(tr);
        recalcSubtotal(addTbody, "articlesSubtotal");
      });

    const editAddBtn = document.getElementById("editAddArticleBtn");
    const editTbody = document.querySelector("#editArticlesTable tbody");
    if (editAddBtn && editTbody)
      editAddBtn.addEventListener("click", function () {
        const tr = createArticleRow({});
        editTbody.appendChild(tr);
        computeRowTTC(tr);
        recalcSubtotal(editTbody, "editArticlesSubtotal");
      });

    // Populate functions used by the page
    window.populateEditDevis = function (tr) {
      const form = document.getElementById("editDevisForm");
      form.id.value = tr.getAttribute("data-id");
      form.devis_number.value = tr.getAttribute("data-devis_number");
      form.title.value = tr.getAttribute("data-title");
      form.description.value = tr.getAttribute("data-description");
      form.contract_id.value = tr.getAttribute("data-contract_id");
      form.stakeholder_id &&
        (form.stakeholder_id.value =
          tr.getAttribute("data-stakeholder_id") || "");
      form.status.value = tr.getAttribute("data-status");
      form.issue_date.value = tr.getAttribute("data-issue_date");
      form.expiry_date.value = tr.getAttribute("data-expiry_date");
      form.notes.value = tr.getAttribute("data-notes");
      // clear edit table body
      const tbody = document.querySelector("#editArticlesTable tbody");
      tbody.innerHTML = "";
      try {
        const arts = JSON.parse(tr.getAttribute("data-articles")) || [];
        arts.forEach((a, i) => {
          const r = createArticleRow({
            name: a.article_name || "",
            desc: a.description || "",
            um: a.UM || "",
            qty: a.quantity ?? 1,
            unit: a.unit_price ?? 0,
            tva: a.tva_rate ?? 0,
            ttc: a.total_ttc ?? 0,
          });
          // console.log(a.UM, "Adding article row:", r);
          tbody.appendChild(r);
          computeRowTTC(r);
        });
      } catch (e) {}
      reindexRows(tbody);
      recalcSubtotal(tbody, "editArticlesSubtotal");
    };

    window.populateViewDevis = function (tr) {
      const body = document.getElementById("viewDevisBody");
      const articles = JSON.parse(tr.getAttribute("data-articles") || "[]");
      let html =
        "<p><strong>Devis #:</strong> " +
        tr.getAttribute("data-devis_number") +
        "</p>";
      const stakeholderName = tr.getAttribute("data-stakeholder_name") || "";
      if (stakeholderName !== "") {
        html += "<p><strong>Client:</strong> " + stakeholderName + "</p>";
      }
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
          '<table class="devis-articles" style="width:100%;border-collapse:collapse;text-align:right;">';
        html +=
          "<thead><tr><th>Article</th><th>Qty</th><th>Unit</th><th>TVA%</th><th>Total TTC</th></tr></thead><tbody>";
        articles.forEach((a) => {
          const um = a.um ? " (" + a.um + ")" : "";
          html +=
            "<tr><td>" +
            (a.article_name + um) +
            "</td><td style='text-align:right'>" +
            a.quantity +
            "</td><td style='text-align:right'>" +
            parseFloat(a.unit_price).toFixed(2) +
            "</td><td style='text-align:right'>" +
            parseFloat(a.tva_rate).toFixed(2) +
            "</td><td style='text-align:right'>" +
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

    // ensure at least one row exists for add form
    if (addTbody && addTbody.children.length === 0) {
      addTbody.appendChild(createArticleRow({}));
      recalcSubtotal(addTbody, "articlesSubtotal");
    }

    // before submit, update subtotal hidden input
    document
      .getElementById("devisForm")
      ?.addEventListener("submit", function (e) {
        const tbody = document.querySelector("#articlesTable tbody");
        recalcSubtotal(tbody, "articlesSubtotal");
      });

    document
      .getElementById("editDevisForm")
      ?.addEventListener("submit", function (e) {
        const tbody = document.querySelector("#editArticlesTable tbody");
        recalcSubtotal(tbody, "editArticlesSubtotal");
      });
  });

  // Print a devis: opens a new window with printable A4 HTML
  window.openPrintDevis = function (tr) {
    try {
      const dv = {
        id: tr.getAttribute("data-id"),
        number: tr.getAttribute("data-devis_number"),
        title: tr.getAttribute("data-title"),
        description: tr.getAttribute("data-description"),
        contract: tr.getAttribute("data-contract_id"),
        stakeholder_name: tr.getAttribute("data-stakeholder_name") || "",
        status: tr.getAttribute("data-status"),
        issue_date: tr.getAttribute("data-issue_date"),
        expiry_date: tr.getAttribute("data-expiry_date"),
        total_ht: tr.getAttribute("data-total_ht"),
        total_tva: tr.getAttribute("data-total_tva"),
        total_ttc: tr.getAttribute("data-total_ttc"),
        notes: tr.getAttribute("data-notes"),
        articles: JSON.parse(tr.getAttribute("data-articles") || "[]"),
      };

      // build printable HTML (use only header/footer images)
      const headerImg = "/projectos/img/header%20model.png";
      const footerImg = "/projectos/img/footer%20model.png";

      // number formatter
      const fmt = new Intl.NumberFormat("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });

      // convert integer number to French words (supports up to billions)
      function numberToFrench(n) {
        if (!isFinite(n)) return '';
        n = Math.floor(Math.abs(n));
        if (n === 0) return 'zéro';
        const units = ['','un','deux','trois','quatre','cinq','six','sept','huit','neuf','dix','onze','douze','treize','quatorze','quinze','seize'];
        const tens = ['','','vingt','trente','quarante','cinquante','soixante','soixante','quatre-vingt','quatre-vingt'];

        function underHundred(num) {
          if (num < 17) return units[num];
          if (num < 20) return 'dix-' + units[num-10];
          if (num < 70) {
            const t = Math.floor(num/10);
            const u = num%10;
            if (u === 1) return tens[t] + '-et-un';
            return tens[t] + (u ? '-' + units[u] : '');
          }
          if (num < 80) {
            // 70..79 => soixante- + 10..19
            return 'soixante-' + underHundred(num-60);
          }
          // 80..99
          if (num === 80) return 'quatre-vingts';
          return 'quatre-vingt' + (num%20 ? '-' + underHundred(num%20) : '');
        }

        function underThousand(num) {
          let s = '';
          const h = Math.floor(num/100);
          const rest = num%100;
          if (h > 0) {
            if (h === 1) s += 'cent';
            else s += units[h] + ' cent';
            if (rest === 0 && h > 1) s += 's';
          }
          if (rest > 0) {
            if (s) s += ' ';
            s += underHundred(rest);
          }
          return s;
        }

        const parts = [];
        const billions = Math.floor(n / 1000000000);
        if (billions) {
          parts.push((billions===1? 'un' : numberToFrench(billions)) + ' milliard' + (billions>1?'s':''));
          n = n % 1000000000;
        }
        const millions = Math.floor(n / 1000000);
        if (millions) {
          parts.push((millions===1? 'un' : numberToFrench(millions)) + ' million' + (millions>1?'s':''));
          n = n % 1000000;
        }
        const thousands = Math.floor(n / 1000);
        if (thousands) {
          if (thousands === 1) parts.push('mille');
          else parts.push(numberToFrench(thousands) + ' mille');
          n = n % 1000;
        }
        if (n) parts.push(underThousand(n));
        return parts.join(' ').replace(/\s+/g,' ').trim();
      }

      let articlesHtml = "";
      if (dv.articles.length === 0) {
        articlesHtml =
          '<tr><td colspan="6" style="text-align:center">No articles</td></tr>';
      } else {
        dv.articles.forEach((a, idx) => {
          const um = a.um ? " " + a.um : "";
          const qty = isFinite(parseFloat(a.quantity))
            ? fmt.format(parseFloat(a.quantity))
            : "0.00";
          const unit = isFinite(parseFloat(a.unit_price))
            ? fmt.format(parseFloat(a.unit_price))
            : "0.00";
          const tva = isFinite(parseFloat(a.tva_rate))
            ? parseFloat(a.tva_rate).toFixed(2)
            : "0.00";
          const tttc = isFinite(parseFloat(a.total_ttc))
            ? fmt.format(parseFloat(a.total_ttc))
            : "0.00";
          articlesHtml +=
            "<tr>" +
            '<td style="width:40px;text-align:center">' +
            (idx + 1) +
            "</td>" +
            "<td style=\"width:60%\">" +
            (a.article_name || "") +
            (um ? " <small>(" + a.um + ")</small>" : "") +
            "</td>" +
            '<td style="text-align:right">' +
            qty +
            "</td>" +
            '<td style="text-align:right">' +
            unit +
            "</td>" +
            '<td style="text-align:right">' +
            tva +
            "%</td>" +
            '<td style="text-align:right">' +
            tttc +
            "</td>" +
            "</tr>";
        });
      }

      const total_ht = isFinite(parseFloat(dv.total_ht))
        ? fmt.format(parseFloat(dv.total_ht))
        : "0.00";
      const total_tva = isFinite(parseFloat(dv.total_tva))
        ? fmt.format(parseFloat(dv.total_tva))
        : "0.00";
      const total_ttc = isFinite(parseFloat(dv.total_ttc))
        ? fmt.format(parseFloat(dv.total_ttc))
        : "0.00";

      const html = `<!doctype html><html><head><meta charset="utf-8"><title>Devis ${dv.number}</title><style>
        @page { size: A4; margin: 12mm }
        body{font-family: Arial, Helvetica, sans-serif; color:#111; font-size:12px;margin:0}
        .page{padding:6mm}
        .header{width:100%;padding-bottom:4px;margin-bottom:6px}
        .header img{max-height:120px;width:100%;height:auto;object-fit:contain}
        .company{display:block}
        .company > div{box-sizing:border-box}
        .title{font-size:16px;font-weight:700;margin-top:6px}
        table{width:100%;border-collapse:collapse;margin-top:8px;font-size:12px}
        th,td{padding:6px;border:1px solid #ddd}
        th{background:#f5f5f5;text-align:left}
        .totals{margin-top:8px;width:320px;float:right}
        .footer{position:fixed;bottom:8mm;left:0;right:0;text-align:center}
        .footer img{width:calc(100% - 24mm);max-height:110px;height:auto;object-fit:contain}
        </style></head><body>
        <div class="page">
        <div class="header"><div class="company"><div style="text-align:center"><img src="${headerImg}" alt="header-model"></div></div></div>
        <div style="margin-top:6px"><strong>Devis #</strong> ${dv.number} &nbsp; <strong>Status:</strong> ${dv.status}</div>
        <div style="margin-top:6px"><strong>Client:</strong> ${dv.stakeholder_name}</div>
        <div style="margin-top:6px"><strong>Title:</strong> ${dv.title}</div>
        <table>
          <thead>
            <tr><th style="width:40px;text-align:center">No.</th><th>Article</th><th style="text-align:right">Qty</th><th style="text-align:right">Unit</th><th style="text-align:right">TVA</th><th style="text-align:right">Total TTC</th></tr>
          </thead>
          <tbody>
            ${articlesHtml}
          </tbody>
          <tfoot>
            <tr><td colspan="5" style="text-align:right;font-weight:700">Total HT</td><td style="text-align:right">${total_ht}</td></tr>
            <tr><td colspan="5" style="text-align:right">Total TVA</td><td style="text-align:right">${total_tva}</td></tr>
            <tr><td colspan="5" style="text-align:right;font-weight:700">Total TTC</td><td style="text-align:right">${total_ttc}</td></tr>
          </tfoot>
        </table>
        <div style="margin-top:8px;font-style:italic">Montant Hors Taxe (HT): ${total_ht}</div>
        <div style="margin-top:12px"><strong>Notes:</strong><div>${dv.notes || ""}</div></div>
        <div style="height:60px"></div>
        </div>
        <div class="footer"><img src="${footerImg}" alt="footer"></div>
        <script>window.onload = function(){ setTimeout(function(){ window.print(); },300); };</script>
      </body></html>`;

      const w = window.open("", "_blank");
      w.document.open();
      w.document.write(html);
      w.document.close();
    } catch (e) {
      console.error("Print error", e);
      alert("Unable to prepare print view.");
    }
  };

  // helper: reindex rows to ensure sequential article indices
  function reindexRows(tbody) {
    // flat-array inputs (article_name[], description[], qte[], tva[], ttc[]) do not need index rewriting
    return;
  }
})();
