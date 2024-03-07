<?php namespace App\Http\Controllers\Crud;

use Illuminate\Http\Request;
use Vladmunj\CrudGenerator\Traits\RestActions;
use App\Http\Controllers\Controller;

class ParamController extends Controller{
    const MODEL = 'App\Models\Crud\ParamModel';

    use RestActions{
        all as protected all;
        get as protected get;
        create as protected create;
        update as protected update;
        delete as protected delete;
    }
}