<?php namespace Vladmunj\CrudGenerator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;

class CrudRefreshCommand extends Command{
    const EXCLUDED_TABLES = ['migrations', 'password_resets', 'failed_jobs', 'error_logs'];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:refresh {--nocrud}';

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
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->migrate(); // validate migrations is correct before drop tables and data
        $tablesData = $this->loadTablesData();
        $foreignKeysInfo = [];
        foreach($tablesData as $table){
            $foreignKeysInfo[$table['name']] = $this->getForeignKeysInfo($table['name']);
            $this->dropMigrationsHistory($table);
            $this->truncateForeignTables($table);
            $this->dropTable($table);
        }
        $this->migrate();
        if($this->option('nocrud') == false) $this->crud();
        foreach($tablesData as $table){
            $this->restoreForeignKeys($foreignKeysInfo,$table);
        }
        $this->seed();
    }

    /**
     * Seed tables
     * @param array $tablesData
     * @return void
     */
    private function seed(){
        $this->call('db:seed');
    }

    /**
     * Load tables data from migrations files
     * @return array
     */
    private function loadTablesData() : array{
        $migrations = preg_grep('/^([^.])/', scandir(database_path('migrations')));
        $tableNames = [];
        foreach($migrations as $migration){
            preg_match_all('/Schema::create\(\'(.*)\', function/', file_get_contents(database_path('migrations/'.$migration)), $matches);
            if(count($matches) <= 1) continue;
            foreach($matches[1] as $tableName){
                if(in_array($tableName, self::EXCLUDED_TABLES)) continue;
                $tableNames[] = [
                    'name' => $tableName,
                    'filename' => $migration
                ];
            }
        }
        $this->info(join(",",array_map(function($table){
            return $table['name'];
        },$tableNames)).' tables loaded');
        return $tableNames;
    }

    /**
     * Get foreign keys info
     * @param string $tableName
     * @return array
     */
    private function getForeignKeysInfo($tableName){
        return DB::table('information_schema.constraint_column_usage as ccu')
        ->select('r.table_name','r.column_name','r.constraint_name')
        ->join('information_schema.referential_constraints as fk', function($join){
            $join->on('fk.unique_constraint_catalog', '=', 'ccu.constraint_catalog')
                ->on('fk.unique_constraint_schema', '=', 'ccu.constraint_schema')
                ->on('fk.unique_constraint_name', '=', 'ccu.constraint_name');
        })
        ->join('information_schema.key_column_usage as r', function($join){
            $join->on('r.constraint_catalog', '=', 'fk.constraint_catalog')
                ->on('r.constraint_schema', '=', 'fk.constraint_schema')
                ->on('r.constraint_name', '=', 'fk.constraint_name');
        })
        ->where('ccu.column_name', 'id')
        ->where('ccu.table_catalog', env('DB_DATABASE'))
        ->where('ccu.table_schema', 'public')
        ->where('ccu.table_name', $tableName)
        ->get()->toArray();
    }

    /**
     * Restore foreign keys
     * @param array $foreignKeysInfo
     * @return void
     */
    private function restoreForeignKeys($foreignKeysInfo){
        foreach($foreignKeysInfo as $tableName => $foreignKeys){
            foreach($foreignKeys as $foreignKey){
                try{
                    DB::statement('ALTER TABLE '.$foreignKey->table_name.' ADD CONSTRAINT '.$foreignKey->constraint_name.' FOREIGN KEY ('.$foreignKey->column_name.') REFERENCES '.$tableName.'(id);');
                    $this->info($foreignKey->constraint_name.' restored');
                }catch(\Exception $e){
                    if(strpos($e->getMessage(),'already exists')) $this->warn($foreignKey->constraint_name.' not restored. Details: '.$e->getMessage());
                    else $this->error($foreignKey->constraint_name.' not restored. Details: '.$e->getMessage());
                }
            }
        }
    }

    /**
     * Drop migrations history
     * @param array $tablesData
     * @return void
     */
    private function dropMigrationsHistory($table) : void{
        DB::table('migrations')->where('migration', str_replace('.php','',$table['filename']))->delete();
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
     * Truncate foreign tables
     * @param array $table
     * @return void
     */
    private function truncateForeignTables($table) : void{
        $foreignKeys = $this->getForeignKeysInfo($table['name']);
        foreach($foreignKeys as $foreignKey){
            DB::table($foreignKey->table_name)->truncate();
            $this->info($foreignKey->table_name.' truncated');
        }
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