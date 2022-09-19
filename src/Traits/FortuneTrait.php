<?php

namespace Maturest\Trigram\Traits;

use Maturest\Trigram\Traits\Fortune\AccTrait;
use Maturest\Trigram\Traits\Fortune\CommonRelationTrait;
use Maturest\Trigram\Traits\Fortune\DissolveTrait;
use Maturest\Trigram\Traits\Fortune\GodTrait;
use Maturest\Trigram\Traits\Fortune\GoodIllTrait;
use Maturest\Trigram\Traits\Fortune\NumenTrait;
use Maturest\Trigram\Traits\Fortune\ShieldTrait;

trait FortuneTrait
{
    //用神的位置,数组
    protected $god_positions = [];

    use GodTrait, CommonRelationTrait, NumenTrait, GoodIllTrait, ShieldTrait, AccTrait, DissolveTrait;
}