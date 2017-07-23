<?php

namespace Arkitecht\LaravelLoader\Console;

use Illuminate\Console\Command;

abstract class LoadDataCommand extends Command
{
    private $data_types;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->data_types = collect();
    }

    /**
     * Add a class to the data loader
     *
     * @param string       $class Class name of the class
     * @param string|array $keys  Key or keys to use as the unique key
     *
     * @return void
     */
    public function addDataClass($class, $keys)
    {
        $this->data_types->put($class, $keys);
    }

    /**
     * Load the data
     * This will check if the given data exists based on the defined keys, and if not add it
     *
     * @param string $class Class name
     * @param array  $data  The data (as if calling Class::create())
     *
     * @throws \Exception
     * @return void
     */
    public function loadData($class, $data)
    {
        if (!$this->data_types->has($class)) {
            throw new \Exception(sprintf('You must first define a datatatype for %s', $class));
        }

        $dataKeys = $this->data_types->get($class);

        if (!is_array($dataKeys)) {
            $dataKeys = [$dataKeys];
        }

        $updateCheck = [];

        foreach ($dataKeys as $dataKey) {
            if (!array_key_exists($dataKey, $data)) {
                throw new \Exception(sprintf('You must provide a %s field in the data for datatatype for %s', $dataKey, $class));
            }
            $updateCheck[ $dataKey ] = $data[ $dataKey ];
        }

        $class::updateOrCreate($updateCheck, $data);
    }
}