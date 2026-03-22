/**
 * Client-side idle timeout (mirrors server SESSION_IDLE_TIMEOUT).
 * Tab switch / minimize do not reset activity; real interaction does.
 */
(function () {
  var IDLE_MS = 5 * 60 * 1000;
  var WARN_MS = 4 * 60 * 1000;
  var LOGIN_URL = 'login.php';
  var THROTTLE_MS = 400;

  var idleTimer = null;
  var warnTimer = null;
  var warnEl = null;
  var lastBurst = 0;

  function hideWarning() {
    if (warnEl && warnEl.parentNode) {
      warnEl.parentNode.removeChild(warnEl);
    }
    warnEl = null;
  }

  function showWarning() {
    if (warnEl) return;
    warnEl = document.createElement('div');
    warnEl.setAttribute('role', 'status');
    warnEl.style.cssText =
      'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);max-width:min(520px,92vw);' +
      'background:#1e293b;color:#f8fafc;padding:14px 18px;border-radius:12px;box-shadow:0 12px 40px rgba(0,0,0,.25);' +
      'z-index:10050;font:14px/1.4 system-ui,-apple-system,sans-serif;display:flex;align-items:center;gap:12px;flex-wrap:wrap;';
    warnEl.innerHTML =
      '<span><strong>Session ending soon.</strong> You will be logged out in about 1 minute due to inactivity.</span>' +
      '<button type="button" style="margin-left:auto;background:#f97316;color:#fff;border:none;padding:8px 14px;border-radius:8px;font-weight:600;cursor:pointer;">Stay signed in</button>';
    var btn = warnEl.querySelector('button');
    btn.addEventListener('click', function () {
      resetTimers();
    });
    document.body.appendChild(warnEl);
  }

  function resetTimers() {
    clearTimeout(idleTimer);
    clearTimeout(warnTimer);
    hideWarning();
    warnTimer = setTimeout(showWarning, WARN_MS);
    idleTimer = setTimeout(function () {
      window.location.href = LOGIN_URL;
    }, IDLE_MS);
  }

  function onActivity() {
    var now = Date.now();
    if (now - lastBurst < THROTTLE_MS) return;
    lastBurst = now;
    resetTimers();
  }

  ['click', 'keydown', 'scroll', 'touchstart'].forEach(function (ev) {
    document.addEventListener(ev, onActivity, { passive: true });
  });
  document.addEventListener('mousemove', onActivity, { passive: true });

  document.addEventListener('DOMContentLoaded', resetTimers);
})();
