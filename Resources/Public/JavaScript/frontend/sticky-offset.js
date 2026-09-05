/* Generated from Resources/Private/TypeScript — do not edit. */
const observePageHeaderOffset = (pageHeaderSelector, apply) => {
  const pageHeader = document.querySelector(pageHeaderSelector);
  if (!(pageHeader instanceof HTMLElement)) {
    apply(null);
    return;
  }
  const updateOffset = () => {
    apply(Math.max(0, Math.ceil(pageHeader.getBoundingClientRect().height)) + 10);
  };
  updateOffset();
  if (typeof ResizeObserver === "function") {
    const resizeObserver = new ResizeObserver(updateOffset);
    resizeObserver.observe(pageHeader, { box: "border-box" });
    globalThis.addEventListener("pagehide", () => resizeObserver.disconnect(), { once: true });
    return;
  }
  globalThis.addEventListener("resize", updateOffset);
  globalThis.addEventListener(
    "pagehide",
    () => globalThis.removeEventListener("resize", updateOffset),
    { once: true }
  );
};
export {
  observePageHeaderOffset
};
