/* Generated from Resources/Private/TypeScript — do not edit. */
import { observePageHeaderOffset } from "@fgtclb/academic-persons/frontend/sticky-offset.js";
const profileSelector = "[data-academic-persons-detail]";
const accordionTriggerSelector = "[data-academic-persons-accordion-trigger]";
const stickyNavigationSelector = "[data-academic-persons-sticky-navigation]";
const navigationSelector = "[data-academic-persons-scrollspy-navigation]";
const navigationLinkSelector = `${navigationSelector} a[href^="#"]`;
const pageHeaderSelector = "#page-header";
const scrollSpyConstructor = () => {
  const bootstrap = globalThis.bootstrap;
  if (typeof bootstrap !== "object" || bootstrap === null || !("ScrollSpy" in bootstrap)) {
    return void 0;
  }
  const scrollSpy = bootstrap.ScrollSpy;
  return typeof scrollSpy === "function" ? scrollSpy : void 0;
};
const setAccordionState = (button, expanded) => {
  const panelId = button.getAttribute("aria-controls");
  if (panelId === null) {
    return;
  }
  const panel = document.getElementById(panelId);
  if (panel === null) {
    return;
  }
  button.setAttribute("aria-expanded", String(expanded));
  panel.hidden = !expanded;
};
const initializeProfileAccordions = (root) => {
  root.querySelectorAll(accordionTriggerSelector).forEach((button) => {
    if (!(button instanceof HTMLButtonElement) || button.dataset.academicPersonsAccordionInitialized === "true") {
      return;
    }
    button.dataset.academicPersonsAccordionInitialized = "true";
    setAccordionState(button, button.getAttribute("aria-expanded") === "true");
    button.addEventListener("click", () => {
      setAccordionState(button, button.getAttribute("aria-expanded") !== "true");
    });
  });
};
const initializeStickyNavigationOffset = (root) => {
  const stickyNavigation = root.querySelector(stickyNavigationSelector);
  if (!(stickyNavigation instanceof HTMLElement)) {
    return;
  }
  observePageHeaderOffset(pageHeaderSelector, (offset) => {
    if (offset === null) {
      stickyNavigation.style.removeProperty("top");
      root.style.removeProperty("--academic-persons-detail-scroll-offset");
      return;
    }
    stickyNavigation.style.setProperty("top", `${offset}px`, "important");
    root.style.setProperty("--academic-persons-detail-scroll-offset", `${offset}px`);
  });
};
const synchronizeScrollSpyLinks = (root, relatedTarget) => {
  const target = typeof relatedTarget === "string" ? relatedTarget : relatedTarget == null ? void 0 : relatedTarget.getAttribute("href");
  if (target === void 0 || target === null || target === "") {
    return;
  }
  root.querySelectorAll(navigationLinkSelector).forEach((link) => {
    const active = link.getAttribute("href") === target;
    link.classList.toggle("active", active);
    if (active) {
      link.setAttribute("aria-current", "true");
      return;
    }
    link.removeAttribute("aria-current");
  });
};
const initializeScrollSpy = (root) => {
  const ScrollSpy = scrollSpyConstructor();
  const navigation = root.querySelector(`${stickyNavigationSelector} ${navigationSelector}`) ?? root.querySelector(navigationSelector);
  if (ScrollSpy === void 0 || !(navigation instanceof HTMLElement)) {
    return;
  }
  root.addEventListener("activate.bs.scrollspy", (event) => {
    synchronizeScrollSpyLinks(root, event.relatedTarget);
  });
  const scrollSpy = ScrollSpy.getOrCreateInstance(root, {
    target: navigation,
    rootMargin: "0px 0px -40%",
    smoothScroll: false
  });
  globalThis.addEventListener("load", () => scrollSpy.refresh(), { once: true });
};
const initializeProfile = (root) => {
  if (!(root instanceof HTMLElement) || root.dataset.academicPersonsDetailInitialized === "true") {
    return;
  }
  root.dataset.academicPersonsDetailInitialized = "true";
  initializeProfileAccordions(root);
  initializeStickyNavigationOffset(root);
  initializeScrollSpy(root);
};
const initializeProfiles = () => {
  document.querySelectorAll(profileSelector).forEach(initializeProfile);
};
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initializeProfiles, { once: true });
} else {
  initializeProfiles();
}
