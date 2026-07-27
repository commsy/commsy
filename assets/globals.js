import $ from 'jquery';

// Expose jQuery for legacy inline scripts in Twig templates. Assign via
// globalThis: Encore's autoProvidejQuery() (ProvidePlugin) rewrites
// `window.jQuery` even as an assignment target, which would silently create
// a module-local variable instead of the real global. This module must be
// imported before anything that relies on the global.
globalThis.$ = globalThis.jQuery = $;
