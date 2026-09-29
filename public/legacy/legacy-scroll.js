/** Scroll-to-top for legacy HTML pages (run on user gesture + after async loads). */
(function (global) {
  if ('scrollRestoration' in global.history) {
    global.history.scrollRestoration = 'manual';
  }

  var backToTopUpdate = null;

  function scrollToPageTop(headingId) {
    if (global.document.activeElement instanceof HTMLElement) {
      global.document.activeElement.blur();
    }
    global.document.documentElement.scrollTop = 0;
    global.document.body.scrollTop = 0;
    global.scrollTo(0, 0);
    var id = headingId || 'page-heading';
    var heading = global.document.getElementById(id);
    if (heading) {
      heading.scrollIntoView({ behavior: 'auto', block: 'start' });
    }
    global.requestAnimationFrame(function () {
      global.scrollTo(0, 0);
      refreshBackToTop();
    });
  }

  function getScrollTop() {
    return global.scrollY || global.document.documentElement.scrollTop || global.document.body.scrollTop || 0;
  }

  function pageCanScroll() {
    var root = global.document.documentElement;
    return root.scrollHeight > global.innerHeight + 32;
  }

  var controlSelector = 'input, select, textarea, button, label';

  function isFormControl(target) {
    if (!(target instanceof HTMLElement)) {
      return false;
    }
    if (target.matches(controlSelector)) {
      return true;
    }
    return Boolean(target.closest(controlSelector));
  }

  function isSelectControl(target) {
    return target instanceof HTMLElement && (target.matches('select') || Boolean(target.closest('select')));
  }

  function isTextEntryControl(target) {
    if (!(target instanceof HTMLElement)) {
      return false;
    }
    var field = target.closest('input, textarea');
    if (!field) {
      return false;
    }
    if (field.tagName === 'TEXTAREA') {
      return true;
    }
    if (field instanceof HTMLInputElement) {
      var type = (field.type || 'text').toLowerCase();
      return ['button', 'submit', 'reset', 'checkbox', 'radio', 'file', 'hidden'].indexOf(type) === -1;
    }
    return false;
  }

  function bindSectionScroll(sectionEl, headingId) {
    if (!sectionEl) {
      return;
    }
    sectionEl.addEventListener('click', function (event) {
      if (!isFormControl(event.target) || isSelectControl(event.target) || isTextEntryControl(event.target)) {
        return;
      }
      scrollToPageTop(headingId);
    });
  }

  function refreshBackToTop() {
    if (backToTopUpdate) {
      backToTopUpdate();
    }
  }

  function bindBackToTop(options) {
    var opts = options || {};
    var threshold = typeof opts.threshold === 'number' ? opts.threshold : 120;
    var headingId = opts.headingId;

    var btn = global.document.querySelector('.back-to-top');
    if (!btn) {
      btn = global.document.createElement('button');
      btn.type = 'button';
      btn.className = 'back-to-top';
      btn.setAttribute('aria-label', 'Back to top');
      btn.textContent = 'Back to top';
      btn.style.position = 'fixed';
      btn.style.top = 'auto';
      btn.style.left = 'auto';
      btn.style.right = 'max(1rem, env(safe-area-inset-right, 0px))';
      btn.style.bottom = 'max(1rem, env(safe-area-inset-bottom, 0px))';
      btn.style.zIndex = '1000';
      btn.style.margin = '0';
      global.document.body.appendChild(btn);

      function resolveHeadingId() {
        if (headingId) {
          return headingId;
        }
        return global.document.body.getAttribute('data-page-heading') || 'page-heading';
      }

      btn.addEventListener('click', function () {
        scrollToPageTop(resolveHeadingId());
      });

      function onScroll() {
        refreshBackToTop();
      }

      global.addEventListener('scroll', onScroll, { passive: true, capture: true });
      global.addEventListener('resize', onScroll, { passive: true });
      global.addEventListener('load', onScroll);

      if (typeof global.ResizeObserver !== 'undefined') {
        var resizeObserver = new global.ResizeObserver(onScroll);
        resizeObserver.observe(global.document.documentElement);
        if (global.document.body) {
          resizeObserver.observe(global.document.body);
        }
      }
    }

    backToTopUpdate = function updateVisibility() {
      if (pageCanScroll()) {
        btn.hidden = false;
        return;
      }
      btn.hidden = getScrollTop() < threshold;
    };

    backToTopUpdate();
  }

  function initPage() {
    var body = global.document.body;
    if (!body) {
      return;
    }
    if (body.getAttribute('data-scroll-top-on-load') !== 'false') {
      scrollToPageTop(body.getAttribute('data-page-heading') || 'page-heading');
    }
    if (body.getAttribute('data-back-to-top') !== 'false') {
      bindBackToTop();
    }
  }

  function onDocumentReady(fn) {
    if (global.document.readyState === 'loading') {
      global.document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  global.LegacyScroll = {
    scrollToPageTop: scrollToPageTop,
    bindSectionScroll: bindSectionScroll,
    bindBackToTop: bindBackToTop,
    refreshBackToTop: refreshBackToTop,
  };

  onDocumentReady(initPage);
})(window);
