/*
 * GATE Lebanon admin additions (loaded after app.js):
 *  - image pickers ([data-media-preview] on a select) show a thumbnail of the chosen image.
 * The media URLs come from <script type="application/json" id="media-urls"> (printed by the admin layout).
 */
(function () {
  'use strict';
  var urls = {};
  try {
    var data = document.getElementById('media-urls');
    urls = data ? JSON.parse(data.textContent || '{}') : {};
  } catch (e) {
    urls = {};
  }

  function preview(wrap) {
    var native = wrap.querySelector('select');
    if (!native) {
      return;
    }
    var box = document.createElement('span');
    box.className = 'media-preview';
    box.setAttribute('aria-hidden', 'true');
    var img = document.createElement('img');
    img.alt = '';
    img.decoding = 'async';
    box.appendChild(img);
    wrap.appendChild(box);
    function update() {
      var url = urls[native.value];
      if (url) {
        img.src = url;
        box.hidden = false;
      } else {
        img.removeAttribute('src');
        box.hidden = true;
      }
    }
    native.addEventListener('change', update);
    // The custom listbox updates the native select; follow its visible value as well.
    var shown = wrap.querySelector('[data-select-value]');
    if (shown && 'MutationObserver' in window) {
      new MutationObserver(function () { setTimeout(update, 0); }).observe(shown, { childList: true, characterData: true, subtree: true });
    }
    wrap.addEventListener('click', function () { setTimeout(update, 0); });
    update();
  }

  Array.prototype.forEach.call(document.querySelectorAll('[data-media-preview]'), preview);
})();
