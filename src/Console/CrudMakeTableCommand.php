<?php namespace Vladmunj\CrudGenerator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Vladmunj\CrudGenerator\Builders\Controller;
use Vladmunj\CrudGenerator\Builders\Router;
use Vladmunj\CrudGenerator\Builders\Model;
use Vladmunj\CrudGenerator\Builders\ModelFactory;
use Vladmunj\CrudGenerator\Builders\UnitTest;
use Symfony\Component\Process\Process;

class CrudMakeTableCommand extends Command{
    const EXCLUDED_TABLES = ['migrations', 'password_resets', 'failed_jobs', 'crud_route_groups'];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud:table {--s}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create CRUD controller,model and routes for all tables';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->params = [];
        $this->tables = [];
    }

    /**
     * prepare params for crud
     */
    private function prepareParams($table){
        $controllerName = implode('',array_map(function($part){
            return Str::ucfirst(Str::singular($part));
        },explode("_",$table['name'])));
        $params['controller_name'] = $controllerName.'Controller';
        $params['model_name'] = $controllerName;
        $params['crud_url'] = '/api/'.str_replace('_','/',Str::singular($table['name']));
        $params['table_name'] = $table['name'];
        $params['author'] = env('PACKAGE_AUTHOR');
        return $params;
    }

    /**
     * generate crud controller,model and routes
     */
    private function crud(){
        DB::table('crud_route_groups')->truncate();
        foreach($this->tables as $table){
            $params = $this->prepareParams($table);
            $this->line('CRUD for '.$table['name']);
            (new Controller($params))->build();
            (new Router($params))->build();
            (new Model($params))->build();
            (new ModelFactory($params))->build();
            (new UnitTest($params))->build();
        }
        $this->newLine();
    }

    /**
     * get all tables names, excluding exception tables
     */
    private function getTableNames(){
        $exceptions = array_merge(
            self::EXCLUDED_TABLES,
            explode(",",str_replace(' ','',$this->params['exceptions']))
        );
        $exceptions = array_filter($exceptions);

        $migrations = preg_grep('/^([^.])/', scandir(database_path('migrations')));
        foreach($migrations as $migration){
            preg_match_all('/Schema::create\(\'(.*)\', function/', file_get_contents(database_path('migrations/'.$migration)), $matches);
            if(count($matches) <= 1) continue;
            foreach($matches[1] as $tableName){
                if(in_array($tableName, $exceptions)) continue;
                $this->tables[] = [
                    'name' => $tableName,
                    'filename' => $migration
                ];
            }
        }
    }

    /**
     * Asking user for set parameters
     */
    private function input(){
        if($this->option('s')){
            $this->params['exceptions'] = '';
            return false;
        }
        $this->info('Set table names, that be excluded from CRUD generation');
        $this->params['exceptions'] = $this->ask('Excluded table names');
    }

    /**
     * run all generated tests
     */
    private function runTests(){
        if($this->option('s')) return false;
        $process = new Process(['./vendor/bin/phpunit','--exclude-group','skip-test']);
        $process->start();
        foreach($process as $type => $data){
            if($process::OUT !== $type){
                $this->error($data);
                continue;
            }
            echo $data;
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->input();
        $this->getTableNames();
        $this->crud();
        $this->runTests();
    }
}