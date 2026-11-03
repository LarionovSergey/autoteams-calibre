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

    function paint(td, v, final) {
      var c = color(v);
      td.textContent = v.toFixed(1);
      td.style.backgroundColor = c.bg;
      td.style.color = c.fg;
      if (final) { td.classList.remove('is-tuning'); }
    }

    function mark(state) {
      buttons.forEach(function (b) {
        b.setAttribute('aria-pressed', b.getAttribute('data-state') === state ? 'true' : 'false');
      });
    }

    // мгновенно (без анимации)
    function show(state) {
      cells.forEach(function (td) {
        var v = parseFloat(td.getAttribute('data-' + state));
        td._v = v;
        td._job = null;
        paint(td, v, true);
      });
      mark(state);
    }

    // «Замер на стенде»: значения по очереди пересчитываются волной слева направо
    var jobs = [];
    var looping = false;
    function loop(ts) {
      var alive = false;
      jobs = jobs.filter(function (td) {
        var j = td._job;
        if (!j) { return false; }
        var t = ts - j.start;
        if (t < 0) { alive = true; return true; }
        var k = Math.min(1, t / j.dur);
        var ease = 1 - Math.pow(1 - k, 3);
        td._v = j.from + (j.to - j.from) * ease;
        td.classList.add('is-tuning');
        paint(td, td._v, k >= 1);
        if (k >= 1) { td._v = j.to; td._job = null; return false; }
        alive = true;
        return true;
      });
      if (alive) { requestAnimationFrame(loop); } else { looping = false; }
    }
    function animateTo(state) {
      var now = performance.now();
      var cols = map.querySelectorAll('thead th').length - 1;
      mark(state);
      cells.forEach(function (td, i) {
        var col = i % cols, row = Math.floor(i / cols);
        var to = parseFloat(td.getAttribute('data-' + state));
        if (td._v === undefined) { td._v = parseFloat(td.textContent); }
        td._job = { from: td._v, to: to, start: now + col * 55 + row * 22, dur: 520 };
        if (jobs.indexOf(td) < 0) { jobs.push(td); }
      });
      if (!looping) { looping = true; requestAnimationFrame(loop); }
    }

    buttons.forEach(function (b) {
      b.addEventListener('click', function () {
        var st = b.getAttribute('data-state');
        if (reduceMotion) { show(st); } else { animateTo(st); }
      });
    });

    if (reduceMotion || !('IntersectionObserver' in window)) {
      show('b');
      return;
    }
    // сначала «Исходно», а когда блок виден — волной переходит в «После»
    map.classList.add('is-live');
    show('a');
    var seen = new IntersectionObserver(function (entries) {
      if (!entries[0].isIntersecting) { return; }
      seen.disconnect();
      setTimeout(function () { animateTo('b'); }, 700);
    }, { threshold: 0.45 });
    seen.observe(map);
  }

  /* ---------- Переворот блока: таблица калибровки → стендовый замер ---------- */
  function initFlip() {
    var root = document.querySelector('[data-flip]');
    if (!root) { return; }
    var front = root.querySelector('.flip-front');
    var back = root.querySelector('.flip-back');
    var dots = root.querySelectorAll('.flip-dots button');
    var plot = root.querySelector('[data-plot]');
    var result = root.querySelector('[data-result]');
    var NS = 'http://www.w3.org/2000/svg';
    var AUTO_MS = 7000;   // через сколько секунд после появления блок переворачивается сам

    // условный турбобензиновый мотор: обороты, момент (Н·м) исходно и после прошивки
    var RPM = [1500, 2000, 2500, 3000, 3500, 4000, 4500, 5000, 5500, 6000, 6500];
    var T0 = [190, 290, 330, 340, 340, 335, 320, 295, 265, 235, 205];
    var T1 = [205, 350, 420, 430, 425, 410, 390, 360, 325, 285, 245];
    function hp(t, i) { return t * RPM[i] / 7023; }
    var P0 = T0.map(hp), P1 = T1.map(hp);
    function peak(a) { return Math.max.apply(null, a); }

    var iw = 0, ih = 0, clip0 = null, clip1 = null, cursor = null;
    var token = 0, done = false, flipped = false, userTouched = false, autoTimer = 0;

    function mk(tag, attrs, parent) {
      var n = document.createElementNS(NS, tag);
      Object.keys(attrs || {}).forEach(function (k) { n.setAttribute(k, attrs[k]); });
      if (parent) { parent.appendChild(n); }
      return n;
    }
    // плавная кривая через точки (Catmull-Rom → Безье); first — 'M' или 'L'
    function curve(pts, first) {
      var d = first + pts[0][0].toFixed(1) + ' ' + pts[0][1].toFixed(1);
      for (var i = 0; i < pts.length - 1; i++) {
        var p0 = pts[i - 1] || pts[i], p1 = pts[i], p2 = pts[i + 1], p3 = pts[i + 2] || p2;
        d += ' C' + (p1[0] + (p2[0] - p0[0]) / 6).toFixed(1) + ' ' + (p1[1] + (p2[1] - p0[1]) / 6).toFixed(1) +
             ' ' + (p2[0] - (p3[0] - p1[0]) / 6).toFixed(1) + ' ' + (p2[1] - (p3[1] - p1[1]) / 6).toFixed(1) +
             ' ' + p2[0].toFixed(1) + ' ' + p2[1].toFixed(1);
      }
      return d;
    }

    function build() {
      var w = plot.clientWidth, h = plot.clientHeight;
      if (!w || !h) { return false; }
      plot.textContent = '';
      var narrow = w < 420;
      var m = { l: 34, r: 34, t: 18, b: 22 };
      iw = w - m.l - m.r; ih = h - m.t - m.b;
      var svg = mk('svg', { width: w, height: h, viewBox: '0 0 ' + w + ' ' + h, role: 'img',
        'aria-label': 'График: момент и мощность двигателя до и после прошивки' }, plot);
      var defs = mk('defs', {}, svg);
      var c0 = mk('clipPath', { id: 'dy-c0' }, defs); clip0 = mk('rect', { x: m.l, y: 0, width: 0, height: h }, c0);
      var c1 = mk('clipPath', { id: 'dy-c1' }, defs); clip1 = mk('rect', { x: m.l, y: 0, width: 0, height: h }, c1);
      function X(r) { return m.l + (r - 1500) / 5000 * iw; }
      function YT(v) { return m.t + ih * (1 - v / 600); }
      function YP(v) { return m.t + ih * (1 - v / 300); }
      var grid = mk('g', { 'font-size': narrow ? 9.5 : 11, fill: '#8f8c7e', 'font-family': 'inherit' }, svg);
      for (var i = 0; i <= 6; i++) {
        var y = m.t + ih * (1 - i / 6);
        mk('line', { x1: m.l, x2: m.l + iw, y1: y, y2: y, stroke: '#34373a', 'stroke-width': 1 }, grid);
        mk('text', { x: m.l - 6, y: y + 3.5, 'text-anchor': 'end' }, grid).textContent = i * 100;
        mk('text', { x: m.l + iw + 6, y: y + 3.5, 'text-anchor': 'start' }, grid).textContent = i * 50;
      }
      for (var r = 2000; r <= 6000; r += 1000) {
        mk('line', { x1: X(r), x2: X(r), y1: m.t, y2: m.t + ih, stroke: '#34373a', 'stroke-width': 1 }, grid);
        mk('text', { x: X(r), y: h - 6, 'text-anchor': 'middle' }, grid).textContent = r;
      }
      mk('text', { x: m.l - 6, y: 9, 'text-anchor': 'end', fill: '#ffd60a', 'font-weight': 700 }, grid).textContent = 'Н·м';
      mk('text', { x: m.l + iw + 6, y: 9, 'text-anchor': 'start', fill: '#ff8a1e', 'font-weight': 700 }, grid).textContent = 'л.с.';
      mk('text', { x: m.l + iw, y: 9, 'text-anchor': 'end' }, grid).textContent = 'об/мин';

      function pts(arr, Y) { return arr.map(function (v, i) { return [X(RPM[i]), Y(v)]; }); }
      var g0 = mk('g', { 'clip-path': 'url(#dy-c0)', fill: 'none', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, svg);
      mk('path', { d: curve(pts(T0, YT), 'M'), stroke: '#8a93a0', 'stroke-width': 2 }, g0);
      mk('path', { d: curve(pts(P0, YP), 'M'), stroke: '#8a93a0', 'stroke-width': 2, 'stroke-dasharray': '6 4' }, g0);
      var g1 = mk('g', { 'clip-path': 'url(#dy-c1)', fill: 'none', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, svg);
      var t1 = pts(T1, YT), t0 = pts(T0, YT), p1 = pts(P1, YP), p0 = pts(P0, YP);
      mk('path', { d: curve(t1, 'M') + ' ' + curve(t0.slice().reverse(), 'L') + ' Z', fill: 'rgba(255,214,10,.14)', stroke: 'none' }, g1);
      mk('path', { d: curve(p1, 'M') + ' ' + curve(p0.slice().reverse(), 'L') + ' Z', fill: 'rgba(255,138,30,.14)', stroke: 'none' }, g1);
      mk('path', { d: curve(t1, 'M'), stroke: '#ffd60a', 'stroke-width': 2.6 }, g1);
      mk('path', { d: curve(p1, 'M'), stroke: '#ff8a1e', 'stroke-width': 2.6 }, g1);
      cursor = mk('line', { x1: m.l, x2: m.l, y1: m.t, y2: m.t + ih, stroke: '#f2efe3', 'stroke-width': 1, opacity: 0 }, svg);
      cursor._x = m.l;
      return true;
    }

    function setSweep(rect, frac) { rect.setAttribute('width', Math.max(0, frac) * iw); }
    function showResult(on) {
      if (on) {
        var a0 = Math.round(peak(P0)), a1 = Math.round(peak(P1));
        var b0 = peak(T0), b1 = peak(T1);
        result.innerHTML = 'Мощность <b>' + a0 + ' → ' + a1 + ' л.с.</b> (+' + Math.round((a1 / a0 - 1) * 100) +
          '%) · Момент <b>' + b0 + ' → ' + b1 + ' Н·м</b> (+' + Math.round((b1 / b0 - 1) * 100) + '%)';
      }
      result.classList.toggle('is-in', !!on);
    }
    function finish() {
      token++;
      if (!clip0 && !build()) { return; }
      setSweep(clip0, 1); setSweep(clip1, 1);
      cursor.setAttribute('opacity', 0);
      showResult(true);
      done = true;
    }
    // «Замер»: сначала прорисовывается исходный прогон, затем наш поверх
    function run() {
      if (reduceMotion) { finish(); return; }
      if (!build()) { return; }
      var my = ++token;
      done = false;
      showResult(false);
      var D0 = 1500, GAP = 500, D1 = 1800, start = 0;
      function ease(k) { return k < .5 ? 2 * k * k : 1 - Math.pow(-2 * k + 2, 2) / 2; }
      function frame(ts) {
        if (my !== token) { return; }
        if (!start) { start = ts; }
        var t = ts - start, f0 = Math.min(1, t / D0), f1 = Math.min(1, Math.max(0, (t - D0 - GAP) / D1));
        setSweep(clip0, ease(f0)); setSweep(clip1, ease(f1));
        var lead = t < D0 + GAP ? ease(f0) : ease(f1);
        cursor.setAttribute('x1', cursor._x + lead * iw); cursor.setAttribute('x2', cursor._x + lead * iw);
        cursor.setAttribute('opacity', (f0 >= 1 && f1 <= 0) || f1 >= 1 ? 0 : .45);
        if (f1 >= 1) { done = true; showResult(true); return; }
        requestAnimationFrame(frame);
      }
      requestAnimationFrame(frame);
    }

    function setFace(i) {
      flipped = i === 1;
      root.classList.toggle('is-flipped', flipped);
      dots.forEach(function (d) { d.setAttribute('aria-pressed', d.getAttribute('data-face') === String(i) ? 'true' : 'false'); });
      front.setAttribute('aria-hidden', flipped ? 'true' : 'false');
      back.setAttribute('aria-hidden', flipped ? 'false' : 'true');
      front.inert = flipped; back.inert = !flipped;
      if (flipped) { setTimeout(run, reduceMotion ? 0 : 450); }
      else { token++; }
    }
    function touched() { userTouched = true; clearTimeout(autoTimer); }

    dots.forEach(function (d) {
      d.addEventListener('click', function () { touched(); setFace(parseInt(d.getAttribute('data-face'), 10)); });
    });
    front.addEventListener('pointerdown', touched);
    if (reduceMotion) { root.classList.add('no-anim'); }
    back.inert = true;

    // перерисовка при смене размера (поворот телефона и т.п.)
    var lastW = 0, lastH = 0, rt = 0;
    function onSize() {
      var w = plot.clientWidth, h = plot.clientHeight;
      if (!w || !h || (Math.abs(w - lastW) < 2 && Math.abs(h - lastH) < 2)) { return; }
      lastW = w; lastH = h;
      if (!clip0) { build(); return; }
      var wasDone = done;
      build();
      if (wasDone || reduceMotion) { finish(); }
    }
    if ('ResizeObserver' in window) {
      new ResizeObserver(function () { clearTimeout(rt); rt = setTimeout(onSize, 120); }).observe(plot);
    }
    onSize();

    if (reduceMotion || !('IntersectionObserver' in window)) { return; }
    var seen = new IntersectionObserver(function (entries) {
      if (!entries[0].isIntersecting) { return; }
      seen.disconnect();
      autoTimer = setTimeout(function () { if (!userTouched && !flipped) { setFace(1); } }, AUTO_MS);
    }, { threshold: 0.45 });
    seen.observe(root);
  }

  /* ---------- Полоса «в цифрах»: числа набегают при появлении ---------- */
  function initStats() {
    var root = document.querySelector('[data-stats]');
    if (!root) { return; }
    if (reduceMotion || !('IntersectionObserver' in window)) { root.classList.add('is-static'); return; }
    var nums = root.querySelectorAll('[data-count]');
    nums.forEach(function (n) { n.textContent = '0' + (n.getAttribute('data-suffix') || ''); });
    var io = new IntersectionObserver(function (entries) {
      if (!entries[0].isIntersecting) { return; }
      io.disconnect();
      root.classList.add('is-run');
      var start = 0;
      function frame(ts) {
        if (!start) { start = ts; }
        nums.forEach(function (n, i) {
          var dur = 1500 + i * 250, k = Math.min(1, Math.max(0, (ts - start - i * 150) / dur));
          var e = 1 - Math.pow(1 - k, 3);
          n.textContent = Math.round(parseInt(n.getAttribute('data-count'), 10) * e) + (n.getAttribute('data-suffix') || '');
        });
        if (ts - start < 1500 + (nums.length - 1) * 400 + 100) { requestAnimationFrame(frame); }
      }
      requestAnimationFrame(frame);
    }, { threshold: 0.5 });
    io.observe(root);
  }

  /* ---------- Карусель «Последние работы» ---------- */
  function initCarousel() {
    var root = document.querySelector('[data-carousel]');
    if (!root) { return; }
    var track = root.querySelector('.carousel-track');
    var prev = root.querySelector('.car-prev');
    var next = root.querySelector('.car-next');
    var playBtn = root.querySelector('.car-play');
    var timerBox = root.querySelector('.car-timer');
    var ring = root.querySelector('.car-timer-fg');
    var interval = parseInt(root.getAttribute('data-interval'), 10) || 5000;
    var items = track.children;
    var total = items.length;     // настоящих карточек (без копий)
    var cloned = false;           // есть ли копии в конце для бесконечной ленты
    var userPaused = reduceMotion; // при «уменьшить движение» сами не крутим
    var hovering = false;
    var elapsed = 0;              // сколько прошло с прошлого листания, мс
    var lastTs = 0;
    var circ = ring ? 2 * Math.PI * parseFloat(ring.getAttribute('r')) : 0;

    function gapPx() { return parseFloat(getComputedStyle(track).columnGap) || 0; }
    function stepSize() {
      if (!items.length) { return 0; }
      return items[0].getBoundingClientRect().width + gapPx();
    }
    /* Сколько карточек видно сразу: на столько и листаем (3 на компьютере, 2 на планшете, 1 на телефоне) */
    function perPage() {
      var s = stepSize();
      if (!s) { return 1; }
      return Math.max(1, Math.round((track.clientWidth + gapPx()) / s));
    }
    function fits() { return total <= perPage(); }

    /* Бесконечная лента: после последней карточки в конец добавлены копии первых,
       доехав до них, лента мгновенно возвращается на настоящее начало (картинка та же). */
    function removeClones() {
      [].slice.call(track.querySelectorAll('[data-clone]')).forEach(function (c) { c.parentNode.removeChild(c); });
      cloned = false;
    }
    function buildClones() {
      removeClones();
      var k = perPage();
      var count = 2 * k - 1;
      if (fits() || total < 2 * k) { return; } // слишком мало работ: обычная прокрутка с возвратом
      for (var i = 0; i < count; i++) {
        var c = track.children[i].cloneNode(true);
        c.setAttribute('data-clone', '');
        c.setAttribute('aria-hidden', 'true');
        if ('inert' in c) { c.inert = true; }
        [].slice.call(c.querySelectorAll('a, button')).forEach(function (el) { el.tabIndex = -1; });
        track.appendChild(c);
      }
      cloned = true;
    }
    function normalize() {
      if (!cloned) { return; }
      var s = stepSize();
      if (!s) { return; }
      if (Math.round(track.scrollLeft / s) >= total) {
        var snap = track.style.scrollSnapType;
        track.style.scrollSnapType = 'none';
        track.scrollLeft -= total * s;
        window.requestAnimationFrame(function () { track.style.scrollSnapType = snap; });
      }
    }
    function jump(left) {
      var snap = track.style.scrollSnapType;
      track.style.scrollSnapType = 'none';
      track.scrollLeft = left;
      window.requestAnimationFrame(function () { track.style.scrollSnapType = snap; });
    }
    function goTo(left) {
      track.scrollTo({ left: left, behavior: reduceMotion ? 'auto' : 'smooth' });
    }

    function forward() {
      elapsed = 0;
      var s = stepSize();
      var cur = Math.round(track.scrollLeft / s);
      if (cloned) {
        goTo((cur + perPage()) * s);
      } else if (track.scrollLeft >= track.scrollWidth - track.clientWidth - 2) {
        goTo(0);
      } else {
        goTo(Math.min(track.scrollWidth - track.clientWidth, track.scrollLeft + s * perPage()));
      }
    }
    function back() {
      elapsed = 0;
      var s = stepSize();
      var k = perPage();
      var cur = Math.round(track.scrollLeft / s);
      if (cloned) {
        if (cur <= 0) { jump(total * s); cur = total; }
        goTo(Math.max(0, cur - k) * s);
      } else if (track.scrollLeft <= 2) {
        goTo(track.scrollWidth - track.clientWidth);
      } else {
        goTo(Math.max(0, track.scrollLeft - s * k));
      }
    }

    /* Кружок-таймер: заполняется по часовой стрелке за время до следующего листания */
    function draw() {
      if (ring) { ring.style.strokeDashoffset = String(circ * (1 - Math.min(1, elapsed / interval))); }
    }
    function running() { return !(userPaused || hovering || fits() || document.hidden); }
    function tick(ts) {
      window.requestAnimationFrame(tick);
      var dt = lastTs ? Math.min(ts - lastTs, 100) : 0;
      lastTs = ts;
      if (running()) {
        elapsed += dt;
        if (elapsed >= interval) { forward(); }
      }
      draw();
    }

    function setPlayLabel() {
      if (!playBtn) { return; }
      playBtn.textContent = userPaused ? 'Включить прокрутку' : 'Пауза';
      playBtn.setAttribute('aria-pressed', userPaused ? 'true' : 'false');
    }

    function refresh() {
      buildClones();
      var all = fits();
      [prev, next, playBtn].forEach(function (b) { if (b) { b.hidden = all; } });
      if (timerBox) { timerBox.hidden = all; }
      jump(0);
      elapsed = 0;
      draw();
    }

    if (prev) { prev.addEventListener('click', function () { back(); }); }
    if (next) { next.addEventListener('click', function () { forward(); }); }
    if (playBtn) {
      playBtn.addEventListener('click', function () { userPaused = !userPaused; setPlayLabel(); });
    }

    // Пока человек наводит мышь, касается или работает с клавиатуры, таймер стоит
    root.addEventListener('mouseenter', function () { hovering = true; });
    root.addEventListener('mouseleave', function () { hovering = false; });
    root.addEventListener('focusin', function () { hovering = true; });
    root.addEventListener('focusout', function () { hovering = false; });
    track.addEventListener('touchstart', function () { hovering = true; }, { passive: true });
    track.addEventListener('touchend', function () { hovering = false; elapsed = 0; }, { passive: true });

    var scrollTick = null;
    track.addEventListener('scroll', function () {
      if (scrollTick) { clearTimeout(scrollTick); }
      scrollTick = setTimeout(function () { scrollTick = null; normalize(); }, 140);
    }, { passive: true });
    var resizeTick = null;
    window.addEventListener('resize', function () {
      if (resizeTick) { clearTimeout(resizeTick); }
      resizeTick = setTimeout(refresh, 200);
    });

    setPlayLabel();
    refresh();
    if (ring) { ring.style.strokeDasharray = String(circ); draw(); }
    window.requestAnimationFrame(tick);
  }

  /* ---------- Кнопка «Наверх»: только на страницах выше экрана, после небольшой прокрутки ---------- */
  function initToTop() {
    var btn = document.querySelector('.to-top');
    if (!btn) { return; }
    var ticking = false;
    function update() {
      ticking = false;
      var doc = document.documentElement;
      var tall = doc.scrollHeight > window.innerHeight + 200;
      var show = tall && (window.pageYOffset || doc.scrollTop) > 300;
      btn.classList.toggle('is-visible', show);
      btn.setAttribute('aria-hidden', show ? 'false' : 'true');
      btn.tabIndex = show ? 0 : -1;
    }
    function request() { if (!ticking) { ticking = true; window.requestAnimationFrame(update); } }
    window.addEventListener('scroll', request, { passive: true });
    window.addEventListener('resize', request);
    window.addEventListener('load', request);
    btn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
    });
    update();
  }

  /* ---------- Плавное появление блоков при прокрутке: главная и страница «Работы» ---------- */
  function initReveal() {
    if (reduceMotion || !('IntersectionObserver' in window)) { return; }
    var isHome = document.body.classList.contains('home');
    var isWorks = !!document.querySelector('.works > .work'); // страница «Работы», категории и марки
    if (!isHome && !isWorks) { return; }
    var sel = isHome
      ? '.quick .wrap > *, .section-head, .prices li, .stage, .carousel, .steps li, .faq details, .quote, .news-list li, .contact .wrap > *'
      : '.works > .work';
    var vh = window.innerHeight;
    var items = [].slice.call(document.querySelectorAll(sel)).filter(function (el) {
      // Что уже видно при открытии страницы, не прячем: мерцания не будет.
      return el.getBoundingClientRect().top > vh * 0.92;
    });
    if (!items.length) { return; }
    items.forEach(function (el) { el.classList.add('reveal'); });
    function show(el, delay) {
      if (!el.classList.contains('reveal') || el.classList.contains('is-in')) { return; }
      io.unobserve(el);
      el.style.setProperty('--rv-delay', delay + 'ms');
      el.classList.add('is-in');
      // После показа снимаем служебные классы, чтобы не мешать наведению и фокусу.
      window.setTimeout(function () {
        el.classList.remove('reveal', 'is-in');
        el.style.removeProperty('--rv-delay');
      }, delay + 900);
    }
    var io = new IntersectionObserver(function (entries) {
      var n = 0;
      entries.forEach(function (e) {
        if (!e.isIntersecting) { return; }
        show(e.target, Math.min(n * 90, 450));
        n++;
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
    items.forEach(function (el) { io.observe(el); });
    // Быстрая прокрутка или переход по ссылке-якорю: блоки, оставшиеся выше экрана, показываем сразу.
    var raf = false;
    window.addEventListener('scroll', function () {
      if (raf) { return; }
      raf = true;
      window.requestAnimationFrame(function () {
        raf = false;
        items.forEach(function (el) {
          if (el.classList.contains('reveal') && !el.classList.contains('is-in') && el.getBoundingClientRect().bottom < 0) { show(el, 0); }
        });
      });
    }, { passive: true });
  }

  initNav();
  initReveal();
  initToTop();
  initMap();
  initFlip();
  initStats();
  initCarousel();
})();
