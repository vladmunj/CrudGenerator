<?php namespace Vladmunj\CrudGenerator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Vladmunj\CrudGenerator\Exceptions\CommandException;
use Illuminate\Support\Facades\File;
use Vladmunj\CrudGenerator\Builders\Controller;
use Vladmunj\CrudGenerator\Builders\Router;
use Vladmunj\CrudGenerator\Builders\Model;
use Vladmunj\CrudGenerator\Builders\ModelFactory;
use Vladmunj\CrudGenerator\Builders\UnitTest;
use Symfony\Component\Process\Process;

class CrudMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create controller with CRUD operations and OpeApi annotations.';

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
     * Transform input data to required format
     */
    private function prepareData(){
        $this->params['crud_url'] = '/'.trim($this->params['crud_url'],'/');
    }

    /**
     * Validate command options
     */
    private function validate(){
        $validator = Validator::make($this->params,[
            'controller_name' => ['required'],
            'crud_url' => ['required'],
            'model_name' => ['required'],
            'table_name' => ['required']
        ]);
        if($validator->fails()){
            foreach($validator->errors()->all() as $error){
                $this->error($error);
            }
            throw new CommandException('Validation failed');
        }
        if(empty($this->params['author'])) throw new CommandException('Add PACKAGE_AUTHOR variable to your .env file');
    }

    /**
     * Asking user for set parameters
     */
    private function input(){
        $this->params['controller_name'] = $this->ask('Controller name');
        $this->params['crud_url'] = $this->ask('CRUD url');
        $this->params['model_name'] = $this->ask('Model name');
        $this->params['table_name'] = $this->ask('Table name');
        $this->params['author'] = env('PACKAGE_AUTHOR');
    }

    /**
     * run generated test for current model
     */
    private function runTests(){
        $process = new Process(['./vendor/bin/phpunit --filter '.$this->params['model_name'].'Test']);
        $process->start();
        foreach($process as $type => $data){
            if($process::OUT !== $type){
                $this->error($data);
                continue;
            }
            $this->info($data);
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
        $this->validate();
        $this->prepareData();
        (new Controller($this->params))->build();
        (new Router($this->params))->build();
        (new Model($this->params))->build();
        (new ModelFactory($this->params))->build();
        (new UnitTest($this->params))->build();
        $this->runTests();
    }
}
