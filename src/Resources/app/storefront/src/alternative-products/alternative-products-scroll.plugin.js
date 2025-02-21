import Plugin from 'src/plugin-system/plugin.class';

export default class AlternativeProductsScrollPlugin extends Plugin {
    static options = {
        scrollBehavior: 'smooth', displayType: 'crossselling'  // or 'section'
    };

    init() {
        this._registerEvents();
    }

    _registerEvents() {
        this.el.addEventListener('click', this._onButtonClick.bind(this));
    }

    _onButtonClick(e) {
        e.preventDefault();
        debugger;
        const targetId = this.el.getAttribute('href');

        if (!targetId) {
            return;
        }

        if (this.options.displayType === 'crossselling') {
            const tabNav = document.querySelector('#product-detail-cross-selling-tabs');
            if (tabNav) {
                const tabButton = tabNav.querySelector(`[href="${targetId}"]`);
                if (tabButton) {
                    tabButton.click();
                    this._scrollToElement(tabButton);
                }
            }
        } else {
            const targetElement = document.querySelector(targetId);

            if (targetElement) {
                this._scrollToElement(targetElement);
            }
        }
    }

    _scrollToElement(element) {
        const elementPosition = element.getBoundingClientRect().top;
        let offsetPosition = elementPosition + window.scrollY;

        window.scrollTo({
            top: offsetPosition,
            behavior: this.options.scrollBehavior
        });
    }
} 