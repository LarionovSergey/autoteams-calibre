(function () {
  'use strict';

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Мобильное меню ---------- */
  function initNav() {
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.getElementById('site-nav');
    if (!toggle || !nav) { return; }
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  /* ---------- Карта калибровки: «Исходно / После» ---------- */
  function initMap() {
    var map = document.querySelector('[data-map]');
    if (!map) { return; }
    var cells = map.querySelectorAll('td[data-a]');
    var buttons = map.querySelectorAll('.map-ctl button');
    var min = parseFloat(map.getAttribute('data-min'));
    var max = parseFloat(map.getAttribute('data-max'));
    var stops = [[43, 49, 56], [190, 138, 0], [255, 214, 10]]; // тёмная сталь, янтарь, жёлтый

    function color(v) {
      var t = Math.max(0, Math.min(1, (v - min) / (max - min)));
      var seg = t < 0.5 ? 0 : 1;
      var k = seg === 0 ? t * 2 : (t - 0.5) * 2;
      var a = stops[seg], b = stops[seg + 1];
      var c = [0, 1, 2].map(function (i) { return Math.round(a[i] + (b[i] - a[i]) * k); });
      var lum = 0.299 * c[0] + 0.587 * c[1] + 0.114 * c[2];
      return { bg: 'rgb(' + c.join(',') + ')', fg: lum < 120 ? '#f2efe3' : '#141516' };
    }

    function show(state) {
      cells.forEach(function (td) {
        var v = td.getAttribute('data-' + state);
        var c = color(parseFloat(v));
        td.textContent = v;
        td.style.backgroundColor = c.bg;
        td.style.color = c.fg;
      });
      buttons.forEach(function (b) {
        b.setAttribute('aria-pressed', b.getAttribute('data-state') === state ? 'true' : 'false');
      });
    }

    buttons.forEach(function (b) {
      b.addEventListener('click', function () { show(b.getAttribute('data-state')); });
    });
    show('b');
  }

  /* ---------- Карусель «Последние работы» ---------- */
  function initCarousel() {
    var root = document.querySelector('[data-carousel]');
    if (!root) { return; }
    var track = root.querySelector('.carousel-track');
    var prev = root.querySelector('.car-prev');
    var next = root.querySelector('.car-next');
    var playBtn = root.querySelector('.car-play');
    var count = root.querySelector('.car-count');
    var interval = parseInt(root.getAttribute('data-interval'), 10) || 5000;
    var items = track.children;
    var timer = null;
    var userPaused = reduceMotion; // при «уменьшить движение» сами не крутим
    var hovering = false;

    function stepSize() {
      if (!items.length) { return 0; }
      var gap = parseFloat(getComputedStyle(track).columnGap) || 0;
      return items[0].getBoundingClientRect().width + gap;
    }
    function maxScroll() { return track.scrollWidth - track.clientWidth; }
    function fits() { return maxScroll() <= 2; }

    function goTo(left) {
      track.scrollTo({ left: left, behavior: reduceMotion ? 'auto' : 'smooth' });
      // Счётчик обновляем и после программной прокрутки, не только по событию scroll
      setTimeout(updateCount, reduceMotion ? 60 : 700);
    }
    function forward() {
      if (track.scrollLeft >= maxScroll() - 2) { goTo(0); } else { goTo(track.scrollLeft + stepSize()); }
    }
    function back() {
      if (track.scrollLeft <= 2) { goTo(maxScroll()); } else { goTo(track.scrollLeft - stepSize()); }
    }

    function updateCount() {
      var s = stepSize();
      if (!count || !s) { return; }
      var visible = Math.max(1, Math.round((track.clientWidth + (s - items[0].getBoundingClientRect().width)) / s));
      var first = Math.min(items.length, Math.round(track.scrollLeft / s) + 1);
      var last = Math.min(items.length, first + visible - 1);
      count.textContent = first === last ? first + ' из ' + items.length : first + '–' + last + ' из ' + items.length;
    }

    function stop() { if (timer) { clearInterval(timer); timer = null; } }
    function start() {
      stop();
      if (userPaused || hovering || fits() || document.hidden) { return; }
      timer = setInterval(forward, interval);
    }
    function setPlayLabel() {
      if (!playBtn) { return; }
      playBtn.textContent = userPaused ? 'Включить прокрутку' : 'Пауза';
      playBtn.setAttribute('aria-pressed', userPaused ? 'true' : 'false');
    }

    function refresh() {
      var all = fits();
      [prev, next, playBtn].forEach(function (b) { if (b) { b.hidden = all; } });
      if (count) { count.hidden = all; }
      updateCount();
      start();
    }

    if (prev) { prev.addEventListener('click', function () { back(); start(); }); }
    if (next) { next.addEventListener('click', function () { forward(); start(); }); }
    if (playBtn) {
      playBtn.addEventListener('click', function () { userPaused = !userPaused; setPlayLabel(); start(); });
    }

    // Пока человек наводит мышь или работает с клавиатуры, не крутим
    root.addEventListener('mouseenter', function () { hovering = true; stop(); });
    root.addEventListener('mouseleave', function () { hovering = false; start(); });
    root.addEventListener('focusin', function () { hovering = true; stop(); });
    root.addEventListener('focusout', function () { hovering = false; start(); });
    track.addEventListener('touchstart', function () { stop(); }, { passive: true });
    track.addEventListener('touchend', function () { start(); }, { passive: true });

    var scrollTick = null;
    track.addEventListener('scroll', function () {
      if (scrollTick) { return; }
      scrollTick = setTimeout(function () { scrollTick = null; updateCount(); }, 120);
    }, { passive: true });
    window.addEventListener('resize', refresh);
    document.addEventListener('visibilitychange', start);

    setPlayLabel();
    refresh();
  }

  initNav();
  initMap();
  initCarousel();
})();
