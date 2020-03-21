<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Engine\Contract\Controllerable;
use \Bitrix\Main\Engine\ActionFilter;
use Bitrix\Sale;

if (!Loader::includeModule('iblock') || !Loader::includeModule('sale') || !Loader::includeModule('catalog')) {
    ShowError(Loc::getMessage('IBLOCK_MODULE_NOT_INSTALLED'));
    return;
}

/**
 * Class LocalBasketPage
 */
class LocalBasketPage extends \CBitrixComponent implements Controllerable
{
    /**
     * @var int
     */
    private $iCartSum = 0;
    /**
     * @var null
     */
    private $arDiscountItem = null;
    /**
     * @var null
     */
    private $oBasketItems = null;
    /**
     * @var null
     */
    private $oBasket = null;

    /**
     * @return array
     */
    public function configureActions()
    {
        return [
            'change' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod(
                        [ActionFilter\HttpMethod::METHOD_POST]
                    )
                ]
            ],
            'clear' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod(
                        [ActionFilter\HttpMethod::METHOD_POST]
                    )
                ]
            ],
        ];
    }

    /**
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     */
    public function clearAction()
    {
        $arIds = [];
        foreach ($this->getItemsObject() as $oItem) {
            $arIds[] = $oItem->getID();
        }
        foreach ($arIds as $id) {
            $this->getCurBasket()->getItemById($id)->delete();
        }
        $this->getCurBasket()->save();

        return [
            'ITEMS' => []
        ];
    }

    /**
     * @param $action
     * @param $id
     * @param int $quantity
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\InvalidOperationException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     */
    public function changeAction($action, $id, $quantity = 1)
    {
        $oItem = $this->getCurBasket()->getItemById($id);
        $bIsRemove = false;
        if ($oItem) {
            $quantityCur = $oItem->getQuantity();
            $quantityNext = 1;
            switch ($action) {
                case 'delete' :
                {
                    $bIsRemove = !$bIsRemove;
                    $this->getCurBasket()->getItemById($id)->delete();
                    break;
                }
                case 'change' :
                {
                    $quantityNext = $quantityCur + $quantity;
                    if ($quantityNext <= 0) {
                        $quantityNext = $quantityCur;
                    }
                    break;
                }
                case 'count' :
                {
                    $quantity = (int)$quantity;
                    if ($quantity) {
                        $quantityNext = $quantity;
                    } else {
                        $quantityNext = 1;
                    }
                    break;
                }
                default:
                {
                    break;
                }
            }
            if (!$bIsRemove) {
                $oItem->setField('QUANTITY', $quantityNext);
            }

            $this->getCurBasket()->save();
        }

        return [
            'ITEMS' => $this->getFormatItems()
        ];
    }

    /**
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\NotImplementedException
     */
    private function getItemsObject()
    {
        if ($this->oBasketItems === null) {
            $this->oBasketItems = $this->getCurBasket()->getBasketItems();
        }

        return $this->oBasketItems;
    }

    /**
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\InvalidOperationException
     * @throws \Bitrix\Main\NotImplementedException
     */
    private function checkAndSetDiscounts()
    {
        $discounts = Sale\Discount::buildFromBasket(
            $this->getCurBasket(),
            new Sale\Discount\Context\Fuser(
                $this->getCurBasket()->getFUserId(true)
            )
        );
        $discounts->calculate();
        $discountResult = $discounts->getApplyResult();
        if (empty($discountResult['PRICES']['BASKET'])) {
            return [];
        }

        $discountResult = $discountResult['PRICES']['BASKET'];

        return $discountResult;
    }

    /**
     * @return Sale\BasketBase
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\NotImplementedException
     */
    private function getCurBasket()
    {
        if ($this->oBasket === null) {
            $this->oBasket = Sale\Basket::loadItemsForFUser(
                Sale\Fuser::getId(),
                Bitrix\Main\Context::getCurrent()->getSite()
            );
        }

        return $this->oBasket;
    }

    /**
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\InvalidOperationException
     * @throws \Bitrix\Main\NotImplementedException
     */
    public function getFormatItems()
    {
        $arItemsIds = [];
        $arBasketItems = [];
        $arDiscount = [];
        if ($this->getCurBasket()->count()) {
            $arDiscount = $this->checkAndSetDiscounts();
        }


        foreach ($this->getItemsObject() as $arItem) {
            $iId = $arItem->getProductId();
            $iPrice = $arItem->getPrice();
            $iQuantity = $arItem->getQuantity();
            $iAllSum = ($iPrice * $iQuantity);
            $iPriceOld = $iPrice;
            $code = $arItem->getBasketCode();
            $bDiscount = false;
            if (!empty($arDiscount[$code]) && (int)$arDiscount[$code]['DISCOUNT']) {
                $iAllSum = $arDiscount[$code]['PRICE'] * $arItem->getQuantity();
                $bDiscount = true;
                $iPrice = $arDiscount[$code]['PRICE'];
            }

            $this->iCartSum += $iAllSum;

            $arItemsIds[] = $iId;

            $arBasketItems[$iId] = [
                'NAME' => $arItem->getField('NAME'),
                'QUANTITY' => $iQuantity,
                'IS_DISCOUNT' => $bDiscount,
                'PRODUCT_ID' => $iId,
                'BASKET_ID' => $arItem->getId(),
                'PRICE' => $iPrice,
                'PRICE_FORMAT' => CurrencyFormat($iPrice, 'RUB'),
                'PRICE_OLD' => $iPriceOld,
                'PRICE_OLD_FORMAT' => CurrencyFormat($iPriceOld, 'RUB'),
                'ALL_PRICE' => $iAllSum,
                'ALL_PRICE_FORMAT' => CurrencyFormat($iAllSum, 'RUB')
            ];
        }
        if (count($arItemsIds)) {
            $oIBItems = \CIBlockElement::GetList(
                [],
                ['=ID' => $arItemsIds],
                false,
                false,
                ['ID', 'PREVIEW_TEXT']
            );
            while ($arRow = $oIBItems->GetNext()) {
                $arBasketItems[$arRow['ID']]['DESCRIPTION'] = $arRow['PREVIEW_TEXT'];
            }
        }

        return $arBasketItems;
    }

    /**
     * @return mixed|void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\InvalidOperationException
     * @throws \Bitrix\Main\NotImplementedException
     */
    public function executeComponent()
    {
        $arBasketItems = $this->getFormatItems();
        $priceAll = $this->iCartSum;

        if (count($arBasketItems)) {
            $this->arResult['ITEMS'] = $arBasketItems;
            $this->arResult['ALL_PRICE'] = $priceAll;
            $this->arResult['ALL_PRICE_FORMAT'] = CurrencyFormat($priceAll, 'RUB');
        }

        $this->includeComponentTemplate();
    }
}
