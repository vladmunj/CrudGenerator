<?php namespace Vladmunj\CrudGenerator\Console;

use Illuminate\Console\Command;
use Vladmunj\CrudGenerator\Exceptions\CommandException;

class TestsGenCommand extends Command{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:gen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate tests for all endpoints in project';

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
        dump('test gen');
    }
}