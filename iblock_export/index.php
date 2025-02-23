<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

use Vedita\Iblock\Export;
?>
<?
$APPLICATION->SetTitle('Iblock export');

$iblockId = 5;

$sPath = Export::up(
    Export::collect($iblockId)
    ->select(['ID','NAME'])
    ->order('ID')
    ->columnOrder(['NAME','ID'])
)->write();
echo "Путь до файла: $sPath";


// $collection = Export::up($iblockId)
//     ->select(['ID','NAME','WEIGHT','CHAPTER.ITEM','IMAGE.FILE','TEST.ELEMENT','CHAPTER_BLOCK.SECTION'])
//     ->order('ID')
//     ->columnOrder(['NAME','ID','WEIGHT'])
//     ->write();
?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");