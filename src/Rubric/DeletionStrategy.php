<?php

namespace App\Rubric;

enum DeletionStrategy: string
{
    case CASCADE_ITEMS = 'cascade';
    case KEEP_ITEMS = 'keep';
}
