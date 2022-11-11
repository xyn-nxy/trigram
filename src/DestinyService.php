<?php

namespace Maturest\Trigram;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Maturest\Trigram\Exceptions\InvalidArgumentException;
use Maturest\Trigram\Traits\DestinyTrait;
use Maturest\Trigram\Traits\FortuneTrait;

class DestinyService
{
    use DestinyTrait, FortuneTrait;

    private static $instance;

    public $draw = [
        'kong_wang' => ['coords' => [], 'radius' => 20], // 标空亡,给出具体的坐标原点，半径,文字图片是40x40 半径应为20 ，然后画圆
        'an_dong' => ['coords' => [], 'img' => 'dark_on/dark_on.png'], // 暗动，标注坐标原点,然后画箭头并写字 字体的坐标可以相对计算
        'six_chong' => [],//六冲
        'six_he' => [], //六合
        'hui_ju' => [],//汇局
        'ru_mu' => [],//入墓
        'jin_tui' => [],//进退神
        'fu_yao' => [],//伏爻
    ];

    protected $calendar;

    // \ => 1 \\ => 2  o => 3  x => 4 123412 从左至右按顺序解释为：一爻 二爻 --- 六爻
    protected $gua;

    //用户问句
    protected $question;

    //用户姓名
    protected $userName;

    //卜卦类型
    protected $trigramType;

    //服务归属人
    protected $owner;

    //是否需要水印
    protected $watermark;

    /**
     * DestinyService constructor.
     * @param $date 1996-01-01 05:26:38 阳历的日期
     * @param $gua
     * @param array $extends
     * @throws InvalidArgumentException
     */
    private function __construct($date, $gua, $extends = [], $watermark = true)
    {
        $this->calendar = app('calendar');
        $this->date = $date;
        $this->gua = $gua;
        $this->watermark = $watermark;

        if (!empty($extends)) {
            $this->question = $extends['question'] ?? '';
            $this->userName = $extends['userName'] ?? '';
            $this->trigramType = $extends['trigramType'] ?? '';
            $this->owner = $extends['owner'] ?? '';
        }

        $this->parseDate();
    }

    public static function getInstance($date, $gua, $extends = [], $watermark = true)
    {
        if (static::$instance instanceof static) {
            return static::$instance;
        }

        static::$instance = new static($date, $gua, $extends, $watermark);

        return static::$instance;
    }

    /**
     * 检测是否显卦
     * 不显卦：纯净爻并且没有与日令存在六冲的
     */
    public function isAvailable()
    {
        if (strlen($this->gua) != 6) {
            throw new InvalidArgumentException('六爻卦参数不合法');
        }

        $tmp_arr = str_split($this->gua);
        foreach ($tmp_arr as $tmp_val) {
            if ($tmp_val < 1 || $tmp_val > 4) {
                throw new InvalidArgumentException('六爻卦参数不合法');
            }
        }

        if (Str::contains($this->gua, [3, 4])) {
            return true;
        }

        // 为了安全考虑 查看是否是64卦中的一种
        if (!Arr::exists($this->totalGua, $this->gua)) {
            return false;
        }

        // 看卦的十二地支 与当天的日令是否存在对冲关系，如果不存在对冲的话就不现卦
        $gua_dz = Arr::get($this->totalGua, $this->gua . '.di_zhi');
        $day_cong = $this->getCong($this->diZhiDay);

        if (Str::contains($gua_dz, $day_cong)) {
            return true;
        }

        return false;
    }

    public function getTrigramPic($draw = true)
    {
        $this->whiteDeath()
            ->deployDiZhi()
            ->handleWhiteDeath()
            ->getYaoDetail()
            ->handleDarkOn()
            ->handleRelationSixCong()
            ->handleRelationSixHe()
            ->handleRelationConvergeSet()
            ->handleEnterTomb()
            ->handleDilemma()
            ->handleVoltTrigram();

        if ($draw) {
            $pic_url = $this->draw();
        }

        return [
            'pic_url' => $pic_url ?? '',
            'is_dangerous' => $this->resultDiZhi['is_dangerous'] ?? false,
            'dangerous_note' => $this->resultDiZhi['dangerous_note'] ?? '',
        ];
    }

    /**
     * 年运势卦
     * @param string $god 用神
     * @param string $year 干支年
     * @param boolean $is_pregnant 是否孕妇
     * @return array
     */
    public function fortune($god, $year, $is_pregnant)
    {
        //获取用神的位置
        $god_positions = $this->getGodPositions($god);
        //全局设置用神的位置
        $this->setGodPositions($god_positions);

        //1、守护神
        $numen = $this->numen();

        //2、运势吉凶
        $good_ill = $this->goodOrIll($god);

        //3、五行护持
        $shield = $this->shield();

        //4、幸运配饰
        $acc = $this->acc($god);

        //5、化解之道
        $dissolve = $this->dissolve($year, $is_pregnant);

        //6、财运
        $wealth = $this->wealth();

        //7、贵人运
        $honourable_men = $this->honourableMen();

        //8、事业运
        $cause = $this->cause();

        //9、时来运转
        $transform = $this->transform($god);

        return compact('numen', 'good_ill', 'shield', 'acc',
            'dissolve', 'wealth', 'honourable_men','cause','transform');
    }

    /**
     * 私有化克隆方法
     */
    private function __clone()
    {

    }
}
