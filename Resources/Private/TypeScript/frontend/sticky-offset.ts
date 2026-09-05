/**
 * The offset a sticky element needs below a sticky page header, kept current
 * while the header changes height.
 *
 * Both the public detail view of this extension and the profile editor of
 * EXT:academic_persons_edit park an element below the site's fixed header, and
 * both had their own copy of the same measurement. It lives here because the
 * editing extension depends on this one and not the other way round.
 *
 * `apply` is called with the offset in pixels, and with `null` when the page
 * has no fixed header - a site whose header scrolls away needs no offset at all,
 * and the caller then removes whatever it had set.
 */
export const observePageHeaderOffset = (
  pageHeaderSelector: string,
  apply: (offset: number | null) => void,
): void => {
  const pageHeader = document.querySelector(pageHeaderSelector);
  if (!(pageHeader instanceof HTMLElement)) {
    apply(null);
    return;
  }
  const updateOffset = (): void => {
    apply(Math.max(0, Math.ceil(pageHeader.getBoundingClientRect().height)) + 10);
  };
  updateOffset();
  if (typeof ResizeObserver === 'function') {
    const resizeObserver = new ResizeObserver(updateOffset);
    resizeObserver.observe(pageHeader, { box: 'border-box' });
    globalThis.addEventListener('pagehide', (): void => resizeObserver.disconnect(), { once: true });
    return;
  }
  globalThis.addEventListener('resize', updateOffset);
  globalThis.addEventListener(
    'pagehide',
    (): void => globalThis.removeEventListener('resize', updateOffset),
    { once: true },
  );
};
