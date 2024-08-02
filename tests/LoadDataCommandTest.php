<?php

namespace Tests;

use Arkitecht\LaravelLoader\Console\LoadDataCommand;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class LoadDataCommandTest extends TestCase
{
    /** @test */
    function adds_data_type()
    {
        $loader = new TestLoader();
        $classes = $loader->getDataClasses();

        $this->assertTrue($classes->has('Tests\SampleClass'));
        $this->assertEquals('key', $classes->get('Tests\SampleClass'));

        $this->assertTrue($classes->has('Tests\SampleComplexKeyClass'));
        $this->assertEquals(['key', 'database'], $classes->get('Tests\SampleComplexKeyClass'));
    }

    /** @test */
    function can_add_loaders()
    {
        $loader = new TestLoader();
        $loader->addLoader('samples', 'loadSomeSamples');
        $loader->addLoader('complex-samples', function () use ($loader) {
            $loader->loadData(SampleComplexKeyClass::class, [
                'key'      => 'key-1',
                'database' => 'db-1',
                'value'    => '1.1',
            ]);
            $loader->loadData(SampleComplexKeyClass::class, [
                'key'      => 'key-1',
                'database' => 'db-2',
                'value'    => '1.2',
            ]);
        });

        $loaders = $loader->getLoaders();
        $this->assertTrue($loaders->has('samples'));
        $this->assertEquals($loaders->get('samples'), 'loadSomeSamples');

        $this->assertTrue($loaders->has('complex-samples'));
    }

    /** @test */
    function loader_loads_simple_data()
    {
        $this->loadMigrationsFrom(__DIR__ . '/database');

        SampleClass::create([
            'key'   => 'key-1',
            'value' => 'initial value',
        ]);

        SampleClass::create([
            'key'   => 'key-2',
            'value' => 'second value',
        ]);

        $this->assertEquals(2, SampleClass::count());
        $this->assertEquals('initial value', SampleClass::where('key', 'key-1')->first()->value);

        $this->runCommand(TestLoader::class, 'data:load', []);

        $this->assertEquals(2, SampleClass::count());
        $this->assertEquals(1, SampleClass::where('key', 'key-1')->first()->value);
    }

    /** @test */
    function loader_loads_complex_data()
    {
        $this->loadMigrationsFrom(__DIR__ . '/database');

        SampleComplexKeyClass::create([
            'key'      => 'key-1',
            'database' => 'db-1',
            'value'    => 'initial value, db 1',
        ]);

        SampleComplexKeyClass::create([
            'key'      => 'key-2',
            'database' => 'db-1',
            'value'    => 'second value, db 1',
        ]);

        SampleComplexKeyClass::create([
            'key'      => 'key-1',
            'database' => 'db-2',
            'value'    => 'initial value, db 2',
        ]);

        SampleComplexKeyClass::create([
            'key'      => 'key-2',
            'database' => 'db-2',
            'value'    => 'second value, db 2',
        ]);

        $this->assertEquals(4, SampleComplexKeyClass::count());
        $this->assertEquals('initial value, db 1', SampleComplexKeyClass::where('key', 'key-1')->where('database', 'db-1')->first()->value);
        $this->assertEquals('initial value, db 2', SampleComplexKeyClass::where('key', 'key-1')->where('database', 'db-2')->first()->value);

        $this->runCommand(TestLoader::class, 'data:load', []);

        $this->assertEquals(4, SampleComplexKeyClass::count());
        $this->assertEquals('1.1', SampleComplexKeyClass::where('key', 'key-1')->where('database', 'db-1')->first()->value);
        $this->assertEquals('1.2', SampleComplexKeyClass::where('key', 'key-1')->where('database', 'db-2')->first()->value);
    }

    /** @test */
    function can_specify_loader()
    {
        $this->loadMigrationsFrom(__DIR__ . '/database');

        SampleClass::create([
            'key'   => 'key-1',
            'value' => 'initial value',
        ]);

        SampleClass::create([
            'key'   => 'key-2',
            'value' => 'second value',
        ]);

        $this->assertEquals(2, SampleClass::count());
        $this->assertEquals('initial value', SampleClass::where('key', 'key-1')->first()->value);

        SampleComplexKeyClass::create([
            'key'      => 'key-1',
            'database' => 'db-1',
            'value'    => 'initial value, db 1',
        ]);

        SampleComplexKeyClass::create([
            'key'      => 'key-2',
            'database' => 'db-1',
            'value'    => 'second value, db 1',
        ]);

        SampleComplexKeyClass::create([
            'key'      => 'key-1',
            'database' => 'db-2',
            'value'    => 'initial value, db 2',
        ]);

        SampleComplexKeyClass::create([
            'key'      => 'key-2',
            'database' => 'db-2',
            'value'    => 'second value, db 2',
        ]);

        $this->assertEquals(4, SampleComplexKeyClass::count());
        $this->assertEquals('initial value, db 1', SampleComplexKeyClass::where('key', 'key-1')->where('database', 'db-1')->first()->value);
        $this->assertEquals('initial value, db 2', SampleComplexKeyClass::where('key', 'key-1')->where('database', 'db-2')->first()->value);

        $this->runCommand(TestLoader::class, 'data:load', ['--loader' => 'complex-samples']);

        //These values updated
        $this->assertEquals(4, SampleComplexKeyClass::count());
        $this->assertEquals('1.1', SampleComplexKeyClass::where('key', 'key-1')->where('database', 'db-1')->first()->value);
        $this->assertEquals('1.2', SampleComplexKeyClass::where('key', 'key-1')->where('database', 'db-2')->first()->value);

        //check simple keys stayed the same, since they should not have been called
        $this->assertEquals(2, SampleClass::count());
        $this->assertEquals('initial value', SampleClass::where('key', 'key-1')->first()->value);

    }

    /** @test */
    function loader_loads_keyless_data()
    {
        $this->loadMigrationsFrom(__DIR__ . '/database');

        SampleKeylessClass::create([
            'name' => 'Original',
        ]);

        SampleKeylessClass::create([
            'name' => 'Stays the Same',
        ]);


        $this->assertEquals(2, SampleKeylessClass::count());
        $this->assertEquals('Original', SampleKeylessClass::first()->name);
        $this->assertEquals('Stays the Same', SampleKeylessClass::skip(1)->first()->name);

        $this->runCommand(TestLoader::class, 'data:load', []);


        $this->assertEquals(2, SampleKeylessClass::count());
        $this->assertEquals('Updated', SampleKeylessClass::first()->name);
        $this->assertEquals('Stays the Same', SampleKeylessClass::skip(1)->first()->name);
    }

    private function loadSomeSamples()
    {
        $this->loadData(SampleClass::class, [
            'key'   => 'key-1',
            'value' => 1,
        ]);
        $this->loadData(SampleClass::class, [
            'key'   => 'key-2',
            'value' => 2,
        ]);
    }

    private function runCommand($commandClass, $signature, $options = [])
    {
        $application = new Application();
        $appCommand = $this->app->make($commandClass);
        $appCommand->setLaravel($this->app);

        $application->add($appCommand);

        $command = $application->find($signature);

        $commandTester = new CommandTester($command);

        $arguments = array_merge(['command' => $command->getName()], $options);

        $commandTester->execute($arguments);
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}

class TestLoader extends LoadDataCommand
{
    protected $signature = 'data:load';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load up some data';


    public function __construct()
    {
        parent::__construct();

        $this->addDataClass(SampleClass::class, 'key');
        $this->addDataClass(SampleComplexKeyClass::class, ['key', 'database']);
        $this->addDataClass(SampleKeylessClass::class, 'name');
    }

    public function handle()
    {
       /* $this->addLoader('samples', 'loadSomeSamples');
        $this->addLoader('complex-samples', function () {
            $this->loadData(SampleComplexKeyClass::class, [
                'key'      => 'key-1',
                'database' => 'db-1',
                'value'    => '1.1',
            ]);
            $this->loadData(SampleComplexKeyClass::class, [
                'key'      => 'key-1',
                'database' => 'db-2',
                'value'    => '1.2',
            ]);
        });*/
        $this->addLoader('keyless-samples', function () {
            $this->loadData(SampleKeylessClass::class, [
                'name' => 'Updated',
            ], 'Original');
            $this->loadData(SampleKeylessClass::class, [
                'name' => 'Stays the Same',
            ], 'Something Else');
        });
        parent::handle();
    }

    public function loadSomeSamples()
    {
        $this->loadData(SampleClass::class, [
            'key'   => 'key-1',
            'value' => 1,
        ]);
        $this->loadData(SampleClass::class, [
            'key'   => 'key-2',
            'value' => 2,
        ]);
    }
}

class SampleClass extends Model
{
    protected $guarded = [];
    protected $table = 'keys';
}

class SampleComplexKeyClass extends Model
{
    protected $guarded = [];
    protected $table = 'complex_keys';
}

class SampleKeylessClass extends Model
{
    protected $guarded = [];
    protected $table = 'keyless';
}
