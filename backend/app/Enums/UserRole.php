<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case TeamLeader = 'team_leader';
    case Employee = 'employee';
}
