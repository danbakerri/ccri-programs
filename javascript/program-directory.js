/**
 * Program Directory Page Script
 * Requires: program-config.js (loaded first)
 * 
 * This file contains the directory-specific functionality (filtering, pagination, etc.)
 * All configuration is in program-config.js
 */

const PER_PAGE = 24;

let allCards = [];
let pathwaySel, typeSel, azSel, workforceCategorySel; // Global references to select elements

const qInput = document.getElementById('q');
const cntEl = document.getElementById('cnt');
const pager = document.getElementById('pager');
const chipsEl = document.getElementById('chips');
const clearBtn = document.getElementById('clearBtn');

let currentPage = 1;
let filteredCards = [];
let state = { q: '', pathway: '', type: '', az: '', workforceCategory: '' };

/* ══ INITIALIZATION ══ */
function initDirectory() {
  allCards = [...document.querySelectorAll('.program-card')];
  
  // Sort all cards alphabetically by title initially
  allCards.sort((a, b) => {
    const titleA = a.querySelector('.program-title').textContent.trim();
    const titleB = b.querySelector('.program-title').textContent.trim();
    return titleA.localeCompare(titleB);
  });
  
  filteredCards = [...allCards];
  
  // Set total count
  const totalEl = document.getElementById('total');
  if (totalEl) totalEl.textContent = allCards.length;
  
  // Initialize filters from URL
  pullParams();
  
  // Build filter dropdowns
  buildFilters();
  
  // Render initial page
  go();
}

// Pull params is called after cards exist
function pullParams() {
  const p = getParams();
  applyState(p, p.page, false);
}

// Auto-initialize if cards already exist in DOM
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelectorAll('.program-card').length > 0) {
      initDirectory();
    }
  });
} else {
  if (document.querySelectorAll('.program-card').length > 0) {
    initDirectory();
  }
}

/* ══ POPULATE SELECTS + A–Z ══ */
function buildFilters() {
  console.log('buildFilters called, allCards.length:', allCards.length);
  
  // Assign to global variables
  pathwaySel = document.getElementById('pathwayF');
  typeSel = document.getElementById('typeF');
  azSel = document.getElementById('azF');
  workforceCategorySel = document.getElementById('workforceCategoryF');
  
  console.log('Found select elements:', {pathwaySel, typeSel, azSel, workforceCategorySel});
  
  /* Rewrite card pathway and type tags to use the mapped display names */
  document.querySelectorAll('[data-pathway]').forEach(card => {
    const rawPathway = card.dataset.pathway;
    card.dataset.pathway = normPathway(rawPathway);
  });
  
  document.querySelectorAll('.program-type-tag').forEach(el => {
    const rawType = el.textContent.trim();
    el.textContent = PROGRAM_TYPE_LABELS[rawType] || rawType;
  });
  
  document.querySelectorAll('.program-pathway-tag').forEach(el => {
    const rawPathway = el.textContent.trim();
    el.textContent = normPathway(rawPathway);
  });
  
  /* Set the "of X" total, excluding hidden programs */
  const visibleTotal = allCards.filter(c => {
    const url = c.dataset.url || '';
    return !HIDDEN_PROGRAMS.includes(url);
  }).length;
  document.getElementById('total').textContent = visibleTotal;
  
  /* Count programs by pathway, type, workforce category */
  const pathwayCounts = {}, typeCounts = {}, azCounts = {}, workforceCategoryCounts = {};
  allCards.forEach(c => {
    const url = c.dataset.url || '';
    if (HIDDEN_PROGRAMS.includes(url)) return;
    
    const p = normPathway(c.dataset.pathway);
    const t = c.dataset.type;
    const ch = c.dataset.letter || '#';
    const wc = c.dataset.workforceCategory;
    
    if (p) pathwayCounts[p] = (pathwayCounts[p] || 0) + 1;
    if (t) typeCounts[t] = (typeCounts[t] || 0) + 1;
    azCounts[ch] = (azCounts[ch] || 0) + 1;
    if (wc) workforceCategoryCounts[wc] = (workforceCategoryCounts[wc] || 0) + 1;
  });
  
  console.log('Pathway counts:', pathwayCounts);
  console.log('Type counts:', typeCounts);
  console.log('Workforce Category counts:', workforceCategoryCounts);
  console.log('AZ counts:', azCounts);
  
  /* Populate pathway filter in specified order */
  PATHWAY_ORDER.forEach(pathway => {
    if (pathwayCounts[pathway]) {
      const o = document.createElement('option');
      o.value = pathway;
      o.textContent = pathway;
      pathwaySel.appendChild(o);
    }
  });
  
  // Add any pathways not in PATHWAY_ORDER
  Object.keys(pathwayCounts).sort().forEach(pathway => {
    if (!PATHWAY_ORDER.includes(pathway)) {
      const o = document.createElement('option');
      o.value = pathway;
      o.textContent = pathway;
      pathwaySel.appendChild(o);
    }
  });
  
  /* Populate type filter */
  Object.keys(PROGRAM_TYPE_LABELS).forEach(type => {
    if (typeCounts[type]) {
      const o = document.createElement('option');
      o.value = type;
      o.textContent = PROGRAM_TYPE_LABELS[type];
      typeSel.appendChild(o);
    }
  });
  
  /* Populate workforce category filter (in order) */
  const workforceCategories = [
    'Business and Technology',
    'Education',
    'Healthcare',
    'Manufacturing and Trades',
    'Renewable Energy',
    'Fit2Serve',
    'GED & Adult Education',
    'Transportation Education'
  ];
  
  workforceCategories.forEach(category => {
    if (workforceCategoryCounts[category] && workforceCategoryCounts[category] > 0) {
      const o = document.createElement('option');
      o.value = category;
      o.textContent = category;
      workforceCategorySel.appendChild(o);
    }
  });
  
  /* A–Z select */
  'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('').forEach(ch => {
    if (!azCounts[ch]) return;
    const o = document.createElement('option');
    o.value = ch; o.textContent = ch;
    azSel.appendChild(o);
  });
}

/* ══ FILTER INTERACTIONS ══ */
function pickAZ(ch) {
  state.az = ch;
  go();
}

/* ══ URL ↔ STATE ══ */
function getParams() {
  const p = new URLSearchParams(window.location.search);
  return {
    q: p.get('q') || '',
    pathway: p.get('pathway') || '',
    type: p.get('type') || '',
    az: p.get('az') || '',
    workforceCategory: p.get('workforceCategory') || '',
    page: parseInt(p.get('page') || '1', 10)
  };
}

function pushParams() {
  const p = new URLSearchParams();
  if (state.q) p.set('q', state.q);
  if (state.pathway) p.set('pathway', state.pathway);
  if (state.type) p.set('type', state.type);
  if (state.az) p.set('az', state.az);
  if (state.workforceCategory) p.set('workforceCategory', state.workforceCategory);
  if (currentPage > 1) p.set('page', currentPage);
  const qs = p.toString();
  history.pushState({...state, page: currentPage}, '', 
    qs ? '?' + qs : window.location.pathname);
}

function applyState(s, page, shouldScroll) {
  const pathwaySel = document.getElementById('pathwayF');
  const typeSel = document.getElementById('typeF');
  const azSel = document.getElementById('azF');
  const workforceCategorySel = document.getElementById('workforceCategoryF');
  
  state = { q: s.q, pathway: s.pathway, type: s.type, az: s.az, workforceCategory: s.workforceCategory };
  qInput.value = state.q;
  pathwaySel.value = state.pathway;
  typeSel.value = state.type;
  azSel.value = state.az;
  workforceCategorySel.value = state.workforceCategory;
  _filter();
  renderPage(page, false, shouldScroll);
}

/* ══ FILTER ══ */
function _filter() {
  const q = state.q.toLowerCase();
  filteredCards = allCards.filter(c => {
    const url = c.dataset.url || '';
    if (HIDDEN_PROGRAMS.includes(url)) return false;
    
    const p = normPathway(c.dataset.pathway);
    if (q && !c.dataset.search.includes(q)) return false;
    if (state.pathway && p !== state.pathway) return false;
    if (state.type && c.dataset.type !== state.type) return false;
    if (state.az && c.dataset.letter !== state.az) return false;
    if (state.workforceCategory && c.dataset.workforceCategory !== state.workforceCategory) return false;
    return true;
  });
  
  // Sort alphabetically by title
  filteredCards.sort((a, b) => {
    const titleA = a.querySelector('.program-title').textContent.trim();
    const titleB = b.querySelector('.program-title').textContent.trim();
    return titleA.localeCompare(titleB);
  });
  
  cntEl.textContent = filteredCards.length;
  renderChips();
  clearBtn.classList.toggle('visible',
    !!(state.q || state.pathway || state.type || state.az || state.workforceCategory));
}

function goSearch() {
  state.q = qInput.value;
  _filter();
  renderPage(1, true, false);
}

function go() {
  state.pathway = pathwaySel.value;
  state.type = typeSel.value;
  state.az = azSel.value;
  state.workforceCategory = workforceCategorySel.value;
  _filter();
  renderPage(1, true, false);
}

/* ══ CHIPS ══ */
function renderChips() {
  chipsEl.innerHTML = '';
  if (state.q) chipsEl.appendChild(makeChip('Search: ' + state.q, () => { 
    state.q = ''; qInput.value = ''; goSearch(); 
  }));
  if (state.pathway) chipsEl.appendChild(makeChip(state.pathway, () => { 
    pathwaySel.value = ''; go(); 
  }));
  if (state.type) {
    const typeLabel = PROGRAM_TYPE_LABELS[state.type] || state.type;
    chipsEl.appendChild(makeChip(typeLabel, () => { 
      typeSel.value = ''; go(); 
    }));
  }
  if (state.workforceCategory) chipsEl.appendChild(makeChip('Category: ' + state.workforceCategory, () => { 
    workforceCategorySel.value = ''; go(); 
  }));
  if (state.az) chipsEl.appendChild(makeChip('A–Z: ' + state.az, () => { 
    azSel.value = ''; go(); 
  }));
}

function makeChip(label, onRemove) {
  const c = document.createElement('span');
  c.className = 'chip';
  c.innerHTML = label + ' <span class="x">✕</span>';
  c.onclick = onRemove;
  return c;
}

function clearAll() {
  state = { q: '', pathway: '', type: '', az: '', workforceCategory: '' };
  qInput.value = '';
  pathwaySel.value = '';
  typeSel.value = '';
  azSel.value = '';
  workforceCategorySel.value = '';
  _filter();
  renderPage(1, true, false);
}

/* ══ PAGINATION ══ */
function renderPage(page, pushUrl, shouldScroll) {
  const pages = Math.max(1, Math.ceil(filteredCards.length / PER_PAGE));
  currentPage = Math.min(Math.max(page, 1), pages);

  const grid = document.getElementById('grid');
  
  // Hide all cards first
  allCards.forEach(c => c.style.display = 'none');
  
  // Remove old letter dividers
  grid.querySelectorAll('.letter-divider').forEach(d => d.remove());

  const pageSlice = filteredCards.slice(
    (currentPage - 1) * PER_PAGE,
    currentPage * PER_PAGE
  );

  // Build a fragment to reorder cards
  let lastLetter = null;
  pageSlice.forEach((card, index) => {
    const letter = card.dataset.letter || '#';
    
    // Insert letter divider if letter changed
    if (letter !== lastLetter) {
      const divider = document.createElement('div');
      divider.className = 'letter-divider';
      divider.innerHTML = 
        '<span class="letter-badge">' + letter + '</span>' +
        '<span class="letter-rule"></span>';
      grid.appendChild(divider);
      lastLetter = letter;
    }
    
    // Move card to end of grid and show it
    grid.appendChild(card);
    card.style.display = '';
  });

  document.getElementById('empty').style.display = filteredCards.length === 0 ? 'block' : 'none';
  renderPager();
  if (pushUrl !== false) pushParams();
  if (shouldScroll !== false) {
    const mainEl = document.getElementById('display-nav');
    if (mainEl) {
      mainEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }
}

function renderPager() {
  const total = filteredCards.length;
  const pages = Math.ceil(total / PER_PAGE);
  pager.innerHTML = '';
  if (pages <= 1) return;
  
  const info = document.createElement('span');
  info.className = 'pager-info';
  info.textContent = 
    ((currentPage - 1) * PER_PAGE + 1) + '–' +
    Math.min(currentPage * PER_PAGE, total) + ' of ' + total;
  
  const prev = document.createElement('button');
  prev.textContent = '←'; 
  prev.disabled = currentPage === 1;
  prev.onclick = () => renderPage(currentPage - 1, true, true);
  pager.appendChild(prev);
  pager.appendChild(info);
  
  const WIN = 5;
  let wS = Math.max(1, currentPage - Math.floor(WIN / 2));
  let wE = Math.min(pages, wS + WIN - 1);
  if (wE - wS + 1 < WIN) wS = Math.max(1, wE - WIN + 1);
  
  if (wS > 1) { 
    pager.appendChild(makePageBtn(1)); 
    if (wS > 2) pager.appendChild(makeDots()); 
  }
  for (let i = wS; i <= wE; i++) pager.appendChild(makePageBtn(i));
  if (wE < pages) { 
    if (wE < pages - 1) pager.appendChild(makeDots());
    pager.appendChild(makePageBtn(pages)); 
  }
  
  const next = document.createElement('button');
  next.textContent = '→'; 
  next.disabled = currentPage === pages;
  next.onclick = () => renderPage(currentPage + 1, true, true);
  pager.appendChild(next);
}

function makePageBtn(n) {
  const btn = document.createElement('button');
  btn.textContent = n;
  if (n === currentPage) btn.classList.add('active');
  btn.onclick = () => renderPage(n, true, true);
  return btn;
}

function makeDots() {
  const s = document.createElement('span'); 
  s.className = 'pager-info'; 
  s.textContent = '…';
  return s;
}

/* ══ BACK/FORWARD ══ */
window.addEventListener('popstate', () => {
  const p = getParams(); 
  applyState(p, p.page, true);
});
