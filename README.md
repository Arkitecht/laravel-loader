
# Laravel Data Loader
------

Database seeders are awesome for loading up your database with test data, but what about when you need to load **real** data into your database across environments?

You could always load that data in with migrations... but then you have to manage DB dependencies, which can get messy with relational databases, especially when doing rollbacks and resets. Plus, thats not really where they should be, and if you are using TDD (which you should be) that is not where you want them - since you probably want to create the data in your tests.
 
 Along comes the Laravel Data Loader. You define data sources, as a Model and Unique Key(s) and then define the data. When you run the loader it will insert or update the data, preserving primary keys, and ensuring you are up to date. 
 
 ## Install
 
 That is as easy as:
 ```
 composer require arkitecht/laravel-loader
 ```
 
 
 ## Setup
 
 1\. Create a new console command as normal 
 ```
 php artisan make:command YourCommandName
 ```
 
 2\. Import the data loader
  ```php
  use Arkitecht\LaravelLoader\Console\LoadDataCommand;
  ```
 
 3\. Change the base class of your command
 ```php
 class YourCommandName extends LoadDataCommand
 ```
 
 4\. Initiate the models
 ```php
 public function __construct()
 {
         parent::__construct();
 
         $this->addDataClass(MyModelName::class, 'uniquekey');
 }
```
Each data class take the model class you will be loading in, and the column, or columns you want to match on. Under the hood we are doing an ```updateOrCreate``` and the data from the column(s) will be used as the $attributes sent to the method.   

You can use an array for a multi dimensional unique key, ie:
```php
$this->addDataClass(MultiDimensionalKeyClass::class, ['key_part_1', 'Key_part_2']);
```

5\. Load the data
 ```php
 public function handle()
 {
    $this->loadData(MyModelName::class, [
        'uniquekey' => 'unique', 
        'data`'     => 'data', 
        'data2'     => 'data' .... 
    ]);
 }   
 ```
 
 ## Real world example
 
 ```php
 use App\Models\TimeZone;
 use Arkitecht\LaravelLoader\Console\LoadDataCommand;

 class LoadInitialData extends LoadDataCommand
 {
     
     protected $signature = 'data:load';
     
     protected $description = 'Load Initial Data';
     
     public function __construct()
     {
            parent::__construct();
                
            $this->addDataClass(TimeZone::class, 'name');
     }
     
     public function handle()
     {
            $this->loadTimeZones();
     }       
         
    /**
      * Load in the initial Time Zones
      *
      * @return void
      */
     public function loadTimeZones()
     {
         $this->loadData(TimeZone::class, [
             'name'   => 'Hawaii-Aleutian Standard Time',
             'code'   => 'HAST',
             'offset' => 'Pacific/Honolulu',
         ]);
         $this->loadData(TimeZone::class, [
             'name'   => 'Hawaii-Aleutian with Daylight Savings Time',
             'code'   => 'HADT',
             'offset' => 'US/Aleutian',
         ]);
         $this->loadData(TimeZone::class, [
             'name'   => 'Alaska Standard Time',
             'code'   => 'AKST',
             'offset' => 'Etc/GMT+9',
         ]);
         ....
    }     
}         
 