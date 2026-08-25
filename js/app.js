/* app.js
   - Mock data, rendering, UI wiring, localStorage helpers
*/

const courts = [
    { id: 1, sport: "Pickleball", name: "Pickleball Court 1", price: 300 },
    { id: 2, sport: "Pickleball", name: "Pickleball Court 2", price: 300 },
    { id: 3, sport: "Pickleball", name: "Pickleball Court 3", price: 300 },
    { id: 4, sport: "Badminton", name: "Badminton Court 1", price: 200 },
    { id: 5, sport: "Basketball", name: "Basketball Court 1", price: 500 }
];

const STORAGE_KEYS = {
  RESERVATIONS: 'sb_reservations_v1'
};

function $(sel){return document.querySelector(sel)}
function $all(sel){return Array.from(document.querySelectorAll(sel))}

document.addEventListener('DOMContentLoaded', () => {
  $all('#year,#year2,#year3').forEach(el => el && (el.textContent = new Date().getFullYear()));

  $('#hero-book')?.addEventListener('click', () => openBookingModal());
  $('#cta-book')?.addEventListener('click', () => openBookingModal());
  $('#nav-book-now')?.addEventListener('click', (e)=>{ e.preventDefault(); openBookingModal(); });
  $('#nav-book-now-2')?.addEventListener('click', (e)=>{ e.preventDefault(); openBookingModal(); });
  $('#nav-book-now-3')?.addEventListener('click', (e)=>{ e.preventDefault(); openBookingModal(); });

  $('#hamburger')?.addEventListener('click', ()=> toggleMobileNav());
  $('#hamburger2')?.addEventListener('click', ()=> toggleMobileNav());
  $('#hamburger3')?.addEventListener('click', ()=> toggleMobileNav());

  $('#modalClose')?.addEventListener('click', closeBookingModal);
  $('#modalClose2')?.addEventListener('click', closeBookingModal);

  $('#filterSport')?.addEventListener('change', (e)=> renderCourtsGrid(e.target.value));
  $('#resetFilter')?.addEventListener('click', ()=> { $('#filterSport').value='All'; renderCourtsGrid('All'); });

  renderHeroPreview();
  renderCourtsGrid('All');
  renderLiveGrid();
  renderSchedule();
  renderMyReservations();

  Timer.init();
});

function toggleMobileNav(){
  const navs = $all('.nav');
  navs.forEach(n => n.style.display = n.style.display === 'flex' ? 'none' : 'flex');
}

/* LocalStorage helpers */
function loadReservations(){
  const raw = localStorage.getItem(STORAGE_KEYS.RESERVATIONS);
  if(!raw) return [];
  try { return JSON.parse(raw); } catch(e){ return []; }
}
function saveReservations(list){
  localStorage.setItem(STORAGE_KEYS.RESERVATIONS, JSON.stringify(list));
}

/* Booking modal helpers */
function openBookingModal(pref = {}) {
  Booking.open(pref);
}
function closeBookingModal(){
  Booking.close();
}

/* Render hero preview cards */
function renderHeroPreview(){
  const container = $('#hero-cards');
  if(!container) return;
  container.innerHTML = '';
  const sample = courts.slice(0,4);
  sample.forEach(c => {
    const status = getCourtStatus(c.id);
    const card = document.createElement('div');
    card.className = 'card';
    card.innerHTML = `
      <div class="meta">
        <div class="kicker">${c.sport}</div>
        <div class="badge ${status.class}">${status.label}</div>
      </div>
      <div style="font-weight:800">${c.name}</div>
      <div class="small">₱${c.price} / hour</div>
      <div style="margin-top:8px">
        <button class="btn primary" data-book="${c.id}">Book Now</button>
      </div>
    `;
    container.appendChild(card);
    card.querySelector('[data-book]')?.addEventListener('click', ()=> openBookingModal({courtId:c.id}));
  });
}

/* Determine court status from reservations */
function getCourtStatus(courtId){
  const now = Date.now();
  const reservations = loadReservations();
  const active = reservations.find(r => r.courtId === courtId && r.status === 'ACTIVE');
  if(active){
    return { label: 'OCCUPIED', class: 'status-occupied', status:'OCCUPIED', reservation: active };
  }
  const upcoming = reservations
    .filter(r => r.courtId === courtId && r.status === 'UPCOMING')
    .sort((a,b)=>a.start - b.start)[0];
  if(upcoming){
    const diff = upcoming.start - now;
    if(diff <= 2 * 60 * 60 * 1000) {
      return { label: 'RESERVED SOON', class: 'status-soon', status:'RESERVED_SOON', reservation: upcoming };
    }
    return { label: 'RESERVED', class: 'status-soon', status:'RESERVED', reservation: upcoming };
  }
  return { label: 'AVAILABLE', class: 'status-available', status:'AVAILABLE' };
}

/* Render courts grid */
function renderCourtsGrid(filter = 'All'){
  const grid = $('#courtsGrid');
  if(!grid) return;
  grid.innerHTML = '';
  const list = courts.filter(c => filter === 'All' ? true : c.sport === filter);
  list.forEach(c => {
    const status = getCourtStatus(c.id);
    const el = document.createElement('div');
    el.className = 'court-card';
    el.innerHTML = `
      <div class="court-top">
        <div>
          <div class="court-title">${c.name}</div>
          <div class="court-sub">${c.sport} • Court ${c.id}</div>
        </div>
        <div class="badge ${status.class}">${status.label}</div>
      </div>
      <div class="court-price">₱${c.price} / hour</div>
      <div class="small">Status: <strong>${status.label}</strong></div>
      <div class="court-actions">
        <button class="btn primary" data-book="${c.id}">Book Now</button>
        <button class="btn outline" data-view="${c.id}">View</button>
      </div>
    `;
    grid.appendChild(el);
    el.querySelector('[data-book]')?.addEventListener('click', ()=> openBookingModal({courtId:c.id}));
    el.querySelector('[data-view]')?.addEventListener('click', ()=> {
      showToast(`${c.name} — ${status.label}`);
    });
  });
}

/* Live dashboard */
function renderLiveGrid(){
  const grid = $('#liveGrid');
  if(!grid) return;
  grid.innerHTML = '';
  courts.forEach(c => {
    const status = getCourtStatus(c.id);
    const card = document.createElement('div');
    card.className = 'card';
    const timeInfo = status.reservation ? formatTimeRange(status.reservation.start, status.reservation.end) : '';
    const timerId = status.reservation ? `timer-${status.reservation.ref}` : '';
    card.innerHTML = `
      <div class="meta">
        <div class="kicker">${c.sport}</div>
        <div class="badge ${status.class}">${status.label}</div>
      </div>
      <div style="font-weight:800">${c.name}</div>
      <div class="small">${timeInfo}</div>
      <div style="margin-top:8px" id="${timerId}">
        ${status.reservation ? `<div class="small" data-timer="${status.reservation.ref}">Loading timer...</div>` : `<button class="btn primary" data-book="${c.id}">Book Now</button>`}
      </div>
    `;
    grid.appendChild(card);
    card.querySelector('[data-book]')?.addEventListener('click', ()=> openBookingModal({courtId:c.id}));
  });
  Timer.renderAllTimers();
}

/* Schedule table */
function renderSchedule(){
  const container = $('#scheduleTable');
  if(!container) return;
  const reservations = loadReservations().sort((a,b)=>a.start - b.start);
  let html = `<div class="schedule"><table><thead><tr><th>Time</th><th>Sport</th><th>Court</th><th>Status</th></tr></thead><tbody>`;
  if(reservations.length === 0){
    html += `<tr><td colspan="4" class="center small">No reservations yet</td></tr>`;
  } else {
    reservations.forEach(r => {
      const court = courts.find(c => c.id === r.courtId);
      const timeRange = formatTimeRange(r.start, r.end);
      const statusBadge = r.status === 'ACTIVE' ? '🔴 Active' : r.status === 'UPCOMING' ? '🟡 Upcoming' : '✅ Completed';
      html += `<tr><td>${timeRange}</td><td>${r.sport}</td><td>${court?.name || 'Court'}</td><td>${statusBadge}</td></tr>`;
    });
  }
  html += `</tbody></table></div>`;
  container.innerHTML = html;
}

/* My reservations page */
function renderMyReservations(){
  const container = $('#myReservations');
  if(!container) return;
  const reservations = loadReservations().sort((a,b)=>b.start - a.start);
  if(reservations.length === 0){
    container.innerHTML = `<div class="card center small">You have no reservations yet.</div>`;
    return;
  }
  container.innerHTML = '';
  reservations.forEach(r => {
    const court = courts.find(c => c.id === r.courtId);
    const el = document.createElement('div');
    el.className = 'res-card';
    const timeRange = formatTimeRange(r.start, r.end);
    const statusLabel = r.status === 'ACTIVE' ? '🔴 Active' : r.status === 'UPCOMING' ? '🟡 Upcoming' : '✅ Completed';
    el.innerHTML = `
      <div class="res-left">
        <div class="res-meta">
          <div style="font-weight:800">${court?.name || r.courtName}</div>
          <div class="small">${r.sport} • ${new Date(r.start).toLocaleDateString()}</div>
          <div class="small">${timeRange}</div>
          <div class="small">Status: <strong>${statusLabel}</strong></div>
        </div>
      </div>
      <div class="res-actions">
        ${r.status === 'ACTIVE' ? `<div class="kicker" data-timer="${r.ref}">Loading timer...</div>` : ''}
        <button class="btn outline" data-view="${r.ref}">View</button>
        <button class="btn ghost" data-cancel="${r.ref}">Cancel</button>
      </div>
    `;
    container.appendChild(el);
    el.querySelector('[data-view]')?.addEventListener('click', ()=> {
      Booking.open({reservationRef: r.ref});
    });
    el.querySelector('[data-cancel]')?.addEventListener('click', ()=> {
      cancelReservation(r.ref);
    });
  });
  Timer.renderAllTimers();
}

/* Cancel reservation */
function cancelReservation(ref){
  const reservations = loadReservations();
  const idx = reservations.findIndex(r => r.ref === ref);
  if(idx === -1) { showToast('Reservation not found'); return; }
  const r = reservations[idx];
  reservations.splice(idx,1);
  saveReservations(reservations);
  showToast('Reservation cancelled');
  renderLiveGrid();
  renderSchedule();
  renderMyReservations();
}

/* Utilities */
function formatTimeRange(startTs, endTs){
  const s = new Date(startTs);
  const e = new Date(endTs);
  const opts = { hour: 'numeric', minute: '2-digit' };
  return `${s.toLocaleTimeString([],opts)} – ${e.toLocaleTimeString([],opts)}`;
}

function showToast(msg, timeout=3000){
  const t = $('#toast');
  if(!t) return;
  t.textContent = msg;
  t.classList.remove('hidden');
  clearTimeout(t._hide);
  t._hide = setTimeout(()=> t.classList.add('hidden'), timeout);
}

/* Expose to Booking and Timer modules */
window.SB = {
  courts,
  loadReservations,
  saveReservations,
  getCourtStatus,
  renderLiveGrid,
  renderSchedule,
  renderCourtsGrid,
  renderMyReservations,
  showToast,
  formatTimeRange
};
