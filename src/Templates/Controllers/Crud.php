<?php namespace App\Http\Controllers\Crud;

use Illuminate\Http\Request;
use Vladmunj\CrudGenerator\Traits\RestActions;
use App\Http\Controllers\Controller;

class ParamController extends Controller{
    const MODEL = 'App\Models\Crud\ParamModel';

    use RestActions;
}