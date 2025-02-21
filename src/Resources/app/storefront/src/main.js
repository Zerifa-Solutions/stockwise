const PluginManager = window.PluginManager;

PluginManager.register('AlternativeProductsScroll', () => import('./alternative-products/alternative-products-scroll.plugin'), '[data-alternative-products-scroll]');
PluginManager.override('OffCanvasCart', () => import('./offcanvas-cart/offcanvas-cart-extended.plugin'), '[data-off-canvas-cart]');