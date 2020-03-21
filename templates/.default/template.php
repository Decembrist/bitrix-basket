<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>
<?php if (count($arResult['ITEMS'])): ?>
    <div class="row">
        <div class="col">
            <div class="cart__top-container">
                <a href="#" class="cart__button js-cart-clear">
                    <span class="cart__button-text">Очистить корзину</span>
                </a>
            </div>
        </div>
    </div>
    <div class="row" style="margin-bottom: 20px">
        <div class="col-lg-3 order-lg-last">
            <div class="cart__fly">
                <div class="cart__fly-title">Ваш заказ</div>
                <div class="cart__fly-top">
                    <div>
                        <span class="cart__fly-top-text">Всего товаров</span>
                        <span class="cart__fly-top-num"><?= $arResult['COUNT_ITEMS'] ?>&nbsp;шт.</span>
                    </div>
                    <div>
                        <span class="cart__fly-top-text">Сумма</span>
                        <span class="cart__fly-top-num"><?= $arResult['ALL_PRICE_FORMAT'] ?></span>
                    </div>
                </div>
                <div class="cart__fly-bottom">
                    <div>
                    <span class="cart__fly-bottom-text">
                        К оплате
                    </span>
                        <span class="cart__fly-bottom-price">
                        <?= $arResult['ALL_PRICE_FORMAT'] ?>
                    </span>
                    </div>
                </div>
                <a href="<?= $arParams['LINK_ORDER'] ?>"
                   class="button button--size-m button--full-w button--color-purple button--theme-simple">
                    <span class="button__text">Оформить заказ</span>
                </a>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="cart__items">
                <? foreach ($arResult['ITEMS'] as $arItem): ?>
                    <div class="cart__items--item js-cart-item js-cart-item-<?= $arItem['BASKET_ID'] ?>"
                         data-id="<?= $arItem['BASKET_ID'] ?>">
                        <div class="cart__items--top">
                            <div class="cart__items--image-container">
                                <img class="cart__items--image" src="<?= $arItem['PICTURE']['src'] ?>" alt="">
                            </div>
                            <div class="cart__items--text-container">
                                <div class="cart__items--text-main">
                                    <a href="<?= $arItem['DETAIL_PAGE_URL'] ?>"><?= $arItem['NAME'] ?></a>
                                </div>
                                <?if($arItem['ARTICLE']):?>
                                <div class="cart__items--text-art"><?=$arItem['ARTICLE']?></div>
                                <?endif;?>
                            </div>
                            <div class="cart__items--count-container">
                                <div class="cart__items--count-wrapper">
                                    <div class="js-cart-remove-<?= $arItem['BASKET_ID'] ?>">-</div>
                                    <input class="js-cart-change-<?= $arItem['BASKET_ID'] ?>" type="text"
                                           value="<?= $arItem['QUANTITY'] ?>">
                                    <div class="js-cart-add-<?= $arItem['BASKET_ID'] ?>">+</div>
                                </div>
                            </div>
                            <div class="cart__items--price-container">
                                <div class="cart__items--price">
                                    <?= $arItem['PRICE_FORMAT'] ?>
                                </div>
                            </div>
                            <div class="cart__items--buttons-container">
                                <div class="cart__items--actions">
                                    <a href="#" class="js-cart-delete-<?= $arItem['BASKET_ID'] ?>">
                                        <i class="fa fa-ban" aria-hidden="true"></i>
                                    </a>
                                    <a href="/cart/?ADD_WISH_PRODUCT=<?= $arItem['PRODUCT_ID'] ?>">
                                        <? if (in_array($arItem['PRODUCT_ID'], $arParams['AR_WISH_LIST'])): ?>
                                            <i class="fa fa-bookmark" aria-hidden="true"></i>
                                        <? else: ?>
                                            <i class="fa fa-bookmark-o" aria-hidden="true"></i>
                                        <? endif; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="cart__items--bottom">
                            <div class="cart__items--total">
                                <span class="cart__items--total-text">Сумма:</span>
                                <span class="cart__items--total-sum"><?= $arItem['ALL_PRICE_FORMAT'] ?></span>
                            </div>
                        </div>
                    </div>
                <? endforeach; ?>
            </div>
        </div>
    </div>

<?php else: ?>
    <p>Корзина пуста</p>
<?php endif; ?>
<?php

$signer = new \Bitrix\Main\Security\Sign\Signer;
$signedTemplate = $signer->sign($templateName, 'okr.basket.page');
$signedParams = $signer->sign(base64_encode(serialize($arParams)), 'okr.basket.page');
?>
<script>
    window.okrParamsBasket = {
        items: Object.values(<?=CUtil::PhpToJSObject($arResult['ITEMS'], false, false, true)?>),
        container: '<?=CUtil::JSEscape($arParams['CONTAINER'])?>',
        templateFolder: '<?=CUtil::JSEscape($templateFolder)?>',
        template: '<?=CUtil::JSEscape($signedTemplate)?>',
        signedParamsString: '<?=CUtil::JSEscape($signedParams)?>',
        customEvent: '<?=CUtil::JSEscape("refreshSmallBasket")?>'
    };
</script>
