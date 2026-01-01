<?php

namespace App\Services;

class GoogleTaxonomy
{
    public static function getSoftwareCategories(): array
    {
        return [
            '316' => 'Software',
            '317' => 'Software > Business & Productivity Software',
            '5306' => 'Software > Business & Productivity Software > Accounting & Finance Software',
            '5308' => 'Software > Business & Productivity Software > CRM Software',
            '5309' => 'Software > Business & Productivity Software > ERP Software',
            '5307' => 'Software > Business & Productivity Software > HR Software',
            '5310' => 'Software > Business & Productivity Software > Supply Chain Management Software',
            '322' => 'Software > Compilers & Programming Tools',
            '319' => 'Software > Computer Security Software',
            '323' => 'Software > Computer Utilities',
            '328' => 'Software > Multimedia Design & Creation Software',
            '320' => 'Software > Network Software',
            '318' => 'Software > Operating Systems',
            '321' => 'Software > Web Services & Content Management Systems',
            '8076' => 'Software > Educational Software',
            '8077' => 'Software > Gaming Software',
            '8079' => 'Software > Reference Software',
            '1279' => 'Electronics > Electronics Accessories > Computer Components',
            '278' => 'Electronics > Computers',
            '329' => 'Software > Digital Media > Clip Art & Stock Photography',
            '331' => 'Software > Digital Media > Fonts',
        ];
    }
}
