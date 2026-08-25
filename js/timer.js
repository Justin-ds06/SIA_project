/* timer.js
   - Countdown timers for ACTIVE reservations, persisted via timestamps.
*/

const Timer = (function(){
  const timers = {}; // ref -> intervalId

  function init(){
    const reservations = window.SB.loadReservations();
    const now = Date.now();
    let changed = false;
    reservations.forEach(r => {
      if(r.status === 'ACTIVE'){
        if(r.end <= now){
          r.status = 'COMPLETED';
          changed = true;
        } else {
          addTimer(r);
        }
      } else if(r.status === 'UPCOMING'){
        if(r.start <= now && r.end > now){
          r.status = 'ACTIVE';
          addTimer(r);
          changed = true;
        } else if(r.end <= now){
          r.status = 'COMPLETED';
          changed = true;
        }
      }
    });
    if(changed) window.SB.saveReservations(reservations);
    renderAllTimers();
  }

  function addTimer(reservation){
    const ref = reservation.ref;
    if(timers[ref]) return;
    timers[ref] = setInterval(()=> {
      updateTimer(reservation);
    }, 1000);
    updateTimer(reservation);
  }

  function updateTimer(reservation){
    const now = Date.now();
    const ref = reservation.ref;
    const el = document.querySelector(`[data-timer="${ref}"]`);
    const remaining = Math.max(0, reservation.end - now);
    const formatted = formatMs(remaining);
    if(el) el.textContent = formatted + ' remaining';
    if(remaining <= 0){
      clearInterval(timers[ref]);
      delete timers[ref];
      const reservations = window.SB.loadReservations();
      const r = reservations.find(x => x.ref === ref);
      if(r){
        r.status = 'COMPLETED';
        window.SB.saveReservations(reservations);
      }
      window.SB.renderLiveGrid();
      window.SB.renderSchedule();
      window.SB.renderMyReservations();
    }
  }

  function renderAllTimers(){
    const reservations = window.SB.loadReservations();
    reservations.forEach(r => {
      if(r.status === 'ACTIVE' && !timers[r.ref]){
        addTimer(r);
      }
      const el = document.querySelector(`[data-timer="${r.ref}"]`);
      if(el && r.status !== 'ACTIVE'){
        el.textContent = r.status === 'UPCOMING' ? 'Upcoming' : 'Completed';
      }
    });
  }

  function formatMs(ms){
    const total = Math.floor(ms/1000);
    const hours = Math.floor(total/3600);
    const minutes = Math.floor((total % 3600)/60);
    const seconds = total % 60;
    return `${String(hours).padStart(2,'0')}:${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}`;
  }

  return { init, addTimer, renderAllTimers };
})();
