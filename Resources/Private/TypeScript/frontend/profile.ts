/**
 * The public profile detail view: the fold-out list of the profile entries,
 * the sticky section navigation kept below the page header, and the
 * Bootstrap ScrollSpy that marks the section in view.
 *
 * One instance per "[data-academic-persons-detail]" root, so a page may carry
 * several profiles. Bootstrap is optional: without "globalThis.bootstrap" the
 * navigation is plain anchor links, everything else works the same.
 */
const profileSelector = '[data-academic-persons-detail]';
const accordionTriggerSelector = '[data-academic-persons-accordion-trigger]';
const stickyNavigationSelector = '[data-academic-persons-sticky-navigation]';
const navigationSelector = '[data-academic-persons-scrollspy-navigation]';
const navigationLinkSelector = `${navigationSelector} a[href^="#"]`;
const pageHeaderSelector = '#page-header';

/**
 * The part of Bootstrap's ScrollSpy this module uses. Bootstrap is loaded by
 * the site, not by this extension, so it is read from the global at runtime
 * and typed here rather than imported.
 */
interface ScrollSpyInstance {
    refresh(): void;
}

interface ScrollSpyConstructor {
    getOrCreateInstance(element: Element, config: { target: Element; rootMargin: string; smoothScroll: boolean }): ScrollSpyInstance;
}

interface ScrollSpyActivateEvent extends Event {
    relatedTarget?: Element | string;
}

const scrollSpyConstructor = (): ScrollSpyConstructor | undefined => {
    const bootstrap: unknown = (globalThis as { bootstrap?: unknown }).bootstrap;
    if (typeof bootstrap !== 'object' || bootstrap === null || !('ScrollSpy' in bootstrap)) {
        return undefined;
    }
    const scrollSpy: unknown = (bootstrap as { ScrollSpy: unknown }).ScrollSpy;
    return typeof scrollSpy === 'function' ? (scrollSpy as unknown as ScrollSpyConstructor) : undefined;
};

const setAccordionState = (button: HTMLButtonElement, expanded: boolean): void => {
    const panelId = button.getAttribute('aria-controls');
    if (panelId === null) {
        return;
    }
    const panel = document.getElementById(panelId);
    if (panel === null) {
        return;
    }
    button.setAttribute('aria-expanded', String(expanded));
    panel.hidden = !expanded;
};

const initializeProfileAccordions = (root: HTMLElement): void => {
    root.querySelectorAll(accordionTriggerSelector).forEach((button): void => {
        if (!(button instanceof HTMLButtonElement) || button.dataset.academicPersonsAccordionInitialized === 'true') {
            return;
        }
        button.dataset.academicPersonsAccordionInitialized = 'true';
        setAccordionState(button, button.getAttribute('aria-expanded') === 'true');
        button.addEventListener('click', (): void => {
            setAccordionState(button, button.getAttribute('aria-expanded') !== 'true');
        });
    });
};

/**
 * Keeps the sticky navigation below a sticky page header and tells the
 * stylesheet the same offset, so an anchor jump lands below the header too.
 */
const updateStickyNavigationOffset = (root: HTMLElement, stickyNavigation: HTMLElement, pageHeader: HTMLElement): void => {
    const headerOuterHeight = Math.max(0, Math.ceil(pageHeader.getBoundingClientRect().height));
    const offset = headerOuterHeight + 10;
    stickyNavigation.style.setProperty('top', `${offset}px`, 'important');
    root.style.setProperty('--academic-persons-detail-scroll-offset', `${offset}px`);
};

const initializeStickyNavigationOffset = (root: HTMLElement): void => {
    const stickyNavigation = root.querySelector(stickyNavigationSelector);
    const pageHeader = document.querySelector(pageHeaderSelector);
    if (!(stickyNavigation instanceof HTMLElement)) {
        return;
    }
    if (!(pageHeader instanceof HTMLElement)) {
        stickyNavigation.style.removeProperty('top');
        root.style.removeProperty('--academic-persons-detail-scroll-offset');
        return;
    }
    const updateOffset = (): void => updateStickyNavigationOffset(root, stickyNavigation, pageHeader);
    updateOffset();
    if (typeof ResizeObserver === 'function') {
        const resizeObserver = new ResizeObserver(updateOffset);
        resizeObserver.observe(pageHeader, { box: 'border-box' });
        globalThis.addEventListener('pagehide', (): void => resizeObserver.disconnect(), { once: true });
        return;
    }
    globalThis.addEventListener('resize', updateOffset);
    globalThis.addEventListener('pagehide', (): void => globalThis.removeEventListener('resize', updateOffset), { once: true });
};

const synchronizeScrollSpyLinks = (root: HTMLElement, relatedTarget: Element | string | undefined): void => {
    const target = typeof relatedTarget === 'string' ? relatedTarget : relatedTarget?.getAttribute('href');
    if (target === undefined || target === null || target === '') {
        return;
    }
    root.querySelectorAll(navigationLinkSelector).forEach((link): void => {
        const active = link.getAttribute('href') === target;
        link.classList.toggle('active', active);
        if (active) {
            link.setAttribute('aria-current', 'true');
            return;
        }
        link.removeAttribute('aria-current');
    });
};

const initializeScrollSpy = (root: HTMLElement): void => {
    const ScrollSpy = scrollSpyConstructor();
    const navigation = root.querySelector(`${stickyNavigationSelector} ${navigationSelector}`)
        ?? root.querySelector(navigationSelector);
    if (ScrollSpy === undefined || !(navigation instanceof HTMLElement)) {
        return;
    }
    root.addEventListener('activate.bs.scrollspy', (event: Event): void => {
        synchronizeScrollSpyLinks(root, (event as ScrollSpyActivateEvent).relatedTarget);
    });
    const scrollSpy = ScrollSpy.getOrCreateInstance(root, {
        target: navigation,
        rootMargin: '0px 0px -40%',
        smoothScroll: false,
    });
    globalThis.addEventListener('load', (): void => scrollSpy.refresh(), { once: true });
};

const initializeProfile = (root: Element): void => {
    if (!(root instanceof HTMLElement) || root.dataset.academicPersonsDetailInitialized === 'true') {
        return;
    }
    root.dataset.academicPersonsDetailInitialized = 'true';
    initializeProfileAccordions(root);
    initializeStickyNavigationOffset(root);
    initializeScrollSpy(root);
};

const initializeProfiles = (): void => {
    document.querySelectorAll(profileSelector).forEach(initializeProfile);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeProfiles, { once: true });
} else {
    initializeProfiles();
}
