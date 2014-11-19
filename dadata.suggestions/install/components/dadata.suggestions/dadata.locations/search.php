<?
define("STOP_STATISTICS", true);
define("PUBLIC_AJAX_MODE", true);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);

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
                "ID", "CITY_ID", "CITY_NAME", "COUNTRY_NAME_LANG", "REGION_NAME_LANG"
            )
        );
        while ($arCity = $rsLocationsList->GetNext()) {
            $arResult[] = array(
                "ID" => $arCity["ID"],
                "NAME" => $arCity["CITY_NAME"],
                "REGION_NAME" => $arCity["REGION_NAME_LANG"],
                "COUNTRY_NAME" => $arCity["COUNTRY_NAME_LANG"],
            );

        }
        if (sizeof($arResult) == 0) {
            // Для городов федерального значения, имеет смысл попробовать еще поискать
            if ($region == "Москва")
                $filter["~REGION_NAME"] = "Московская%";
            if ($region == "Санкт-Петербург")
                $filter["~REGION_NAME"] = "Ленинградская%";
            if ($region == "Севастополь")
                $filter["~REGION_NAME"] = "Крым%";
            if ($region == "Байконур") {
                unset($filter["~REGION_NAME"]);
                $filter["~COUNTRY_NAME"] = "Казахстан%";
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
                    "ID", "CITY_ID", "CITY_NAME", "COUNTRY_NAME_LANG", "REGION_NAME_LANG"
                )
            );
            while ($arCity = $rsLocationsList->GetNext()) {
                $arResult[] = array(
                    "ID" => $arCity["ID"],
                    "NAME" => $arCity["CITY_NAME"],
                    "REGION_NAME" => $arCity["REGION_NAME_LANG"],
                    "COUNTRY_NAME" => $arCity["COUNTRY_NAME_LANG"],
                );

            }

        }


    }
}

echo CUtil::PhpToJSObject($arResult);

require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_after.php");
die();

?>