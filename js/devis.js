// devis.js - dynamic articles, totals, and modal population
(function () {
  "use strict";

  function money(v) {
    return parseFloat(v || 0).toFixed(2);
  }
  function formatNumber(v){
    const n = Number(v || 0);
    return n.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
  }
  function recalcSubtotal(tbody, subtotalSpanId) {
    const rows = tbody.querySelectorAll('tr');
    let subtotal = 0;
    rows.forEach(r => {
      const ttcInput = r.querySelector('input[name="article_total_ttc[]"]') || r.querySelector('.a-ttc');
      const val = parseFloat(ttcInput?.value || 0) || 0;
      subtotal += val;
    });
    const span = document.getElementById(subtotalSpanId);
    if (span) span.textContent = formatNumber(subtotal);
    // also update hidden total_ttc in tfoot if present
    const hidden = tbody.closest('table')?.querySelector('input[name="total_ttc"]');
    if (hidden) hidden.value = money(subtotal);
  }

  function computeRowTTC(tr){
    try{
      const qIn = tr.querySelector('input[name="quantity[]"]');
      const unitIn = tr.querySelector('input[name="unit_price[]"]');
      const tvaIn = tr.querySelector('input[name="tva_rate[]"]');
      const ttcIn = tr.querySelector('input[name="article_total_ttc[]"]');
      const q = parseFloat(qIn?.value || 0) || 0;
      const unit = parseFloat(unitIn?.value || 0) || 0;
      const tva = parseFloat(tvaIn?.value || 0) || 0;
      const ht = q * unit;
      const tax = ht * (tva/100);
      const ttc = ht + tax;
      if (ttcIn) ttcIn.value = money(ttc);
      return ttc;
    }catch(e){return 0}
  }

  function createArticleRow(values) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="text" name="article_name[]" class="a-name" placeholder="Product name" required value="${values.name||''}"></td>
      <td><input type="text" name="article_description[]" class="a-desc item-description" placeholder="Details..." value="${values.desc||''}"></td>
      <td style="text-align:right"><input type="number" name="quantity[]" class="input-box w-16 item-calc" min="1" step="0.01" value="${values.qty!==undefined?values.qty:1}"></td>
      <td style="text-align:right"><input type="number" name="unit_price[]" class="input-box w-24 item-calc" min="0" step="0.01" value="${values.unit!==undefined?values.unit:0}"></td>
      <td style="text-align:right"><input type="number" name="tva_rate[]" class="input-box w-16 item-calc" min="0" step="0.01" value="${values.tva||20}"></td>
      <td style="text-align:right"><input type="number" name="article_total_ttc[]" class="text-right item-ttc" step="0.01" value="${values.ttc||0}"></td>
      <td style="text-align:center"><button type="button" class="btn-remove" title="Remove"><i class="fa-solid fa-trash"></i></button></td>
    `;

    // attach listeners: auto-calc TTC when quantity/unit/tva change
    ['input[name="quantity[]"]','input[name="unit_price[]"]','input[name="tva_rate[]"]'].forEach(sel => {
      const inp = tr.querySelector(sel);
      if (!inp) return;
      inp.addEventListener('input', function(){
        computeRowTTC(tr);
        const tbody = tr.parentNode;
        const id = tbody.closest('table').id === 'articlesTable' ? 'articlesSubtotal' : 'editArticlesSubtotal';
        recalcSubtotal(tbody, id);
      });
    });
    // if user edits TTC directly, just update subtotal
    const ttcManual = tr.querySelector('input[name="article_total_ttc[]"]');
    if (ttcManual) ttcManual.addEventListener('input', function(){
      const tbody = tr.parentNode;
      const id = tbody.closest('table').id === 'articlesTable' ? 'articlesSubtotal' : 'editArticlesSubtotal';
      recalcSubtotal(tbody, id);
    });

    tr.querySelector('.btn-remove')?.addEventListener('click', function(){
      const tbody = tr.parentNode;
      tbody.removeChild(tr);
      const id = tbody.closest('table').id === 'articlesTable' ? 'articlesSubtotal' : 'editArticlesSubtotal';
      recalcSubtotal(tbody, id);
    });

    return tr;
  }

  document.addEventListener("DOMContentLoaded", function () {
    const addBtn = document.getElementById('addArticleBtn');
    const addTbody = document.querySelector('#articlesTable tbody');
    if (addBtn && addTbody) addBtn.addEventListener('click', function(){
      const tr = createArticleRow({});
      addTbody.appendChild(tr);
      computeRowTTC(tr);
      recalcSubtotal(addTbody, 'articlesSubtotal');
    });

    const editAddBtn = document.getElementById('editAddArticleBtn');
    const editTbody = document.querySelector('#editArticlesTable tbody');
    if (editAddBtn && editTbody) editAddBtn.addEventListener('click', function(){
      const tr = createArticleRow({});
      editTbody.appendChild(tr);
      computeRowTTC(tr);
      recalcSubtotal(editTbody, 'editArticlesSubtotal');
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
      // clear edit table body
      const tbody = document.querySelector('#editArticlesTable tbody');
      tbody.innerHTML = '';
      try {
        const arts = JSON.parse(tr.getAttribute('data-articles')) || [];
        arts.forEach((a, i) => {
          const r = createArticleRow({ name: a.article_name || '', desc: a.description || '', qty: a.quantity ?? 1, unit: a.unit_price ?? 0, tva: a.tva_rate ?? 0, ttc: a.total_ttc ?? 0 });
          tbody.appendChild(r);
          computeRowTTC(r);
        });
      } catch (e) {}
      reindexRows(tbody);
      recalcSubtotal(tbody, 'editArticlesSubtotal');
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

    // ensure at least one row exists for add form
    if (addTbody && addTbody.children.length === 0) {
      addTbody.appendChild(createArticleRow({}));
      recalcSubtotal(addTbody, 'articlesSubtotal');
    }

    // before submit, update subtotal hidden input
    document.getElementById('devisForm')?.addEventListener('submit', function(e){
      const tbody = document.querySelector('#articlesTable tbody');
      recalcSubtotal(tbody, 'articlesSubtotal');
    });

    document.getElementById('editDevisForm')?.addEventListener('submit', function(e){
      const tbody = document.querySelector('#editArticlesTable tbody');
      recalcSubtotal(tbody, 'editArticlesSubtotal');
    });
  });

  // helper: reindex rows to ensure sequential article indices
  function reindexRows(tbody){
    // flat-array inputs (article_name[], description[], qte[], tva[], ttc[]) do not need index rewriting
    return;
  }
})();
