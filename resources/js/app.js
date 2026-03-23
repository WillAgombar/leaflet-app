import './bootstrap';

const PAGE_NAV_SELECTOR = '[data-mobile-nav-link]';
const prefetchedUrls = new Set();

const isModifierClick = (event) => {
    return event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;
};

const isSamePageNavigation = (targetUrl) => {
    return targetUrl.pathname === window.location.pathname && targetUrl.search === window.location.search;
};

const prefetchUrl = (href) => {
    if (!href || prefetchedUrls.has(href)) {
        return;
    }

    prefetchedUrls.add(href);

    const prefetchLink = document.createElement('link');
    prefetchLink.rel = 'prefetch';
    prefetchLink.as = 'document';
    prefetchLink.href = href;
    document.head.append(prefetchLink);
};

const markPageReady = () => {
    document.body.classList.add('is-ready');
    document.body.classList.remove('is-leaving');
};

const attachPageNavigation = () => {
    document.querySelectorAll(PAGE_NAV_SELECTOR).forEach((link) => {
        const href = link.getAttribute('href');

        link.addEventListener('pointerenter', () => prefetchUrl(href), { passive: true });
        link.addEventListener('touchstart', () => prefetchUrl(href), { passive: true });

        link.addEventListener('click', (event) => {
            if (isModifierClick(event) || !href || href.startsWith('#')) {
                return;
            }

            const targetUrl = new URL(href, window.location.href);

            if (targetUrl.origin !== window.location.origin || isSamePageNavigation(targetUrl)) {
                return;
            }

            event.preventDefault();
            prefetchUrl(targetUrl.href);
            document.body.classList.add('is-leaving');

            window.setTimeout(() => {
                window.location.assign(targetUrl.href);
            }, 90);
        });
    });
};

window.addEventListener('pageshow', markPageReady);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        markPageReady();
        attachPageNavigation();
    });
} else {
    markPageReady();
    attachPageNavigation();
}
