document.addEventListener('DOMContentLoaded', function(){
    // Open edit modal and populate fields
    document.querySelectorAll('.open-edit-project').forEach(function(btn){
        btn.addEventListener('click', function(e){
            var tr = e.currentTarget.closest('.proj-row');
            if (!tr) return;
            openModal('editProjectModal');
            fillForm('editProjectModal', {
                id: tr.getAttribute('data-id'),
                project_name: tr.getAttribute('data-project_name'),
                description: tr.getAttribute('data-description'),
                wilaya: tr.getAttribute('data-wilaya'),
                commune: tr.getAttribute('data-commune'),
                latitude: tr.getAttribute('data-latitude'),
                longitude: tr.getAttribute('data-longitude'),
                client: tr.getAttribute('data-client'),
                realisateur: tr.getAttribute('data-realisateur'),
                bureau_etude: tr.getAttribute('data-bureau_etude'),
                start_date: tr.getAttribute('data-start_date'),
                end_date: tr.getAttribute('data-end_date'),
                status: tr.getAttribute('data-status'),
                budget: tr.getAttribute('data-budget'),
                other_info: tr.getAttribute('data-other_info')
            });
        });
    });

    // Delete project modal
    document.querySelectorAll('.open-delete-project').forEach(function(btn){
        btn.addEventListener('click', function(e){
            var tr = e.currentTarget.closest('.proj-row');
            if (!tr) return;
            fillForm('deleteProjectModal', {id: tr.getAttribute('data-id')});
            openModal('deleteProjectModal');
        });
    });

    // Toggle lots row and update chevron icon
    document.querySelectorAll('.toggle-lots').forEach(function(btn){
        btn.addEventListener('click', function(e){
            var projectId = e.currentTarget.dataset.projectId || e.currentTarget.getAttribute('data-project-id');
            if (!projectId) return;
            var lotsRow = document.querySelector('.lots-row[data-parent="'+projectId+'"]');
            if (!lotsRow) return;
            var isHidden = window.getComputedStyle(lotsRow).display === 'none';
            // hide all others
            document.querySelectorAll('.lots-row').forEach(function(r){ r.style.display = 'none'; });
            // reset all chevrons to down
            document.querySelectorAll('.toggle-lots i').forEach(function(ic){ ic.classList.remove('fa-chevron-up'); ic.classList.add('fa-chevron-down'); });
            // toggle selected
            lotsRow.style.display = isHidden ? 'table-row' : 'none';
            var icon = e.currentTarget.querySelector('i');
            if (icon) {
                if (isHidden) { icon.classList.remove('fa-chevron-down'); icon.classList.add('fa-chevron-up'); }
                else { icon.classList.remove('fa-chevron-up'); icon.classList.add('fa-chevron-down'); }
            }
            // optional: scroll into view on mobile
            var tr = document.querySelector('.proj-row[data-id="'+projectId+'"]');
            if (tr && window.innerWidth < 700) tr.scrollIntoView({behavior:'smooth',block:'center'});
        });
    });

    // Open Add Lot (top or per-project)
    document.querySelectorAll('.open-add-lot').forEach(function(btn){
        btn.addEventListener('click', function(e){
            var pid = e.currentTarget.dataset.projectId || '';
            var tr = e.currentTarget.closest('.proj-row');
            var pname = '';
            if (tr) pname = tr.getAttribute('data-project_name') || '';
            openModal('addLotModal');
            var values = {};
            if (pid) values.project_id = pid;
            if (pname) values.project_name = pname;
            if (Object.keys(values).length) fillForm('addLotModal', values);
        });
    });

    // Edit lot
    document.querySelectorAll('.open-edit-lot').forEach(function(btn){
        btn.addEventListener('click', function(e){
            var d = e.currentTarget.dataset;
            openModal('editLotModal');
            fillForm('editLotModal', {
                id: d.id,
                project_id: d.project_id,
                lot_code: d.lot_code,
                lot_name: d.lot_name,
                description: d.description,
                contractor_id: d.contractor_id,
                bureau_etude_id: d.bureau_etude_id,
                status: d.status
            });
        });
    });

    // Delete lot
    document.querySelectorAll('.open-delete-lot').forEach(function(btn){
        btn.addEventListener('click', function(e){
            var id = e.currentTarget.dataset.id;
            fillForm('deleteLotModal', {id: id});
            openModal('deleteLotModal');
        });
    });

    // Open project details modal
    document.querySelectorAll('.open-project-details').forEach(function(btn){
        btn.addEventListener('click', function(e){
            var tr = e.currentTarget.closest('.proj-row');
            if (!tr) return;
            var get = function(k){ return tr.getAttribute('data-'+k) || ''; };
            var name = get('project_name');
            var desc = get('description');
            var client = get('client');
            var realisateur = get('realisateur');
            var bureau = get('bureau_etude');
            var start = get('start_date');
            var end = get('end_date');
            var status = get('status');
            var budget = get('budget');
            var lat = get('latitude');
            var lng = get('longitude');
            var other = get('other_info');

            var setText = function(id, val){ var el = document.getElementById(id); if (el) el.textContent = val || ''; };
            setText('pd-name', name);
            setText('pd-desc', desc);
            setText('pd-client', client);
            setText('pd-realisateur', realisateur);
            setText('pd-bureau', bureau);
            setText('pd-start', start);
            setText('pd-end', end);
            setText('pd-status', status);
            setText('pd-budget', budget);
            setText('pd-coords', (lat||'—') + ', ' + (lng||'—'));
            setText('pd-other', other);

            var map = document.getElementById('pd-map');
            if (map) {
                if (lat && lng) {
                    var src = 'https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng) + '&z=15&output=embed';
                    map.innerHTML = '<iframe src="'+src+'" width="100%" height="200" style="border:0;" allowfullscreen="" loading="lazy"></iframe>';
                } else {
                    map.innerHTML = '<div style="color:#64748b">No coordinates available</div>';
                }
            }

            var openLink = document.getElementById('pd-open-map');
            if (openLink) {
                if (lat && lng) openLink.href = 'https://www.google.com/maps/search/?api=1&query='+encodeURIComponent(lat+','+lng);
                else openLink.href = '#';
            }

            openModal('projectDetailsModal');
        });
    });
});
