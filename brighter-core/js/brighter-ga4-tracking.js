
/**
 * Brighter Websites - GA4 Universal Tracking Script
 * Tracks clicks, impressions, and form engagement using data attributes
 * Auto-applies GA attributes based on CSS selectors
 * Author: Brighter Websites
 * Version: 1.1.0
 * Updated: 2025-08-21
 */

(function () {
  var region = new URLSearchParams(location.search).get('region') || 'zone4-remote';
  if (window.gtag) {
    gtag('set', 'user_properties', { region_id: region });
  }

  // --- SELECTOR-BASED DEFAULTS ---
  const SELECTOR_RULES = [
    { selector: '.ga-form',          attrs: { gaCategory: 'Forms', gaLabel: 'Contact Form' }},
    { selector: '.ga-cta-phone',     attrs: { gaEvent: 'click_phone', gaCategory: 'Contact', gaLabel: 'Phone CTA' }},
    { selector: '.ga-cta-email',     attrs: { gaEvent: 'click_email', gaCategory: 'Contact', gaLabel: 'Email CTA' }},
    { selector: '.ga-cta-main',      attrs: { gaEvent: 'click_main_cta', gaCategory: 'Quote', gaLabel: 'Main CTA' }},
    { selector: '.ga-download-lm',   attrs: { gaEvent: 'download', gaCategory: 'Lead Magnet', gaLabel: 'Download LM' }},
    { selector: '.ga-nav-blog',      attrs: { gaEvent: 'nav_blog', gaCategory: 'Navigation', gaLabel: 'Blog' }},
    { selector: '.ga-nav-folio',     attrs: { gaEvent: 'nav_folio', gaCategory: 'Navigation', gaLabel: 'Portfolio' }},
    { selector: '.ga-nav-product',   attrs: { gaEvent: 'click_product', gaCategory: 'Product', gaLabel: 'Product' }},
    { selector: '.ga-nav-service',   attrs: { gaEvent: 'click_service', gaCategory: 'Service', gaLabel: 'Service' }},
    { selector: '.ga-cta-meeting',   attrs: { gaEvent: 'click', gaCategory: 'Meetings', gaLabel: 'Meeting CTA' }}
  ];

  SELECTOR_RULES.forEach(rule => {
    document.querySelectorAll(rule.selector).forEach(el => {
      Object.entries(rule.attrs).forEach(([key, val]) => {
        if (!el.dataset[key]) el.dataset[key] = val;
      });
      // Auto-insert data-ga-impression
      if (!el.dataset.gaImpression) el.dataset.gaImpression = '';
    });
  });

  // Build GA payload from data attributes
  function buildPayload(el, defaults) {
    var ds = el.dataset || {};
    var p = Object.assign({ region_id: region }, defaults || {});

    if (ds.gaCategory) p.event_category = ds.gaCategory;
    if (ds.gaLabel)    p.event_label    = ds.gaLabel;
    if (ds.value && !isNaN(ds.value)) p.value = parseFloat(ds.value);
    if (ds.currency)   p.currency = ds.currency;
    if (ds.productSize) p.product_size = ds.productSize;
    if (ds.installOption) p.install_option = ds.installOption;

    if (!p.event_label) {
      p.event_label = el.getAttribute('aria-label') || el.textContent.trim() || 'unlabeled';
    }

    return p;
  }

  // Infer default event name based on element context
  function inferredEvent(el) {
    var href = el.getAttribute && el.getAttribute('href') || '';
    if (/^tel:/i.test(href)) return 'click_phone';
    if (/^mailto:/i.test(href)) return 'click_email';
    if (/\.(pdf|docx?|pptx?|xlsx?|zip)$/i.test(href)) return 'download';
    if (/\/blog(\/|$)/i.test(href)) return 'nav_blog';
    return null;
  }

  // CLICK TRACKING
  document.addEventListener('click', function(e) {
    var el = e.target.closest('a, button, [data-ga-event], [data-ga-label], [href^="tel:"], [href^="mailto:"]');
    if (!el || !window.gtag) return;

    var eventName = el.dataset.gaEvent || inferredEvent(el);
    if (!eventName) return;

    var payload = buildPayload(el);

    var href = el.tagName === 'A' ? el.getAttribute('href') : null;
    var newTab = el.hasAttribute('target') && el.getAttribute('target') !== '_self';
    if (href && !newTab && !/^#/.test(href)) {
      e.preventDefault();
      payload.event_callback = function(){ location.href = href; };
      payload.event_timeout  = 200;
    }

    gtag('event', eventName, payload);
  }, true);

  // IMPRESSION TRACKING
  document.querySelectorAll('[data-ga-impression]').forEach(el => {
    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const eventName = el.dataset.gaEvent || 'view_item';
            const payload = buildPayload(el);
            gtag('event', eventName, payload);
            observer.unobserve(el);
          }
        });
      }, { threshold: 0.5 });
      observer.observe(el);
    }
  });

  // FORM START + SUBMIT TRACKING
  document.querySelectorAll('form').forEach(form => {
    let started = false;
    const formId = form.getAttribute('id') || 'Unnamed Form';
    const ds = form.dataset || {};
    const category = ds.gaCategory || 'Forms';
    const label = ds.gaLabel || formId;

    form.addEventListener('input', function () {
      if (!started && typeof gtag === 'function') {
        gtag('event', 'form_start', {
          event_category: category,
          event_label: label,
          page_title: document.title,
          page_path: window.location.pathname,
          region_id: region
        });
        started = true;
      }
    });

    form.addEventListener('submit', function () {
      if (typeof gtag === 'function') {
        gtag('event', 'form_submit', {
          event_category: category,
          event_label: label,
          page_title: document.title,
          page_path: window.location.pathname,
          region_id: region
        });
      }
    });
  });

})();
