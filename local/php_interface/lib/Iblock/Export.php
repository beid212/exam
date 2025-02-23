<? 

namespace Vedita\Iblock;

use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

use Vedita\Iblock\ElementsData;
use Vedita\Interfaces\IblockData;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

Loader::includeModule('iblock');
Loc::loadLanguageFile(__FILE__);

class Export 
{
    //путь до файла
    public static string $path = '/local/tools';

    //объект экспортёра (создаётся 1 раз и при обращении меняет поставщика данных если необходимо)
    private static ?Export $obExporter = null;

    //массив поставщиков данных по определённым id инфоблоков
    private static array $arExportCollection = [];

    //массив с указанием типов поставщиков и классов самих поставщиков данных
    private static array $arDataTypes = [
        'default' => ElementsData::class
    ];


    //объект поставщика дланных
    private IblockData $obElementsData;

    //функция возвращает объект экспортёра данных
    //инициализация экспортёра происходит 1 раз - после меняются поставщики данных
    public static function up(IblockData $obElementsData)
    {
        if(is_null(self::$obExporter))
            self::$obExporter = new self($obElementsData);
        
        return self::$obExporter->swap($obElementsData);
    }
    
    //функция инициализирует поставщика данных под конкретный id инфоблока (есть возможность под разные инфоблоки создавать своих поставщиков, добавляя их типы в массив)
    public static function collect(int $iIblockId, string $sDataType = 'default'): IblockData
    {
        if(is_null(self::$arDataTypes[$sDataType]))
            throw new \Exception('Invalid data provider type has been entered!');

        //берём класс поставщика данных по переданному типу в $sDataType
        $sDataClass = self::$arDataTypes[$sDataType];

        //если поставщик данных для инфоблока уже есть - возвращаем его объект, иначе создаём
        if(is_null(self::$arExportCollection[$iIblockId]))
        {
            self::$arExportCollection[$iIblockId] = new $sDataClass($iIblockId);
        }
        
        return self::$arExportCollection[$iIblockId];
    }

    //конструктор экспортёра принимает в себя объект поставщика данных
    public function __construct(IblockData $obElementsData)
    {
        $this->obElementsData = $obElementsData;
    }

    //функция реализует изменение поставщика данных для последующего экспорта данных по данным поставщика
    public function swap(IblockData $obElementsData)
    {
        $this->obElementsData = $obElementsData;
        return $this;
    }

    //функция приводит данные для экспорта в нормальный вид (для списков - берёт их значения, для элементов - названия и тд)
    //чтобы работало взятие данных из типов свойств список/элемент и тд при select поставщика нужно указывать правильное название
    //.FILE - для файлов, .SECTION - для секций, .ITEM - для списка, .ELEMENT - для элементов
    //для элементов изначально не доступен динамический get свойств (getElement) - необходимо чтобы тип инфоблока связанного элемента имел символьное API
    private function shape($obProperty, string $sPropertyType):string
    {
        if($sPropertyType === PropertyTable::TYPE_STRING || $sPropertyType ===PropertyTable::TYPE_NUMBER)
            return $obProperty->getValue();

        if($sPropertyType === PropertyTable::TYPE_FILE)
        {
            if(is_null($obProperty->getFile()))
                return $obProperty->getValue();
            else
                return '/upload/'.$obProperty->getFile()->getSubdir().'/'.$obProperty->getFile()->getFileName();
        }
            
        if($sPropertyType === PropertyTable::TYPE_ELEMENT)
        {
            if(is_null($obProperty->getElement()))
                return $obProperty->getValue();
            else
                return $obProperty->getElement()->getName();
        }

        if($sPropertyType === PropertyTable::TYPE_SECTION)
        {
            if(is_null($obProperty->getSection()))
                return $obProperty->getValue();
            else
                return $obProperty->getSection()->getName();
        }
            

        if($sPropertyType === PropertyTable::TYPE_LIST)
        {
            if(is_null($obProperty->getItem()))
                return $obProperty->getValue();
            else
                return $obProperty->getItem()->getValue();
        }
            

        return 'empty';
    }

    //функция собирает шапку с названиями в соответствии с порядком, переданным в columnOrder
    private function collectHeader()
    {
        $arHeader = [];

        foreach($this->obElementsData->property('columnOrder') as $sColumn)
        {
            if(!in_array($sColumn, array_keys($this->obElementsData->property('properties'))))
                $arHeader[] = Loc::getMessage("IBLOCK_FIELD_$sColumn");
            else 
                $arHeader[] = $this->obElementsData->property('properties')[$sColumn]['NAME'];
        }

        return $arHeader;
    }

    //функция собирает строку в соответствии с порядком, переданным в columnOrder
    private function collectRow(object $obElement):array
    {
        $arRow = [];

        foreach($this->obElementsData->property('columnOrder') as $sColumn)
        { 

            $arFieldProperty = $this->obElementsData->property('properties')[$sColumn];

            if(!in_array($sColumn,array_keys($this->obElementsData->property('properties'))))
            {
                $arRow[$sColumn] = $obElement->get($sColumn);
                continue;
            }
            
            if($arFieldProperty['MULTIPLE'] === 'Y')
            {
                $sValues = '';
                foreach($obElement->get($sColumn)->getAll() as $obItem)
                {
                    $sValues .= $this->shape($obItem, $arFieldProperty['PROPERTY_TYPE']).PHP_EOL;
                }
                $arRow[$sColumn] = $sValues;
            }
            else
            {
                if(!is_null($obElement->get($sColumn)))
                    $arRow[$sColumn] = $this->shape($obElement->get($sColumn),$arFieldProperty['PROPERTY_TYPE']);
                else
                    $arRow[$sColumn] = '';
            }
            
        }

        return $arRow;
    }

    //просто функция которая вызывает сброку строк
    private function collectRows($arElements)
    {
        $arRows = [];

        foreach($arElements as $obElement)
        {
            $arRows[] = $this->collectRow($obElement);
        }

        return $arRows;
    }

    public function write()
    {
        
        $arHeader = $this->collectHeader();
        $arRows = $this->collectRows($this->obElementsData->get());
        

        $obWriter = WriterEntityFactory::createXLSXWriter();  // Создаем XLSX писатель
        $obWriter->setTempFolder(sys_get_temp_dir());
        // 2. Открытие файла для записи
        $sFilePath = $_SERVER['DOCUMENT_ROOT'].self::$path.'/output_'.date('Ymd_His').'.xlsx';  // Путь к выходному файлу
        if (!file_exists($_SERVER['DOCUMENT_ROOT'].self::$path))
            throw new \Exception('The catalog does not exist! Change the values of the path variable');

        $obWriter->openToFile($sFilePath);

        // 4. Создание строки заголовков (необязательно)
        $obRow = WriterEntityFactory::createRowFromArray($arHeader);

        $obWriter->addRow($obRow);  // Добавляем строку заголовков

        foreach ($arRows as $arRowData) {
            $obRow = WriterEntityFactory::createRowFromArray($arRowData);  // Создаем строку из массива
            $obWriter->addRow($obRow); // Добавляем строку в файл
        }

        // 6. Закрытие писателя (writer)
        $obWriter->close();

        return $sFilePath;
    }
}