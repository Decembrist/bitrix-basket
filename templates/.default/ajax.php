<?
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

use Bitrix\Main\Loader;

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter);

if (!check_bitrix_sessid() || !$request->isPost())
    return;

if (!Loader::includeModule('sale') || !Loader::includeModule('catalog'))
    return;

$params = array();

if ($request->get('via_ajax') === 'Y')
{
    $signer = new \Bitrix\Main\Security\Sign\Signer;
    try
    {
        $params = $signer->unsign($request->get('signedParamsString'), 'okr.basket.page');
        $params = unserialize(base64_decode($params));
    }
    catch (\Bitrix\Main\Security\Sign\BadSignatureException $e)
    {
        die('Bad signature.');
    }

    try
    {
        $template = $signer->unsign($request->get('template'), 'okr.basket.page');
    }
    catch (Exception $e)
    {
        $template = '.default';
    }
}

global $APPLICATION;

$APPLICATION->IncludeComponent(
    'okr:basket.page',
    $template,
    $params
);