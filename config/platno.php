<?php

declare(strict_types=1);

use Platno\Plugins\Columns;
use Platno\Plugins\Divider;
use Platno\Plugins\File;
use Platno\Plugins\Group;
use Platno\Plugins\Heading;
use Platno\Plugins\Image;
use Platno\Plugins\Link;
use Platno\Plugins\RichText;
use Platno\Plugins\Text;

return [
    // This list replaces the defaults. Include the built-in plugins you want to keep.
    // Tokens merge with the documented defaults; plugin lists are replaced.
    'theme' => ['background' => '#ffffff', 'text' => '#202821', 'accent' => '#285937', 'surface' => '#eef1e9', 'width' => 1080, 'font' => 'system'],

    'assets' => ['disk' => 'platno', 'max_kilobytes' => 10240],

    'plugins' => [
        Text::class,
        Image::class,
        File::class,
        Heading::class,
        Link::class,
        Group::class,
        Columns::class,
        Divider::class,
        RichText::class,

    ],
];
