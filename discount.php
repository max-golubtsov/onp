<?php
use Bitrix\Main\Context;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\Internals\OrderPropsValueTable;
use Bitrix\Sale\Internals\OrderTable;

class OrderDiscount extends \CSaleActionCtrlAction {

    public static function GetClassName() {
        return __CLASS__;
    }

    public static function GetControlID() {
        return "DiscountPriceType";
    }

    public static function GetShow($arParams) {
        $arControls = static::GetEx();
        $arResult = array(
            'controlgroup' => true,
            'group' => false,
            'label' => 'Правила',
            'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
            'children' => [
                array(
                    'controlId' => static::GetControlID(),
                    'group' => false,
                    'label' => "Количество меньше, чем ",
                    'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
                    'control' => array(
                        "Количество меньше, чем",
                        $arControls["PT"]
                    )
                )
            ]
        );
        return $arResult;
    }

    public static function GetEx($strControlID = false, $boolEx = false) {
        $boolEx = (true === $boolEx ? true : false);
        $arList = [
            "PT" => [
                "JS" => [
                    "id" => "PT",
                    "name" => "extra",
                    "type" => "select",
                    "values" => [
                        "1" => "1",
                        "2" => "2",
                        "3" => "3"
                    ],
                    "defaultText" => "...",
                    "defaultValue" => "",
                    "first_option" => "..."
                ],
                "AT" => [
                    "ID" => "PT",
                    "FIELD_TYPE" => "string",
                    "FIELD_LENGTH" => 255,
                    "MULTIPLE" => "N",
                    "VALIDATE" => "list"
                ]
            ],
        ];

        if (!$boolEx) {
            foreach ($arList as &$arOne) {
                $arOne = $arOne["JS"];
            }
            if (isset($arOne)) {
                unset($arOne);
            }
        }
        return $arList;
    }

    public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false) {
        return __CLASS__ . '::applyDiscount($row,' . $arOneCondition["PT"] . ')';
    }

    public static function applyPDiscount($row, $count) {
        $request = Context::getCurrent()->getRequest();
        global $USER;

        // Если авторизован
        if ($USER->IsAuthorized()) {
            $userId = (int)$USER->getId();
            if ($userId) {
                $orders = OrderTable::getList(['filter' => ['USER_ID' => $userId], 'select' => ['ID']]);
                $orderCount = $orders->getSelectedRowsCount();
                if ($orderCount > $count) {
                    return false;
                }
            }
        }

        // Ищем по номеру телефона
        $prop = OrderPropsTable::getList([
            'filter' => ['IS_PHONE' => 'Y', '=PERSON_TYPE_ID' => 3],
            'cache' => ['ttl' => 3600]
        ])->fetch();

        if (!$prop) {
            return false;
        }

        $phoneId = $prop['ID'];
        $phone = $request->getPost('ORDER_PROP_' . $phoneId);
        if (empty($phone)) {
            $props = $request->getPost('order');
            $phone = $props['ORDER_PROP_' . $phoneId];
        }

        if (!$phone) {
            return false;
        }

        $phone = str_replace(['+', '-', '(', ')'], '', $phone);
        $propVal = OrderPropsValueTable::getList([
            'filter' => [
                'ORDER_PROPS_ID' => $phoneId,
                'VALUE' => $phone
            ],
            'select' => ['ORDER_ID']
        ]);

        $orderCount = $propVal->getSelectedRowsCount();
        return $orderCount < $count;
    }
}
