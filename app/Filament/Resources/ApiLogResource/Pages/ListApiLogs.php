<?php

namespace App\Filament\Resources\ApiLogResource\Pages;

use App\Filament\Resources\ApiLogResource;
use Filament\Resources\Pages\ListRecords;

class ListApiLogs extends ListRecords
{
    protected static string $resource = ApiLogResource::class;
}
