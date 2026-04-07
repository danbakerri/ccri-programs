/**
 * Program Directory Page Script
 * Requires: program-config.js (loaded first)
 *
 * This file contains the directory-specific functionality (filtering, pagination, etc.)
 * All configuration is in program-config.js
 */

const PER_PAGE = 24;

let allCards = [];
let pathwaySel, typeSel, azSel, workforceCategorySel, locationSel;

const qInput = document.getElementById("q");
const cntEl = document.getElementById("cnt");
const pager = document.getElementById("pager");
const chipsEl = document.getElementById("chips");
const clearBtn = document.getElementById("clearBtn");

let currentPage = 1;
let filteredCards = [];
let state = {
  q: "",
  pathway: "",
  type: "",
  az: "",
  workforceCategory: "",
  location: "",
};

/* ══ INITIALIZATION ══ */
function initDirectory() {
  allCards = [...document.querySelectorAll(".program-card")];

  allCards.sort((a, b) => {
    const titleA = a.querySelector(".program-title").textContent.trim();
    const titleB = b.querySelector(".program-title").textContent.trim();
    return titleA.localeCompare(titleB);
  });

  filteredCards = [...allCards];

  const totalEl = document.getElementById("total");
  if (totalEl) totalEl.textContent = allCards.length;

  // FIX: build dropdowns first, then read URL params and apply state
  // Previously pullParams() ran before buildFilters(), so dropdown options
  // didn't exist yet when we tried to set their values from the URL.
  buildFilters();
  pullParams();
}

function pullParams() {
  const p = getParams();
  applyState(p, p.page, false);
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    if (document.querySelectorAll(".program-card").length > 0) {
      initDirectory();
    }
  });
} else {
  if (document.querySelectorAll(".program-card").length > 0) {
    initDirectory();
  }
}

/* ══ POPULATE SELECTS + A–Z ══ */
function buildFilters() {
  pathwaySel = document.getElementById("pathwayF");
  typeSel = document.getElementById("typeF");
  azSel = document.getElementById("azF");
  workforceCategorySel = document.getElementById("workforceCategoryF");
  locationSel = document.getElementById("locationF");

  /* Normalize card data attributes */
  document.querySelectorAll("[data-pathway]").forEach((card) => {
    card.dataset.pathway = normPathway(card.dataset.pathway);
  });
  document.querySelectorAll(".program-type-tag").forEach((el) => {
    el.textContent =
      PROGRAM_TYPE_LABELS[el.textContent.trim()] || el.textContent.trim();
  });
  document.querySelectorAll(".program-pathway-tag").forEach((el) => {
    el.textContent = normPathway(el.textContent.trim());
  });

  /* Set visible total excluding hidden programs */
  const visibleTotal = allCards.filter(
    (c) => !HIDDEN_PROGRAMS.includes(c.dataset.url || ""),
  ).length;
  document.getElementById("total").textContent = visibleTotal;

  /* Count by each dimension */
  const pathwayCounts = {},
    typeCounts = {},
    azCounts = {},
    workforceCategoryCounts = {},
    locationCounts = {};
  allCards.forEach((c) => {
    if (HIDDEN_PROGRAMS.includes(c.dataset.url || "")) return;
    const p = normPathway(c.dataset.pathway);
    const t = c.dataset.type;
    const ch = c.dataset.letter || "#";
    const wc = c.dataset.workforceCategory;
    const locs = c.dataset.locations
      ? c.dataset.locations
          .split(",")
          .map((l) => l.trim())
          .filter(Boolean)
      : [];

    if (p) pathwayCounts[p] = (pathwayCounts[p] || 0) + 1;
    if (t) typeCounts[t] = (typeCounts[t] || 0) + 1;
    azCounts[ch] = (azCounts[ch] || 0) + 1;
    if (wc)
      workforceCategoryCounts[wc] = (workforceCategoryCounts[wc] || 0) + 1;
    locs.forEach((loc) => {
      locationCounts[loc] = (locationCounts[loc] || 0) + 1;
    });
  });

  /* Pathway filter */
  PATHWAY_ORDER.forEach((pathway) => {
    if (pathwayCounts[pathway]) {
      const o = document.createElement("option");
      o.value = o.textContent = pathway;
      pathwaySel.appendChild(o);
    }
  });
  Object.keys(pathwayCounts)
    .sort()
    .forEach((pathway) => {
      if (!PATHWAY_ORDER.includes(pathway)) {
        const o = document.createElement("option");
        o.value = o.textContent = pathway;
        pathwaySel.appendChild(o);
      }
    });

  /* Type filter */
  Object.keys(PROGRAM_TYPE_LABELS).forEach((type) => {
    if (typeCounts[type]) {
      const o = document.createElement("option");
      o.value = type;
      o.textContent = PROGRAM_TYPE_LABELS[type];
      typeSel.appendChild(o);
    }
  });

  /* Workforce category filter */
  const workforceCategories = [
    "Business and Technology",
    "Education",
    "Healthcare",
    "Manufacturing and Trades",
    "Renewable Energy",
    "Fit2Serve",
    "GED & Adult Education",
    "Transportation Education",
  ];
  workforceCategories.forEach((category) => {
    if (workforceCategoryCounts[category]) {
      const o = document.createElement("option");
      o.value = o.textContent = category;
      workforceCategorySel.appendChild(o);
    }
  });

  /* Location filter */
  const locationOrder = ["Flanagan", "Knight", "Liston", "Newport", "Online"];
  const locationLabels = {
    Flanagan: "Flanagan (Lincoln)",
    Knight: "Knight (Warwick)",
    Liston: "Liston (Providence)",
    Newport: "Newport County",
    Online: "Online",
  };
  locationOrder.forEach((loc) => {
    if (locationCounts[loc]) {
      const o = document.createElement("option");
      o.value = loc;
      o.textContent = locationLabels[loc] || loc;
      locationSel.appendChild(o);
    }
  });

  /* A–Z filter */
  "ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("").forEach((ch) => {
    if (!azCounts[ch]) return;
    const o = document.createElement("option");
    o.value = o.textContent = ch;
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
    q: p.get("q") || "",
    pathway: p.get("pathway") || "",
    type: p.get("type") || "",
    az: p.get("az") || "",
    workforceCategory: p.get("workforceCategory") || "",
    location: p.get("location") || "",
    page: parseInt(p.get("page") || "1", 10),
  };
}

function pushParams() {
  const p = new URLSearchParams();
  if (state.q) p.set("q", state.q);
  if (state.pathway) p.set("pathway", state.pathway);
  if (state.type) p.set("type", state.type);
  if (state.az) p.set("az", state.az);
  if (state.workforceCategory)
    p.set("workforceCategory", state.workforceCategory);
  if (state.location) p.set("location", state.location);
  if (currentPage > 1) p.set("page", currentPage);
  const qs = p.toString();
  history.pushState(
    { ...state, page: currentPage },
    "",
    qs ? "?" + qs : window.location.pathname,
  );
}

function applyState(s, page, shouldScroll) {
  const pathwaySel = document.getElementById("pathwayF");
  const typeSel = document.getElementById("typeF");
  const azSel = document.getElementById("azF");
  const workforceCategorySel = document.getElementById("workforceCategoryF");
  const locationSel = document.getElementById("locationF");

  state = {
    q: s.q,
    pathway: s.pathway,
    type: s.type,
    az: s.az,
    workforceCategory: s.workforceCategory,
    location: s.location,
  };
  qInput.value = state.q;
  pathwaySel.value = state.pathway;
  typeSel.value = state.type;
  azSel.value = state.az;
  workforceCategorySel.value = state.workforceCategory;
  locationSel.value = state.location;
  _filter();
  renderPage(page, false, shouldScroll);
}

/* ══ FILTER ══ */
function _filter() {
  const q = state.q.toLowerCase();
  filteredCards = allCards.filter((c) => {
    if (HIDDEN_PROGRAMS.includes(c.dataset.url || "")) return false;
    const p = normPathway(c.dataset.pathway);
    if (q && !c.dataset.search.includes(q)) return false;
    if (state.pathway && p !== state.pathway) return false;
    if (state.type && c.dataset.type !== state.type) return false;
    if (state.az && c.dataset.letter !== state.az) return false;
    if (
      state.workforceCategory &&
      c.dataset.workforceCategory !== state.workforceCategory
    )
      return false;
    if (state.location) {
      const locs = c.dataset.locations
        ? c.dataset.locations.split(",").map((l) => l.trim())
        : [];
      if (!locs.includes(state.location)) return false;
    }
    return true;
  });

  filteredCards.sort((a, b) => {
    const titleA = a.querySelector(".program-title").textContent.trim();
    const titleB = b.querySelector(".program-title").textContent.trim();
    return titleA.localeCompare(titleB);
  });

  cntEl.textContent = filteredCards.length;
  renderChips();
  clearBtn.classList.toggle(
    "visible",
    !!(
      state.q ||
      state.pathway ||
      state.type ||
      state.az ||
      state.workforceCategory ||
      state.location
    ),
  );
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
  state.location = locationSel.value;
  _filter();
  renderPage(1, true, false);
}

/* ══ CHIPS ══ */
function renderChips() {
  const locationLabels = {
    Flanagan: "Flanagan (Lincoln)",
    Knight: "Knight (Warwick)",
    Liston: "Liston (Providence)",
    Newport: "Newport County",
    Online: "Online",
  };

  chipsEl.innerHTML = "";
  if (state.q)
    chipsEl.appendChild(
      makeChip("Search: " + state.q, () => {
        state.q = "";
        qInput.value = "";
        goSearch();
      }),
    );
  if (state.pathway)
    chipsEl.appendChild(
      makeChip(state.pathway, () => {
        pathwaySel.value = "";
        go();
      }),
    );
  if (state.type)
    chipsEl.appendChild(
      makeChip(PROGRAM_TYPE_LABELS[state.type] || state.type, () => {
        typeSel.value = "";
        go();
      }),
    );
  if (state.workforceCategory)
    chipsEl.appendChild(
      makeChip("Category: " + state.workforceCategory, () => {
        workforceCategorySel.value = "";
        go();
      }),
    );
  if (state.location)
    chipsEl.appendChild(
      makeChip(locationLabels[state.location] || state.location, () => {
        locationSel.value = "";
        go();
      }),
    );
  if (state.az)
    chipsEl.appendChild(
      makeChip("A–Z: " + state.az, () => {
        azSel.value = "";
        go();
      }),
    );
}

function makeChip(label, onRemove) {
  const c = document.createElement("span");
  c.className = "chip";
  c.innerHTML = label + ' <span class="x">✕</span>';
  c.onclick = onRemove;
  return c;
}

function clearAll() {
  state = {
    q: "",
    pathway: "",
    type: "",
    az: "",
    workforceCategory: "",
    location: "",
  };
  qInput.value = "";
  pathwaySel.value = "";
  typeSel.value = "";
  azSel.value = "";
  workforceCategorySel.value = "";
  locationSel.value = "";
  _filter();
  renderPage(1, true, false);
}

/* ══ PAGINATION ══ */
function renderPage(page, pushUrl, shouldScroll) {
  const pages = Math.max(1, Math.ceil(filteredCards.length / PER_PAGE));
  currentPage = Math.min(Math.max(page, 1), pages);

  const grid = document.getElementById("grid");
  allCards.forEach((c) => (c.style.display = "none"));
  grid.querySelectorAll(".letter-divider").forEach((d) => d.remove());

  const pageSlice = filteredCards.slice(
    (currentPage - 1) * PER_PAGE,
    currentPage * PER_PAGE,
  );

  let lastLetter = null;
  pageSlice.forEach((card) => {
    const letter = card.dataset.letter || "#";
    if (letter !== lastLetter) {
      const divider = document.createElement("div");
      divider.className = "letter-divider";
      divider.innerHTML =
        '<span class="letter-badge">' +
        letter +
        "</span>" +
        '<span class="letter-rule"></span>';
      grid.appendChild(divider);
      lastLetter = letter;
    }
    grid.appendChild(card);
    card.style.display = "";
  });

  document.getElementById("empty").style.display =
    filteredCards.length === 0 ? "block" : "none";
  renderPager();
  if (pushUrl !== false) pushParams();
  if (shouldScroll !== false) {
    const mainEl = document.getElementById("display-nav");
    if (mainEl) mainEl.scrollIntoView({ behavior: "smooth", block: "start" });
  }
}

function renderPager() {
  const total = filteredCards.length;
  const pages = Math.ceil(total / PER_PAGE);
  pager.innerHTML = "";
  if (pages <= 1) return;

  const info = document.createElement("span");
  info.className = "pager-info";
  info.textContent =
    (currentPage - 1) * PER_PAGE +
    1 +
    "–" +
    Math.min(currentPage * PER_PAGE, total) +
    " of " +
    total;

  const prev = document.createElement("button");
  prev.textContent = "←";
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

  const next = document.createElement("button");
  next.textContent = "→";
  next.disabled = currentPage === pages;
  next.onclick = () => renderPage(currentPage + 1, true, true);
  pager.appendChild(next);
}

function makePageBtn(n) {
  const btn = document.createElement("button");
  btn.textContent = n;
  if (n === currentPage) btn.classList.add("active");
  btn.onclick = () => renderPage(n, true, true);
  return btn;
}

function makeDots() {
  const s = document.createElement("span");
  s.className = "pager-info";
  s.textContent = "…";
  return s;
}

/* ══ BACK/FORWARD ══ */
window.addEventListener("popstate", () => {
  const p = getParams();
  applyState(p, p.page, true);
});
