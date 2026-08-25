/* js/map.js
   Renders SVG floorplan + side panel with court cards inside #mapWrapper.
   Requires: window.SB (from app.js) and Booking, Timer modules.
*/

(function(){
  const MAP_KEY = 'sb_court_layout_v1';
  const wrapper = document.getElementById('mapWrapper');
  if(!wrapper) return;

  // Default layout (percent-based)
  const defaultLayout = {
    1: { x: 6,  y: 8,  w: 28, h: 36 }, // Pickleball Court 1
    2: { x: 36, y: 8,  w: 28, h: 36 }, // Pickleball Court 2
    3: { x: 66, y: 8,  w: 28, h: 36 }, // Pickleball Court 3
    4: { x: 6,  y: 52, w: 44, h: 36 }, // Badminton Court 1
    5: { x: 54, y: 52, w: 40, h: 36 }  // Basketball Court 1
  };

  let layout = loadLayout();
  let isAdmin = false;
  let dragging = null;

  // Main render
  function renderMap(){
    wrapper.innerHTML = ''; // clear
    wrapper.classList.add('has-panel');

    const svg = createSVG();
    wrapper.appendChild(svg);

    renderSidePanel();
    Timer && Timer.renderAllTimers && Timer.renderAllTimers();
  }

  // Create SVG floorplan
  function createSVG(){
    const svgNS = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(svgNS, 'svg');
    svg.setAttribute('viewBox', '0 0 1000 600');
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '70vh');
    svg.classList.add('court-svg');
    svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');

    // floor background
    const floor = document.createElementNS(svgNS, 'rect');
    floor.setAttribute('x', 0);
    floor.setAttribute('y', 0);
    floor.setAttribute('width', 1000);
    floor.setAttribute('height', 600);
    floor.setAttribute('fill', '#f3f6fb');
    svg.appendChild(floor);

    // draw courts
    window.SB.courts.forEach(c => {
      const pos = layout[c.id] || defaultLayout[c.id];
      const x = (pos.x/100) * 1000;
      const y = (pos.y/100) * 600;
      const w = (pos.w/100) * 1000;
      const h = (pos.h/100) * 600;

      const g = document.createElementNS(svgNS, 'g');
      g.setAttribute('data-court-id', c.id);
      g.setAttribute('transform', `translate(${x},${y})`);
      g.classList.add('court-group');

      const rect = document.createElementNS(svgNS, 'rect');
      rect.setAttribute('width', w);
      rect.setAttribute('height', h);
      rect.setAttribute('rx', 12);
      rect.setAttribute('ry', 12);
      rect.classList.add('court-shape');
      rect.setAttribute('fill', '#ffffff');
      rect.setAttribute('stroke', '#e6e9ef');
      rect.setAttribute('stroke-width', 2);

      const label = document.createElementNS(svgNS, 'text');
      label.setAttribute('x', 14);
      label.setAttribute('y', 30);
      label.setAttribute('class', 'court-label');
      label.setAttribute('fill', '#111827');
      label.setAttribute('font-size', 18);
      label.textContent = c.name;

      // badge as foreignObject
      const fo = document.createElementNS(svgNS, 'foreignObject');
      fo.setAttribute('x', Math.max(8, w - 140));
      fo.setAttribute('y', 12);
      fo.setAttribute('width', 130);
      fo.setAttribute('height', 36);
      const div = document.createElement('div');
      div.setAttribute('xmlns','http://www.w3.org/1999/xhtml');
      div.style.display = 'flex';
      div.style.justifyContent = 'flex-end';
      const badge = document.createElement('div');
      badge.className = 'court-badge';
      const status = window.SB.getCourtStatus(c.id);
      badge.textContent = status.label;
      if(status.label.includes('AVAILABLE')) badge.style.background = 'var(--green)';
      else if(status.label.includes('OCCUPIED')) badge.style.background = 'var(--red)';
      else badge.style.background = 'var(--yellow)';
      div.appendChild(badge);
      fo.appendChild(div);

      g.appendChild(rect);
      g.appendChild(label);
      g.appendChild(fo);
      svg.appendChild(g);

      // click opens booking (ignore if dragging)
      g.addEventListener('click', (ev) => {
        if(dragging) return;
        ev.stopPropagation();
        Booking.open({ courtId: c.id });
      });

      // admin drag handlers
      g.addEventListener('pointerdown', (ev) => {
        if(!isAdmin) return;
        ev.preventDefault();
        g.setPointerCapture(ev.pointerId);
        dragging = { g, startX: ev.clientX, startY: ev.clientY, origTransform: g.getAttribute('transform'), w, h, id: c.id };
        g.classList.add('dragging');
      });
      g.addEventListener('pointermove', (ev) => {
        if(!dragging || dragging.g !== g) return;
        ev.preventDefault();
        const dx = ev.clientX - dragging.startX;
        const dy = ev.clientY - dragging.startY;
        // compute new translate based on original transform
        const match = /translate\(([-\d.]+),\s*([-\d.]+)\)/.exec(dragging.origTransform);
        const origX = match ? parseFloat(match[1]) : 0;
        const origY = match ? parseFloat(match[2]) : 0;
        const newX = origX + dx;
        const newY = origY + dy;
        g.setAttribute('transform', `translate(${newX},${newY})`);
      });
      g.addEventListener('pointerup', (ev) => {
        if(!dragging || dragging.g !== g) return;
        g.releasePointerCapture(ev.pointerId);
        g.classList.remove('dragging');
        // save new percent positions
        const transform = g.getAttribute('transform');
        const m = /translate\(([-\d.]+),\s*([-\d.]+)\)/.exec(transform);
        if(m){
          const newX = parseFloat(m[1]);
          const newY = parseFloat(m[2]);
          const px = (newX / 1000) * 100;
          const py = (newY / 600) * 100;
          const pos = layout[dragging.id] || defaultLayout[dragging.id];
          layout[dragging.id] = { x: clamp(px, 0, 100 - pos.w), y: clamp(py, 0, 100 - pos.h), w: pos.w, h: pos.h };
          saveLayout();
        }
        dragging = null;
        // re-render to update badges and panel positions
        renderMap();
      });
    });

    return svg;
  }

  // Side panel with cards
  function renderSidePanel(){
    // remove existing
    const existing = wrapper.querySelector('.map-side-panel');
    if(existing) existing.remove();

    const panel = document.createElement('div');
    panel.className = 'map-side-panel';
    panel.innerHTML = `
      <div class="panel-header">
        <h3>All Courts</h3>
        <div class="panel-actions">
          <button class="btn outline" id="panelToggleAdmin">Layout</button>
        </div>
      </div>
      <div class="panel-body" id="panelBody"></div>
    `;
    wrapper.appendChild(panel);

    // wire admin toggle
    const panelToggle = panel.querySelector('#panelToggleAdmin');
    panelToggle.addEventListener('click', () => toggleAdmin());

    const body = panel.querySelector('#panelBody');
    body.innerHTML = '';

    window.SB.courts.forEach(c => {
      const status = window.SB.getCourtStatus(c.id);
      const card = document.createElement('div');
      card.className = 'map-card';
      card.innerHTML = `
        <div class="row">
          <div>
            <div class="title">${c.name}</div>
            <div class="meta">${c.sport} • Court ${c.id}</div>
          </div>
          <div>
            <div class="badge" style="background:${status.label.includes('AVAILABLE') ? 'var(--green)' : status.label.includes('OCCUPIED') ? 'var(--red)' : 'var(--yellow)'}">
              ${status.label}
            </div>
          </div>
        </div>
        <div class="row">
          <div class="price">₱${c.price} / hour</div>
          <div class="card-actions">
            <button class="btn outline btn-view" data-id="${c.id}">View</button>
            <button class="btn primary btn-book" data-id="${c.id}">Book Now</button>
          </div>
        </div>
      `;
      body.appendChild(card);

      card.querySelector('.btn-book').addEventListener('click', (e) => {
        const id = parseInt(e.currentTarget.dataset.id, 10);
        Booking.open({ courtId: id });
      });
      card.querySelector('.btn-view').addEventListener('click', (e) => {
        const id = parseInt(e.currentTarget.dataset.id, 10);
        const st = window.SB.getCourtStatus(id);
        window.SB.showToast(`${c.name} — ${st.label}`);
      });
    });
  }

  // Admin toggle
  function toggleAdmin(){
    isAdmin = !isAdmin;
    if(isAdmin){
      wrapper.classList.add('map-admin');
      const btns = wrapper.querySelectorAll('#panelToggleAdmin, #toggleAdmin');
      btns.forEach(b => b && (b.textContent = 'Exit Layout Mode', b.classList.remove('outline'), b.classList.add('primary')));
    } else {
      wrapper.classList.remove('map-admin');
      const btns = wrapper.querySelectorAll('#panelToggleAdmin, #toggleAdmin');
      btns.forEach(b => b && (b.textContent = 'Enter Layout Mode', b.classList.remove('primary'), b.classList.add('outline')));
      saveLayout();
    }
  }

  // load / save layout
  function loadLayout(){
    try {
      const raw = localStorage.getItem(MAP_KEY);
      if(!raw) return JSON.parse(JSON.stringify(defaultLayout));
      const parsed = JSON.parse(raw);
      window.SB.courts.forEach(c => {
        if(!parsed[c.id]) parsed[c.id] = defaultLayout[c.id] || { x:5, y:5, w:30, h:30 };
      });
      return parsed;
    } catch(e){
      return JSON.parse(JSON.stringify(defaultLayout));
    }
  }
  function saveLayout(){
    localStorage.setItem(MAP_KEY, JSON.stringify(layout));
    window.SB.showToast('Layout saved');
  }
  function resetLayout(){
    layout = JSON.parse(JSON.stringify(defaultLayout));
    saveLayout();
    renderMap();
  }

  // helpers
  function clamp(v, a, b){ return Math.max(a, Math.min(b, v)); }

  // refresh badges and panel
  function refreshBadges(){
    renderMap();
  }

  // wire external buttons if present
  document.getElementById('toggleAdmin')?.addEventListener('click', toggleAdmin);
  document.getElementById('resetMap')?.addEventListener('click', () => {
    if(confirm('Reset layout to default positions?')) resetLayout();
  });

  // ensure SB.saveReservations triggers refresh
  if(window.SB && typeof window.SB.saveReservations === 'function'){
    const origSave = window.SB.saveReservations;
    window.SB.saveReservations = function(list){
      origSave(list);
      refreshBadges();
    };
  }

  // initial render
  renderMap();

  // expose refresh for other modules
  window.SB && (window.SB.refreshMap = refreshBadges);

})();
