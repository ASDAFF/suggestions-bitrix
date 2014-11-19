<?php


class CDadataSuggestions
{

    private static $module_id = 'dadata.suggestions';

    public static function request($code)
    {
        return array_key_exists($code, $_REQUEST) ? $_REQUEST[$code] : false;
    }

    public static function request_full($code)
    {
        return array_key_exists($code, $_REQUEST) && strlen($_REQUEST[$code]) ? $_REQUEST[$code] : false;
    }

    public static function application()
    {
        return $GLOBALS['APPLICATION'];
    }

    public static function user()
    {
        return $GLOBALS['USER'];
    }


    /*
     * Выводим необходимый javascript.
     */
    public static function setJS($arErrors = array())
    {
        ?>
        <link href="<?= COption::GetOptionString(self::$module_id, 'url_static_css', 'test', SITE_ID) ?>" type="text/css" rel="stylesheet"/>
        <script type="text/javascript" src="<?= COption::GetOptionString(self::$module_id, 'url_static_js', 'testjs', SITE_ID) ?>"></script>
        <script type="text/javascript">
            <? $arFieldValues = unserialize(COption::GetOptionString(self::$module_id, 'mapping', "", SITE_ID));
            ?>
            var dadataReady = true;
            dadataSearchLocation = function (city, region, country, objectid) {
                PShowWaitMessage('wait_container', true);
                var TID = CPHttpRequest.InitThread();
                dadataReady = false;
                CPHttpRequest.SetAction(
                    TID,
                    function (data) {
                        var result = {};

                        eval('result = ' + data);
                        if (result.length > 0) {
                            BX(objectid).value = result[0]['ID'];

                            var locationArray = [];
                            if (result[0]['NAME']) locationArray.push(result[0]['NAME']);
                            if (result[0]['REGION_NAME']) locationArray.push(result[0]['REGION_NAME']);
                            if (result[0]['COUNTRY_NAME']) locationArray.push(result[0]['COUNTRY_NAME']);
                            BX(objectid + "_val").value = locationArray.join(', ');

                        }
                        PCloseWaitMessage('wait_container', true);
                        dadataReady = true;
                    }
                );
                url = '/bitrix/components/dadata.suggestions/dadata.locations/search.php';
                CPHttpRequest.Send(TID, url, {"city": city, "region": region, "country": country, "params": "siteId:<?=SITE_ID?>"});

            };
            initSuggestionFields = function () {
                <? foreach($arFieldValues as $fieldNo => $fieldVal)
                if ($fieldVal) {

                $suggestionType = strstr($fieldVal,'_',true);
                $suggestionVar = substr($fieldVal,strpos($fieldVal,'_')+1);
                $suggestionParameter =CDadataSuggestionsSettings::GetPartParameterString($suggestionType,$suggestionVar);
                if ($suggestionVar=='value') { ?>

                if ($('#ORDER_PROP_<?=$fieldNo?>').length > 0) $('#ORDER_PROP_<?=$fieldNo?>').suggestions({
                    serviceUrl: '  <?=COption::GetOptionString(self::$module_id,'url','',SITE_ID)?>',
                    token: '<?=COption::GetOptionString(self::$module_id,'apikey','',SITE_ID)?>',
                    type: '<?=$suggestionType?>',
                    onSelect: function (suggestion) {
                        <? foreach ($arFieldValues as $innerFieldNo => $innerFieldVal) {
                            $innerSuggestionType = strstr($innerFieldVal,'_',true);
                            $innerSuggestionVar = substr($innerFieldVal,strpos($innerFieldVal,'_')+1);
                            if ($innerSuggestionType==$suggestionType&&$innerSuggestionVar=='LOCATION') {?>

                        if ($('#ORDER_PROP_<?=$innerFieldNo?>').length > 0) dadataSearchLocation(suggestion.data.city, suggestion.data.region, suggestion.data.country, 'ORDER_PROP_<?=$innerFieldNo?>');

                        <?}}?>

                        <? foreach ($arFieldValues as $innerFieldNo => $innerFieldVal) {
                            $innerSuggestionType = strstr($innerFieldVal,'_',true);
                            $innerSuggestionVar = substr($innerFieldVal,strpos($innerFieldVal,'_')+1);
                            if ($innerSuggestionType==$suggestionType&&!in_array($innerSuggestionVar,array('value','LOCATION'))){?>

                        if ($("#ORDER_PROP_<?=$innerFieldNo?>").length > 0)  $("#ORDER_PROP_<?=$innerFieldNo?>").val(suggestion.<?=$innerSuggestionVar?>);

                        <?}}?>

                    }
                });

                <?} elseif($suggestionVar=='LOCATION') {?>

                <?} elseif($suggestionParameter!=null) {?>

                if ($('#ORDER_PROP_<?=$fieldNo?>').length > 0) $('#ORDER_PROP_<?=$fieldNo?>').suggestions({
                    serviceUrl: '<?=COption::GetOptionString(self::$module_id,'url','',SITE_ID)?>',
                    token: '<?=COption::GetOptionString(self::$module_id,'apikey','',SITE_ID)?>',
                    type: '<?=$suggestionType?>',
                    <?=$suggestionParameter?>

                    onSelect: function (suggestion) {
                        $('#ORDER_PROP_<?=$fieldNo?>').val(suggestion.<?=$suggestionVar?>);
                    }
                });
                <?}}?>
            };

            var personType = $('input[name=PERSON_TYPE_OLD]').val();
            var locationType;

            <? foreach($arFieldValues as $fieldNo => $fieldVal)
            if ($fieldVal=="ADDRESS_LOCATION"):?>
            if ($('#ORDER_PROP_<?=$fieldNo?>').length > 0) locationType = $('#ORDER_PROP_<?=$fieldNo?>').val();
            <? endif; ?>

            BX.ready(function () {
                $('#order_form_content').bind("DOMSubtreeModified", function () {
                    var personTypeEl = $('input[name=PERSON_TYPE_OLD]');
                    var locationTypeNew;
                    <? foreach($arFieldValues as $fieldNo => $fieldVal) if ($fieldVal=="ADDRESS_LOCATION"):?>
                    if ($('#ORDER_PROP_<?=$fieldNo?>').length > 0) locationTypeNew = $('#ORDER_PROP_<?=$fieldNo?>').val();
                    <? endif; ?>

                    if ((personTypeEl.length > 0 && personTypeEl.val() != personType) || locationTypeNew != locationType) {
                        locationType = locationTypeNew;
                        personType = personTypeEl.val();
                        initSuggestionFields();
                    }
                });

                initSuggestionFields();

            });
        </script>
    <?
    }

    /*
     * Чтобы ничего не ломалось при обфускации.
     * stdClass Object ([detail] => Zero balance)
     */
    public static function magicFunction($response)
    {
        if (is_array($response->data)) {
            if (is_array($response->data[0]) && is_object($response->data[0][0])) {
                return (array)$response->data[0][0];
            }
        }
        return array();
    }

    public static function onSaleComponentOrderOneStepProcess(&$arResult, $arUserResult, $arParams)
    {
        if (CDadataSuggestions::request('is_ajax_post') != 'Y') {
            if (COption::GetOptionString(self::$module_id, 'enabled', 'N', SITE_ID) == 'Y') {
                require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/dadata.suggestions/classes/general/settingsclass.php');
                CJSCore::Init(array('jquery'));
                CDadataSuggestions::setJS();
            }
        }
    }
}