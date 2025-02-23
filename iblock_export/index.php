<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

use Vedita\Iblock\Export;
?>
<?
$APPLICATION->SetTitle('Iblock export');

$iblockId = 16;

$sPath = Export::up(
    Export::collect($iblockId)
    ->select(['ID','NAME','STRING_TEST','NUMBER_TEST','LIST_TEST.ITEM','FILE_TEST.FILE'])
    ->order('ID')
    ->columnOrder(['ID','STRING_TEST','NAME','FILE_TEST','LIST_TEST'])
)->write();
echo "Путь до файла: $sPath";


// $collection = Export::up($iblockId)
//     ->select(['ID','NAME','WEIGHT','CHAPTER.ITEM','IMAGE.FILE','TEST.ELEMENT','CHAPTER_BLOCK.SECTION'])
//     ->order('ID')
//     ->columnOrder(['NAME','ID','WEIGHT'])
//     ->write();
?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");