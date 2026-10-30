(function(){
  let last=Number(localStorage.getItem('ff-last-new-orders')||0), initialized=false;
  function toast(title,body){
    let t=document.getElementById('ffLiveToast');
    if(!t){t=document.createElement('div');t.id='ffLiveToast';t.className='ff-live-toast';document.body.appendChild(t)}
    t.innerHTML='<div class="ff-live-icon">🔔</div><div><strong>'+title+'</strong><span>'+body+'</span></div><button aria-label="Close">×</button>';
    t.querySelector('button').onclick=()=>t.remove();
    requestAnimationFrame(()=>t.classList.add('show')); clearTimeout(t._timer); t._timer=setTimeout(()=>t.classList.remove('show'),7000);
    try{const C=window.AudioContext||window.webkitAudioContext;if(C){const c=new C(),o=c.createOscillator(),g=c.createGain();o.frequency.value=740;g.gain.value=.035;o.connect(g);g.connect(c.destination);o.start();o.stop(c.currentTime+.16)}}catch(e){}
  }
  function update(d){
    if(!d.ok)return;
    document.querySelectorAll('.nav-badge').forEach(b=>{b.textContent=d.count>99?'99+':d.count;b.style.display=d.count?'flex':'none'});
    if(initialized && d.count>last){const x=d.latest||{};toast('Yangi buyurtma #'+(x.id||''),(x.customer_name||'Mijoz')+' · '+(x.table_number?'Stol #'+x.table_number:'Stolsiz')+' · '+Number(x.total||0).toLocaleString('uz-UZ')+' so\'m')}
    last=d.count;localStorage.setItem('ff-last-new-orders',String(last));initialized=true;
  }
  async function poll(){try{const r=await fetch((location.pathname.indexOf('/delivery/')>-1?'../':'')+'notifications.php?_='+Date.now(),{cache:'no-store'});update(await r.json())}catch(e){}}
  document.addEventListener('DOMContentLoaded',()=>{poll();setInterval(poll,10000)});
})();
