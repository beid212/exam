<? 

namespace Vedita\Interfaces;

interface IblockData
{
    public function select(array $select);
    public function filter(array $filter);
    public function order(string $field, bool $asc = true);
    public function columnOrder(array $columnOrder);
    public function property(string $sProperty);
    public function get();
}