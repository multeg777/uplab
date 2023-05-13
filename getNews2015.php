<?php

/**
 * Выводит массив новостей из инфоблока id 12, за 2015 год в формате JSON.
 */

use Bitrix\Main;

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

define("NO_AGENT_CHECK", true);
define("DisableEventsCheck", true);
define("NO_KEEP_STATISTIC", true);
define('BX_WITH_ON_AFTER_EPILOG', true);
define('BX_NO_ACCELERATOR_RESET', true);

define('STATISTIC_SKIP_ACTIVITY_CHECK', true);

if (!Main\Loader::includeModule('iblock')) {
    die(Main\Web\Json::encode([]));
}

//todo: можно получать по кодам, но лень
$iblockId = 12;
$authorIblockId = 2;

$dateStart = new Main\Type\DateTime();
$dateStart
    ->setDate(2015, 1, 1)
    ->setTime(0, 0);

$dateEnd = new Main\Type\DateTime();
$dateEnd
    ->setDate(2015, 12, 31)
    ->setTime(23, 59);

/**
 * @todo: можно зайти со стороны CIblockElement::GetList(), но не хочется как-то
 */
$collection = \Bitrix\Iblock\Iblock::wakeUp($iblockId)->getEntityDataClass()::getList([
    'select' => [
        'ID',
        'NAME',
        'CODE',
        'ELEMENT_CODE' => 'CODE',
        'IBLOCK_ID',
        'SECTION_NAME' => 'IBLOCK_SECTION.NAME',
        'SECTION_CODE' => 'IBLOCK_SECTION.CODE',
        'PREVIEW_PICTURE',
        'TAGS',
        'ACTIVE_FROM',
        'AUTHOR_NAME' => 'AUTHOR.ELEMENT.NAME',
        'DETAIL_PAGE_URL' => 'IBLOCK.DETAIL_PAGE_URL'
    ],
    'filter' => [
        '>=ACTIVE_FROM' =>  $dateStart,
        '<=ACTIVE_FROM' =>  $dateEnd,
        'ACTIVE' => 'Y',
    ],
])->fetchAll();

/**
 * @todo: Работать с коллекциями интереснее, но они тяжелые
 */

$news = [];

$proto = Main\Application::getInstance()->getContext()->getRequest()->isHttps() ? "https://" : "http://";
$path = $proto . SITE_SERVER_NAME;

/**
 * @todo: я бы зашел через DTO, но требований таких нет
 */
foreach ($collection as $item) {
    $image = $item['PREVIEW_PICTURE'] ? ($path . CFile::GetPath($item['PREVIEW_PICTURE'])) : '';
    $url = $path . CIBlock::ReplaceDetailUrl($item['DETAIL_PAGE_URL'], $item);
    $news[] = [
        'id' => $item['ID'],
        'url' => $url,
        'image' =>  $image,
        'name' => $item['NAME'],
        'sectionName' => $item['SECTION_NAME'],
        'date' => CIBlockFormatProperties::DateFormat('d F Y H:i', MakeTimeStamp($item['ACTIVE_FROM'], CSite::GetDateFormat())),
        'author' => $item['AUTHOR_NAME'],
        'tags' => explode(',', $item['TAGS'])
    ];
}

die(Main\Web\Json::encode($news));