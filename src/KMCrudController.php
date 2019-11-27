<?php

namespace KM\KMCrud;

use App\Http\Controllers\Controller;
use App\Libraries\Permission;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use KM\KMCrud\Models\GenericRelationship;
use KM\KMCrud\Models\KMCategory;
use KM\KMCrud\Models\KMCategoryRelation;
use KM\KMCrud\Models\KMMedia;

class KMCrudController extends Controller
{
    private $model;
    private $kmModules;
    private $kmTaxanomies;

    public function __construct()
    {
        $this->kmModules = config('km_crud.modules');
        $this->kmTaxanomies = config('km_crud.taxanomy');
    }

    public function index()
    {
        $model_name = request()->segment(1);

        Permission::checkAndExit($model_name . '-view');

        $config = $this->kmModules[$model_name];
        $formConfig = $config::config();

        $modelClass = $formConfig['model'];
        $model = new $modelClass;

        return view('km_crud::index', compact('model_name', 'model', 'formConfig'));
    }

    public function view($id)
    {
        $model_name = request()->segment(1);
        $configClass = $this->kmModules[$model_name];
        $formConfig = $configClass::config();

        if (isset($formConfig['detailPage']['permission'])) {
            $hasPermission = $formConfig['detailPage']['permission']();
            if (!$hasPermission) {
                Permission::noAccess(true);
            }
        } else {
            Permission::checkAndExit($model_name . '-view');
        }

        $class = $formConfig['model'];
        $data_model = $class::findOrFail($id);

        $activities = Activity::where([
            'entity_type' => $model_name,
            'entity_id' => $data_model->id,
        ])->orderBy('created_at', 'DESC')->get();

        return view('km_crud::view', compact('data_model', 'model_name', 'configClass', 'activities'));
    }

    public function listAllJson()
    {
        $data['data'] = $this->getJsonData();
        $data['count'] = $this->getJsonData(true);

        return response()->json($data, Response::HTTP_OK);
    }

    public function getJsonData($count = false)
    {
        $model_name = request()->segment(1);
        Permission::checkAndExit($model_name . '-view');
        $configClass = $this->kmModules[$model_name];
        $config = $configClass::config();
        $request = request();
        $offset = $request->get('offset');
        $limit = $request->get('limit');
        $filters = $request->get('filter');
        $id = $request->get('id');

        $modelClass = $config['model'];
        $allData = $modelClass::orderBy('id', 'DESC');

        // Filter Starts
        if (isset($filters)) {
            foreach ($config['attributes'] as $name => $conf) {
                $condition1 = (isset($conf['search']) && $conf['search'] == true) ? true : false;
                $condition2 = (isset($conf['tableView']['default']) && !empty($conf['tableView']['default'])) ? true : false;
                if (($condition1 || $condition2) && isset($filters[$name]) && $filters[$name] != '') {
                    if ($conf['type'] == 'text') {
                        $allData->where($name, 'LIKE', '%' . $filters[$name] . '%');
                    }
                    if ($conf['type'] == 'modelSelect') {
                        $allData->whereHas($conf['relationship']['method'], function ($query) use ($filters, $name) {
                            $query->whereIn('to_id', $filters[$name]);
                        });
                    }
                    if ($conf['type'] == 'entitySelect') {
                        $allData->leftJoin('generic_relationships', $model_name . 's.id', '=', 'generic_relationships.from_id')
                            ->where('meta_key', $name)
                            ->where('entity_type', $model_name)
                            ->whereIn('to_id', $filters[$name])
                            ->select($model_name . 's.*');
                    }

                    if ($conf['type'] == 'customList_1') {
                        $method = $conf['relation']['relationMethod'];
                        $relId = $filters[$name];
                        $foreign_key = $conf['relation']['foreign_key'];
                        $allData->whereHas($method, function ($query) use ($relId, $foreign_key) {
                            $query->where($foreign_key, $relId);
                        });
                    }
                    if ($conf['type'] == 'customList_2') {
                        $allData->where($name, $filters[$name]);
                    }
                }
            }
        }
        // Filter Ends


        if ($count) {
            $count = $allData->count();
            return $count;
        }

        $results = $allData->offset($offset)
            ->limit($limit)
            ->get();

        $json_array = [];
        foreach ($results as $result) {

            $data = [];
            $data['id'] = $result->id;
            foreach ($config['attributes'] as $name => $conf) {

                if (isset($conf['relationship'])) {

                    $newCollection = [];
                    $collections = [];
                    $key = $conf['relationship']['key'];
                    $value = $conf['relationship']['value'];

                    if (isset($conf['relationship']['type']) && $conf['relationship']['type'] == 'hasOne') {
                        $collections[] = (object)[
                            $key => $result->{$conf['relationship']['method']}->{$key},
                            $value => $result->{$conf['relationship']['method']}->{$value}
                        ];
                    } else {
                        $collections = $result->{$conf['relationship']['method']};
                    }


                    foreach ($collections as $col) {

                        $newCollection[] = '<a href="' . url($conf['relationship']['modelName'] . '/view/' . $col->{$key}) . '">' . $col->{$value} . '</a>';
                    }
                    $data[$name] = implode(', ', $newCollection);//$collections->implode($conf['relationship']['value'], ', ');
                }
                elseif ($conf['type'] == 'image') {
                    $mediaUri = kmGetMedia($result->id, $model_name, $name);
                    $data[$name] = '';
                    if (!empty($mediaUri->uri)) {
                        $data[$name] = '<img src="' . $mediaUri->uri . '" height="50"/>';
                    }
                }
                elseif ($conf['type'] == 'taxonomy') {
                    $savedCategories = kmGetTaxonomyRelations($result->id, $model_name, $name, true);
                    $category_names = [];
                    if ($savedCategories->count() > 0) {
                        foreach ($savedCategories as $kk) {
                            $taxanomy = KMCategory::findOrFail($kk->category_id);
                            $category_names[] = $taxanomy->name;
                        }
                    }
                    $data[$name] = implode(', ', $category_names);
                }
                elseif ($conf['type'] == 'entitySelect') {
                    $savedCategories = kmGetGenericRelations($result->id, $model_name, $name);
                    $category_names = [];
                    if ($savedCategories->count() > 0) {
                        foreach ($savedCategories as $kk) {
                            $relModelName = $conf['modelName'];
                            $taxonomy = $conf['model']::findOrFail($kk->to_id);
                            if (isset($conf['customLink'])) {
                                $link = $conf['customLink'];
                                $link = str_replace('{id}', $kk->to_id, $link);
                                $category_names[] = '<a href="' . $link . '">' . $taxonomy->name . '</a>';
                            } else {
                                $category_names[] = '<a href="' . url($relModelName . '/view/' . $kk->to_id) . '">' . $taxonomy->name . '</a>';
                            }
                        }
                    }
                    $data[$name] = implode(', ', $category_names);
                }
                elseif ($conf['type'] == 'customList_1') {

                    $newCollection = [];
                    $key = $conf['options']['key'];
                    $value = $conf['options']['value'];
                    $collections = $result->{$conf['relation']['relationMethod']};

                    foreach ($collections as $col) {
                        $newCollection[] = $col->{$value};//'<a href="' . url($conf['relation']['relationMethod'] . '/view/' . $col->{$key}) . '">' . $col->{$value} . '</a>';
                    }

                    $data[$name] = implode(', ', $newCollection);

                }
                elseif ($conf['type'] == 'customList_2') {
                    $value = $conf['options']['value'];
                    $collections = $conf['options']['data']();
                    $rej = $collections->firstWhere('id', '=', $result->{$name});
                    $data[$name] = isset($rej->{$value}) ? $rej->{$value} : '';//isset($conf['options']['data']()[$result->{$name}]->{$value}) ? $conf['options']['data']()[$result->{$name}]->{$value} : '';
                }
                elseif ($conf['type'] == 'customRender') {
                    $function = $conf['render']['function'];
                    $data[$name] = $function($result);
                }
                elseif (isset($result->{$name})) {
                    $data[$name] = kmMBWordWrap($result->{$name}, 100);
                }
                else {
                    //Prefix data
                    if (isset($conf['tableView']['prefix'])) {
                        $function = $conf['tableView']['prefix'];
                        $data[$name] = $function($result) . ' ' . $data[$name];
                    } else {
                        $data[$name] = '';
                    }
                }
            }

            $json_array[] = $data;
        }

        return $json_array;

    }

    public function form_backup($id = null)
    {
        $model_name = request()->segment(1);
        if ($id) {
            Permission::checkAndExit($model_name . '-edit');
        } else {
            Permission::checkAndExit($model_name . '-create');
        }

        $configClass = $this->kmModules[$model_name];
        $formConfig = $configClass::config();
        $className = strtolower($model_name);
        $view = view('km_crud::form');
        $view->formAction = "$className/save";
        $class = $formConfig['model'];
        $view->dataModel = new $class;
        $view->configClass = $configClass;
        $view->modelName = $model_name;

        $destination = request()->get('destination');
        if ($destination) {
            $destination = url($destination);
        } else {
            $destination = url($view->model_name);
        }

        $view->redirectDestination = $destination;

        if (!is_null($id)) {
            $view->dataModel = $class::findOrFail($id);

            $view->formAction .= "/{$view->dataModel->id}";
        }

        return $view;
    }

    public function form($id = null)
    {
        $model_name = request()->segment(1);
        if ($id) {
            Permission::checkAndExit($model_name . '-edit');
        } else {
            Permission::checkAndExit($model_name . '-create');
        }

        $configClass = $this->kmModules[$model_name];
        $formConfig = $configClass::config();
        $className = strtolower($model_name);
        $view = view('km_crud::form');
        $class = $formConfig['model'];
        $view->dataModel = new $class;
        $view->configClass = $configClass;
        $view->modelName = $model_name;

        $redirectDestination = '';
        $destination = request()->get('destination');
        if ($destination) {
            $redirectDestination = '?destination=' . $destination;
        }

        $view->formAction = "$className/save" . $redirectDestination;
        $view->redirectDestination = $destination;

        if (!is_null($id)) {
            $view->dataModel = $class::findOrFail($id);

            $idd = $view->dataModel->id;
            $view->formAction .= "/{$idd}" . $redirectDestination;
        }

        return $view;
    }

    public function save($id = null)
    {
        $model_name = request()->segment(1);
        if ($id) {
            Permission::checkAndExit($model_name . '-edit');
        } else {
            Permission::checkAndExit($model_name . '-create');
        }
        try {

            $configClass = $this->kmModules[$model_name];
            $formConfig = $configClass::config();
            $modelClass = $formConfig['model'];
            $mod = new $modelClass;

            $request = request();

            // Validation Start
            $formConfig = $configClass::config();
            $validate = [];
            $validateMessage = [];
            $input = [];

            foreach ($formConfig['attributes'] as $name => $config) {
                $input[$name] = $request->get($name);
                if (isset($config['validate'])) {
                    $validate[$name] = $config['validate'];
                    if ($id != "") {
                        $validate[$name] = str_replace('!id', $id, $validate[$name]);
                    }
                    if (isset($config['validationMessage']) && !empty($config['validationMessage'])) {
                        $validateMessage[$name . '.required'] = $config['validationMessage'];
                    }
                }

                if ($config['type'] == 'password' && !empty($input[$name])) {
                    $input[$name] = bcrypt($input[$name]);
                }

                if ($id != "" && $config['type'] == 'password' && empty($input[$name])) {
                    unset($validate[$name]);
                    if (isset($config['validationMessage']) && !empty($config['validationMessage'])) {
                        unset($validateMessage[$name . '.required']);
                    }
                    unset($input[$name]);
                }

                if (($config['type'] == 'dateTime' || $config['type'] == 'date') && !empty($input[$name])) {
                    $input[$name] = date('Y-m-d h:i:s', strtotime($input[$name]));
                }
            }

            $validation = Validator::make($request->all(), $validate, $validateMessage);

            //exit();
            if ($validation->fails()) {
                $errors = $validation->errors();
                throw new \Exception($errors, Response::HTTP_BAD_REQUEST);
            }

            // Validation ends

            if ($id > 0) {
                $data_model = $modelClass::findOrFail($id);
                if (isset($mod->editor_id)) {
                    $input['editor_id'] = Auth::user()->id;
                }

                $data_model->fill($input)->save();

                $request->session()->flash('flash_message', 'Successfully Updated');

                $insertOrUpdate = 'update';

            } else {

                if (Schema::hasColumn($model_name . 's', 'creator_id')) {
                    $input['creator_id'] = Auth::user()->id;
                }
                if (Schema::hasColumn($model_name . 's', 'editor_id')) {
                    $input['editor_id'] = Auth::user()->id;
                }
                if (Schema::hasColumn($model_name . 's', 'status')) {
                    $input['status'] = 1;
                }
                $data_model = $modelClass::create($input);
                $request->session()->flash('flash_message', 'Successfully Created');

                $insertOrUpdate = 'insert';

            }

            //Media
            foreach ($formConfig['attributes'] as $name => $config) {
                if ($config['type'] == 'image') {
                    $mediaName = $request->get($name);

                    KMMedia::where([
                        'entity_id' => $data_model->id,
                        'entity_type' => $model_name,
                        'field_name' => $name,
                    ])->delete();

                    if (!empty($mediaName)) {
                        $mediaInput = [
                            'entity_id' => $data_model->id,
                            'entity_type' => $model_name,
                            'field_name' => $name,
                            'name' => $mediaName,
                            'short_descrption' => '',
                            'description' => '',
                            'media_type' => 'image',
                            'extension' => '',
                            'path' => '',
                            'size' => 0,
                            'status' => 1,
                            'uri' => url('uploads/' . $mediaName),
                        ];

                        KMMedia::create($mediaInput);
                    }
                }

                if ($config['type'] == 'reference') {
                    $uploads = $request->get($name);

                    KMMedia::where([
                        'entity_id' => $data_model->id,
                        'entity_type' => $model_name,
                        'field_name' => $name,
                    ])->delete();

                    foreach ($uploads as $mediaName) {

                        $path = public_path() . '/uploads/reference/' . $mediaName;
                        $info = pathinfo($path);

                        $mediaInput = [
                            'entity_id' => $data_model->id,
                            'entity_type' => $model_name,
                            'field_name' => $name,
                            'name' => $mediaName,
                            'short_descrption' => '',
                            'description' => '',
                            'media_type' => 'reference_file',
                            'extension' => '',
                            'path' => '',
                            'size' => 0,
                            'status' => 1,
                            'uri' => url('uploads/reference/' . $mediaName),
                        ];
                        KMMedia::create($mediaInput);
                    }
                }
            }

            //Relationships
            //TODO: make it more flexible
            foreach ($formConfig['attributes'] as $name => $config) {
                if (isset($config['relationship']) && !empty($config['relationship'])) {
                    //Many To Many
                    if ($config['relationship']['type'] == 'many_to_many' || $config['relationship']['type'] == 'one_to_one') {
                        $relationArrayValues = $request->get($name);
                        $relationData = [];
                        foreach ($relationArrayValues as $value) {
                            $relationData[] = [
                                'entity_type' => $model_name,
                                'meta_key' => $name,
                                'from_id' => $data_model->id,
                                'to_id' => $value,
                            ];
                        }
                        $method = $config['relationship']['method'];
                        $data_model->$method()->sync($relationData);
                    }
                }

                if ($config['type'] == 'taxonomy') {
                    if (isset($config['multiple']) && $config['multiple'] == true) {

                        if ($insertOrUpdate == 'update') {
                            KMCategoryRelation::where([
                                'entity_id' => $data_model->id,
                                'entity_type' => $model_name,
                                'field_name' => $name,
                            ])->delete();
                        }
                        $category_ids = $request->get($name);
                        foreach ($category_ids as $category_id) {
                            $categoryRelationInput = [
                                'entity_id' => $data_model->id,
                                'entity_type' => $model_name,
                                'field_name' => $name,
                                'category_id' => $category_id,
                            ];
                            KMCategoryRelation::create($categoryRelationInput);
                        }

                    } else {
                        $category_id = $request->get($name);
                        if ($insertOrUpdate == 'update') {
                            KMCategoryRelation::where([
                                'entity_id' => $data_model->id,
                                'entity_type' => $model_name,
                                'field_name' => $name,
                            ])->delete();
                        }
                        $categoryRelationInput = [
                            'entity_id' => $data_model->id,
                            'entity_type' => $model_name,
                            'field_name' => $name,
                            'category_id' => $category_id,
                        ];
                        KMCategoryRelation::create($categoryRelationInput);
                    }
                }

                if ($config['type'] == 'entitySelect') {
                    if (isset($config['multiple']) && $config['multiple'] == true) {

                        if ($insertOrUpdate == 'update') {
                            GenericRelationship::where([
                                'from_id' => $data_model->id,
                                'entity_type' => $model_name,
                                'meta_key' => $name,
                            ])->delete();
                        }
                        $category_ids = $request->get($name);
                        foreach ($category_ids as $category_id) {
                            $categoryRelationInput = [
                                'from_id' => $data_model->id,
                                'entity_type' => $model_name,
                                'meta_key' => $name,
                                'to_id' => $category_id,
                            ];
                            GenericRelationship::create($categoryRelationInput);
                        }

                    } else {
                        $category_id = $request->get($name);
                        if ($insertOrUpdate == 'update') {
                            GenericRelationship::where([
                                'from_id' => $data_model->id,
                                'entity_type' => $model_name,
                                'meta_key' => $name,
                            ])->delete();
                        }
                        $categoryRelationInput = [
                            'from_id' => $data_model->id,
                            'entity_type' => $model_name,
                            'meta_key' => $name,
                            'to_id' => $category_id,
                        ];
                        GenericRelationship::create($categoryRelationInput);
                    }
                }
            }

            //Activity
            //TODO: make the track of every detail
            $activity = [
                'entity_type' => $model_name,
                'entity_id' => $data_model->id,
                'type' => $insertOrUpdate,
                'user_id' => Auth::user()->id,
            ];

            Activity::create($activity);

            // CustomList Relationship
            foreach ($formConfig['attributes'] as $name => $config) {
                if ($config['type'] == 'customList_1') {
                    if (isset($config['multiple']) && $config['multiple'] == true) {
                        //TODO:under construction
                    } else {
                        $entity_id = $config['relation']['entity_id'];
                        $foreign_key = $config['relation']['foreign_key'];
                        $rel = request()->get($name);

                        if (isset($config['relation']['relationMethod'])) {

                            $method = $config['relation']['relationMethod'];

                            if (method_exists($modelClass, $method)) {
                                $table = $config['relation']['table'];
                                DB::table($table)
                                    ->where($entity_id, '=', $data_model->id)->delete();
                                if (!empty($rel)) {
                                    $relationData = [
                                        $entity_id => $data_model->id,
                                        $foreign_key => $rel
                                    ];
                                    //$data_model->$method()->sync($relationData);
                                    DB::table($table)->insert($relationData);
                                }
                            }
                        }
                    }
                }
            }

            $state['destination'] = url($model_name . '/view/' . $data_model->id);
            if (!empty($request->get('destination'))) {
                $state['destination'] = url($request->get('destination'));
            }
            if (method_exists($configClass, 'saveCallback')) {
                $configClass::saveCallback($data_model, $insertOrUpdate, $state);
            }
            $response = [
                'status' => Response::HTTP_OK,
                'error' => false,
                'message' => 'Successfully saved',
                'data' => [
                    'destination' => $state['destination']
                ],
            ];

            return response()->json($response, Response::HTTP_OK);

        } catch (\Exception $exception) {
            $code = !empty($exception->getCode()) ? $exception->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

            $response = [
                'status' => $code,
                'error' => true,
                'message' => $exception->getMessage(),
                'data' => [],
            ];

            if ($code != Response::HTTP_BAD_REQUEST) {
                http_response_code(500);
                echo $exception->getMessage();
                exit();
            }

            return response()->json($response, $code);
        }
    }

    public function delete($id)
    {
        $model_name = request()->segment(1);
        Permission::checkAndExit($model_name . '-delete');
        $config = $this->kmModules[$model_name];
        $formConfig = $config::config();

        $class = $formConfig['model'];
        $data_model = $class::findOrFail($id);
        $data_model->delete();

        return redirect($model_name);
    }

    public function listAllRelJson()
    {
        $data['data'] = $this->getRelJsonData();
        $data['count'] = $this->getRelJsonData(true);

        return response()->json($data, Response::HTTP_OK);
    }

    public function getRelJsonData($count = false)
    {
        $model_name = request()->segment(1);
        Permission::checkAndExit($model_name . '-view');
        $configClass = $this->kmModules[$model_name];
        $config = $configClass::config();
        $request = request();
        $offset = $request->get('offset');
        $limit = $request->get('limit');
        $filter = $request->get('filter');

        $relationModelClass = $config['model'];
        $relId = $filter['rel_id'];
        $relationKey = $filter['relationKey'];


        if (isset($config['attributes'][$relationKey]) && $config['attributes'][$relationKey]) {
            $type = $config['attributes'][$relationKey]['type'];
            $collections = $relationModelClass::orderBy('id', 'DESC');

            if ($type == 'entitySelect') {
                $collections->
                leftJoin('generic_relationships', $model_name . 's.id', '=', 'generic_relationships.from_id')
                    ->where([
                        'to_id' => $relId,
                        'entity_type' => $model_name,
                        'meta_key' => $relationKey,
                    ])
                    ->select($model_name . 's.*');
            }

            if ($type == 'customList_1') {
                $method = $config['attributes'][$relationKey]['relation']['relationMethod'];
                $entity_id = $config['attributes'][$relationKey]['relation']['entity_id'];
                $foreign_key = $config['attributes'][$relationKey]['relation']['foreign_key'];
                $collections->whereHas($method, function ($query) use ($relId, $foreign_key) {
                    $query->where($foreign_key, $relId);
                });
                //$collections->whereHas();
            }

            if ($type == 'customList_2') {
                $collections->where($relationKey, $relId);
            }
            // Filter Starts
            if (isset($filter)) {
                foreach ($config['attributes'] as $name => $conf) {
                    if (isset($conf['relationSearch']) && $conf['relationSearch'] === TRUE && isset($filter[$name]) && $filter[$name] != '') {
                        if ($conf['type'] == 'text') {
                            $collections->where($name, 'LIKE', '%' . $filter[$name] . '%');
                        }
                        if ($conf['type'] == 'modelSelect') {
                            $collections->whereHas($conf['relationship']['method'], function ($query) use ($filter, $name) {
                                $query->whereIn('to_id', $filter[$name]);
                            });
                        }
                        if ($conf['type'] == 'entitySelect') {
                            $collections->whereIn('to_id', $filter[$name]);// ->select($model_name . 's.*');
                        }
                    }
                }
            }
        }

        if ($count) {
            $count = $collections->count();
            return $count;
        }

        $results = $collections->offset($offset)
            ->limit($limit)
            ->orderBy('id', 'DESC')
            ->get();

        $json_array = [];
        foreach ($results as $result) {

            $data = [];
            $data['id'] = $result->id;
            foreach ($config['attributes'] as $name => $conf) {
                if (isset($result->{$name})) {
                    if ($conf['type'] == 'dateTime') {
                        $data[$name] = date('m/d/Y', strtotime($result->{$name}));
                    } else {
                        $data[$name] = kmMBWordWrap($result->{$name}, 70);
                    }
                } elseif (isset($conf['relationship'])) {
                    $collections = $result->{$conf['relationship']['method']};
                    $data[$name] = $collections->implode($conf['relationship']['value'], ', ');
                } elseif ($conf['type'] == 'entitySelect') {
                    $savedCategories = kmGetGenericRelations($result->id, $model_name, $name);
                    $category_names = [];
                    if ($savedCategories->count() > 0) {
                        foreach ($savedCategories as $kk) {
                            $taxonomy = $conf['model']::findOrFail($kk->to_id);
                            $category_names[] = $taxonomy->name;
                        }
                    }
                    $data[$name] = implode(', ', $category_names);
                } elseif ($conf['type'] == 'customRender') {
                    $function = $conf['render']['function'];
                    $data[$name] = $function($result);
                }

                //Prefix data
                if (isset($conf['tableView']['prefix'])) {
                    $function = $conf['tableView']['prefix'];
                    $data[$name] = $function($result) . ' ' . $data[$name];
                }
            }

            $json_array[] = $data;
        }

        return $json_array;

    }

    public function txIndex($category_type)
    {
        $taxonomyConfiguration = $this->kmTaxanomies[$category_type];
        return view('km_crud::tx-index', compact('category_type', 'taxonomyConfiguration'));
    }

    public function txForm($category_type, $id = null)
    {

        $allTaxonomy = KMCategory::where([
            'category_type' => $category_type
        ])->get();

        $view = view('km_crud::tx-form', compact('category_type'));
        $view->formAction = "taxonomy/$category_type/save";

        $view->model = new KMCategory();
        if (!is_null($id)) {
            $view->model = KMCategory::findOrFail($id);
            $view->formAction .= "/{$view->model->id}";
        }
        $view->allTaxonomy = $allTaxonomy;

        $destination = request()->get('destination');
        if ($destination) {
            $destination = url($destination);
        } else {
            $destination = url('taxonomy/' . $category_type);
        }
        $view->redirectDestination = $destination;

        return $view;
    }

    public function txSave($category_type, $id = null)
    {
        try {

            $request = request();
            $input = $request->all();
            $validate = [
                'name' => 'required'
            ];

            $validateMessage = [
                'name.required' => __('This field is required')
            ];

            $validation = Validator::make($input, $validate, $validateMessage);

            if ($validation->fails()) {
                $errors = $validation->errors();
                throw new \Exception($errors, Response::HTTP_BAD_REQUEST);
            }

            if ($id > 0) {

                $data_model = KMCategory::findOrFail($id);
                $input['editor_id'] = Auth::user()->id;
                $data_model->fill($input)->save();
                $request->session()->flash('flash_message', 'Successfully Updated');
                $insertOrUpdate = 'update';

            } else {

                $weight = 1;
                $lastItem = KMCategory::where('category_type', $category_type)->orderBy('id', 'desc')->first();
                if ($lastItem) {
                    $weight = $lastItem->weight + 1;
                }
                $input['category_type'] = $category_type;
                $input['parent_id'] = $input['parent_id'] > 0 ? $input['parent_id'] : 0;
                $input['creator_id'] = Auth::user()->id;
                $input['editor_id'] = Auth::user()->id;
                $input['status'] = 1;
                $input['weight'] = $weight;
                $data_model = KMCategory::create($input);
                $request->session()->flash('flash_message', 'Successfully Created');
                $insertOrUpdate = 'insert';
            }

            $response = [
                'status' => Response::HTTP_OK,
                'error' => false,
                'message' => 'Successfully saved',
                'data' => [],
            ];

            return response()->json($response, Response::HTTP_OK);

        } catch (\Exception $exception) {
            $code = !empty($exception->getCode()) ? $exception->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

            $response = [
                'status' => $code,
                'error' => true,
                'message' => $exception->getMessage(),
                'data' => [],
            ];

            return response()->json($response, $code);
        }
    }

    public function txListAllJson($category_type)
    {
        $data['data'] = $this->getTxJsonData($category_type);
        $data['count'] = $this->getTxJsonData($category_type, true);

        return response()->json($data, Response::HTTP_OK);
    }

    public function getTxJsonData($category_type, $count = false)
    {
        $request = request();
        $offset = $request->get('offset');
        $limit = $request->get('limit');
        $filters = $request->get('filter');
        $id = $request->get('id');

        $allData = KMCategory::where('category_type', $category_type);

        if ($count) {
            $count = $allData->count();
            return $count;
        }

        $results = $allData->orderBy('weight', 'ASC')->offset($offset)
            ->limit($limit)
            ->get();

        $json_array = [];
        foreach ($results as $result) {

            $json_array[] = [
                'id' => $result->id,
                'name' => $result->name,
                'description' => $result->description,
                'parent_id' => $result->parent_id,
                'weight' => $result->weight,
                'parent' => isset($result->parentCategory->name) ? $result->parentCategory->name : '',
                'status' => $result->status,
                'category_type' => $result->category_type,
                'created_at' => $result->created_at,
                'updated_at' => $result->updated_at,
            ];
        }

        return $json_array;
    }

    public function weightUpdate(Request $request)
    {
        $weightList = $request->get('list');
        foreach ($weightList as $taxonomyId => $weight) {

            $category = KMCategory::findOrFail($taxonomyId);
            $input = [
                'weight' => $weight
            ];

            $category->fill($input)->save();
        }

        echo "success";
        exit();
    }

}
