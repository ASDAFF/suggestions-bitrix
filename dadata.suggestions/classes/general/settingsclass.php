<?php

class CDadataSuggestionsSettings
{
    public static $module_id = 'dadata.suggestions';
    private static $suggestionsFields = array(
        'NAME' => array(
            'value' => 'ФИО одной строкой',
            'data.surname' => 'Фамилия',
            'data.name' => 'Имя',
            'data.patronymic' => 'Отчество',
            'data.gender' => 'Пол',
            'data.qc' => 'Код качества'
        ),
        'ADDRESS' => array(
            'value' => 'Адрес одной строкой',
            'data.postal_code' => 'Индекс',
            'data.country' => 'Страна',
            'data.region_type' => 'Тип субъекта (сокращенный)',
            'data.region_type_full' => 'Тип субъекта',
            'data.region' => 'Субъект',
            'data.area_type' => 'Тип района (сокращенный)',
            'data.area_type_full' => 'Тип района',
            'data.area' => 'Район',
            'data.city_type' => 'Тип города (сокращенный)',
            'data.city_type_full' => 'Тип города',
            'data.city' => 'Город',
            'data.settlement_type' => 'Тип населенного пункта (сокращенный)',
            'data.settlement_type_full' => 'Тип населенного пункта',
            'data.settlement' => 'Населенный пункт',
            'data.street_type' => 'Тип улицы (сокращенный)',
            'data.street_type_full' => 'Тип улицы',
            'data.street' => 'Улица',
            'data.house_type' => 'Тип дома (сокращенный)',
            'data.house_type_full' => 'Тип дома',
            'data.house' => 'Дом',
            'data.block_type' => 'Тип расширения дома (корпус / строение / секция)',
            'data.block' => 'Расширение дома',
            'data.flat_type' => 'Тип квартиры (квартира / офис / комната)',
            'data.flat' => 'Номер квартиры',
            'data.postal_box' => 'Абонентский ящик',
            'data.kladr_id' => 'Код КЛАДР',
            'data.okato' => 'Код ОКАТО',
            'data.oktmo' => 'Код ОКТМО',
            'data.tax_office' => 'Код ИФНС (ФЛ)',
            'data.tax_office_legal' => 'Код ИФНС (ЮЛ)',
            'data.flat_area' => 'Площадь квартиры',
            'data.kladr_id' => 'Код КЛАДР',
            'data.fias_id' => 'Код ФИАС',
            'data.qc_complete' => 'Код полноты',
            'data.qc_house' => 'Код проверки дома',
            'data.qc' => 'Код качества',
            'data.unparsed_parts' => 'Нераспознанная часть адреса'
        ),
        'PARTY' => array(
            'value' => 'Наименование организации одной строкой',
            'data.address.value' => 'Адрес организации одной строкой',
            'data.branch_count' => 'Число филиалов',
            'data.branch_type' => 'Тип подразделения',
            'data.inn' => 'ИНН',
            'data.kpp' => 'КПП',
            'data.management.name' => 'ФИО руководителя',
            'data.management.post' => 'Должность руководителя',
            'data.name.full' => 'Полное наименование',
            'data.name.latin' => 'Наименование на латинице',
            'data.name.short' => 'Краткое наименование',
            'data.ogrn' => 'ОГРН',
            'data.okpo' => 'Код ОКПО',
            'data.okved' => 'Код ОКВЭД',
            'data.opf.code' => 'Код ОКОПФ',
            'data.opf.full' => 'Полное название ОПФ',
            'data.opf.short' => 'Краткое название ОПФ',
            'data.state.registration_date' => 'Дата регистрации',
            'data.state.liquidation_date' => 'Дата ликвидации',
            'data.state.status' => 'Статус организации',
            'data.type' => 'Тип организации'
        )
    );

    public static function GetSuggestionsFields() {
        return self::$suggestionsFields;
    }

    public static function GetSettingsArray() {
        $arSaleProps = array();
        if (CModule::IncludeModule('sale')) {
            $rsPersonType = CSalePersonType::GetList(Array('SORT' => 'ASC', 'NAME' => 'ASC'), Array('ACTIVE' => 'Y'));
            while ($rsPersonRow = $rsPersonType->GetNext()) {
                $arSaleProps[] = $rsPersonRow;
            }
            foreach ($arSaleProps as $arSalePropKey => $arSalePropVal) {
                $rsOrderProps = CSaleOrderProps::GetList(array('SORT' => 'ASC'), array('PERSON_TYPE_ID' => $arSalePropVal['ID']));

                while ($rsOrderPropRow = $rsOrderProps->GetNext()) {
                    $arSaleProps[$arSalePropKey]['PROPERTIES'][] = $rsOrderPropRow;
                }
            }

        }
        return $arSaleProps;

    }

    public static function GetMappingFromPost($LId) {
        $arPostFields = array();
        foreach (self::GetSettingsArray() as $arSaleProp){
            if (in_array($LId,$arSaleProp['LIDS'])) {
                foreach ($arSaleProp['PROPERTIES'] as $arSaleField){
                    $arPostFields[$arSaleField['ID']]=$_POST[self::GetFieldName($LId,$arSaleField['ID'])];
                }
            }
        }
        return $arPostFields;
    }

    public static function GetFieldName($LId,$Id){
        return str_replace('.','_',self::$module_id . '_' . $LId . '_' . $Id);
    }
    public static function GetFieldsFromMapping($LId,$Mapping) {
        $arSaleFields = array();
        foreach (self::GetSettingsArray() as $arSaleProp){
            if (in_array($LId,$arSaleProp['LIDS'])) {
                foreach ($arSaleProp['PROPERTIES'] as $arSaleField){
                    $arSaleFields[self::GetFieldName($LId, $arSaleField['ID'])] = $Mapping[$arSaleField['ID']];
                }
            }
        }
        return $arSaleFields;
    }
    public static function TranslateFieldsToOrderFields($LId,$Mapping){
        $orderFields = array();
        foreach ($Mapping as $k => $val) {
            $orderFields[str_replace(str_replace('.','_',self::$module_id.'_'.$LId.'_'),'ORDER_PROP_',$k)] = $val;
        }
        return $orderFields;
    }
    public static function GetFieldNames($LId) {
        $arFieldNames = array();
        foreach (self::GetSettingsArray() as $arSaleProp) {
            if (in_array($LId, $arSaleProp['LIDS'])) {
                foreach ($arSaleProp['PROPERTIES'] as $arSaleField) {
                    $arFieldNames[self::GetFieldName($LId, $arSaleField['ID'])] = $arSaleField['NAME'];
                }
            }
        }
        return $arFieldNames;
    }

    public static function GetPartParameterString($SuggestionType,$ParamName) {
        if ($SuggestionType=='NAME') {
            if ($ParamName == 'data.name')
                return "params: { parts:['NAME']},";
            if ($ParamName == 'data.surname')
                return "params: { parts:['SURNAME']},";
            if ($ParamName == 'data.patronymic')
                return "params: { parts:['PATRONYMIC']},";
        } elseif ($SuggestionType=='ADDRESS') {
            if (in_array($ParamName,array('data.region','data.country','data.area')))
                return "bounds: 'region-area',";
            if (in_array($ParamName, array('data.city', 'data.settlement')))
                return "bounds: 'city-settlement',";
            if (in_array($ParamName,array('data.street')))
                return "bounds: 'street',";
            if (in_array($ParamName,array('data.house','data.block')))
                return "bounds: 'house',";

        }

    }


}