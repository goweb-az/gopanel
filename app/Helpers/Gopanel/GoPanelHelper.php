<?php


namespace App\Helpers\Gopanel;

use Illuminate\Database\Eloquent\Model;

class GoPanelHelper
{




    public function is_active_btn(Model $item, $row = 'is_active', $checked = false, $class = [], $url = null, $active_text = 'Aktiv', $deactive_text = 'Deaktiv')
    {
        $class      = count($class) ? implode(" ", $class) : 'is_active';
        $model      = get_class($item);
        $checkedAttr = $checked ? 'checked' : '';
        $url        = is_null($url) ? route("gopanel.general.status.change") : $url;
        $labelText  = $checked ? $active_text : $deactive_text;
        $labelClass = $checked ? 'text-success' : 'text-danger';
        return '
            <div class="form-check form-switch">
                <input
                      class="form-check-input ' . $class . '"
                      type="checkbox"
                      role="switch"
                      data-id="' . $item->identifier_id . '"
                      data-row="' . $row . '"
                      data-model="' . $model . '"
                      data-url="' . $url . '"
                      data-on-text="' . $active_text . '"
                      data-off-text="' . $deactive_text . '"
                      ' . $checkedAttr . '
                />
                <label class="form-check-label ' . $labelClass . ' fw-semibold" style="font-size:12px;">' . $labelText . '</label>
            </div>
        ';
    }

    /**
     * Bootstrap Switch Button toggle (requires bootstrap-switch-button library)
     */
    public function toggle_btn(Model $item, $row = 'is_active', $checked = false, $class = [], $url = null, $active_text = 'Aktiv', $deactive_text = 'Deaktiv')
    {
        $class      = count($class) ? implode(" ", $class) : 'is_active';
        $model      = get_class($item);
        $checkedAttr = $checked ? 'checked' : '';
        $url        = is_null($url) ? route("gopanel.general.status.change") : $url;
        return '
            <input
                  class="' . $class . '"
                  type="checkbox"
                  data-toggle="switchbutton"
                  data-onlabel="' . $active_text . '"
                  data-offlabel="' . $deactive_text . '"
                  data-onstyle="success"
                  data-offstyle="danger"
                  data-id="' . $item->identifier_id . '"
                  data-row="' . $row . '"
                  data-model="' . $model . '"
                  data-url="' . $url . '"
                  data-size="sm"
                  ' . $checkedAttr . '
            />
        ';
    }


    public function edit_btn(Model $item, $url = null, $redirect = false): string
    {
        $url        = is_null($url) ? route("gopanel.general.edit") : $url;
        $editClass  = $redirect ? 'redirect_manage' : 'edit';
        return ' <a href="' . $url . '" class="btn btn-outline-success waves-effect waves-light ' . $editClass . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumata düzəliş et">
                    <i class="fas fa-pen f-20"></i>
                </a> ';
    }

    public function delete_btn(Model $item, $url = null)
    {
        $key        = method_exists($item, 'getIdentifierIdAttribute') ? $item->identifier_id : $item->id;
        $url        = is_null($url) ? route("gopanel.general.delete", $key) : $url;
        return '<a  class="btn btn-outline-danger waves-effect waves-light delete" data-url="' . $url . '" data-key="' . get_class($item)  . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumatı sil">
                    <i class="fas fa-trash"></i>
                </a> ';
    }



    public function getYoutuebId($url)
    {
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
        return $match[1] ?? NULL;
    }


}
