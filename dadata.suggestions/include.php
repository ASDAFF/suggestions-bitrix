<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/dadata.suggestions/classes/general/dadataclass.php');
if ($_GET['MS_LOG'] == 'N') unset($_SESSION['MS_LOG']);
if ($_GET['MS_LOG'] == 'Y') $_SESSION['MS_LOG'] = 'Y';
?>