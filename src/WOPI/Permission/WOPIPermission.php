<?php

namespace App\WOPI\Permission;

enum WOPIPermission: string
{
    case VIEW = 'view';
    case EDIT = 'edit';
}
