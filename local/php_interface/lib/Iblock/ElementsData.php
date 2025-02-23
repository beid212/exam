<?

namespace Vedita\Iblock;

use Vedita\Interfaces\IblockData;
use Bitrix\Iblock\Iblock;
use Bitrix\Iblock\PropertyTable;

class ElementsData implements IblockData
{
    private string $sIblockClass;
    
    private array $arFilter = [];
    private array $arSelect = [];
    private array $arOrder = [];
    public array $arColumnOrder = [];
    private array $arProperties = [];

    //конструктор сохраняет свойства выбранного инфоблока (для каждого инфоблока он сохранит описание его свойств) и класс для выборке элементов
    public function __construct(int $iIblockId)
    {
        $this->sIblockClass = Iblock::wakeUp($iIblockId)->getEntityDataClass();
        $this->arProperties = $this->setProperties($iIblockId);
    }

    //указывются колонки для выборки (если порядок экспорта не указан - указывается вместе с работой метода)
    public function select(array $arSelect)
    {
        $this->arSelect = $arSelect;

        if(empty($this->arColumnOrder))
        {
            foreach($arSelect as $sField)
            {
                if(strpos($sField,'.')!==false)
                    $this->arColumnOrder[] = explode('.', $sField)[0];
                else 
                    $this->arColumnOrder[] = $sField;
            }
        }
        return $this;
    }

    //сохраняет переданный фильтр
    public function filter(array $arFilter)
    {
        $this->arFilter = $arFilter;
        return $this;
    }

    //сохраняет переданную сортировку
    public function order(string $sField, bool $bAsc = true)
    {
        $this->arOrder = [
            $sField => $bAsc?'ASC':'DESC'
        ];
        return $this;
    }

    //сохраняет порядок - работает аналогично методу select
    public function columnOrder(array $arColumnOrder)
    {
        $this->arColumnOrder = $arColumnOrder;

        if(empty($this->arSelect))
            $this->arSelect = $arColumnOrder;

        return $this;
    }

    //метод возрващает коллекцию элементов по переданным параметрам
    public function get()
    {
        $arElements = $this->sIblockClass::getList([
            'select'=>$this->arSelect,
            'filter'=>$this->arFilter,
            'order'=>$this->arOrder
        ])->fetchCollection();

        return $arElements;
    }

    //метод даёт возможность выборки некоторых данных из класса (в основном сделано для возможности экспортирующим классам брать порядок колонок)
    public function property(string $sProperty)
    {
        if($sProperty === 'select')
            return $this->arSelect;

        if($sProperty === 'filter')
            return $this->arFilter;

        if($sProperty === 'order')
            return $this->arOrder;

        if($sProperty === 'columnOrder')
            return $this->arColumnOrder;

        if($sProperty === 'properties')
            return $this->arProperties;

        throw new \Exception('Attempt to request a non-existent property');
    }

    //сохраняет описание свойств выбранного инфоблока 
    private function setProperties(int $iIblockId): array
    {
        $arPropertiesMap = PropertyTable::getList([
            'filter' => [
                '=IBLOCK_ID' => $iIblockId,
            ],
            'select' => [
                'ID', 
                'CODE', 
                'NAME', 
                'PROPERTY_TYPE', 
                'USER_TYPE',
                'MULTIPLE',
                'ACTIVE'
            ],
            'order' => [
                'NAME' => 'ASC'
            ],
        ])->fetchAll();
        
        $arProperties = [];

        foreach( $arPropertiesMap as $arProperty)
        {
            $arProperties[$arProperty['CODE']] = $arProperty;
        }
        
        return $arProperties;
    }
}