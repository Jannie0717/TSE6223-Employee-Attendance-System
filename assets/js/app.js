function updateServerClock(){
  const el = document.querySelector('[data-server-clock]');
  if(!el) return;
  const now = new Date();
  let h = now.getHours();
  const m = String(now.getMinutes()).padStart(2,'0');
  const s = String(now.getSeconds()).padStart(2,'0');
  const suffix = h >= 12 ? 'PM' : 'AM';
  h = h % 12 || 12;
  el.textContent = `${String(h).padStart(2,'0')}:${m}:${s} ${suffix}`;
}
setInterval(updateServerClock,1000);
updateServerClock();

const roleInput = document.querySelector('#roleInput');
document.querySelectorAll('[data-role]').forEach(btn=>{
  btn.addEventListener('click',()=>{
    document.querySelectorAll('[data-role]').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    if(roleInput) roleInput.value = btn.dataset.role;
  });
});

function printReport(){
  document.body.classList.add('printing-records');
  window.print();
  setTimeout(() => document.body.classList.remove('printing-records'), 500);
}


// Show a hidden form only when its button is clicked.
document.querySelectorAll('[data-toggle-target]').forEach(btn => {
  btn.addEventListener('click', () => {
    const target = document.getElementById(btn.dataset.toggleTarget);
    if (!target) return;
    document.querySelectorAll('.toggle-panel').forEach(panel => {
      if (panel !== target) panel.classList.add('hidden');
    });
    target.classList.toggle('hidden');
    if (!target.classList.contains('hidden')) {
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});

document.querySelectorAll('.role-btn').forEach(button => {
    button.addEventListener('click', function () {
        document.querySelectorAll('.role-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        this.classList.add('active');

        const selectedRole = this.getAttribute('data-role');
        document.getElementById('roleInput').value = selectedRole;
    });
});