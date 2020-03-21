document.addEventListener('DOMContentLoaded', e => {
    (function (paramsBasket) {
        class OkrBasketClass {
            constructor(params) {
                this.items = params.items || [];
                this.container = params.container;
                this.template = params.template;
                this.loaderClass = 'cart__loader';
                this.signedParamsString = params.signedParamsString;
                this.ajaxUrl = `${params.templateFolder}/ajax.php`;
                this.customEvent = params.customEvent;
                this.item = '.js-cart-item-';
                this.add = '.js-cart-add-';
                this.remove = '.js-cart-remove-';
                this.change = '.js-cart-change-';
                this.delete = '.js-cart-delete-';
                this.clear = '.js-cart-clear';
            }

            init() {
                this.bindEvents();
            }

            loaderClose() {
                let _loader = document.querySelector(`.${this.loaderClass}`);
                let _container = document.querySelector(this.container);
                _container.classList.remove('cart__loader-container');
                if (_loader) {
                    _loader.remove();
                }
            }

            loaderOpen() {
                let _loader = document.createElement('div');
                let _container = document.querySelector(this.container);
                _loader.classList.add(this.loaderClass);
                _container.append(_loader);
                _container.classList.add('cart__loader-container');
            }

            bindEvents() {
                let _this = this;
                let clearBasket = document.querySelector(this.clear);

                this.items.forEach((item) => {
                    let addItem = document.querySelector(this.add + item.BASKET_ID),
                        removeItem = document.querySelector(this.remove + item.BASKET_ID),
                        changeItem = document.querySelector(this.change + item.BASKET_ID),
                        deleteItem = document.querySelector(this.delete + item.BASKET_ID);

                    addItem.addEventListener('click', function () {
                        _this.ajaxTemplate('change', item.BASKET_ID, 1)
                    });
                    removeItem.addEventListener('click', function () {
                        _this.ajaxTemplate('change', item.BASKET_ID, -1)
                    });
                    changeItem.addEventListener('change', function (e) {
                        _this.ajaxTemplate('count', item.BASKET_ID, e.currentTarget.value)
                    });
                    deleteItem.addEventListener('click', function (e) {
                        e.preventDefault();
                        _this.ajaxTemplate('delete', item.BASKET_ID)
                    });
                });

                if (clearBasket) {
                    clearBasket.addEventListener('click', e => {
                        e.preventDefault();
                        _this.loaderOpen();
                        BX.ajax.runComponentAction('okr:basket.page', 'clear', {
                            mode: 'class'
                        }).then(response => {
                            _this.refreshItems(response.data.ITEMS);
                            _this.loadCustomEvent();
                            _this.getTemplate();
                        });
                    });
                }
            }

            getTemplate() {
                let _this = this;
                BX.ajax({
                    method: 'POST',
                    url: _this.ajaxUrl,
                    data: _this.getData(),
                    onsuccess: BX.delegate(function (result) {
                        let _container = document.querySelector(_this.container);
                        _container.innerHTML = result;
                        _this.loaderClose();
                        _this.init();
                    })
                });
            }

            ajaxTemplate(action, id, quantity = 1) {
                let _this = this;
                _this.loaderOpen();
                BX.ajax.runComponentAction('okr:basket.page', 'change', {
                    mode: 'class',
                    data: {
                        action,
                        id,
                        quantity
                    }
                }).then(response => {
                    _this.refreshItems(response.data.ITEMS);
                    _this.loadCustomEvent();
                    _this.getTemplate();
                });
            }

            refreshItems(items) {
                this.items = typeof items === 'object' ? Object.values(items) : items;
            }

            loadCustomEvent() {
                document.dispatchEvent(new CustomEvent(this.customEvent));
            }

            getData(data) {
                data = data || {};
                data.via_ajax = 'Y';
                data.sessid = BX.bitrix_sessid();
                data.template = this.template;
                data.signedParamsString = this.signedParamsString;

                return data;
            }
        }

        var OkrBasket = new OkrBasketClass(paramsBasket);
        OkrBasket.init();
    })(window.okrParamsBasket);
});


