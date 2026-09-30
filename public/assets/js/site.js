/*
 * GATE Lebanon — public website behaviour. Plain JavaScript, no dependencies; everything degrades to working HTML
 * (links, forms, CSS hover menus) without it. Motion only runs when <html> has the .motion class, which the head script
 * sets unless the Appearance setting turned motion off or the visitor prefers reduced motion. Styles are only set
 * through the CSSOM (never style attributes in HTML), so the Content Security Policy needs no 'unsafe-inline'.
 */
(function () {
  'use strict';

  var root = document.documentElement;
  var motion = root.classList.contains('motion');
  var rtl = root.getAttribute('dir') === 'rtl';

  function $all(selector, scope) {
    return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
  }

  // ------------------------------------------------------------------------------------------ header and to-top
  var header = document.querySelector('[data-header]');
  var toTop = document.querySelector('[data-to-top]');
  var progress = toTop ? toTop.querySelector('.to-top__progress') : null;
  var ticking = false;

  function onScroll() {
    ticking = false;
    var y = window.scrollY || window.pageYOffset;
    if (header) {
      header.classList.toggle('is-scrolled', y > 80);
    }
    if (toTop) {
      toTop.classList.toggle('is-visible', y > 600);
      if (progress) {
        var max = document.documentElement.scrollHeight - window.innerHeight;
        progress.style.strokeDashoffset = String(100 - Math.min(100, max > 0 ? (y / max) * 100 : 0));
      }
    }
  }
  window.addEventListener('scroll', function () {
    if (!ticking) {
      ticking = true;
      window.requestAnimationFrame(onScroll);
    }
  }, { passive: true });
  onScroll();
  if (toTop) {
    toTop.hidden = false;
    toTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: motion ? 'smooth' : 'auto' });
      var main = document.getElementById('main');
      if (main) {
        main.focus({ preventScroll: true });
      }
    });
  }

  // --------------------------------------------------------------------------------------------- mobile menu
  var nav = document.querySelector('[data-nav]');
  var menuToggle = document.querySelector('[data-menu-toggle]');
  function setMenu(open) {
    if (!nav || !menuToggle) {
      return;
    }
    nav.classList.toggle('is-open', open);
    menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  if (menuToggle) {
    menuToggle.addEventListener('click', function () {
      setMenu(menuToggle.getAttribute('aria-expanded') !== 'true');
    });
  }

  // ------------------------------------------------------------------------------------- navigation dropdowns
  $all('[data-dropdown]').forEach(function (item) {
    var button = item.querySelector('.nav__toggle');
    if (!button) {
      return;
    }
    button.addEventListener('click', function (event) {
      event.stopPropagation();
      var open = !item.classList.contains('is-open');
      $all('[data-dropdown].is-open').forEach(function (other) {
        if (other !== item) {
          other.classList.remove('is-open');
          var b = other.querySelector('.nav__toggle');
          if (b) {
            b.setAttribute('aria-expanded', 'false');
          }
        }
      });
      item.classList.toggle('is-open', open);
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });

  // ------------------------------------------------------------------------------------------ language menu
  var lang = document.querySelector('[data-lang-menu]');
  if (lang) {
    lang.setAttribute('data-ready', '');
    var langButton = lang.querySelector('.lang__btn');
    var items = $all('a', lang);
    var setLang = function (open, focus) {
      lang.classList.toggle('is-open', open);
      langButton.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open && focus) {
        var current = lang.querySelector('[aria-checked="true"]') || items[0];
        if (current) {
          current.focus();
        }
      }
    };
    langButton.addEventListener('click', function (event) {
      event.stopPropagation();
      setLang(!lang.classList.contains('is-open'), true);
    });
    lang.addEventListener('keydown', function (event) {
      var index = items.indexOf(document.activeElement);
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        items[(index + 1) % items.length].focus();
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        items[(index - 1 + items.length) % items.length].focus();
      } else if (event.key === 'Escape') {
        setLang(false);
        langButton.focus();
      }
    });
    lang.addEventListener('focusout', function (event) {
      if (!lang.contains(event.relatedTarget)) {
        setLang(false);
      }
    });
  }

  document.addEventListener('click', function (event) {
    if (lang && !lang.contains(event.target)) {
      lang.classList.remove('is-open');
      var b = lang.querySelector('.lang__btn');
      if (b) {
        b.setAttribute('aria-expanded', 'false');
      }
    }
    $all('[data-dropdown].is-open').forEach(function (item) {
      if (!item.contains(event.target)) {
        item.classList.remove('is-open');
        var b2 = item.querySelector('.nav__toggle');
        if (b2) {
          b2.setAttribute('aria-expanded', 'false');
        }
      }
    });
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      setMenu(false);
      $all('[data-dropdown].is-open').forEach(function (item) {
        item.classList.remove('is-open');
      });
    }
  });

  // ---------------------------------------------------------------------------------------- hero word motion
  if (motion) {
    $all('[data-split]').forEach(function (title) {
      var n = 0;
      var split = function (node) {
        $all(':scope > *', node).forEach(split);
        Array.prototype.slice.call(node.childNodes).forEach(function (child) {
          if (child.nodeType !== 3 || !child.textContent.trim()) {
            return;
          }
          var fragment = document.createDocumentFragment();
          child.textContent.split(/(\s+)/).forEach(function (part) {
            if (!part) {
              return;
            }
            if (/^\s+$/.test(part)) {
              fragment.appendChild(document.createTextNode(part));
              return;
            }
            var span = document.createElement('span');
            span.className = 'w';
            span.textContent = part;
            span.style.animationDelay = (250 + n * 90) + 'ms';
            n += 1;
            fragment.appendChild(span);
          });
          node.replaceChild(fragment, child);
        });
      };
      title.setAttribute('aria-label', title.textContent.replace(/\s+/g, ' ').trim());
      split(title);
      $all('.w', title).forEach(function (w) {
        w.setAttribute('aria-hidden', 'true');
      });
    });
  }

  // -------------------------------------------------------------------------------- scroll reveal and counters
  function countUp(el) {
    var end = parseInt(el.getAttribute('data-count'), 10);
    if (!motion || isNaN(end)) {
      return;
    }
    var format = new Intl.NumberFormat(root.lang === 'fr' ? 'fr-FR' : 'en-US');
    var duration = 1800;
    var start = null;
    var step = function (time) {
      start = start || time;
      var p = Math.min(1, (time - start) / duration);
      el.textContent = format.format(Math.round(end * (1 - Math.pow(1 - p, 4))));
      if (p < 1) {
        window.requestAnimationFrame(step);
      }
    };
    window.requestAnimationFrame(step);
  }

  // Stagger: children of a [data-stagger] group reveal one after another (index in --i, capped in CSS).
  $all('[data-stagger]').forEach(function (group) {
    $all('.reveal', group).forEach(function (el, i) {
      el.style.setProperty('--i', String(i));
    });
  });
  $all('.map-card .map__region').forEach(function (path, i) {
    path.style.transitionDelay = (i * 80) + 'ms';
  });

  var reveals = $all('.reveal');
  if (!motion || !('IntersectionObserver' in window)) {
    reveals.forEach(function (el) {
      el.classList.add('in');
    });
  } else {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }
        entry.target.classList.add('in');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.14, rootMargin: '0px 0px -8% 0px' });
    reveals.forEach(function (el) {
      observer.observe(el);
    });
    var counters = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          countUp(entry.target);
          counters.unobserve(entry.target);
        }
      });
    }, { threshold: 0.6 });
    $all('[data-count]').forEach(function (el) {
      el.textContent = '0';
      counters.observe(el);
    });
  }

  // ----------------------------------------------------------------------------------------------------- toast
  var toast = document.querySelector('[data-toast]');
  if (toast) {
    var hide = function () {
      toast.classList.add('is-hidden');
      window.setTimeout(function () {
        toast.remove();
      }, 400);
    };
    var close = toast.querySelector('[data-toast-close]');
    if (close) {
      close.addEventListener('click', hide);
    }
    window.setTimeout(hide, 7000);
  }

  // -------------------------------------------------------------------------------------------------- lightbox
  var dialog = document.querySelector('[data-lightbox-dialog]');
  var links = $all('[data-lightbox]');
  if (dialog && links.length && typeof dialog.showModal === 'function') {
    var img = dialog.querySelector('[data-lightbox-img]');
    var caption = dialog.querySelector('[data-lightbox-caption]');
    var count = dialog.querySelector('[data-lightbox-count]');
    var current = 0;
    var opener = null;
    var show = function (index) {
      current = (index + links.length) % links.length;
      var link = links[current];
      img.src = link.getAttribute('href');
      img.alt = link.getAttribute('data-caption') || '';
      caption.textContent = link.getAttribute('data-caption') || '';
      count.textContent = (current + 1) + ' / ' + links.length;
    };
    links.forEach(function (link, i) {
      link.addEventListener('click', function (event) {
        event.preventDefault();
        opener = link;
        show(i);
        dialog.showModal();
      });
    });
    dialog.querySelector('[data-lightbox-close]').addEventListener('click', function () {
      dialog.close();
    });
    dialog.querySelector('[data-lightbox-prev]').addEventListener('click', function () {
      show(current - 1);
    });
    dialog.querySelector('[data-lightbox-next]').addEventListener('click', function () {
      show(current + 1);
    });
    dialog.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowLeft') {
        show(current + (rtl ? 1 : -1));
      } else if (event.key === 'ArrowRight') {
        show(current + (rtl ? -1 : 1));
      }
    });
    dialog.addEventListener('click', function (event) {
      if (event.target === dialog) {
        dialog.close();
      }
    });
    dialog.addEventListener('close', function () {
      if (opener) {
        opener.focus();
      }
    });
  }
})();
