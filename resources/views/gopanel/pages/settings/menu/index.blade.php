@extends('gopanel.layouts.main')
@section('content') 
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Bloq</h4>

                    <div class="page-title-right">
                        <a class="btn btn-success" href="{{route("gopanel.settings.menu.store", ['parent_id' => $parent_id])}}">
                            <i class="fas fa-plus"></i> Əlavə et
                        </a>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">

                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Adı</th>
                                        <th>Alt Menyular</th>
                                        <th>Menyu Tipi</th>
                                        <th>Menyu Movqeyi</th>
                                        <th>manage</th>
                                    </tr>
                                </thead>
                                <tbody class="sortable" data-key="{{ get_class(new \App\Models\Navigation\Menu) }}" data-row="sort_order" data-url="{{route("gopanel.general.sortable")}}" >
                                    @foreach ($menuList as $menu)
                                        <tr id="item_{{$menu->id}}">
                                            <th scope="row">{{$loop->iteration}}</th>
                                            <td>{{$menu->title}}</td>
                                            <td>
                                                <a href="{{route("gopanel.settings.menu.index", ['parent_id' => $menu->id])}}">
                                                    Alt Menyular [{{$menu->children->count()}}]
                                                </a>
                                            </td>
                                            <td>{{$menu->menu_type}}</td>
                                            <td>{{$menu->menu_position}}</td>
                                            <td>{!! app('gopanel')->is_active_btn($menu, "is_active", ($menu->is_active == "1" ? true : false)) !!}</td>
                                            <td>
                                                <a href="{{route("gopanel.settings.menu.store", $menu)}}" class="btn btn-outline-success waves-effect waves-light" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumata düzəliş et"> 
                                                    <i class="fas fa-pen f-20"></i> 
                                                </a>
                                                <a href="{{route("gopanel.general.delete", $menu)}}" class="btn btn-outline-danger waves-effect waves-light delete" data-url="{{route("gopanel.general.delete", $menu)}}" data-key="{{ get_class($menu) }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumatı sil"> 
                                                    <i class="fas fa-trash"></i> 
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div><!--end col-->
        </div>
        
    </div> <!-- container-fluid -->
</div>
<!-- End Page-content -->
@endsection