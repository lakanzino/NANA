const API = 'https://qpedia.ir/wp-json/wp/v2';
const CACHE_KEY = 'qpedia_cache_v1';
const BOOKMARKS_KEY = 'qpedia_bookmarks';

let state = {
  screen: 'home',
  articles: [],
  scientists: [],
  categories: [],
  bookmarks: JSON.parse(localStorage.getItem(BOOKMARKS_KEY) || '[]'),
  currentArticle: null,
  currentScientist: null,
  loading: false,
  searchQuery: '',
  deferredPrompt: null
};

// ========== Utils ==========
function toast(msg, duration = 2200) {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), duration);
}

function stripHtml(html) {
  const tmp = document.createElement('div');
  tmp.innerHTML = html || '';
  return tmp.textContent || tmp.innerText || '';
}

function formatDate(dateStr) {
  try {
    const d = new Date(dateStr);
    return new Intl.DateTimeFormat('fa-IR', { year: 'numeric', month: 'long', day: 'numeric' }).format(d);
  } catch {
    return dateStr?.slice(0, 10) || '';
  }
}

function getExcerpt(html, len = 110) {
  const text = stripHtml(html).trim();
  return text.length > len ? text.slice(0, len) + '…' : text;
}

// ========== Data ==========
async function fetchJSON(url) {
  const res = await fetch(url);
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}

async function loadData(force = false) {
  const cached = localStorage.getItem(CACHE_KEY);
  if (cached && !force) {
    try {
      const data = JSON.parse(cached);
      if (Date.now() - data.ts < 1000 * 60 * 30) { // 30 min cache
        state.articles = data.articles || [];
        state.scientists = data.scientists || [];
        state.categories = data.categories || [];
        return;
      }
    } catch {}
  }

  state.loading = true;
  render();

  try {
    const [articles, scientists, categories] = await Promise.all([
      fetchJSON(`${API}/quantum_article?per_page=100&_embed&orderby=date&order=desc`),
      fetchJSON(`${API}/quantum_scientist?per_page=100&orderby=title&order=asc`),
      fetchJSON(`${API}/quantum_category?per_page=50`)
    ]);

    state.articles = articles.map(a => ({
      id: a.id,
      slug: a.slug,
      title: stripHtml(a.title?.rendered || ''),
      content: a.content?.rendered || '',
      excerpt: getExcerpt(a.excerpt?.rendered || a.content?.rendered || '', 120),
      date: a.date,
      link: a.link,
      categories: a.quantum_category || []
    }));

    state.scientists = scientists.map(s => ({
      id: s.id,
      slug: s.slug,
      title: stripHtml(s.title?.rendered || ''),
      content: s.content?.rendered || '',
      excerpt: getExcerpt(s.content?.rendered || '', 100),
      link: s.link
    }));

    // Only top-level categories with count > 0
    state.categories = categories
      .filter(c => c.count > 0)
      .sort((a, b) => b.count - a.count)
      .map(c => ({
        id: c.id,
        name: c.name,
        slug: c.slug,
        count: c.count,
        parent: c.parent
      }));

    localStorage.setItem(CACHE_KEY, JSON.stringify({
      ts: Date.now(),
      articles: state.articles,
      scientists: state.scientists,
      categories: state.categories
    }));

    toast('داده‌ها به‌روز شد');
  } catch (err) {
    console.error(err);
    if (!state.articles.length) {
      toast('خطا در دریافت داده. اتصال اینترنت را بررسی کنید');
    } else {
      toast('از حافظهٔ محلی استفاده شد');
    }
  } finally {
    state.loading = false;
    render();
  }
}

function refreshData() {
  loadData(true);
}

// ========== Bookmarks ==========
function isBookmarked(id) {
  return state.bookmarks.includes(id);
}

function toggleBookmark(id) {
  if (isBookmarked(id)) {
    state.bookmarks = state.bookmarks.filter(b => b !== id);
    toast('از نشان‌ها حذف شد');
  } else {
    state.bookmarks.unshift(id);
    toast('به نشان‌ها اضافه شد');
  }
  localStorage.setItem(BOOKMARKS_KEY, JSON.stringify(state.bookmarks));
  render();
}

// ========== Navigation ==========
function navigate(screen, data = null) {
  state.screen = screen;
  if (screen === 'article') state.currentArticle = data;
  if (screen === 'scientist') state.currentScientist = data;
  if (screen === 'search') state.searchQuery = '';
  // Update nav
  document.querySelectorAll('.nav-item').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.screen === screen);
  });
  // Hide nav on reader screens
  document.querySelector('.bottom-nav').style.display =
    (screen === 'article' || screen === 'scientist') ? 'none' : 'flex';
  render();
  window.scrollTo(0, 0);
  document.getElementById('main-content').scrollTop = 0;
}

// ========== Renderers ==========
function renderHome() {
  const latest = state.articles.slice(0, 12);
  let html = `
    <div class="screen">
      <h1 class="screen-title">کوانتوم پدیا</h1>
      <p class="screen-subtitle">دانشنامه فیزیک کوانتوم به زبان ساده</p>
  `;

  // Install banner
  if (state.deferredPrompt) {
    html += `
      <div class="install-banner" id="install-banner">
        <p>این اپ را روی گوشی نصب کنید تا مثل برنامهٔ واقعی کار کند</p>
        <button class="install-btn" onclick="installApp()">نصب</button>
      </div>
    `;
  }

  html += `<div class="section-label">آخرین مقالات</div><div class="card-list">`;

  if (state.loading && !latest.length) {
    html += `<div class="loading-state"><div class="spinner"></div>در حال بارگذاری…</div>`;
  } else if (!latest.length) {
    html += `<div class="empty-state"><div class="empty-icon">📭</div>مقاله‌ای یافت نشد</div>`;
  } else {
    latest.forEach(a => {
      html += articleCard(a);
    });
  }

  html += `</div></div>`;
  return html;
}

function articleCard(a) {
  return `
    <article class="card" onclick="navigate('article', ${a.id})">
      <div class="card-meta">
        <span>${formatDate(a.date)}</span>
      </div>
      <h3 class="card-title">${a.title}</h3>
      <p class="card-excerpt">${a.excerpt}</p>
    </article>
  `;
}

function renderTopics() {
  let html = `
    <div class="screen">
      <h1 class="screen-title">موضوعات</h1>
      <p class="screen-subtitle">${state.categories.length} دسته</p>
      <div class="topic-grid">
  `;

  const icons = {
    fundamentals: '🔬', technology: '💻', phenomena: '✨',
    history: '📜', interpretations: '🧠', pseudoscience: '⚠️',
    particles: '⚛️', 'quantum-computing': '🖥️', 'quantum-biology': '🧬',
    'everyday-tech': '📱', experiments: '🧪', 'core-concepts': '📘',
    mathematics: '∑', 'history-experiments': '🔭'
  };

  state.categories.forEach(c => {
    const icon = icons[c.slug] || '📂';
    html += `
      <div class="topic-card" onclick="openCategory(${c.id}, '${c.name.replace(/'/g, "\\'")}')">
        <span class="topic-icon">${icon}</span>
        <div class="topic-name">${c.name}</div>
        <div class="topic-count">${c.count} مقاله</div>
      </div>
    `;
  });

  html += `</div></div>`;
  return html;
}

function openCategory(catId, name) {
  const filtered = state.articles.filter(a => a.categories.includes(catId));
  state.screen = 'category';
  state.categoryArticles = filtered;
  state.categoryName = name;
  document.querySelector('.bottom-nav').style.display = 'flex';
  render();
}

function renderCategory() {
  const list = state.categoryArticles || [];
  let html = `
    <div class="screen">
      <button class="reader-back" onclick="navigate('topics')">← بازگشت به موضوعات</button>
      <h1 class="screen-title">${state.categoryName || 'دسته'}</h1>
      <p class="screen-subtitle">${list.length} مقاله</p>
      <div class="card-list">
  `;
  if (!list.length) {
    html += `<div class="empty-state"><div class="empty-icon">📭</div>مقاله‌ای در این دسته نیست</div>`;
  } else {
    list.forEach(a => { html += articleCard(a); });
  }
  html += `</div></div>`;
  return html;
}

function renderScientists() {
  let html = `
    <div class="screen">
      <h1 class="screen-title">دانشمندان</h1>
      <p class="screen-subtitle">${state.scientists.length} چهره</p>
      <div class="card-list">
  `;

  if (state.loading && !state.scientists.length) {
    html += `<div class="loading-state"><div class="spinner"></div>در حال بارگذاری…</div>`;
  } else {
    state.scientists.forEach(s => {
      const initial = s.title.charAt(0) || '?';
      html += `
        <article class="card" onclick="navigate('scientist', ${s.id})">
          <div class="scientist-card">
            <div class="scientist-avatar">${initial}</div>
            <div class="scientist-info">
              <h3 class="card-title" style="margin-bottom:4px">${s.title}</h3>
              <p class="card-excerpt">${s.excerpt}</p>
            </div>
          </div>
        </article>
      `;
    });
  }

  html += `</div></div>`;
  return html;
}

function renderSearch() {
  const q = state.searchQuery.trim().toLowerCase();
  let results = [];
  if (q.length >= 2) {
    results = state.articles.filter(a =>
      a.title.toLowerCase().includes(q) || a.excerpt.toLowerCase().includes(q)
    );
  }

  let html = `
    <div class="screen">
      <h1 class="screen-title">جستجو</h1>
      <div class="search-box">
        <input type="search" id="search-input" placeholder="جستجوی مقاله…" 
               value="${state.searchQuery.replace(/"/g, '&quot;')}"
               oninput="onSearch(this.value)" autofocus />
        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </div>
  `;

  if (q.length < 2) {
    html += `<div class="empty-state"><div class="empty-icon">🔍</div>حداقل ۲ حرف بنویسید</div>`;
  } else if (!results.length) {
    html += `<div class="empty-state"><div class="empty-icon">😕</div>نتیجه‌ای یافت نشد</div>`;
  } else {
    html += `<p class="screen-subtitle">${results.length} نتیجه</p><div class="card-list">`;
    results.forEach(a => { html += articleCard(a); });
    html += `</div>`;
  }

  html += `</div>`;
  return html;
}

function onSearch(val) {
  state.searchQuery = val;
  render();
  // Keep focus
  setTimeout(() => {
    const input = document.getElementById('search-input');
    if (input) {
      input.focus();
      input.setSelectionRange(input.value.length, input.value.length);
    }
  }, 0);
}

function renderBookmarks() {
  const bookmarked = state.articles.filter(a => state.bookmarks.includes(a.id));
  let html = `
    <div class="screen">
      <h1 class="screen-title">نشان‌ها</h1>
      <p class="screen-subtitle">${bookmarked.length} مقاله ذخیره‌شده</p>
      <div class="card-list">
  `;

  if (!bookmarked.length) {
    html += `
      <div class="empty-state">
        <div class="empty-icon">🔖</div>
        هنوز مقاله‌ای نشان نکرده‌اید<br>
        <span style="font-size:13px;opacity:0.7">روی آیکون نشان در صفحهٔ مقاله بزنید</span>
      </div>`;
  } else {
    bookmarked.forEach(a => { html += articleCard(a); });
  }

  html += `</div></div>`;
  return html;
}

function renderArticle() {
  const a = state.articles.find(x => x.id === state.currentArticle);
  if (!a) {
    return `<div class="screen"><div class="empty-state">مقاله یافت نشد</div></div>`;
  }

  const bookmarked = isBookmarked(a.id);

  return `
    <div class="reader">
      <div class="reader-header">
        <button class="reader-back" onclick="history.back ? history.back() : navigate('home')">← بازگشت</button>
        <h1 class="reader-title">${a.title}</h1>
        <div class="reader-meta">
          <span>${formatDate(a.date)}</span>
          <div class="reader-actions">
            <button class="action-btn ${bookmarked ? 'active' : ''}" onclick="toggleBookmark(${a.id})" title="نشان کردن">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="${bookmarked ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2">
                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
              </svg>
            </button>
            <button class="action-btn" onclick="shareArticle(${a.id})" title="اشتراک">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
      <div class="reader-body">${a.content}</div>
    </div>
  `;
}

function renderScientist() {
  const s = state.scientists.find(x => x.id === state.currentScientist);
  if (!s) {
    return `<div class="screen"><div class="empty-state">دانشمند یافت نشد</div></div>`;
  }

  return `
    <div class="reader">
      <div class="reader-header">
        <button class="reader-back" onclick="navigate('scientists')">← بازگشت به دانشمندان</button>
        <h1 class="reader-title">${s.title}</h1>
      </div>
      <div class="reader-body">${s.content}</div>
    </div>
  `;
}

function shareArticle(id) {
  const a = state.articles.find(x => x.id === id);
  if (!a) return;
  if (navigator.share) {
    navigator.share({ title: a.title, url: a.link, text: a.excerpt }).catch(() => {});
  } else {
    navigator.clipboard?.writeText(a.link).then(() => toast('لینک کپی شد'));
  }
}

// ========== Main Render ==========
function render() {
  const main = document.getElementById('main-content');
  let html = '';

  switch (state.screen) {
    case 'home': html = renderHome(); break;
    case 'topics': html = renderTopics(); break;
    case 'category': html = renderCategory(); break;
    case 'scientists': html = renderScientists(); break;
    case 'search': html = renderSearch(); break;
    case 'bookmarks': html = renderBookmarks(); break;
    case 'article': html = renderArticle(); break;
    case 'scientist': html = renderScientist(); break;
    default: html = renderHome();
  }

  main.innerHTML = html;
}

// ========== PWA Install ==========
window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  state.deferredPrompt = e;
  render();
});

function installApp() {
  if (!state.deferredPrompt) return;
  state.deferredPrompt.prompt();
  state.deferredPrompt.userChoice.then(() => {
    state.deferredPrompt = null;
    const banner = document.getElementById('install-banner');
    if (banner) banner.remove();
  });
}

// ========== Service Worker ==========
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('sw.js').catch(console.error);
}

// ========== Init ==========
loadData();
render();

// Handle back button for article view
window.addEventListener('popstate', () => {
  if (state.screen === 'article' || state.screen === 'scientist') {
    navigate('home');
  }
});
