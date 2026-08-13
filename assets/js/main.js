/**
 * SAMRIDHI AGRO — main.js
 * Vanilla JS only. Organized into small self-contained modules
 * that each init() themselves on DOMContentLoaded.
 */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  document.addEventListener('DOMContentLoaded', function () {
    initNavbarScroll();
    initMobileMenu();
    initLoginModal();
    initScrollReveal();
    initCounters();
    initCardTilt();
    initSmoothAnchors();
  });

  /* ---------- 1. Navbar scroll effect ---------- */
  function initNavbarScroll() {
    var nav = document.getElementById('navbar');
    if (!nav) return;

    function onScroll() {
      if (window.scrollY > 24) {
        nav.classList.add('is-scrolled');
      } else {
        nav.classList.remove('is-scrolled');
      }
    }
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ---------- 2. Mobile hamburger menu ---------- */
  function initMobileMenu() {
    var btn = document.getElementById('hamburger');
    var links = document.getElementById('navLinks');
    if (!btn || !links) return;

    btn.addEventListener('click', function () {
      var isOpen = links.classList.toggle('is-open');
      btn.classList.toggle('is-open', isOpen);
      btn.setAttribute('aria-expanded', String(isOpen));
    });

    links.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        links.classList.remove('is-open');
        btn.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
      });
    });
  }

  /* ---------- 3. Login selection modal ---------- */
  function initLoginModal() {
    var modal = document.getElementById('loginModal');
    if (!modal) return;

    var openers = document.querySelectorAll('#loginTrigger, [data-open-login]');
    var closers = modal.querySelectorAll('[data-close-login]');
    var lastFocused = null;

    function openModal() {
      lastFocused = document.activeElement;
      modal.hidden = false;
      document.body.style.overflow = 'hidden';
      var closeBtn = modal.querySelector('.login-modal__close');
      if (closeBtn) closeBtn.focus();
      document.addEventListener('keydown', onKeydown);
    }

    function closeModal() {
      modal.hidden = true;
      document.body.style.overflow = '';
      document.removeEventListener('keydown', onKeydown);
      if (lastFocused) lastFocused.focus();
    }

    function onKeydown(e) {
      if (e.key === 'Escape') closeModal();
      if (e.key === 'Tab') trapFocus(e);
    }

    function trapFocus(e) {
      var focusable = modal.querySelectorAll('a, button');
      if (!focusable.length) return;
      var first = focusable[0];
      var last = focusable[focusable.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }

    openers.forEach(function (el) { el.addEventListener('click', openModal); });
    closers.forEach(function (el) { el.addEventListener('click', closeModal); });
  }

  /* ---------- 4. Scroll reveal ---------- */
  function initScrollReveal() {
    var items = document.querySelectorAll('[data-reveal]');
    if (!items.length) return;

    if (reduceMotion || !('IntersectionObserver' in window)) {
      items.forEach(function (el) { el.classList.add('is-visible'); });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var el = entry.target;
          var delay = el.getAttribute('data-reveal-delay');
          if (delay) el.style.transitionDelay = delay + 'ms';
          el.classList.add('is-visible');
          observer.unobserve(el);
        }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

    items.forEach(function (el) { observer.observe(el); });
  }

  /* ---------- 5. Animated number counters ---------- */
  function initCounters() {
    var counters = document.querySelectorAll('[data-counter]');
    if (!counters.length) return;

    function animateCounter(el) {
      var target = parseInt(el.getAttribute('data-target'), 10) || 0;

      if (reduceMotion) {
        el.textContent = target.toLocaleString('en-IN');
        return;
      }

      var duration = 1600;
      var start = null;

      function step(timestamp) {
        if (start === null) start = timestamp;
        var progress = Math.min((timestamp - start) / duration, 1);
        var eased = 1 - Math.pow(1 - progress, 3); /* ease-out cubic */
        var value = Math.floor(eased * target);
        el.textContent = value.toLocaleString('en-IN');
        if (progress < 1) {
          requestAnimationFrame(step);
        } else {
          el.textContent = target.toLocaleString('en-IN');
        }
      }
      requestAnimationFrame(step);
    }

    if (!('IntersectionObserver' in window)) {
      counters.forEach(animateCounter);
      return;
    }

    var counterObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          counterObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.4 });

    counters.forEach(function (el) { counterObserver.observe(el); });
  }

  /* ---------- 6. Card 3D hover tilt ---------- */
  function initCardTilt() {
    if (reduceMotion) return;
    var cards = document.querySelectorAll('.portal-card, .feature-card, .flow-step');

    cards.forEach(function (card) {
      card.addEventListener('mousemove', function (e) {
        var rect = card.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var y = e.clientY - rect.top;
        var rotateX = ((y / rect.height) - 0.5) * -6;
        var rotateY = ((x / rect.width) - 0.5) * 6;
        card.style.transform = 'perspective(700px) rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg) translateY(-4px)';
      });
      card.addEventListener('mouseleave', function () {
        card.style.transform = '';
      });
    });
  }

  /* ---------- 7. Smooth in-page anchor scrolling (with fixed navbar offset) ---------- */
  function initSmoothAnchors() {
    var nav = document.getElementById('navbar');
    var navHeight = nav ? nav.offsetHeight : 0;

    document.querySelectorAll('a[href^="#"]').forEach(function (link) {
      link.addEventListener('click', function (e) {
        var id = link.getAttribute('href');
        if (id.length < 2) return;
        var target = document.querySelector(id);
        if (!target) return;

        e.preventDefault();
        var top = target.getBoundingClientRect().top + window.scrollY - (navHeight + 16);
        window.scrollTo({ top: top, behavior: reduceMotion ? 'auto' : 'smooth' });
      });
    });
  }
})();