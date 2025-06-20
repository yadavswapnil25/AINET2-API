<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use ArchTech\Enums\Values;

enum GenderEnum: string
{
    use Options, Names, _Options, Values;

    case Male = 'male';

    case Female = 'female';

    case Other = 'other';
}
