/* booking.js
   - Booking modal, form, validation, price calc, confirmation, saving to localStorage.
*/

const Booking = (function(){
  const modal = document.getElementById('bookingModal');
  const content = document.getElementById('bookingContent');

  function open(pref = {}){
    modal?.classList.remove('hidden');
    modal?.setAttribute('aria-hidden','false');
    renderForm(pref);
  }
  function close(){
    modal?.classList.add('hidden');
    modal?.setAttribute('aria-hidden','true');
    content.innerHTML = '';
  }

  function renderForm(pref){
    const courts = window.SB.courts;
    const selectedCourt = courts.find(c => c.id === pref.courtId) || null;
    const reservationRef = pref.reservationRef || null;

    if(reservationRef){
      const reservations = window.SB.loadReservations();
      const r = reservations.find(x => x.ref === reservationRef);
      if(!r){ content.innerHTML = `<div class="center">Reservation not found</div>`; return; }
      content.innerHTML = `
        <h2>Reservation Details</h2>
        <div class="summary">
          <div><strong>Reference:</strong> ${r.ref}</div>
          <div><strong>Sport:</strong> ${r.sport}</div>
          <div><strong>Court:</strong> ${r.courtName}</div>
          <div><strong>Date:</strong> ${new Date(r.start).toLocaleDateString()}</div>
          <div><strong>Time:</strong> ${window.SB.formatTimeRange(r.start,r.end)}</div>
          <div><strong>Status:</strong> ${r.status}</div>
        </div>
        <div style="margin-top:12px">
          <button class="btn primary" id="confirmViewClose">Close</button>
        </div>
      `;
      document.getElementById('confirmViewClose').addEventListener('click', close);
      return;
    }

    content.innerHTML = `
      <h2>Book a Court</h2>
      <form id="bookingForm">
        <div class="form-row">
          <div class="form-field">
            <label for="sportSelect">Select Sport</label>
            <select id="sportSelect" required>
              <option value="">Choose sport</option>
              <option>Pickleball</option>
              <option>Badminton</option>
              <option>Basketball</option>
            </select>
          </div>
          <div class="form-field">
            <label for="courtSelect">Select Court</label>
            <select id="courtSelect" required>
              <option value="">Choose court</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="dateInput">Select Date</label>
            <input id="dateInput" type="date" required />
          </div>
          <div class="form-field">
            <label for="timeSelect">Start Time</label>
            <select id="timeSelect" class="time-select" required></select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label>Duration</label>
            <div class="duration-options">
              <button type="button" data-duration="30">30 min</button>
              <button type="button" data-duration="60" class="active">1 hour</button>
              <button type="button" data-duration="90">1.5 hours</button>
              <button type="button" data-duration="120">2 hours</button>
            </div>
          </div>
          <div class="form-field">
            <label for="nameInput">Customer Name</label>
            <input id="nameInput" type="text" placeholder="Your name" required />
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="contactInput">Contact Number</label>
            <input id="contactInput" type="tel" placeholder="09XXXXXXXXX" required />
          </div>
          <div class="form-field">
            <label>Price</label>
            <div class="summary" id="priceSummary">₱0</div>
          </div>
        </div>

        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
          <button type="button" class="btn outline" id="cancelBooking">Cancel</button>
          <button type="submit" class="btn primary">Confirm Reservation</button>
        </div>
      </form>
    `;

    const sportSelect = document.getElementById('sportSelect');
    const courtSelect = document.getElementById('courtSelect');
    const dateInput = document.getElementById('dateInput');
    const timeSelect = document.getElementById('timeSelect');
    const durationButtons = Array.from(content.querySelectorAll('.duration-options button'));
    const priceSummary = document.getElementById('priceSummary');

    const today = new Date();
    dateInput.min = today.toISOString().split('T')[0];
    dateInput.value = today.toISOString().split('T')[0];

    if(selectedCourt){
      sportSelect.value = selectedCourt.sport;
      populateCourtOptions(selectedCourt.sport, selectedCourt.id);
    } else {
      populateCourtOptions('', null);
    }

    populateTimeSlots();

    let selectedDuration = 60;
    durationButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        durationButtons.forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        selectedDuration = parseInt(btn.dataset.duration,10);
        updatePrice();
      });
    });

    sportSelect.addEventListener('change', (e) => {
      populateCourtOptions(e.target.value, null);
      updatePrice();
    });

    courtSelect.addEventListener('change', updatePrice);
    timeSelect.addEventListener('change', updatePrice);
    dateInput.addEventListener('change', updatePrice);

    document.getElementById('cancelBooking').addEventListener('click', close);

    document.getElementById('bookingForm').addEventListener('submit', (ev) => {
      ev.preventDefault();
      const sport = sportSelect.value;
      const courtId = parseInt(courtSelect.value,10);
      const court = window.SB.courts.find(c=>c.id===courtId);
      const date = dateInput.value;
      const time = timeSelect.value;
      const name = document.getElementById('nameInput').value.trim();
      const contact = document.getElementById('contactInput').value.trim();

      if(!sport || !courtId || !date || !time || !name || !contact){
        window.SB.showToast('Please fill all fields');
        return;
      }

      const startTs = combineDateTimeToTs(date, time);
      const endTs = startTs + selectedDuration * 60 * 1000;

      if(isConflict(courtId, startTs, endTs)){
        window.SB.showToast('Selected time conflicts with existing reservation');
        return;
      }

      const ref = generateRef();
      const reservation = {
        ref,
        courtId,
        courtName: court.name,
        sport,
        start: startTs,
        end: endTs,
        duration: selectedDuration,
        price: calculatePrice(court.price, selectedDuration),
        customerName: name,
        contact,
        status: startTs <= Date.now() && endTs > Date.now() ? 'ACTIVE' : (startTs > Date.now() ? 'UPCOMING' : 'COMPLETED'),
        createdAt: Date.now()
      };

      const reservations = window.SB.loadReservations();
      reservations.push(reservation);
      window.SB.saveReservations(reservations);

      if(reservation.status === 'ACTIVE'){
        Timer.addTimer(reservation);
      }

      window.SB.showToast('Reservation confirmed');
      close();
      showConfirmation(reservation);
      window.SB.renderLiveGrid();
      window.SB.renderSchedule();
      window.SB.renderMyReservations();
    });

    updatePrice();

    function populateCourtOptions(sport, preselectId){
      courtSelect.innerHTML = '<option value="">Choose court</option>';
      window.SB.courts.filter(c => !sport || c.sport === sport).forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.name;
        if(preselectId && c.id === preselectId) opt.selected = true;
        courtSelect.appendChild(opt);
      });
    }

    function populateTimeSlots(){
      timeSelect.innerHTML = '';
      const slots = generateTimeSlots(7, 22, 30);
      slots.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.value;
        opt.textContent = s.label;
        timeSelect.appendChild(opt);
      });
    }

    function updatePrice(){
      const courtId = parseInt(courtSelect.value,10);
      const court = window.SB.courts.find(c=>c.id===courtId);
      const price = court ? calculatePrice(court.price, selectedDuration) : 0;
      priceSummary.textContent = `₱${price}`;
    }
  }

  function generateTimeSlots(startHour=7, endHour=22, stepMinutes=30){
    const slots = [];
    for(let h=startHour; h<endHour; h++){
      for(let m=0; m<60; m+=stepMinutes){
        const date = new Date();
        date.setHours(h, m, 0, 0);
        const label = date.toLocaleTimeString([], {hour:'numeric', minute:'2-digit'});
        const value = `${pad(h)}:${pad(m)}`;
        slots.push({label, value});
      }
    }
    return slots;
  }
  function pad(n){ return n.toString().padStart(2,'0'); }

  function combineDateTimeToTs(dateStr, timeStr){
    const [h,m] = timeStr.split(':').map(Number);
    const d = new Date(dateStr);
    d.setHours(h, m, 0, 0);
    return d.getTime();
  }

  function calculatePrice(hourlyPrice, durationMinutes){
    return Math.round((hourlyPrice/60) * durationMinutes);
  }

  function isConflict(courtId, start, end){
    const reservations = window.SB.loadReservations();
    return reservations.some(r => r.courtId === courtId && r.status !== 'COMPLETED' && !(end <= r.start || start >= r.end));
  }

  function generateRef(){
    const now = new Date();
    const y = now.getFullYear();
    const m = pad(now.getMonth()+1);
    const d = pad(now.getDate());
    const base = `BK-${y}${m}${d}`;
    const reservations = window.SB.loadReservations();
    const todayCount = reservations.filter(r => r.ref && r.ref.startsWith(base)).length + 1;
    return `${base}-${String(todayCount).padStart(3,'0')}`;
  }

  function showConfirmation(reservation){
    const panel = document.createElement('div');
    panel.innerHTML = `
      <h2>Reservation Confirmed! ✅</h2>
      <div class="summary">
        <div><strong>Booking Reference:</strong> ${reservation.ref}</div>
        <div><strong>Sport:</strong> ${reservation.sport}</div>
        <div><strong>Court:</strong> ${reservation.courtName}</div>
        <div><strong>Date:</strong> ${new Date(reservation.start).toLocaleDateString()}</div>
        <div><strong>Time:</strong> ${window.SB.formatTimeRange(reservation.start,reservation.end)}</div>
        <div><strong>Total:</strong> ₱${reservation.price}</div>
      </div>
      <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
        <button class="btn outline" id="confClose">Close</button>
        <button class="btn primary" id="viewMy">View My Reservation</button>
      </div>
    `;
    const old = content.innerHTML;
    content.innerHTML = panel.innerHTML;
    document.getElementById('confClose').addEventListener('click', () => {
      close();
      content.innerHTML = old;
    });
    document.getElementById('viewMy').addEventListener('click', () => {
      close();
      window.location.href = 'reservations.html';
    });
  }

  return { open, close };
})();
