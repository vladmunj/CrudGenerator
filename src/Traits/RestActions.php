<?php namespace Vladmunj\CrudGenerator\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * trait for all REST actions
 * controller must have constant with model's name
 * model must have public static $rules array to validate input data
 */
trait RestActions{
    public function get(Request $request): mixed{
        if($request->filled('id')){
            if(!is_numeric($request->id)) return response()->json('Not found',404);
            return response()->json(self::$MODEL::find($request->id),200);
        }
        if($request->filled('limit')) return response()->json(self::$MODEL::limit($request->limit)->get(),200);
        return response()->json(self::$MODEL::all(),200);
    }

    public function create(Request $request): mixed{
        $this->validate($request,self::$MODEL::$rules);
        $fields = Arr::only($request->all(),array_keys(self::$MODEL::$rules));
        if($request->filled('id')) $fields['id'] = $request->id;
        if($model = self::$MODEL::where($fields)->first()) return response()->json('Found',302);
        return response()->json(self::$MODEL::create($fields),201);
    }

    public function update(Request $request): mixed{
        $this->validate($request,array_merge(self::$MODEL::$rules,[
            'id'    =>  'required|numeric|exists:'.self::$MODEL::$tableStatic.',id'
        ]));
        $model = self::$MODEL::find($request->id);
        $model->update($request->all());
        return response()->json($model,200);
    }

    public function delete(Request $request): mixed{
        $this->validate($request,[
            'id'    =>  'required|numeric|exists:'.self::$MODEL::$tableStatic.',id'
        ]);
        self::$MODEL::destroy($request->id);
        return response()->json(self::$MODEL.' with id: '.$request->id.' removed',204);
    }
}