<?php
/**
 * Plugin Name: Brighter Tracking (Meetings + Forms)
 * Description: Tracks meeting button impressions/clicks and form start/submit events for GA4.
 * Author: Brighter Websites
 * Version: 1.0
 */

if (!defined('ABSPATH')) exit;

add_action('wp_footer', function () {
    ?>
    <script>
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
                            'event_category': 'Meetings',
                            'event_label': `${label} - ${service}`,
                            'page_title': document.title,
                            'page_path': window.location.pathname
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
                                    'event_category': 'Meetings Impressions',
                                    'event_label': `${label} - ${service}`,
                                    'page_title': document.title,
                                    'page_path': window.location.pathname
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

            // Track when user starts filling
            form.addEventListener('input', function () {
                if (!started) {
                    if (typeof gtag === 'function') {
                        gtag('event', 'form_start', {
                            'event_category': 'Forms',
                            'event_label': formId,
                            'page_title': document.title,
                            'page_path': window.location.pathname
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
                        'event_category': 'Forms',
                        'event_label': formId,
                        'page_title': document.title,
                        'page_path': window.location.pathname
                    });
                    console.log(`[GA4] Form submitted: ${formId}`);
                }
            });
        });
    });
    </script>
    <?php
});


// Labrika Bots Allow Whitelist IP

function allow_my_ips($allow, $ip) {
    $allowed_ips = array('178.32.114.61', '162.55.244.68');
    if (in_array($ip, $allowed_ips)) {
        $allow = true;
    }
    return $allow;
}
add_filter('block_ips', 'allow_my_ips', 10, 2);

