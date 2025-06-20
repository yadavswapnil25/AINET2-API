<?php

namespace App\Enums;

enum Membership: string
{
    case Individual = 'Individual';
    case Institutional = 'Institutional';
}

enum IndividualMembership: string
{
    case Annual = 'Annual';
    case LongTerm = 'LongTerm';
    case Overseas = 'Overseas';
}

enum InstitutionalMembership: string
{
    case Annual = 'Annual';
    case LongTerm = 'LongTerm';
    case Overseas = 'Overseas';
}
