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
// NOTE: we must NOT set btn.disabled=true — disabled buttons are excluded from
// POST data by the browser, which breaks all named submit-button checks in PHP.
// Instead we flag the form itself to block double-submissions.
document.addEventListener('submit', function (e) {
  const form = e.target;
  if (form.dataset.submitting === '1') { e.preventDefault(); return; }
  // If a form-level validation handler already cancelled submission, don't lock
  if (e.defaultPrevented) return;
  form.dataset.submitting = '1';

  const btn = form.querySelector('button[type="submit"]:not([data-no-load])');
  if (btn) {
    btn.dataset.origHtml = btn.innerHTML;
    btn.innerHTML = '<span class="tq-spinner"></span> Processing…';
    btn.style.pointerEvents = 'none';
    btn.style.opacity = '0.7';
    setTimeout(function () {
      btn.innerHTML = btn.dataset.origHtml || btn.innerHTML;
      btn.style.pointerEvents = '';
      btn.style.opacity = '';
      delete form.dataset.submitting;
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
// Skip buttons already handled by a page-level self-contained script (_gaugeHandled flag).
// For remaining buttons, find the hidden input by id (data-target) with a DOM-proximity fallback.
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.tq-toggle-btn').forEach(function (btn) {
    if (btn._gaugeHandled) return;
    btn.addEventListener('click', function () {
      var val  = this.dataset.val;
      var wrap = this.closest('.tq-toggle-wrap');

      // Try getElementById first, then nearest hidden input in the same parent
      var input = (this.dataset.target ? document.getElementById(this.dataset.target) : null)
               || wrap.parentElement.querySelector('input[type="hidden"]');

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
  if (p.get('error')   === 'invalid') tqToast('Invalid username or password.', 'error');
  if (p.get('error')   === 'locked')  tqToast('You have reached the maximum 3 attempts for this exam.', 'error');
  if (p.get('error')   === 'nokey')   tqToast('Answer key not set up yet — contact your admin.', 'error');
  if (p.get('added')   === '1') tqToast('Question added!', 'success');
});
