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

    public static function fieldSelector($fieldNo)
    {
        return "'[name=ORDER_PROP_" . $fieldNo . "]'";

    }

    private static function isNewLocationModule()
    {
        if (!method_exists(CSaleLocation, "isLocationProEnabled"))
            return false;
        return CSaleLocation::isLocationProEnabled();
    }

    public static function GetPartParameterString($SuggestionType, $ParamName)
    {
        if ($SuggestionType == 'NAME') {
            if ($ParamName == 'data.name')
                return "params: { parts:['NAME']},";
            if ($ParamName == 'data.surname')
                return "params: { parts:['SURNAME']},";
            if ($ParamName == 'data.patronymic')
                return "params: { parts:['PATRONYMIC']},";
        } elseif ($SuggestionType == 'ADDRESS') {
            if (in_array($ParamName, array('data.region', 'data.country', 'data.area')))
                return "bounds: 'region-area',";
            if (in_array($ParamName, array('data.city', 'data.settlement')))
                return "bounds: 'city-settlement',";
            if (in_array($ParamName, array('data.street')))
                return "bounds: 'street',";
            if (in_array($ParamName, array('data.house', 'data.block')))
                return "bounds: 'house',";

        }

    }

    private function getMappingObject()
    {
        $arFieldValues = unserialize(COption::GetOptionString(self::$module_id, 'mapping', "", SITE_ID));
        $arGroupMapping = unserialize(COption::GetOptionString(self::$module_id, 'typemapping', "", SITE_ID));
        $newArray = array();
        $boundFields = array();
        foreach ($arFieldValues as $fieldNo => $fieldVal)
            if ($fieldVal) {
                $suggestionType = strstr($fieldVal, '_', true);
                $suggestionVar = substr($fieldVal, strpos($fieldVal, '_') + 1);
                $newArray[$fieldNo]['type'] = $suggestionType;
                foreach ($arGroupMapping as $groupId => $groupFields)
                    if (in_array($fieldNo, $groupFields)) {
                        $newArray[$fieldNo]['group'] = $groupId;
                    }


                $newArray[$fieldNo]['var'] = $suggestionVar;
                if ($suggestionType == 'NAME') {
                    if ($suggestionVar == 'data.name')
                        $newArray[$fieldNo]['params'] = "{ parts:['NAME']}";
                    if ($suggestionVar == 'data.surname')
                        $newArray[$fieldNo]['params'] = "{ parts:['SURNAME']}";
                    if ($suggestionVar == 'data.patronymic')
                        $newArray[$fieldNo]['params'] = "{ parts:['PATRONYMIC']}";
                } elseif ($suggestionType == 'ADDRESS') {
                    if (in_array($suggestionVar, array('data.region', 'data.country', 'data.area'))) {
                        $newArray[$fieldNo]['bounds'] = "region-area";
                        if (isset($newArray[$fieldNo]['group']))
                            $boundFields[$newArray[$fieldNo]['group']]['city-settlement'] = $fieldNo;
                    }
                    if (in_array($suggestionVar, array('data.city', 'data.settlement'))) {
                        $newArray[$fieldNo]['bounds'] = "city-settlement";
                        if (isset($newArray[$fieldNo]['group']))
                            $boundFields[$newArray[$fieldNo]['group']]['street'] = $fieldNo;
                    }

                    if (in_array($suggestionVar, array('data.street'))) {
                        $newArray[$fieldNo]['bounds'] = "street";
                        if (isset($newArray[$fieldNo]['group']))
                            $boundFields[$newArray[$fieldNo]['group']]['house'] = $fieldNo;

                    }
                    if (in_array($suggestionVar, array('data.house', 'data.block'))) {

                        $newArray[$fieldNo]['bounds'] = "house";
                    }
                }

            }
        foreach ($newArray as $fieldNo => $fieldProps) {
            $fieldBounds = $fieldProps['bounds'];
            $fieldGroup = $fieldProps['group'];
            if (isset($fieldBounds) && isset($fieldGroup)) {
                $fieldConstraint = $boundFields[$fieldGroup][$fieldBounds];
                if (isset($fieldConstraint))
                    $newArray[$fieldNo]['constraint'] = $fieldConstraint;
            }
        }

        return CUtil::PhpToJSObject($newArray);
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
            var dadataSuggestions = {
                dadataSearchLocation: function (city, region, country, objectid) {
                    <?if(!self::isNewLocationModule()):?>
                    function getLocationDaData(country_id, region_id, city_id, objectid) {
                        BX.showWait();

                        property_id = objectid;
                        innercity_id = city_id;

                        function getLocationResultDaData(res) {
                            BX.closeWait();

                            var obContainer = document.getElementById('LOCATION_' + property_id);
                            if (obContainer) {
                                obContainer.innerHTML = res;
                                var idObject = BX(objectid);
                                if (idObject != null)
                                    idObject.value = innercity_id;
                            }
                        }

                        var arParams = {
                            'COUNTRY_INPUT_NAME': 'COUNTRY',
                            'REGION_INPUT_NAME': 'REGION',
                            'CITY_INPUT_NAME': objectid,
                            'CITY_OUT_LOCATION': 'Y',
                            'ALLOW_EMPTY_CITY': 'Y',
                            'COUNTRY': parseInt(country_id),
                            'REGION': parseInt(region_id),
                            'SITE_ID': "<?=SITE_ID?>"
                        };

                        var url = '/bitrix/components/bitrix/sale.ajax.locations/templates/.default/ajax.php';
                        BX.ajax.post(url, arParams, getLocationResultDaData)
                    };
                    <?endif?>

                    PShowWaitMessage('wait_container', true);
                    var TID = CPHttpRequest.InitThread();
                    dadataReady = false;
                    CPHttpRequest.SetAction(
                        TID,
                        function (data) {
                            var result = {};

                            eval('result = ' + data);
                            if (result.length > 0) {
                                <?if(self::isNewLocationModule()):?>
                                var idObject = $('[name=' + objectid + ']');
                                if (idObject != null && idObject.length > 0)
                                    idObject.val(result[0]['ID']);
                                <?else:?>
                                var idObject = BX("COUNTRY" + objectid);
                                if (idObject != null)
                                    idObject.value = result[0]['COUNTRY_ID'];
                                idObject = BX.findNextSibling(idObject, {"name": "REGION" + objectid});
                                if (idObject != null) {
                                    idObject.value = result[0]['REGION_ID'];
                                    getLocationDaData(result[0]['COUNTRY_ID'], result[0]['REGION_ID'], result[0]['ID'], objectid)
                                }
                                idObject = BX(objectid);
                                if (idObject != null)
                                    idObject.value = result[0]['ID'];
                                <?endif?>

                                var locationArray = [];
                                if (result[0]['NAME']) locationArray.push(result[0]['NAME']);
                                if (result[0]['REGION_NAME']) locationArray.push(result[0]['REGION_NAME']);
                                if (result[0]['COUNTRY_NAME']) locationArray.push(result[0]['COUNTRY_NAME']);

                                <?if(self::isNewLocationModule()):?>
                                var textObject = $('[name=' + objectid + ']');
                                if (textObject != null && textObject.length > 0) {
                                    textObject = textObject.parent().find('.bx-ui-sls-fake');
                                    if (textObject != null && textObject.length > 0) {
                                        textObject.attr('title', locationArray.join(', '));
                                    }
                                }
                                <?else:?>
                                var textObject = BX(objectid + '_val');
                                if (textObject != null)
                                    textObject.value = locationArray.join(', ');
                                <?endif?>


                            }
                            PCloseWaitMessage('wait_container', true);
                            <?if(self::isNewLocationModule()):?>
                            submitForm();
                            <?endif?>
                            dadataReady = true;
                        }
                    );
                    var url = '/bitrix/components/dadata.suggestions/dadata.locations/search.php';
                    CPHttpRequest.Send(TID, url, {"city": city, "region": region, "country": country, "params": "siteId:<?=SITE_ID?>"});

                },

                fieldSelector: function (id) {
                    return '[name=ORDER_PROP_' + id + ']';
                },
                handleMappingObject: function (id, obj, allFields) {
                    var sugConf = {
                        serviceUrl: '<?=COption::GetOptionString(self::$module_id,'url','',SITE_ID)?>',
                        token: '<?=COption::GetOptionString(self::$module_id,'apikey','',SITE_ID)?>',
                        type: obj.type
                    };
                    if ($(this.fieldSelector(id)).length > 0) {
                        var that = this;
                        switch (obj.var) {
                            case 'LOCATION':
                                break;
                            case 'value':
                                sugConf.onSelect = function (suggestion) {

                                    for (var m in allFields) {
                                        var field = allFields[m];
                                        if (field.type == obj.type) {
                                            if ($(that.fieldSelector(m)).length > 0) {
                                                switch (field.var) {
                                                    case 'value':
                                                        break;
                                                    case 'LOCATION':
                                                        that.dadataSearchLocation((suggestion.data.city ? suggestion.data.city : suggestion.data.settlement), suggestion.data.region, suggestion.data.country, 'ORDER_PROP_' + m);
                                                        break;
                                                    default:
                                                        $(that.fieldSelector(m)).val(eval('suggestion.' + field.var));
                                                        break;
                                                }
                                            }
                                        }
                                    }

                                };

                                $(this.fieldSelector(id)).suggestions(sugConf);


                                break;
                            default:
                                sugConf.onSelect = function (suggestion) {
                                    $(that.fieldSelector(id)).val(eval('suggestion.' + obj.var));
                                };

                                if (obj.bounds) {
                                    sugConf.bounds = obj.bounds;
                                    if (obj.bounds == 'house')
                                        sugConf.onSelect = function (suggestion) {
                                            $(that.fieldSelector(id)).val(eval('suggestion.value'));
                                        };

                                } else {
                                }
                                if (obj.constraint) {
                                    sugConf.constraints = $(this.fieldSelector(obj.constraint));

                                }

                                $(this.fieldSelector(id)).suggestions(sugConf);

                                break;
                        }
                    }

                }
                ,

                initSuggestionFields: function () {
                    mappingObject = <?=self::getMappingObject()?>;
                    for (var m in mappingObject) {
                        this.handleMappingObject(m, mappingObject[m], mappingObject);
                    }

                }
            };

            var personType = $('input[name=PERSON_TYPE_OLD]').val();
            var locationType;

            <? foreach($arFieldValues as $fieldNo => $fieldVal)
            if ($fieldVal=="ADDRESS_LOCATION"):?>
            if ($(<?=CDadataSuggestions::fieldSelector($fieldNo)?>).length > 0) locationType = $(<?=CDadataSuggestions::fieldSelector($fieldNo)?>).val();
            <? endif; ?>

            BX.ready(function () {
                $('#order_form_content').bind("DOMSubtreeModified", function () {
                    var personTypeEl = $('input[name=PERSON_TYPE_OLD]');
                    var locationTypeNew;
                    <? foreach($arFieldValues as $fieldNo => $fieldVal) if ($fieldVal=="ADDRESS_LOCATION"):?>
                    if ($(<?=CDadataSuggestions::fieldSelector($fieldNo)?>).length > 0) locationTypeNew = $(<?=CDadataSuggestions::fieldSelector($fieldNo)?>).val();
                    <? endif; ?>

                    if ((personTypeEl.length > 0 && personTypeEl.val() != personType) || locationTypeNew != locationType) {
                        locationType = locationTypeNew;
                        personType = personTypeEl.val();
                        dadataSuggestions.initSuggestionFields();
                    }
                });

                dadataSuggestions.initSuggestionFields();

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
                // Если используем штатный jquery
                if (COption::GetOptionString(self::$module_id, 'jquery', 'Y', SITE_ID) == 'Y') CJSCore::Init(array('jquery'));

                CDadataSuggestions::setJS();
            }
        }
    }
}