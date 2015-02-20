<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/dadata.suggestions/classes/general/settingsclass.php');
$module_id = CDadataSuggestionsSettings::$module_id;
$POST_RIGHT = $APPLICATION->GetGroupRight('main');

if (!function_exists('htmlspecialcharsbx')) {
    function htmlspecialcharsbx($string, $flags = ENT_COMPAT)
    {
        return htmlspecialchars($string, $flags, (defined('BX_UTF') ? 'UTF-8' : 'ISO-8859-1'));
    }
}


if ($POST_RIGHT >= 'R'):

    IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/options.php');
    IncludeModuleLangFile(__FILE__);

    $arAllOptions = array();

    // Получаем список сайтов
    $arSites = array();


    $rsSites = CSite::GetList($by = 'sort', $order = 'asc', array());
    while ($arRes = $rsSites->GetNext()) {
        $arSites[] = array('ID' => $arRes['ID'], 'NAME' => $arRes['NAME']);
    }
    // Получаем свойства контрагентов
    $arSaleProps = CDadataSuggestionsSettings::GetSettingsArray();
    $tabControl = new CAdmintabControl('tabControl', array(
        array('DIV' => 'edit1', 'TAB' => GetMessage('MAIN_TAB_SET'), 'ICON' => ''),
    ));


    if (ToUpper($REQUEST_METHOD) == 'POST' &&
        strlen($Update . $Apply . $RestoreDefaults) > 0 &&
        ($POST_RIGHT == 'W' || $POST_RIGHT == 'X') &&
        check_bitrix_sessid()
    ) {
        if (strlen($RestoreDefaults) > 0) {
            COption::RemoveOption($module_id);
        } else {
            foreach ($arSites as $arSite) {
                COption::SetOptionString($module_id, 'apikey', $_POST['apikey_' . $arSite['ID']], GetMessage('OPT_APIKEY'), $arSite['ID']);
                COption::SetOptionString($module_id, 'enabled', $_POST['enabled_on_' . $arSite['ID']], GetMessage('OPT_SERVICE_ON'), $arSite['ID']);
                COption::SetOptionString($module_id, 'mapping', serialize(CDadataSuggestionsSettings::GetMappingFromPost($arSite['ID'])), GetMessage('OPT_MAPPING'), $arSite['ID']);
                COption::SetOptionString($module_id, 'url', 'https://dadata.ru/api/v2', GetMessage('OPT_URL'), $arSite['ID']);
                COption::SetOptionString($module_id, 'url_static_js', 'https://dadata.ru/static/js/lib/jquery.suggestions-4.8.min.js', GetMessage('OPT_URL_JS'), $arSite['ID']);
                COption::SetOptionString($module_id, 'url_static_css', 'https://dadata.ru/static/css/lib/suggestions-4.8.css', GetMessage('OPT_URL_CSS'), $arSite['ID']);
            }

        }
        $Update = $Update . $Apply;
        if (strlen($Update) > 0 && strlen($_REQUEST['back_url_settings']) > 0) {
            LocalRedirect($_REQUEST['back_url_settings']);
        } else {
            LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($mid) . '&lang=' . urlencode(LANGUAGE_ID) . '&back_url_settings=' . urlencode($_REQUEST['back_url_settings']) . '&' . $tabControl->ActiveTabParam());
        }
    }


    ?>


    <form method="post" action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($mid) ?>&amp;lang=<?= LANGUAGE_ID ?>"><?
        $tabControl->Begin();
        $tabControl->BeginNextTab();

        ?>
        <tr>
            <td colspan="2"><br/><?
                $aTabs2 = array();
                foreach ($arSites as $arSite) {
                    $aTabs2[] = Array('DIV' => 'stetab' . $arSite['ID'], 'TAB' => '[' . $arSite['ID'] . '] ' . ($arSite['NAME']), 'TITLE' => '[' . $arSite['ID'] . '] ' . ($arSite['NAME']));
                }
                $tabControl2 = new CAdminViewTabControl('tabControl2', $aTabs2);
                $tabControl2->Begin();

                foreach ($arSites as $arSite) {
                    $tabControl2->BeginNextTab();
                    $arFieldDescription = CDadataSuggestionsSettings::GetFieldNames($arSite['ID']);
                    $arFieldValues = CDadataSuggestionsSettings::GetFieldsFromMapping($arSite['ID'], unserialize(COption::GetOptionString($module_id, 'mapping', "", $arSite['ID'])));
                    ?>
                    <table cellspacing="5" cellpadding="0" border="0" width="100%" align="center">
                        <tr>
                            <td align="right" width="300"><label for="enabled_on_<?= $arSite['ID'] ?>"><?= GetMessage('OPT_SERVICE_ON') ?></label>
                            </td>
                            <td>
                                <? $value = COption::GetOptionString($module_id, 'enabled', "", $arSite['ID']); ?>
                                <input type="hidden" name="enabled_on_<?= $arSite['ID'] ?>" value="N">
                                <input type="checkbox" name="enabled_on_<?= $arSite['ID'] ?>" id="enabled_on_<?= $arSite['ID'] ?>"
                                       value="Y"<? if ($value == 'Y') { ?> checked="checked"<? } ?> >
                            </td>
                        </tr>
                        <tr>
                            <td align="right"><label for="apikey_<?= $arSite['ID'] ?>"><?= GetMessage('OPT_APIKEY') ?></label></td>
                            <td>
                                <input type="text" name="apikey_<?= $arSite['ID'] ?>" size="32" id="apikey_<?= $arSite['ID'] ?>"
                                       value="<?= htmlspecialcharsbx(COption::GetOptionString($module_id, 'apikey', "", $arSite['ID'])) ?>">
                            </td>
                        </tr>
                        <? foreach ($arSaleProps as $arSaleType)
                            if (in_array($arSite['ID'], $arSaleType['LIDS'])): ?>
                                <tr class="heading">
                                    <td colspan="2"><?= $arSaleType['NAME'] ?></td>
                                </tr>
                                <? foreach ($arSaleType['PROPERTIES'] as $arSaleProp): ?>
                                    <tr>

                                        <? $fieldName = CDadataSuggestionsSettings::GetFieldName($arSite['ID'], $arSaleProp['ID']); ?>
                                        <td><label for="<?= $fieldName ?>">
                                                <?= $arFieldDescription[$fieldName] ?></label></td>
                                        <td>
                                            <? if ($arSaleProp['TYPE'] == 'LOCATION') { ?>
                                                <?= BeginNote() . GetMessage('OPT_WARN_LOCATION') . EndNote(); ?>
                                                <input type="hidden" name="<?= $fieldName ?>" value="ADDRESS_LOCATION">
                                            <? } else { ?>

                                                <select name="<?= $fieldName ?>" id="<?= $fieldName ?>">
                                                    <option value=""><?= GetMessage('OPT_PROPS_NO_USE') ?></option>
                                                    <? foreach (CDadataSuggestionsSettings::GetSuggestionsFields() as $arSugGroup => $arSugNames): ?>
                                                        <optgroup label="<?= GetMessage('DADATA_SUGGESTIONS_GROUP_' . $arSugGroup . '_NAME') ?>">
                                                            <? if (!empty($arSugNames)): ?>
                                                                <? foreach ($arSugNames as $sugName => $sugComment): ?>
                                                                    <option
                                                                        value="<?= $arSugGroup ?>_<?= $sugName ?>"<? if ($arSugGroup . '_' . $sugName == $arFieldValues[$fieldName]) { ?> selected="selected"<? } ?>><?= $sugComment ?></option>
                                                                <? endforeach; ?>
                                                            <? else: ?>
                                                                <option value="">&lt;<?= GetMessage('OPT_PROPS_NO') ?>&gt;</option>
                                                            <? endif; ?>
                                                        </optgroup>
                                                    <? endforeach; ?>
                                                </select>
                                            <? } ?>
                                        </td>
                                    </tr>
                                <? endforeach; ?>

                            <? endif;
                        ?>
                    </table>
                <?
                }
                $tabControl2->End();
                ?></td>
        </tr><?

        $tabControl->Buttons();
        ?>
        <input <? if ($POST_RIGHT < 'W') echo 'disabled="disabled"' ?> type="submit" class="adm-btn-save" name="Update"
                                                                       value="<?= GetMessage('MAIN_SAVE') ?>"
                                                                       title="<?= GetMessage('MAIN_OPT_SAVE_TITLE') ?>"/>
        <input <? if ($POST_RIGHT < 'W') echo 'disabled="disabled"' ?> type="submit" name="Apply" value="<?= GetMessage('MAIN_OPT_APPLY') ?>"
                                                                       title="<?= GetMessage('MAIN_OPT_APPLY_TITLE') ?>"/>
        <? if (strlen($_REQUEST["back_url_settings"]) > 0): ?>
            <input <? if ($POST_RIGHT < 'W') echo 'disabled="disabled"' ?> type="button" name="Cancel" value="<?= GetMessage('MAIN_OPT_CANCEL') ?>"
                                                                           title="<?= GetMessage('MAIN_OPT_CANCEL_TITLE') ?>"
                                                                           onclick="window.location='<? echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST['back_url_settings'])) ?>'"/>
            <input type="hidden" name="back_url_settings" value="<?= htmlspecialcharsbx($_REQUEST["back_url_settings"]) ?>"/>
        <? endif ?>
        <input <? if ($POST_RIGHT < 'W') echo 'disabled="disabled"' ?> type="submit" name="RestoreDefaults"
                                                                       title="<? echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS") ?>"
                                                                       onclick="confirm('<? echo AddSlashes(GetMessage('MAIN_HINT_RESTORE_DEFAULTS_WARNING')) ?>')"
                                                                       value="<? echo GetMessage('MAIN_RESTORE_DEFAULTS') ?>"/>
        <?= bitrix_sessid_post(); ?>
        <? $tabControl->End(); ?>
    </form>

<? endif; ?>