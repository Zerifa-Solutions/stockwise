import OffcanvasCartPlugin from 'src/plugin/offcanvas-cart/offcanvas-cart.plugin';
import DomAccess from 'src/helper/dom-access.helper';
import Iterator from 'src/helper/iterator.helper';

export default class ZerOffcanvasCartPlugin extends OffcanvasCartPlugin {
    _registerEvents() {
        super._registerEvents();
        this._registerSwitchProductEvents();
    }

    _registerSwitchProductEvents() {
        const switchProducts = DomAccess.querySelectorAll(document, '[data-zer-switch-product]', false);

        if (switchProducts) {
            Iterator.iterate(switchProducts, switchProduct => switchProduct.addEventListener('submit', this._onSwitchProduct.bind(this)));
        }
    }

    _onSwitchProduct(event) {
        event.preventDefault();
        const form = event.target;

        this._fireRequest(form, '.offcanvas-cart');
    }
} 