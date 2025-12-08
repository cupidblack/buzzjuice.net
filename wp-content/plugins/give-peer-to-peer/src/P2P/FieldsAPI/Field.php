<?php

namespace GiveP2P\P2P\FieldsAPI;

use Give\Framework\Support\Facades\Facade;
use GiveP2P\P2P\FieldsAPI\Factory\FieldFactory;

/**
 * @method static Text text(string $name)
 * @method static FormField date(string $name)
 * @method static FormField hidden(string $name)
 * @method static FormField number(string $name)
 * @method static Textarea textarea(string $name)
 * @method static FormField checkbox(string $name)
 * @method static Image file(string $name)
 * @method static Radio radio(string $name)
 * @method static Select select(string $name)
 * @method static Color color(string $name)
 * @method static Repeater repeater(string $name)
 * @method static Editor editor(string $name)
 * @method static Image image(string $name)
 * @method static Money money(string $name)
 * @method static Virtual virtual(string $name)
 */
class Field extends Facade
{
    protected function getFacadeAccessor()
    {
        return FieldFactory::class;
    }
}
