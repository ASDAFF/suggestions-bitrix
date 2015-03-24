<?
IncludeModuleLangFile(__FILE__);

class dadata_suggestions extends CModule
{

    var $MODULE_ID = "dadata.suggestions";
    public $bNotOutput;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;
    public $MODULE_GROUP_RIGHTS = 'N';
    public $NEED_MAIN_VERSION = '';
    public $NEED_MODULES = array('sale');

    public function __construct()
    {
        $arModuleVersion = array();
        $path = str_replace('\\', '/', __FILE__);
        $path = substr($path, 0, strlen($path) - strlen('/index.php'));
        include($path . '/version.php');
        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }
        $this->PARTNER_NAME = GetMessage('DADATA_SUGGESTIONS_PARTNER_NAME');
        $this->PARTNER_URI = 'http://www.hflabs.ru';
        $this->MODULE_NAME = GetMessage('DADATA_SUGGESTIONS_MODULE_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('DADATA_SUGGESTIONS_MODULE_DESCRIPTION');
        $this->bNotOutput = false;
    }

    public function InstallDB()
    {
        global $APPLICATION, $DB, $DBType, $errors;
        RegisterModuleDependences('sale', 'OnSaleComponentOrderOneStepProcess', $this->MODULE_ID, 'CDadataSuggestions', 'OnSaleComponentOrderOneStepProcess');
        RegisterModule($this->MODULE_ID);
        return true;
    }

    public function UnInstallDB()
    {
        global $APPLICATION, $DB, $DBType, $errors;
        UnRegisterModuleDependences('sale', 'OnSaleComponentOrderOneStepProcess', $this->MODULE_ID, 'CDadataSuggestions', 'OnSaleComponentOrderOneStepProcess');
        UnRegisterModule($this->MODULE_ID);
        return true;
    }

    public function DoInstall()
    {
        global $APPLICATION, $adminPage, $USER, $adminMenu, $adminChain;
        if ($GLOBALS['APPLICATION']->GetGroupRight('main') < 'W') {
            return;
        }
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $this->MODULE_ID . "/install/components/" . $this->MODULE_ID, $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/" . $this->MODULE_ID, true, true);
        if (is_array($this->NEED_MODULES) && !empty($this->NEED_MODULES)) {
            foreach ($this->NEED_MODULES as $module) {
                if (!IsModuleInstalled($module)) {
                    $this->ShowForm('ERROR', GetMessage('DADATA_SUGGESTIONS_NEED_MODULES', array('#MODULE#' => $module)));
                }
            }
        }
        if (strlen($this->NEED_MAIN_VERSION) <= 0 || version_compare(SM_VERSION, $this->NEED_MAIN_VERSION) >= 0) {
            if ($this->InstallDB()) {
                $this->ShowForm('OK', GetMessage('MOD_INST_OK'), 'install');
            } else {
                $strError = '';
                if ($ex = $APPLICATION->GetException()) {
                    $strError = $ex->GetString();
                } else {
                    $strError = GetMessage('DADATA_SUGGESTIONS_UNKNOWN_ERR_INSTALL');
                }
                $this->ShowForm('ERROR', GetMessage('DADATA_SUGGESTIONS_NOT_INSTALL', array('#ERR#' => $strError)));
            }
        } else {
            $this->ShowForm('ERROR', GetMessage('DADATA_SUGGESTIONS_NEED_RIGHT_VER', array('#NEED#' => $this->NEED_MAIN_VERSION)));
        }
    }

    public function DoUninstall()
    {
        global $APPLICATION, $adminPage, $USER, $adminMenu, $adminChain;
        if ($GLOBALS['APPLICATION']->GetGroupRight('main') < 'W') {
            return;
        }
        DeleteDirFilesEx("/bitrix/components/" . $this->MODULE_ID);
        if ($this->UnInstallDB()) {
            $this->ShowForm('OK', GetMessage('MOD_UNINST_OK'));
        } else {
            $strError = '';
            if ($ex = $APPLICATION->GetException()) {
                $strError = $ex->GetString();
            } else {
                $strError = GetMessage('DADATA_SUGGESTIONS_UNKNOWN_ERR_UNINSTALL');
            }
            $this->ShowForm('ERROR', GetMessage('DADATA_SUGGESTIONS_NOT_UNINSTALL', array('#ERR#' => $strError)));
        }
    }

    private function ShowForm($type, $message, $mode = '')
    {
        if ($this->bNotOutput) {
            return;
        }
        global $APPLICATION, $adminPage, $USER, $adminMenu, $adminChain;
        $keys = array_keys($GLOBALS);
        for ($i = 0, $c = count($keys); $i < $c; $i++) {
            if ($keys[$i] != 'i' && $keys[$i] != 'GLOBALS' && $keys[$i] != 'strTitle' && $keys[$i] != 'filepath') {
                global ${$keys[$i]};
            }
        }
        $PathInstall = str_replace('\\', '/', __FILE__);
        $PathInstall = substr($PathInstall, 0, strlen($PathInstall) - strlen('/index.php'));
        IncludeModuleLangFile($PathInstall . '/install.php');
        $GLOBALS['APPLICATION']->SetTitle(GetMessage('DADATA_SUGGESTIONS_MODULE_NAME'));
        include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');
        echo CAdminMessage::ShowMessage(array('MESSAGE' => $message, 'TYPE' => $type));
        ?>
        <form action="<?= $GLOBALS['APPLICATION']->GetCurPage() ?>" method="get">
            <? if ($mode == 'install'): ?>
                <?= GetMessage('DADATA_SUGGESTIONS_MODULE_SETTINGS', array('#LANG#' => LANGUAGE_ID)); ?>
            <? endif; ?>
            <p>
                <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>"/>
                <input type="submit" value="<?= GetMessage('MOD_BACK') ?>"/>
            </p>
        </form>
        <?
        include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
        die();
    }

    private function ShowDataSaveForm()
    {
        global $APPLICATION, $adminPage, $USER, $adminMenu, $adminChain;
        $keys = array_keys($GLOBALS);
        for ($i = 0, $c = count($keys); $i < $c; $i++) {
            if ($keys[$i] != 'i' && $keys[$i] != 'GLOBALS' && $keys[$i] != 'strTitle' && $keys[$i] != 'filepath') {
                global ${$keys[$i]};
            }
        }
        $PathInstall = str_replace('\\', '/', __FILE__);
        $PathInstall = substr($PathInstall, 0, strlen($PathInstall) - strlen('/index.php'));
        IncludeModuleLangFile($PathInstall . '/install.php');
        $GLOBALS['APPLICATION']->SetTitle(GetMessage('DADATA_SUGGESTIONS_MODULE_NAME'));
        include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');
        ?>
        <form action="<?= $GLOBALS['APPLICATION']->GetCurPage() ?>" method="get">
            <?= bitrix_sessid_post() ?>
            <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>"/>
            <input type="hidden" name="id" value="<?= $this->MODULE_ID ?>"/>
            <input type="hidden" name="uninstall" value="Y"/>
            <input type="hidden" name="step" value="2"/>
            <? CAdminMessage::ShowMessage(GetMessage('MOD_UNINST_WARN')) ?>
            <input type="submit" name="inst" value="<? echo GetMessage('MOD_UNINST_DEL') ?>"/>
        </form>
        <?
        include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
        die();
    }
}

?>