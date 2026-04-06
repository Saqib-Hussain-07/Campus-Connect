/* ============================================================
   CampusConnect — main.js
============================================================ */
'use strict';

// ── Scroll reveal ─────────────────────────────────────────────
const revealObs = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

// ── Animated counters ─────────────────────────────────────────
function formatNum(n) {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(0) + 'M';
  if (n >= 1_000)     return (n / 1_000 % 1 === 0 ? Math.floor(n / 1_000) : (n / 1_000).toFixed(1)) + 'K';
  return String(n);
}
function animCounter(el, target, suffix = '', dur = 2000) {
  const t0 = performance.now();
  const step = now => {
    const p = Math.min((now - t0) / dur, 1);
    const eased = 1 - Math.pow(1 - p, 3);
    el.textContent = formatNum(Math.floor(eased * target)) + suffix;
    if (p < 1) requestAnimationFrame(step);
    else {
      el.textContent = formatNum(target) + suffix;
      el.closest('.cc-stat-item')?.classList.add('counted');
    }
  };
  requestAnimationFrame(step);
}
const cntObs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      const el = e.target;
      animCounter(el, +el.dataset.target, el.dataset.suffix || '');
      cntObs.unobserve(el);
    }
  });
}, { threshold: 0.4 });
document.querySelectorAll('.cc-counter').forEach(el => cntObs.observe(el));

// ── Student dept filter ───────────────────────────────────────
document.querySelectorAll('.cc-filter-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.cc-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const f = btn.dataset.filter;
    document.querySelectorAll('.cc-student-card').forEach(card => {
      const show = f === 'all' || card.dataset.dept === f;
      card.style.display = show ? '' : 'none';
    });
  });
});

// ── Group type tabs ───────────────────────────────────────────
document.querySelectorAll('.cc-tab-btn').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.cc-tab-btn').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    const f = tab.dataset.tab;
    document.querySelectorAll('.cc-group-card').forEach(card => {
      const show = f === 'all' || card.dataset.type === f;
      card.style.display = show ? '' : 'none';
    });
  });
});

// ── Smooth scroll for # links ─────────────────────────────────
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const target = document.querySelector(a.getAttribute('href'));
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});

// ── Active nav highlight ──────────────────────────────────────
const navLinks = document.querySelectorAll('.cc-nav-link');
const sections = document.querySelectorAll('section[id]');
const navSectObs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      navLinks.forEach(l => {
        const match = l.getAttribute('href')?.includes('#' + e.target.id);
        l.classList.toggle('active', !!match);
      });
    }
  });
}, { threshold: 0.25, rootMargin: '-80px 0px -50% 0px' });
sections.forEach(s => navSectObs.observe(s));

// ── Bootstrap tooltip init ────────────────────────────────────
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
  new bootstrap.Tooltip(el);
});

// ── Password show/hide ────────────────────────────────────────
document.querySelectorAll('[data-pwd-toggle]').forEach(btn => {
  btn.addEventListener('click', () => {
    const target = document.querySelector(btn.dataset.pwdToggle);
    if (!target) return;
    const isText = target.type === 'text';
    target.type = isText ? 'password' : 'text';
    btn.querySelector('i')?.classList.toggle('fa-eye', isText);
    btn.querySelector('i')?.classList.toggle('fa-eye-slash', !isText);
  });
});
