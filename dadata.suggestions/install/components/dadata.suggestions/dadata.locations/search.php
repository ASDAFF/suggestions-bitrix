<?
define("STOP_STATISTICS", true);
define("PUBLIC_AJAX_MODE", true);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);

IncludeModuleLangFile(__FILE__);

$arResult = array();

if (\Bitrix\Main\Loader::includeModule('sale')) {
    if ((!empty($_REQUEST["city"]) && is_string($_REQUEST["city"])) ||
        (!empty($_REQUEST["region"]) && is_string($_REQUEST["region"])) ||
        (!empty($_REQUEST["country"]) && is_string($_REQUEST["country"]))
    ) {

        $city = $APPLICATION->UnJSEscape($_REQUEST["city"]);
        $region = $APPLICATION->UnJSEscape($_REQUEST["region"]);
        $country = $APPLICATION->UnJSEscape($_REQUEST["country"]);


        $arParams = array();
        $params = explode(",", $_REQUEST["params"]);
        foreach ($params as $param) {
            list($key, $val) = explode(":", $param);
            $arParams[$key] = $val;
        }


        $filter["~CITY_NAME"] = $city . "%";
        $filter["~COUNTRY_NAME"] = $country . "%";
        $filter["~REGION_NAME"] = $region . "%";
        $filter["LID"] = LANGUAGE_ID;

        $rsLocationsList = CSaleLocation::GetList(
            array(
                "CITY_NAME_LANG" => "ASC",
                "COUNTRY_NAME_LANG" => "ASC",
                "SORT" => "ASC",
            ),
            $filter,
            false,
            array("nTopCount" => 10),
            array(
                "ID", "CITY_ID", "CITY_NAME", "COUNTRY_NAME_LANG", "REGION_NAME_LANG", "COUNTRY_ID" , "REGION_ID"
            )
        );
        while ($arCity = $rsLocationsList->GetNext()) {
            $arResult[] = array(
                "ID" => $arCity["ID"],
                "NAME" => $arCity["CITY_NAME"],
                "REGION_NAME" => $arCity["REGION_NAME_LANG"],
                "REGION_ID" => $arCity["REGION_ID"],
                "COUNTRY_NAME" => $arCity["COUNTRY_NAME_LANG"],
                "COUNTRY_ID" => $arCity["COUNTRY_ID"],
            );

        }
        if (sizeof($arResult) == 0) {
            // Для городов федерального значения, имеет смысл попробовать еще поискать
            if ($region == GetMessage("DADATA_SUGGESTIONS_MOSKVA"))
                $filter["~REGION_NAME"] = GetMessage("DADATA_SUGGESTIONS_MOSKOVSKAA");
            if ($region == GetMessage("DADATA_SUGGESTIONS_SANKT_PETERBURG"))
                $filter["~REGION_NAME"] = GetMessage("DADATA_SUGGESTIONS_LENINGRADSKAA");
            if ($region == GetMessage("DADATA_SUGGESTIONS_SEVASTOPOLQ"))
                $filter["~REGION_NAME"] = GetMessage("DADATA_SUGGESTIONS_KRYM");
            if ($region == GetMessage("DADATA_SUGGESTIONS_BAYKONUR")) {
                unset($filter["~REGION_NAME"]);
                $filter["~COUNTRY_NAME"] = GetMessage("DADATA_SUGGESTIONS_KAZAHSTAN");
            }

            $filter["~CITY_NAME"] = $city . "%";
            $filter["LID"] = LANGUAGE_ID;


            $rsLocationsList = CSaleLocation::GetList(
                array(
                    "CITY_NAME_LANG" => "ASC",
                    "COUNTRY_NAME_LANG" => "ASC",
                    "SORT" => "ASC",
                ),
                $filter,
                false,
                array("nTopCount" => 10),
                array(
                    "ID", "CITY_ID", "CITY_NAME", "COUNTRY_NAME_LANG", "REGION_NAME_LANG", "COUNTRY_ID", "REGION_ID"
                )
            );
            while ($arCity = $rsLocationsList->GetNext()) {
                $arResult[] = array(
                    "ID" => $arCity["ID"],
                    "NAME" => $arCity["CITY_NAME"],
                    "REGION_NAME" => $arCity["REGION_NAME_LANG"],
                    "REGION_ID" => $arCity["REGION_ID"],
                    "COUNTRY_NAME" => $arCity["COUNTRY_NAME_LANG"],
                    "COUNTRY_ID" => $arCity["COUNTRY_ID"],
                );

            }

        }


    }
}

echo CUtil::PhpToJSObject($arResult);

require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_after.php");
die();

?>