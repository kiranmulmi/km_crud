<?php

function kmGetMedia($entityId, $entityType, $fieldType, $multiple = false)
{
    $media = \KM\KMCrud\Models\KMMedia::where([
        'entity_id' => $entityId,
        'entity_type' => $entityType,
        'field_name' => $fieldType,
    ]);

    if ($multiple) {
        $data = $media->get();
        return $data;
    }

    $data = $media->first();
    if (!$data) {
        $data = new \KM\KMCrud\Models\KMMedia();
        $data->uri = url('vendor/km/km_lib/images/default_image.jpg');
    }

    return $data;
}

function kmMakeModelName($model_name)
{
    $explode = explode('-', $model_name);
    $explode = array_map('ucfirst', $explode);
    $modelName = implode('', $explode);
    return $modelName;
}

function kmRenderBreadCrumb($array)
{
    $breadcrumb = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    $lastElement = end($array);
    foreach ($array as $link => $name) {
        $attribute = 'class="breadcrumb-item"';
        if ($lastElement == $name) {
            $attribute = 'class="breadcrumb-item active" aria-current="page"';
        }

        if ($link == 'nolink') {
            $breadcrumb .= '<li ' . $attribute . '>' . $name . '</li>';
        } else {
            $breadcrumb .= '<li ' . $attribute . '><a href="' . url($link) . '">' . $name . '</a></li>';
        }
    }
    $breadcrumb .= '</ol></nav>';
    return $breadcrumb;

}

function kmGetIndexPageActionButtonsHtml($model_name, $formConfig)
{
    $action_html = '';
    if (!empty($formConfig['actions'])) {
        foreach ($formConfig['actions'] as $action => $status) {
            if ($status == true && $action == 'edit') {
                if (\App\Libraries\Permission::check($model_name . '-edit')) {
                    $action_html .= '<a class="btn btn-info" href="' . url($model_name . '/edit/{id}') . '"><i class="fa fa-edit" aria-hidden="true"></i></a> ';
                }
            }
            if ($status == true && $action == 'delete') {
                if (\App\Libraries\Permission::check($model_name . '-delete')) {
                    $action_html .= '<a class="btn btn-danger" onclick="return confirm(\'' . __("Are you sure want to delete this?") . '\')" href="' . url($model_name . '/delete/{id}') . '"><i class="fa fa-trash" aria-hidden="true"></i></a> ';
                }
            }
            if ($status == true && $action == 'view') {
                if (\App\Libraries\Permission::check($model_name . '-view')) {
                    $action_html .= '<a class="btn btn-default" href="' . url($model_name . '/view/{id}') . '"><i class="fa fa-eye" aria-hidden="true"></i></a> ';
                }
            }
            if ($action == 'html') {
                $action_html .= $status;
            }
        }
        return $action_html;
    }
}

function kmGetIndexPageAddAction($model_name)
{
    $action = '<a href="' . url($model_name . '/create') . '" class="btn btn-primary">' . __('Add') . '</a>';
    return $action;
}

function kmGetAllTaxonomy($type)
{
    $allCategory = \KM\KMCrud\Models\KMCategory::where([
        'category_type' => $type
    ])->orderBy('weight', 'ASC')
        ->get();

    return $allCategory;
}

function kmGetTaxonomyRelations($entity_id, $entity_type, $field_name, $multiple = false)
{
    $taxanomySavedRelations = \KM\KMCrud\Models\KMCategoryRelation::where([
        'entity_id' => $entity_id,
        'entity_type' => $entity_type,
        'field_name' => $field_name,
    ]);

    return $multiple == true ? $taxanomySavedRelations->get() : $taxanomySavedRelations->first();
}

function kmGetTaxonomyByID($taxonomy_id)
{
    $category = \KM\KMCrud\Models\KMCategory::findOrFail($taxonomy_id);
    return $category;
}

function kmGetGenericRelations($from_id, $entity_type, $meta_key, $model = false)
{
    $relations = \KM\KMCrud\Models\GenericRelationship::where([
        'entity_type' => $entity_type,
        'meta_key' => $meta_key,
        'from_id' => $from_id,
    ])->get();

    if ($model) {
        $return = collect();
        foreach ($relations as $item) {
            $return[] = $model::findOrFail($item->to_id);
        }

        return $return;
    }

    return $relations;
}

function kmGetLastItemWeight($model)
{
    $weight = 1;
    $lastItem = $model::orderBy('id', 'desc')->first();
    if ($lastItem) {
        $weight = $lastItem->weight + 1;
    }

    return $weight;
}

function kmMBWordWrap($string, $max_length, $end_substitute = null, $html_linebreaks = true)
{

    if ($html_linebreaks) $string = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
    $string = strip_tags($string); //gets rid of the HTML

    if (empty($string) || mb_strlen($string) <= $max_length) {
        if ($html_linebreaks) $string = nl2br($string);
        return $string;
    }

    if ($end_substitute) $max_length -= mb_strlen($end_substitute, 'UTF-8');

    $stack_count = 0;
    while ($max_length > 0) {
        $char = mb_substr($string, --$max_length, 1, 'UTF-8');
        if (preg_match('#[^\p{L}\p{N}]#iu', $char)) $stack_count++; //only alnum characters
        elseif ($stack_count > 0) {
            $max_length++;
            break;
        }
    }
    $string = mb_substr($string, 0, $max_length, 'UTF-8') . $end_substitute;
    if ($html_linebreaks) $string = nl2br($string);

    return $string;
}

function kmGetTopBarElements($modelName, $dataModel, $moduleConfiguration)
{

    $returnArray = [
        'top_bar_image' => [],
        'top_bar_title' => [],
        'top_bar_description' => [],
        'top_bar_phase' => [],
        'detail_section' => [],
        'sidebar' => [],
    ];

    $tabularViews = isset($moduleConfiguration['detailPage']['tabularViews']) ? $moduleConfiguration['detailPage']['tabularViews'] : [];

    foreach ($moduleConfiguration['attributes'] as $fieldName => $configuration) {

        // TOP BAR IMAGE
        if (isset($configuration['detailView']['position']) && $configuration['detailView']['position'] == 'top_bar_image') {
            $media = kmGetMedia($dataModel->id, $modelName, $fieldName);
            $returnArray['top_bar_image'] = [
                'label' => $configuration['label'],
                'value' => '',
                'uri' => isset($media->uri) && !empty($media->uri) ? $media->uri : '',
            ];
        }

        // TOP BAR TITLE
        if (isset($configuration['detailView']['position']) && $configuration['detailView']['position'] == 'top_bar_title') {
            $returnArray['top_bar_title'] = [
                'label' => $configuration['label'],
                'value' => $dataModel->{$fieldName}
            ];
        }

        //TOP BAR DESCRIPTION
        if (isset($configuration['detailView']['position']) && $configuration['detailView']['position'] == 'top_bar_description') {
            $returnArray['top_bar_description'][] = [
                'label' => $configuration['label'],
                'value' => $dataModel->{$fieldName}
            ];
        }

        //TOP BAR PHASE
        if (isset($configuration['detailView']['position']) && $configuration['detailView']['position'] == 'top_bar_phase') {

            $phaseData = [];

            if ($configuration['type'] == 'taxonomy') {
                $allTaxonomy = kmGetAllTaxonomy($configuration['taxonomy_key']);
                $savedCategories = kmGetTaxonomyRelations($dataModel->id, $modelName, $fieldName, true);

                foreach ($allTaxonomy as $taxonomy) {
                    $phaseData[] = [
                        'activeClass' => $savedCategories->contains('category_id', $taxonomy->id) ? 'is-active' : '',
                        'value' => $taxonomy->name,
                    ];
                }
            }

            if ($configuration['type'] == 'entitySelect') {
                $savedCategories = kmGetGenericRelations($dataModel->id, $modelName, $fieldName);
                if ($savedCategories->count() > 0) {
                    foreach ($savedCategories as $catgory) {
                        $taxonomy = $configuration['model']::findOrFail($catgory->to_id);
                        $phaseData[] = [
                            'activeClass' => '',
                            'value' => $taxonomy->name,
                        ];
                    }
                }
            }

            $returnArray['top_bar_phase'] = [
                'label' => $configuration['label'],
                'data' => $phaseData
            ];
        }

        // DETAIL SECTION
        if (isset($configuration['detailView']['position']) && $configuration['detailView']['position'] == 'detail_section') {
            $returnArray['detail_section'][] = [
                'label' => $configuration['label'],
                'value' => $dataModel->{$fieldName}
            ];
        }

        // SIDEBAR
        if (isset($configuration['detailView']['position']) && $configuration['detailView']['position'] == 'sidebar') {

            $newCollection = [];

            /* TODO: needs improvement */
            if (isset($configuration['relationship'])) {

                $key = $configuration['relationship']['key'];
                $value = $configuration['relationship']['value'];

                if (isset($configuration['relationship']['type']) && $configuration['relationship']['type'] == 'hasOne') {
                    $collections = [];
                    $collections[] = (object)[
                        $key => $dataModel->{$configuration['relationship']['method']}->{$key},
                        $value => $dataModel->{$configuration['relationship']['method']}->{$value}
                    ];
                } else {
                    $collections = $dataModel->{$configuration['relationship']['method']};
                }

                foreach ($collections as $col) {
                    $newCollection[] = [
                        'link' => url($configuration['relationship']['modelName'] . '/view/' . $col->{$key}),
                        'value' => $col->{$value}
                    ];
                }
            } elseif ($configuration['type'] == 'customList_1') {

                $value = $configuration['options']['value'];
                $collections = $dataModel->{$configuration['relation']['relationMethod']};

                $newCollection = [];
                if ($collections->count() > 0) {

                    foreach ($collections as $col) {
                        $newCollection[] = [
                            'link' => '',
                            'value' => $col->{$value}
                        ];
                    }
                }
            } elseif ($configuration['type'] == 'customList_2') {
                $value = $configuration['options']['value'];
                $collections = $configuration['options']['data']();
                $result = $collections->firstWhere('id', '=', $dataModel->{$fieldName});

                if (!empty($result)) {
                    $newCollection[] = [
                        'link' => '',
                        'value' => $result->{$value}
                    ];
                }
            } elseif ($configuration['type'] == 'entitySelect') {
                $savedCategories = kmGetGenericRelations($dataModel->id, $modelName, $fieldName);
                if ($savedCategories->count() > 0) {
                    foreach ($savedCategories as $cat) {
                        $taxonomy = $configuration['model']::findOrFail($cat->to_id);
                        $newCollection[] = [
                            'link' => '',
                            'value' => $taxonomy->name
                        ];
                    }
                }
            } elseif (isset($dataModel->{$fieldName}) && !empty($dataModel->{$fieldName})) {
                $newCollection[] = [
                    'link' => '',
                    'value' => $dataModel->{$fieldName}
                ];
            }

            $returnArray['sidebar'][] = [
                'label' => $configuration['label'],
                'data' => $newCollection
            ];
        }

        //Tabular Section
        foreach ($tabularViews as $tbViewKey => $tbViewLabel) {
            if (isset($configuration['detailView']['position']) && $configuration['detailView']['position'] == $tbViewKey) {
                $returnArray[$tbViewKey][] = [
                    'prefix' => isset($configuration['prefix']) ? $configuration['prefix'] : '',
                    'label' => $configuration['label'],
                    'value' => $dataModel->{$fieldName}
                ];
            }
        }

    }

    return $returnArray;
}

