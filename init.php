<?
$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandlerCompatible("sale","OnCondSaleActionsControlBuildList",["OrderDiscount", "GetControlDescr"]);
?>