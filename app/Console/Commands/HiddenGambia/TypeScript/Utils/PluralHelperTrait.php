<?php

namespace App\Console\Commands\HiddenGambia\TypeScript\Utils;

use Illuminate\Support\Str;

trait PluralHelperTrait
{
    /**
     * Special plural forms for irregular nouns.
     *
     * @return array<string, string>
     */
    protected function getSpecialPlurals(): array
    {
        return [
            'Activity' => 'Activities',
            'Agency' => 'Agencies',
            'Category' => 'Categories',
            'Company' => 'Companies',
            'Country' => 'Countries',
            'Currency' => 'Currencies',
            'Itinerary' => 'Itineraries',
            'Facility' => 'Facilities',
            'Property' => 'Properties',
            'Story' => 'Stories',
        ];
    }

    /**
     * Get lowercase special plurals.
     *
     * @return array<string, string>
     */
    protected function getLowercaseSpecialPlurals(): array
    {
        $result = [];
        foreach ($this->getSpecialPlurals() as $singular => $plural) {
            $result[Str::lcfirst($singular)] = Str::lcfirst($plural);
        }

        return $result;
    }

    /**
     * Get function name in plural form.
     *
     * @param  string  $name  The singular name
     * @return string The plural function name
     */
    protected function getPluralFunctionName(string $name): string
    {
        $specialPlurals = $this->getSpecialPlurals();

        if (isset($specialPlurals[$name])) {
            return $specialPlurals[$name];
        }

        // Use Laravel's pluralisation
        return Str::studly(Str::plural($name));
    }

    /**
     * Get variable name in plural form.
     *
     * @param  string  $name  The singular name
     * @return string The plural variable name
     */
    protected function getPluralVariableName(string $name): string
    {
        $variableName = Str::lcfirst($name);
        $specialPlurals = $this->getLowercaseSpecialPlurals();

        if (isset($specialPlurals[$variableName])) {
            return $specialPlurals[$variableName];
        }

        // Use Laravel's pluralisation
        return Str::camel(Str::plural($variableName));
    }

    /**
     * Get API endpoint in plural form.
     *
     * @param  string  $name  The singular name
     * @return string The plural kebab-case endpoint
     */
    protected function getApiEndpoint(string $name): string
    {
        // Convert to kebab-case using Laravel's helper
        $name = Str::kebab($name);
        $specialPlurals = $this->getLowercaseSpecialPlurals();

        if (isset($specialPlurals[$name])) {
            return $specialPlurals[$name];
        }

        // Use Laravel's pluralisation
        return Str::plural($name);
    }
}
