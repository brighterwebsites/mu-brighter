
/**
 * Brighter Websites - Frontend Tracking
 * Description: Tracks meeting button clicks, form interactions, scroll depth, etc.
 * Author: Brighter Websites
 * Version: 1.0.0
 * Last Updated: 2025-08-21
 */

/**
 * Brighter Websites - Frontend Tracking Script
 * Tracks meeting buttons, forms, scroll depth, outbound links, and file downloads using GA4
 * Author: Brighter Websites
 * Version: 1.0.0
 * Last Updated: 2025-08-21
 */

document.addEventListener('DOMContentLoaded', function () {

    /**
     * MEETING BUTTON TRACKING (Impressions + Clicks)
     */
    const meetingButtons = document.querySelectorAll('[data-track="meeting"]');
    if (meetingButtons.length > 0) {
        // Track Clicks
        meetingButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const label = btn.dataset.label || 'Meeting Button';
                const service = btn.dataset.service || 'Unknown Service';
                if (typeof gtag === 'function') {
                    gtag('event', 'click', {
                        event_category: 'Meetings',
                        event_label: `${label} - ${service}`,
                        page_title: document.title,
                        page_path: window.location.pathname
                    });
                    console.log(`[GA4] Meeting button clicked: ${label} - ${service}`);
                }
            });
        });

        // Track Impressions
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const btn = entry.target;
                        const label = btn.dataset.label || 'Meeting Button';
                        const service = btn.dataset.service || 'Unknown Service';
                        if (typeof gtag === 'function') {
                            gtag('event', 'view_item', {
                                event_category: 'Meetings Impressions',
                                event_label: `${label} - ${service}`,
                                page_title: document.title,
                                page_path: window.location.pathname
                            });
                            console.log(`[GA4] Meeting button seen: ${label} - ${service}`);
                        }
                        observer.unobserve(btn);
                    }
                });
            });
            meetingButtons.forEach(btn => observer.observe(btn));
        }
    }

    /**
     * FORM TRACKING (Start + Submit)
     */
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        let started = false;
        const formId = form.getAttribute('id') || 'Unnamed Form';

        // Track form start
        form.addEventListener('input', function () {
            if (!started) {
                if (typeof gtag === 'function') {
                    gtag('event', 'form_start', {
                        event_category: 'Forms',
                        event_label: formId,
                        page_title: document.title,
                        page_path: window.location.pathname
                    });
                    console.log(`[GA4] Form started: ${formId}`);
                }
                started = true;
            }
        });

        // Track form submit
        form.addEventListener('submit', function () {
            if (typeof gtag === 'function') {
                gtag('event', 'form_submit', {
                    event_category: 'Forms',
                    event_label: formId,
                    page_title: document.title,
                    page_path: window.location.pathname
                });
                console.log(`[GA4] Form submitted: ${formId}`);
            }
        });
    });

    /**
     * SCROLL DEPTH TRACKING (50%)
     */
    let scrollTracked50 = false;
    window.addEventListener('scroll', function () {
        const scrollY = window.scrollY + window.innerHeight;
        const docHeight = document.body.offsetHeight;
        if (!scrollTracked50 && scrollY / docHeight > 0.5) {
            scrollTracked50 = true;
            if (typeof gtag === 'function') {
                gtag('event', 'scroll', {
                    event_category: 'Engagement',
                    event_label: 'Scrolled 50%',
                    page_title: document.title,
                    page_path: window.location.pathname
                });
                console.log('[GA4] Scrolled 50% of page');
            }
        }
    });

    /**
     * OUTBOUND LINK TRACKING
     */
    document.querySelectorAll('a[href^="http"]').forEach(link => {
        if (!link.href.includes(location.hostname)) {
            link.addEventListener('click', function () {
                if (typeof gtag === 'function') {
                    gtag('event', 'click_outbound', {
                        event_category: 'Outbound Link',
                        event_label: link.href,
                        page_title: document.title,
                        page_path: window.location.pathname
                    });
                    console.log('[GA4] Outbound link clicked:', link.href);
                }
            });
        }
    });

    /**
     * FILE DOWNLOAD TRACKING
     */
    document.querySelectorAll('a[href$=".pdf"], a[href$=".docx"], a[href$=".xlsx"], a[href$=".zip"]').forEach(link => {
        link.addEventListener('click', function () {
            if (typeof gtag === 'function') {
                gtag('event', 'download', {
                    event_category: 'File Download',
                    event_label: link.href,
                    page_title: document.title,
                    page_path: window.location.pathname
                });
                console.log('[GA4] File downloaded:', link.href);
            }
        });
    });

});
