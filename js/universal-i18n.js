(function () {
  const LANGS = ['uz','ru','en'];
  function getLang(){ return document.cookie.match(/(?:^|; )ff_lang=([^;]+)/)?.[1] || 'uz'; }
  function shouldSkip(el){
    if (!el) return true;
    const tag=el.tagName;
    if (['SCRIPT','STYLE','NOSCRIPT','TEXTAREA','INPUT','SELECT','OPTION'].includes(tag)) return true;
    if (el.closest('[data-no-translate],.no-translate,[contenteditable="true"]')) return true;
    return false;
  }
  function collect(){
    const set=new Set();
    const walker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT);
    while(walker.nextNode()){
      const n=walker.currentNode, p=n.parentElement;
      if(shouldSkip(p)) continue;
      const raw=n.nodeValue, t=raw.trim();
      if(t && t.length<=180 && /[A-Za-zА-Яа-яЎўҚқҒғҲҳ]/.test(t)) set.add(t);
    }
    document.querySelectorAll('[placeholder],[title],[aria-label]').forEach(el=>{
      if(shouldSkip(el)) return;
      ['placeholder','title','aria-label'].forEach(a=>{const v=el.getAttribute(a);if(v&&v.length<=180)set.add(v.trim());});
    });
    return [...set];
  }
  function apply(map){
    const walker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT);
    while(walker.nextNode()){
      const n=walker.currentNode,p=n.parentElement;
      if(shouldSkip(p)) continue;
      const raw=n.nodeValue,t=raw.trim();
      if(map[t]) n.nodeValue=raw.replace(t,map[t]);
    }
    document.querySelectorAll('[placeholder],[title],[aria-label]').forEach(el=>{
      if(shouldSkip(el)) return;
      ['placeholder','title','aria-label'].forEach(a=>{const v=el.getAttribute(a);const tr=map[v?.trim()];if(tr)el.setAttribute(a,tr);});
    });
    document.documentElement.lang=getLang();
  }
  async function translateAll(){
    const lang=getLang();
    if(!LANGS.includes(lang) || lang==='uz') return;
    const texts=collect(); if(!texts.length) return;
    const cacheKey='ff-ui-'+lang+'-v3'; let cache={};
    try{cache=JSON.parse(localStorage.getItem(cacheKey)||'{}')||{};}catch(e){}
    const missing=texts.filter(t=>!cache[t]);
    if(missing.length){
      try{
        const endpoint=new URL('../translate_ui.php', document.currentScript || location.href);
        const r=await fetch(endpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({target:lang,texts:missing.slice(0,80)})});
        const j=await r.json(); Object.assign(cache,j.translations||{});
        try{localStorage.setItem(cacheKey,JSON.stringify(cache));}catch(e){}
      }catch(e){}
    }
    apply(cache);
    window.FFUniversalTranslate=translateAll;
  }
  document.addEventListener('DOMContentLoaded',()=>setTimeout(translateAll,80));
  window.FFUniversalTranslate=translateAll;
})();
