<?
//Проверяет элементы инфоблока на наличие DETAIL_PICTURE или PREVIEW_PICTURE
//Если есть,то получает их данные и функкцией findImagesByArticle при помощи артикулов составляет список адресов на изображения, лежащие в отдельной папке
//Потом ищет по сформированным адресам изображения в папке и, если находит, удаляет DETAIL_PICTURE и PREVIEW_PICTURE
//nPageSize в getlist ограничивает количество файлов, т.к. время обработки скрипта для всего пака картинок превышает время ожидания браузера и выводится 502

define('NO_AGENT_CHECK', true);             //отключает выполнение всех агентов
define('NO_AGENT_STATISTIC', 'Y');          //запрету некоторых действий модуля "Статистика"
define("NO_KEEP_STATISTIC", true);          //запрет сбора статистики
define("NOT_CHECK_PERMISSIONS",true);       //отключение проверки прав на доступ к файлам и каталогам

$arServer = Array();
$arServer['REMOTE_ADDR'] = 'bx31581.rdock.ru';
$arServer['SERVER_NAME'] = $arServer['HTTP_HOST'];
$arServer['DOCUMENT_ROOT'] = __DIR__ . '/../';
$_SERVER = (is_array($_SERVER) ? $_SERVER : Array());
$_SERVER = array_merge($_SERVER, $arServer);

require($_SERVER["DOCUMENT_ROOT"] . "bitrix/modules/main/include/prolog_before.php");
var_dump(1);
while (ob_get_level())
{
	ob_end_clean();
}

global $DB;
CModule::IncludeModule("iblock");

$rsProducts = CIBlockElement::GetList(
	array("ID" => "DESC"),
	array(
		"IBLOCK_ID" => 18, //вставить нужный id 
		array(
			'LOGIC' => 'OR',
			"!DETAIL_PICTURE" => false,
			"!PREVIEW_PICTURE" => false,
		),
	),
	false,
	array("nPageSize"=>1000),
	array(
		'ID',
		'NAME',
		'IBLOCK_ID',
		'DETAIL_PICTURE',
		'PREVIEW_PICTURE',
		'PROPERTY_CML2_ARTICLE',
	)
);


$arQueries = array();
while ($arProduct = $rsProducts->fetch()) {
	importPictures($arProduct);
}

function importPictures($arProduct)
{
	$oElement = new \CIBlockElement();
	$arProduct['PROPERTY_CML2_ARTICLE_VALUE'] = trim($arProduct['PROPERTY_CML2_ARTICLE_VALUE']);
	if (trim($arProduct['PROPERTY_CML2_ARTICLE_VALUE'])) {
		$arImages = findImagesByArticle($arProduct['PROPERTY_CML2_ARTICLE_VALUE']);

		$mainImage = trim(array_shift($arImages));
		if ($mainImage) {
			$el = new \CIBlockElement;
			$el->Update($arProduct['ID'], array(
				"PREVIEW_PICTURE" => array('del' => 'Y'),
				"DETAIL_PICTURE" => array('del' => 'Y'),
			), false, false);
		}
	}
}

function findImagesByArticle($article) {
	$extensionsList = ['.jpg', '.jpeg', '.JPG', '.JPEG', '.png' , '.PNG'];
	$dir = $_SERVER['DOCUMENT_ROOT'] . 'bitrix/temp_pictures/pictures/';

	$articles[] = $article . 'b';
	for ($i= 1; $i <= 10; $i++) {
		$articles[] = $article . 'b-' . $i;
	}

	$arImages = [];
	foreach ($articles as $article) {
		foreach ($extensionsList as $extension) {
			$filePath = $dir . $article . $extension;
			if (file_exists($filePath) > 0) {
				$arImages[] = $filePath;
			}
		}
	}

	return $arImages;
}