<?php

namespace Arkitecht\LaravelLoader\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

abstract class LoadDataCommand extends Command
{
    private $data_types;
    private $loaders;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->data_types = collect();
        $this->loaders = collect();
        $loaderOption = new InputOption('loader', 'L', InputOption::VALUE_OPTIONAL, 'Run the specified loader');
        $this->getDefinition()->addOption($loaderOption);
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
     * @return mixed
     * @throws \Exception
     */
    public function loadData($class, $data, $original = null)
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

        if ($original) {
            if (!is_array($original)) {
                $original = [$original];
            }

            $query = $class::query();
            foreach ($dataKeys as $idx => $dataKey) {
                $query->where($dataKey, $original[ $idx ]);
            }
            $existing = $query->first();

            if ($existing) {
                $existing->update($updateCheck);
            }
        }

        return $class::updateOrCreate($updateCheck, $data);
    }

    /**
     * Add a dataloader into the loaders collection
     *
     * @param $key
     * @param $function
     */
    public function addLoader($key, $function)
    {
        $this->loaders->put($key, $function);
    }

    /**
     * Default handler which processes all loaders, or specified loader
     */
    public function handle()
    {
        $this->loaders->each(function ($loader, $loaderKey) {
            if (!$this->option('loader') || $this->option('loader') == $loaderKey) {
                if (is_object($loader) && ($loader instanceof \Closure)) {
                    $loader->call($this);
                } else {
                    $this->$loader();
                }
            }
        });
    }

    /**
     * Get the current data classes
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDataClasses()
    {
        return $this->data_types;
    }

    /**
     * Get the current data loaders
     *
     * @return \Illuminate\Support\Collection
     */
    public function getLoaders()
    {
        return $this->loaders;
    }

}
