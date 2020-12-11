```
use Dbwhddn10\FService\Service;

class BaseService extends Service
{
    public static function getArrBindNames()
    {
        return []; // 변수 이름 정의
    }

    public static function getArrCallbackLists()
    {
        return []; // 유효성 검사후 추가적으로 실행할 함수 정의
    }

    public static function getArrLoaders()
    {
        return []; // 로더
    }

    public static function getArrPromiseLists()
    {
        return []; // 로더 실행전 먼저 실행되야하는 키값 정의
    }

    public static function getArrRuleLists()
    {
        return []; // 유효성 검사 룰 정의
    }

    public static function getArrTraits()
    {
        return []; // 상속할 서비스 목록 정의
    }
}
```
