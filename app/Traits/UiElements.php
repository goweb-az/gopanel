<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait UiElements
{
    /**
     * Generates the HTML output for a checkbox input.
     * The checkbox includes the model's ID as the value for identification purposes.
     *
     * @return string
     */
    public function getCheckInputsAttribute(): string
    {
        return '<label class="checkboxs"><input type="checkbox" value="' . $this->id . '"><span class="checkmarks"></span></label>';
    }

    /**
     * Generates the HTML output for a clickable star icon element.
     * The star icon includes the model's ID as a data attribute for interaction.
     *
     * @return string
     */
    public function getStarIconAttribute(): string
    {
        $id = in_array('uid', $this->getFillable()) ? $this->uid : $this->id;
        $filled = $this->is_favorite == 1 ? 'filled' : '';
        return '<div class="set-star rating-select is_favorite ' . $filled . ' " data-url="' . route("crm.general.is.favorite", $id) . '" data-key="' . $this->getTableHash() . '" data-id="' . $this->id . '"><i class="fa fa-star"></i></div>';
    }

    /**
     * Generates the HTML output for an edit button.
     * Typically used in DataTables or admin panels for inline editing.
     *
     * @param Model $item
     * @return string
     */
    public function editBtn(Model $item): string
    {
        return '<a href="#" class="avtar avtar-s btn btn-primary edit"><i class="ti ti-pencil f-18"></i></a>';
    }

    /**
     * Generates the HTML output for a delete button.
     * This button can be bound to a confirmation modal or direct delete action.
     *
     * @param Model $item
     * @return string
     */
    public function deleteBtn(Model $item): string
    {
        return '<a href="#" class="avtar avtar-s btn bg-white btn-link-danger"><i class="ti ti-trash f-18"></i></a>';
    }


    /**
     * Generates a combined HTML string for action buttons based on allowed actions.
     * Typically used in datatable rows to render Edit/Delete controls dynamically.
     *
     * @param Model $item The Eloquent model instance the actions relate to.
     * @param array $allowedActions List of allowed actions (e.g., ['edit', 'delete']).
     *
     * @return string HTML output containing the corresponding action buttons.
     */
    protected function actions(Model $item, array $allowedActions = []): string
    {
        $view = '';

        if (in_array('edit', $allowedActions)) {
            $view .= $this->editBtn($item);
        }

        if (in_array('delete', $allowedActions)) {
            $view .= $this->deleteBtn($item);
        }

        return $view;
    }

    public function getStatusBadgeAttribute()
    {
        return $this->status == 1 ? '<span class="badge badge-pill badge-status bg-success">Active</span>' : '<span class="badge badge-pill badge-status bg-danger">Deaktiv</span>';
    }

    public function getIsActiveBadgeAttribute()
    {
        return $this->is_active == 1 ? '<span class="badge badge-pill badge-status bg-success">Active</span>' : '<span class="badge badge-pill badge-status bg-danger">Deaktiv</span>';
    }

    public function getIsCurrentBadgeAttribute()
    {
        return $this->is_current == 1 ? '<span class="badge badge-pill badge-status bg-success">Bəli</span>' : '<span class="badge badge-pill badge-status bg-danger">Xeyr</span>';
    }


    public  function double_click_edit($row, $routeName = null)
    {
        $route      = is_null($routeName) ? route("gopanel.general.editable", $this->id) : route($routeName, $this);
        $text_title = !empty($this->{$row}) ? str(html_entity_decode(strip_tags($this->{$row})))->limit(55) : $this->{$row};
        return '
        <span class="editable"
            data-model="' . addslashes($this->getModelClass()) . '"
            data-row="' . addslashes($row) . '"
            data-id="' . addslashes($this->id) . '"
            data-url="' . addslashes($route) . '"
            data-text="' . $this->{$row} . '"
        >
            ' . $text_title . ' <i class="fas fa-pen edit-pen"></i>
        </span>';
    }

    public function is_active_btn($row = 'is_active', $checked = false, $class = [], $url = null, $active_text = 'Aktiv', $deactive_text = 'Deaktiv')
    {
        $class      = count($class) ? implode(" ", $class) : 'is_active';
        $model      = get_class($this);
        $checked    = $checked ? 'checked' : '';
        $url        = is_null($url) ? route("general.status.change") : $url;
        return '
            <input
                  class="' . $class . '"
                  type="checkbox"
                  data-toggle="switchbutton"
                  data-onlabel="' . $active_text . '"
                  data-offlabel="' . $deactive_text . '"
                  data-onstyle="success"
                  data-offstyle="danger"
                  data-id="' . $this->uid . '"
                  data-row="' . $row . '"
                  data-model="' . $model . '"
                  data-url="' . $url . '"
                  data-size="sm"
                  ' . $checked . '
            />
        ';
    }
}
