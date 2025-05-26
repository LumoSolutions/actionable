<?php

describe('Architecture', function () {
    arch('package should have a service provider in root namespace')
        ->expect("LumoSolutions\Actionable\ActionableProvider")
        ->toExtend('Illuminate\Support\ServiceProvider');

    arch('it will not use debugging functions')
        ->expect("LumoSolutions\Actionable")
        ->not->toUse(['die', 'dd', 'dump', 'ray']);

    arch('it will not use super globals')
        ->expect("LumoSolutions\Actionable")
        ->not->toUse(['$_GET', '$_POST', '$_SESSION', '$_COOKIE', '$_SERVER']);

    arch('traits should have trait declaration')
        ->expect("LumoSolutions\Actionable\Traits")
        ->toBeTrait();

    arch('commands should extend artisan command')
        ->expect("LumoSolutions\Actionable\Console\Commands")
        ->toExtend('Illuminate\Console\Command');

    arch('commands should have proper naming')
        ->expect("LumoSolutions\Actionable\Console\Commands")
        ->toHaveSuffix('Command');

    arch('attributes should have attribute declaration')
        ->expect("LumoSolutions\Actionable\Attributes")
        ->toHaveAttribute('Attribute');

    arch('attributes should be read only')
        ->expect("LumoSolutions\Actionable\Attributes")
        ->toBeReadonly();
});
