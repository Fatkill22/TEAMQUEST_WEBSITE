/* ============================================================
   TeamQuest Training Portal — Global JS Utilities
   ============================================================ */

/* === TOAST NOTIFICATIONS === */
(function () {
  const container = document.createElement('div');
  container.id = 'tq-toast-container';
  document.body.appendChild(container);

  window.tqToast = function (message, type) {
    type = type || 'info';
    const icons = { success: 'check-circle-fill', error: 'x-circle-fill', info: 'info-circle-fill' };
    const toast = document.createElement('div');
    toast.className = 'tq-toast ' + type;
    toast.innerHTML = '<i class="bi bi-' + (icons[type] || 'info-circle-fill') + '"></i> ' + message;
    container.appendChild(toast);
    setTimeout(function () {
      toast.classList.add('hiding');
      setTimeout(function () { toast.remove(); }, 260);
    }, 4000);
  };
})();

/* === LOADING BUTTONS === */
document.addEventListener('submit', function (e) {
  const form = e.target;
  const btn = form.querySelector('button[type="submit"]:not([data-no-load])');
  if (btn && !btn.dataset.loading) {
    btn.dataset.loading = '1';
    btn.dataset.origHtml = btn.innerHTML;
    btn.innerHTML = '<span class="tq-spinner"></span> Processing…';
    btn.disabled = true;
    // Safety re-enable after 10 s in case of error
    setTimeout(function () {
      btn.innerHTML = btn.dataset.origHtml || btn.innerHTML;
      btn.disabled = false;
      delete btn.dataset.loading;
    }, 10000);
  }
});

/* === SIDEBAR SECTION NAVIGATION (EMPLOYEE + ADMIN dashboards) === */
document.addEventListener('DOMContentLoaded', function () {
  const navLinks  = document.querySelectorAll('.tq-nav-link[data-section]');
  const sections  = document.querySelectorAll('.tq-section');
  const pageTitle = document.querySelector('.tq-page-title');

  function activateSection(target) {
    navLinks.forEach(function (l) { l.closest('.tq-nav-item').classList.remove('active'); });
    const activeLink = document.querySelector('.tq-nav-link[data-section="' + target + '"]');
    if (activeLink) activeLink.closest('.tq-nav-item').classList.add('active');

    sections.forEach(function (s) { s.classList.remove('active'); });
    const targetSection = document.getElementById('section-' + target);
    if (targetSection) targetSection.classList.add('active');

    if (pageTitle && activeLink) {
      const label = activeLink.querySelector('span');
      if (label) pageTitle.textContent = label.textContent;
    }
  }

  navLinks.forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      activateSection(this.dataset.section);
      history.replaceState(null, '', '?tab=' + this.dataset.section);
    });
  });

  // Auto-activate from URL ?tab= param
  const urlTab = new URLSearchParams(window.location.search).get('tab');
  if (urlTab && document.getElementById('section-' + urlTab)) {
    activateSection(urlTab);
  }

  // "See All" links inside home section that switch tabs
  document.querySelectorAll('[data-goto-section]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      activateSection(this.dataset.gotoSection);
    });
  });
});

/* === EXAM OPTION CARDS === */
document.addEventListener('DOMContentLoaded', function () {
  function checkAllAnswered() {
    const total    = document.querySelectorAll('.tq-question-card').length;
    const answered = document.querySelectorAll('.tq-dot.answered').length;
    const btn      = document.getElementById('tq-submit-btn');
    if (!btn) return;
    if (answered >= total) {
      btn.disabled = false;
      btn.classList.remove('btn-secondary');
    } else {
      btn.disabled = true;
    }
  }

  document.querySelectorAll('.tq-option').forEach(function (opt) {
    opt.addEventListener('click', function () {
      const radio = this.querySelector('input[type="radio"]');
      if (!radio) return;
      const name = radio.name;

      // Deselect siblings
      document.querySelectorAll('input[name="' + name + '"]').forEach(function (r) {
        r.closest('.tq-option').classList.remove('selected');
      });

      radio.checked = true;
      this.classList.add('selected');

      // Mark progress dot
      const qCard = this.closest('.tq-question-card');
      if (qCard) {
        const idx = qCard.dataset.qindex;
        const dot = document.querySelector('.tq-dot[data-qindex="' + idx + '"]');
        if (dot) dot.classList.add('answered');
      }

      checkAllAnswered();
    });
  });

  checkAllAnswered();
});

/* === GAUGE TOGGLE BUTTONS === */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.tq-toggle-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const val    = this.dataset.val;
      const target = this.dataset.target;
      const input  = document.querySelector('input[name="' + target + '"]');
      const wrap   = this.closest('.tq-toggle-wrap');

      wrap.querySelectorAll('.tq-toggle-btn').forEach(function (b) {
        b.classList.remove('active-0', 'active-1');
      });
      this.classList.add('active-' + val);
      if (input) input.value = val;
    });
  });
});

/* === RESULTS FILTER PILLS === */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.tq-pill[data-filter]').forEach(function (pill) {
    pill.addEventListener('click', function () {
      document.querySelectorAll('.tq-pill[data-filter]').forEach(function (p) { p.classList.remove('active'); });
      this.classList.add('active');
      const filter = this.dataset.filter;
      document.querySelectorAll('.tq-result-row').forEach(function (row) {
        row.style.display = (filter === 'all' || row.dataset.status === filter) ? '' : 'none';
      });
    });
  });
});

/* === ADMIN SEARCH === */
document.addEventListener('DOMContentLoaded', function () {
  const inp = document.getElementById('userSearchInput');
  if (inp) {
    inp.addEventListener('input', function () {
      const term = this.value.toLowerCase();
      document.querySelectorAll('#attemptsTableBody tr').forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
      });
    });
  }
});

/* === COLLAPSIBLE PANEL === */
document.addEventListener('DOMContentLoaded', function () {
  const btn   = document.getElementById('tq-collapse-btn');
  const panel = document.getElementById('tq-collapse-panel');
  if (btn && panel) {
    btn.addEventListener('click', function () {
      panel.classList.toggle('open');
      const icon = this.querySelector('.bi');
      if (icon) icon.className = 'bi bi-' + (panel.classList.contains('open') ? 'chevron-up' : 'chevron-down');
      this.querySelector('.btn-label').textContent = panel.classList.contains('open') ? 'Hide Form' : 'Add New Module / Gauge Study';
    });
  }
});

/* === SHOW TOAST FOR URL SIGNALS === */
document.addEventListener('DOMContentLoaded', function () {
  const p = new URLSearchParams(window.location.search);
  if (p.get('saved')   === '1') tqToast('Saved successfully!', 'success');
  if (p.get('deleted') === '1') tqToast('Item deleted.', 'info');
  if (p.get('reset')   === '1') tqToast('Progress reset successfully.', 'success');
  if (p.get('error')   === '1') tqToast('Something went wrong. Please try again.', 'error');
  if (p.get('added')   === '1') tqToast('Question added!', 'success');
});
