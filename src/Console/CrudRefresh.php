<?php namespace Vladmunj\CrudGenerator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;

class CrudRefresh extends Command{
    const EXCLUDED_TABLES = ['migrations', 'password_resets', 'failed_jobs', 'error_logs'];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh migrations & generate CRUD';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->params = [];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tablesData = $this->loadTablesData();
        $foreignKeysInfo = [];
        foreach($tablesData as $table){
            $foreignKeysInfo = array_merge($foreignKeysInfo, $this->getForeignKeysInfo($table['name']));
            $this->dropMigrationsHistory($table);
            $this->dropTable($table);
        }
        $this->migrate();
        $this->seed($tablesData);
        $this->restoreForeignKeys($foreignKeysInfo);
        $this->crud();
    }

    /**
     * Seed tables
     * @param array $tablesData
     * @return void
     */
    private function seed($tablesData){
        foreach($tablesData as $table){
            $model = '\\App\\Models\\'.implode('',array_map(function($part){
                return Str::ucfirst(Str::singular($part));
            },explode("_",$table['name'])));
            try{
                $model::factory()->create();
            }catch(\Exception $e){
                dump('Factory for '.$model.' not found');
            }
        }
    }

    /**
     * Load tables data from migrations files
     * @return array
     */
    private function loadTablesData() : array{
        $migrations = preg_grep('/^([^.])/', scandir(database_path('migrations')));
        $tableNames = [];
        foreach($migrations as $migration){
            preg_match('/Schema::create\(\'(.*)\', function/', file_get_contents(database_path('migrations/'.$migration)), $matches);
            if(count($matches) <= 1) continue;
            if(in_array($matches[1], self::EXCLUDED_TABLES)) continue;
            $tableNames[] = [
                'name' => $matches[1],
                'filename' => $migration
            ];
        }
        return $tableNames;
    }

    /**
     * Get foreign keys info
     * @param string $tableName
     * @return array
     */
    private function getForeignKeysInfo($tableName){
        return DB::table('information_schema.constraint_column_usage')
            ->select('r.table_name','r.column_name')
            ->join('information_schema.referential_constraints as fk', function($join){
                $join->on('fk.unique_constraint_catalog', '=', 'constraint_column_usage.constraint_catalog')
                    ->on('fk.unique_constraint_schema', '=', 'constraint_column_usage.constraint_schema')
                    ->on('fk.unique_constraint_name', '=', 'constraint_column_usage.constraint_name');
            })
            ->join('information_schema.key_column_usage as r', function($join){
                $join->on('r.constraint_catalog', '=', 'fk.constraint_catalog')
                    ->on('r.constraint_schema', '=', 'fk.constraint_schema')
                    ->on('r.constraint_name', '=', 'fk.constraint_name');
            })
            ->where('constraint_column_usage.column_name', 'id')
            ->where('constraint_column_usage.table_catalog', env('DB_DATABASE'))
            ->where('constraint_column_usage.table_schema', 'public')
            ->where('constraint_column_usage.table_name', $tableName)
            ->get()->toArray();
    }

    /**
     * Restore foreign keys
     * @param array $foreignKeysInfo
     * @return void
     */
    private function restoreForeignKeys($foreignKeysInfo){
        foreach($foreignKeysInfo as $foreignKey){
            try{
                DB::statement('ALTER TABLE '.$foreignKey->table_name.' ADD CONSTRAINT '.$foreignKey->table_name.'_'.$foreignKey->column_name.'_foreign FOREIGN KEY ('.$foreignKey->column_name.') REFERENCES '.$foreignKey->table_name.'(id);');
                $this->info($foreignKey->table_name.'.'.$foreignKey->column_name.' restored');
            }catch(\Exception $e){
                $this->error($foreignKey->table_name.'.'.$foreignKey->column_name.' not restored');
            }
        }
    }

    /**
     * Drop migrations history
     * @param array $tablesData
     * @return void
     */
    private function dropMigrationsHistory($table) : void{
        DB::table('migrations')->where('migration', 'like', '%'.$table['filename'].'%')->delete();
    }

    /**
     * Drop tables
     * @param array $table
     * @return void
     */
    private function dropTable($table) : void{
        DB::statement('DROP TABLE IF EXISTS '.$table['name'].' CASCADE;');
    }

    /**
     * Migrate tables
     * @return void
     */
    private function migrate() : void{
        $this->call('migrate');
    }

    /**
     * Create CRUD
     * @return void
     */
    private function crud() : void{
        $this->call('make:crud:table',['--s' => true]);
    }
}