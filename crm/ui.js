(function () {
  if (window.__hrUiLoaded) return;
  window.__hrUiLoaded = true;
  const defaults = {theme: 'light', accent: 'blue', density: 'comfortable'};
  const allowed = {theme: ['light','dark','system'], accent: ['blue','emerald','violet'], density: ['comfortable','compact']};
  function read() {try {return Object.assign({}, defaults, JSON.parse(localStorage.getItem('hr.appearance.v2') || '{}'));} catch (_) {return {...defaults};}}
  function valid(settings) {for (const key of Object.keys(defaults)) if (!allowed[key].includes(settings[key])) settings[key] = defaults[key]; return settings;}
  let settings = valid(read());
  const media = matchMedia('(prefers-color-scheme: dark)');
  function paint(doc) {
    doc.documentElement.dataset.hrTheme = settings.theme === 'system' ? (media.matches ? 'dark' : 'light') : settings.theme;
    try {doc.cookie='hr_theme='+doc.documentElement.dataset.hrTheme+'; path=/; SameSite=Lax';} catch (_) {}
    doc.documentElement.dataset.hrAccent = settings.accent;
    doc.documentElement.dataset.hrDensity = settings.density;
    doc.querySelectorAll('[data-hr-setting]').forEach(select => select.value = settings[select.dataset.hrSetting]);
  }
  function apply() {
    paint(document);
    for (const frame of document.querySelectorAll('iframe')) {try {if(frame.contentDocument) paint(frame.contentDocument);} catch (_) {}}
  }
  paint(document);
  function activate() {
    const hash = location.hash;
    document.body?.classList.toggle('hr-auth-page',/^#\/(Login|login)(\/|$|\?)/.test(hash));
    const match = {'HR_People':'people','HR_Documents':'documents','HR_Templates':'templates','HR_Awards':'awards','HR_Service':'service','HR_Education':'education','HR_Relatives':'relatives','HR_Audit':'audit','HR_Help':'guide','home':'home'};
    const key = Object.keys(match).find(k => hash.includes('/'+k));
    if (!key) return;
    document.querySelectorAll('.hr-sidebar [data-section]').forEach(link => {if(link.dataset.section === match[key]) link.setAttribute('aria-current','page');else link.removeAttribute('aria-current');});
  }
  function ready() {
    if(document.querySelector('.hr-shell'))document.body.classList.add('hr-page');
    if (window.parent !== window) {
      try {
        const parentDoc = window.parent.document;
        if (parentDoc.location.origin === location.origin) {
          document.documentElement.classList.add('hr-in-frame');
          parentDoc.body.classList.add('hr-app-host');
          for (const selector of ['.hr-sidebar','.hr-topbar','.hr-backdrop']) {const node=document.querySelector(selector);if(!node)continue;const previous=parentDoc.querySelector(selector);if(previous)previous.replaceWith(node.cloneNode(true));else parentDoc.body.appendChild(node.cloneNode(true));}
          if (!parentDoc.getElementById('hr-ui-css')) {const css=parentDoc.createElement('link');css.id='hr-ui-css';css.rel='stylesheet';css.href='/hr-ui.css?v=3';parentDoc.head.appendChild(css);}
          if (!parentDoc.getElementById('hr-ui-js')) {const script=parentDoc.createElement('script');script.id='hr-ui-js';script.src='/hr-ui.js?v=3';parentDoc.head.appendChild(script);}
        }
      } catch (_) {}
    }
    document.addEventListener('change', event => {
      const key = event.target.dataset.hrSetting;
      if (!key || !allowed[key].includes(event.target.value)) return;
      settings[key] = event.target.value;
      try {localStorage.setItem('hr.appearance.v2',JSON.stringify(settings));} catch (_) {}
      apply();
    });
    function closeMenu(){document.body.classList.remove('hr-menu-open');document.querySelectorAll('[data-hr-menu]').forEach(button=>button.setAttribute('aria-expanded','false'));}
    function closeSettings(){document.querySelectorAll('.hr-appearance-panel').forEach(panel=>panel.hidden=true);document.querySelectorAll('[data-hr-settings-toggle]').forEach(button=>button.setAttribute('aria-expanded','false'));}
    let loggingOut=false;
    function csrf(){const cookie=document.cookie.split('; ').find(value=>value.startsWith('XSRF-TOKEN='));return cookie?decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)):'';}
    async function logout(button){
      if(loggingOut)return;loggingOut=true;button.disabled=true;const label=button.querySelector('span');if(label)label.textContent='Выход…';
      document.querySelector('.hr-logout-error')?.remove();
      try{
        const send=()=>fetch('/logout',{method:'POST',credentials:'same-origin',headers:{'X-XSRF-TOKEN':csrf(),'Accept':'application/json'},cache:'no-store'});
        let response=await send();
        if(response.status===403){await fetch('/',{credentials:'same-origin',cache:'no-store'});response=await send();}
        if(!response.ok)throw new Error('Не удалось завершить сессию. Повторите выход.');
        window.top.location.assign('/#/Login');
      }catch(error){
        const message=document.createElement('div');message.className='hr-logout-error';message.setAttribute('role','alert');message.textContent='Не удалось выйти. Проверьте соединение и повторите.';button.closest('.hr-topbar').appendChild(message);button.disabled=false;if(label)label.textContent='Выйти';loggingOut=false;
      }
    }
    document.addEventListener('click', event => {
      const logoutButton=event.target.closest('[data-hr-logout]');if(logoutButton){event.preventDefault();logout(logoutButton);return;}
      const settingsButton=event.target.closest('[data-hr-settings-toggle]');
      if(settingsButton){const panel=settingsButton.closest('.hr-appearance').querySelector('.hr-appearance-panel');panel.hidden=!panel.hidden;settingsButton.setAttribute('aria-expanded',String(!panel.hidden));}
      else if(!event.target.closest('.hr-appearance-panel'))closeSettings();
      if(event.target.closest('[data-hr-menu]')){document.body.classList.toggle('hr-menu-open');document.querySelectorAll('[data-hr-menu]').forEach(button=>button.setAttribute('aria-expanded',String(document.body.classList.contains('hr-menu-open'))));}
      if(event.target.closest('.hr-backdrop') || event.target.closest('.hr-sidebar a'))closeMenu();
    });
    document.addEventListener('keydown',event=>{if(event.key==='Escape'){closeMenu();closeSettings();}});
    window.addEventListener('hashchange',activate);
    window.addEventListener('storage',()=>{settings=valid(read());apply();});
    media.addEventListener('change',()=>{if(settings.theme==='system')apply();});
    apply();activate();
  }
  if(document.readyState === 'loading')document.addEventListener('DOMContentLoaded',ready);else ready();
  window.hrEditorTheme = () => document.documentElement.dataset.hrTheme === 'dark' ? 'theme-dark' : 'theme-light';
})();
