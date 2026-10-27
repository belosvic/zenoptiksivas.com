/* ============================================================
   ZEN OPTİK — Site davranışları
   ============================================================ */
(function () {
  'use strict';

  const BASE = '/zenoptik.com';
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ---------- Navigasyon ----------
  const nav = document.getElementById('siteNav');
  const onScroll = () => nav && nav.classList.toggle('nav-scrolled', window.scrollY > 40);
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  const burger = document.getElementById('navBurger');
  const links = document.getElementById('navLinks');
  if (burger && links) {
    burger.addEventListener('click', () => {
      const open = links.classList.toggle('open');
      burger.setAttribute('aria-expanded', open);
      document.body.classList.toggle('nav-open', open);
    });
  }

  // ---------- Reveal ----------
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
    });
  }, { threshold: 0.12 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));

  // ---------- Toast ----------
  let toastTimer;
  window.showToast = function (msg) {
    const t = document.getElementById('toast');
    if (!t) return;
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 2600);
  };

  // ---------- Sepet API ----------
  async function cartApi(action, payload) {
    const res = await fetch(BASE + '/cart_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(Object.assign({ action }, payload || {}))
    });
    if (!res.ok) throw new Error('Sepet isteği başarısız');
    return res.json();
  }

  function bumpBadge(count) {
    const b = document.getElementById('cartBadge');
    if (!b) return;
    b.textContent = count;
    b.classList.add('bump');
    setTimeout(() => b.classList.remove('bump'), 320);
  }

  window.addToCart = async function (productId, variationId, qty) {
    try {
      const data = await cartApi('add', { product_id: productId, variation_id: variationId, qty: qty || 1 });
      if (data.ok) {
        bumpBadge(data.count);
        showToast('Sepete eklendi');
      } else {
        showToast(data.error || 'Bir hata oluştu');
      }
      return data;
    } catch (e) {
      showToast('Bağlantı hatası');
      return { ok: false };
    }
  };

  window.updateCartLine = async function (key, qty) {
    try {
      const data = await cartApi('update', { key, qty });
      if (data.ok && data.reload !== false) window.location.reload();
      return data;
    } catch (e) {
      showToast('Bağlantı hatası');
      return { ok: false };
    }
  };

  window.removeCartLine = async function (key) {
    try {
      const data = await cartApi('remove', { key });
      if (data.ok) window.location.reload();
      return data;
    } catch (e) {
      showToast('Bağlantı hatası');
      return { ok: false };
    }
  };

  // ---------- Sepet sayfası mikro etkileşim ----------
  document.querySelectorAll('[data-cart-qty]').forEach(btn => {
    btn.addEventListener('click', () => {
      const wrap = btn.closest('[data-key]');
      if (!wrap) return;
      const input = wrap.querySelector('input');
      let q = parseInt(input.value, 10) + (btn.dataset.cartQty === 'plus' ? 1 : -1);
      q = Math.max(1, Math.min(99, q));
      input.value = q;
      updateCartLine(wrap.dataset.key, q);
    });
  });

  document.querySelectorAll('[data-cart-remove]').forEach(btn => {
    btn.addEventListener('click', () => {
      const wrap = btn.closest('[data-key]');
      if (wrap) removeCartLine(wrap.dataset.key);
    });
  });
})();
