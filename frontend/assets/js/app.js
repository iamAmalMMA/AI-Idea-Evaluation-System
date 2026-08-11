document.querySelectorAll('.clickable-row').forEach(row=>row.addEventListener('click',event=>{if(!event.target.closest('a,button')) location.href=row.dataset.href;}));

const menuToggle=document.querySelector('[data-menu-toggle]');
const mainNav=document.querySelector('[data-main-nav]');
if(menuToggle&&mainNav) menuToggle.addEventListener('click',()=>mainNav.classList.toggle('open'));

const toast=document.querySelector('[data-toast]');
if(toast) setTimeout(()=>toast.remove(),3000);

const ideaWrap=document.querySelector('[data-idea-form]');
if(ideaWrap){
  let step=1;
  const form=ideaWrap.querySelector('form');
  const panels=[...ideaWrap.querySelectorAll('[data-step]')];
  const indicators=[...ideaWrap.querySelectorAll('.steps>div')];
  const previous=ideaWrap.querySelector('[data-prev]');
  const next=ideaWrap.querySelector('[data-next]');
  const draft=ideaWrap.querySelector('[data-draft]');
  const send=ideaWrap.querySelector('[data-send]');
  const field=name=>form.elements.namedItem(name);

  function setupOtherField(key){
    const select=form.querySelector(`[data-other-select="${key}"]`);
    const input=form.querySelector(`[data-other-input="${key}"]`);
    if(!select||!input)return;
    const sync=()=>{const active=select.value==='__other__';input.classList.toggle('hidden',!active);input.required=active;if(!active)input.setCustomValidity('');};
    select.addEventListener('change',sync);sync();
  }
  setupOtherField('department');setupOtherField('category');
  const selectedValue=key=>{const select=field(key);if(!select)return '';if(select.value==='__other__'){const other=field(key+'_other');return String(other?.value||'').trim();}return String(select.value||'').trim();};

  function validateCurrentStep(){
    const required=step===1?['title','department','category']:step===2?['description']:[];
    for(const name of required){
      const input=field(name);
      if(!input) continue;
      const value=(name==='department'||name==='category')?selectedValue(name):String(input.value||'').trim();
      if(!value){input.setCustomValidity('هذا الحقل مطلوب');input.reportValidity();input.focus();return false;}
      if(name==='title'&&value.length<5){input.setCustomValidity('اكتبي عنوانًا لا يقل عن 5 أحرف');input.reportValidity();input.focus();return false;}
      if(name==='description'&&value.length<30){input.setCustomValidity('اكتبي وصفًا لا يقل عن 30 حرفًا');input.reportValidity();input.focus();return false;}
      input.setCustomValidity('');
    }
    return true;
  }

  form.querySelectorAll('input,textarea,select').forEach(input=>input.addEventListener('input',()=>input.setCustomValidity('')));

  function render(){
    panels.forEach(panel=>panel.classList.toggle('hidden',Number(panel.dataset.step)!==step));
    indicators.forEach((indicator,index)=>indicator.classList.toggle('active',index<step));
    previous.classList.toggle('hidden',step===1);
    next.classList.toggle('hidden',step===3);
    draft.classList.toggle('hidden',step===3);
    send.classList.toggle('hidden',step!==3);
    if(step===3){
      ideaWrap.querySelector('[data-review-title]').textContent=field('title').value||'—';
      ideaWrap.querySelector('[data-review-description]').textContent=field('description').value||'—';
      ideaWrap.querySelector('[data-review-meta]').textContent=(selectedValue('department')||'أخرى')+' — '+(selectedValue('category')||'أخرى');
    }
  }

  next.addEventListener('click',()=>{if(validateCurrentStep()){step=Math.min(3,step+1);render();}});
  previous.addEventListener('click',()=>{step=Math.max(1,step-1);render();});
  draft.addEventListener('click',()=>{form.noValidate=true;});
  send.addEventListener('click',event=>{if(!validateCurrentStep())event.preventDefault();});
  render();
}

const searchOpen=document.querySelector('[data-global-search-open]');
const searchModal=document.querySelector('[data-global-search-modal]');
const searchInput=document.querySelector('[data-global-search-input]');
const searchResults=document.querySelector('[data-global-search-results]');
const searchDataNode=document.getElementById('global-search-data');
let searchIdeas=[];
try{searchIdeas=searchDataNode?JSON.parse(searchDataNode.textContent):[];}catch(error){searchIdeas=[];}

function escapeHtml(value){return String(value).replace(/[&<>'"]/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));}
function closeSearch(){if(!searchModal)return;searchModal.classList.add('hidden');searchModal.setAttribute('aria-hidden','true');document.body.classList.remove('modal-open');}
function openSearch(){if(!searchModal)return;searchModal.classList.remove('hidden');searchModal.setAttribute('aria-hidden','false');document.body.classList.add('modal-open');setTimeout(()=>searchInput?.focus(),40);}
function renderSearch(query){
  if(!searchResults)return;
  const term=query.trim().toLocaleLowerCase('ar');
  if(!term){searchResults.innerHTML='<div class="search-empty">ابدأ بكتابة كلمة للبحث.</div>';return;}
  const matches=searchIdeas.filter(idea=>[idea.title,idea.number,idea.description,idea.department,idea.category,idea.employee].join(' ').toLocaleLowerCase('ar').includes(term)).slice(0,12);
  if(!matches.length){searchResults.innerHTML='<div class="search-empty">لا توجد أفكار مطابقة لبحثك.</div>';return;}
  searchResults.innerHTML=matches.map(idea=>`<a class="global-search-result" href="${escapeHtml(idea.url)}"><div><strong>${escapeHtml(idea.title||'مسودة بدون عنوان')}</strong><span>${escapeHtml(idea.number)} · ${escapeHtml(idea.department||'غير محدد')} · ${escapeHtml(idea.category||'غير محدد')}</span></div><small>${escapeHtml(idea.employee||'')}</small></a>`).join('');
}
if(searchOpen) searchOpen.addEventListener('click',openSearch);
document.querySelectorAll('[data-global-search-close]').forEach(button=>button.addEventListener('click',closeSearch));
if(searchInput) searchInput.addEventListener('input',()=>renderSearch(searchInput.value));
document.addEventListener('keydown',event=>{if(event.key==='Escape')closeSearch();if((event.ctrlKey||event.metaKey)&&event.key.toLowerCase()==='k'){event.preventDefault();openSearch();}});
