/* VolleyTrack app.js */

/* ── Toasts ────────────────────────────────────── */
const TI={ok:'bi-check-circle-fill',err:'bi-x-circle-fill',info:'bi-info-circle-fill',warn:'bi-exclamation-triangle-fill'};
const TC={ok:'#16a34a',err:'#dc2626',info:'#2563eb',warn:'#d97706'};
function toast(msg,type='ok',ms=3200){
  let w=document.getElementById('toasts');
  if(!w){w=document.createElement('div');w.id='toasts';w.className='toasts';document.body.appendChild(w);}
  const t=document.createElement('div');t.className='toast';
  t.innerHTML=`<i class="bi ${TI[type]||TI.info}" style="color:${TC[type]||TC.info};font-size:15px;flex-shrink:0"></i><span style="flex:1;font-size:13px;color:#111">${msg}</span><button class="toast-x" onclick="this.closest('.toast').remove()"><i class="bi bi-x"></i></button>`;
  w.appendChild(t);
  setTimeout(()=>{t.style.opacity='0';t.style.transition='.2s';setTimeout(()=>t.remove(),220);},ms);
}

/* ── Live clock ─────────────────────────────────── */
function tickClocks(){
  const s=new Date().toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  document.querySelectorAll('.live-clock').forEach(e=>e.textContent=s);
}
setInterval(tickClocks,1000);tickClocks();

/* ── Nav dropdowns ──────────────────────────────── */
function toggleDrop(id){
  const el=document.getElementById(id);
  if(!el)return;
  const isOpen=el.classList.contains('open');
  document.querySelectorAll('.nav-drop.open').forEach(d=>d.classList.remove('open'));
  if(!isOpen)el.classList.add('open');
}
document.addEventListener('click',e=>{
  if(!e.target.closest('.nav-drop'))
    document.querySelectorAll('.nav-drop.open').forEach(d=>d.classList.remove('open'));
});

/* ── Mobile nav toggle ──────────────────────────── */
document.addEventListener('DOMContentLoaded',()=>{
  const toggle=document.getElementById('navToggle');
  const mob=document.getElementById('mobileNav');
  const icon=document.getElementById('toggleIcon');
  if(toggle&&mob){
    toggle.addEventListener('click',e=>{
      e.stopPropagation();
      const open=mob.classList.toggle('open');
      if(icon)icon.className=open?'bi bi-x':'bi bi-list';
    });
    mob.querySelectorAll('a').forEach(a=>{
      a.addEventListener('click',()=>{
        mob.classList.remove('open');
        if(icon)icon.className='bi bi-list';
      });
    });
  }
  /* Tooltips */
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el=>new bootstrap.Tooltip(el,{trigger:'hover'}));
});

/* ── API helper ─────────────────────────────────── */
async function api(url,data){
  try{
    const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
    if(!r.ok)return{error:`HTTP ${r.status}`};
    return await r.json();
  }catch(e){return{error:e.message};}
}
