@extends('gopanel.layouts.main')
@section('content') 
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Dillər</h4>

                    <div class="page-title-right">
                        @can('gopanel.contact.socials.add')
                        <button class="btn btn-success" id="open-create-modal" data-route="{{route("gopanel.contact.socials.get.form")}}">
                            <i class="fas fa-plus"></i> Əlavə et
                        </button>
                        @endcan
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
                                        <th>İkon</th>
                                        <th>Adı</th>
                                        <th>Link</th>
                                        <th>Status</th>
                                        <th>manage</th>
                                    </tr>
                                </thead>
                                @php
                                    use App\Enums\Common\SocialIconTypeEnum;
                                @endphp
                                <tbody class="sortable" 
                                        data-key="socials" 
                                        data-row="sort_order"
                                        data-url="{{route("gopanel.general.sortable")}}">
                                    @foreach ($socials as $social)
                                        <tr id="item_{{$social->id}}">
                                            <th scope="row">{{$loop->iteration}}</th>
                                            <td>
                                                @switch($social->icon_type)
                                                    @case(SocialIconTypeEnum::Image)
                                                        <img src="{{ asset($social->icon) }}" alt="{{ $social->name }}">
                                                        @break

                                                    @case(SocialIconTypeEnum::Svg)
                                                    @case(SocialIconTypeEnum::Font)
                                                        {!! $social->icon !!}
                                                        @break

                                                    @case(SocialIconTypeEnum::String)
                                                        <span>{{ $social->icon }}</span>
                                                        @break
                                                @endswitch
                                            </td>
                                            <td>{{$social->name}}</td>
                                            <td><a href="{{$social->url}}">{{$social->url}}</a></td>
                                            <td>{!! app('gopanel')->is_active_btn($social, "is_active", ($social->is_active == "1" ? true : false)) !!}</td>
                                            <td>
                                                <a href="{{route("gopanel.contact.socials.get.form", $social)}}" class="btn btn-outline-success waves-effect waves-light edit" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumata düzəliş et"> 
                                                    <i class="fas fa-pen f-20"></i> 
                                                </a>
                                                <a href="{{route("gopanel.general.delete", $social)}}" class="btn btn-outline-danger waves-effect waves-light delete" data-url="{{route("gopanel.general.delete", $social)}}" data-key="{{get_class($social)}}" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumatı sil"> 
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
@include('gopanel.pages.contact.socials.inc.modal')

<!-- Font Icon Picker Modal (body-level, edit modaldan kənarda) -->
<div class="modal fade" id="iconPickerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" style="z-index:1070;">
    <div class="modal-dialog modal-lg" style="max-width:600px;">
        <div class="modal-content" style="box-shadow: 0 10px 40px rgba(0,0,0,.3);">
            <div class="modal-header py-2">
                <h5 class="modal-title">İkon seçin</h5>
                <div class="ms-auto me-2" style="width:200px;">
                    <input type="text" class="form-control form-control-sm" id="iconSearchInput" placeholder="Axtar...">
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height:400px; overflow-y:auto; padding:12px;">
                <div class="d-flex flex-wrap gap-2 justify-content-center" id="iconGrid"></div>
            </div>
        </div>
    </div>
</div>

@endsection
@push('js_stack')
    <script> 
        var faIcons = [
            'fab fa-facebook-f','fab fa-facebook','fab fa-facebook-messenger',
            'fab fa-instagram','fab fa-twitter','fab fa-x-twitter',
            'fab fa-linkedin-in','fab fa-linkedin',
            'fab fa-youtube','fab fa-tiktok','fab fa-pinterest',
            'fab fa-snapchat-ghost','fab fa-reddit',
            'fab fa-telegram-plane','fab fa-telegram',
            'fab fa-whatsapp','fab fa-viber',
            'fab fa-skype','fab fa-discord','fab fa-slack',
            'fab fa-github','fab fa-gitlab','fab fa-bitbucket',
            'fab fa-dribbble','fab fa-behance',
            'fab fa-vk','fab fa-weixin','fab fa-weibo','fab fa-line',
            'fab fa-medium','fab fa-tumblr',
            'fab fa-spotify','fab fa-soundcloud','fab fa-apple',
            'fab fa-google','fab fa-google-play','fab fa-app-store',
            'fab fa-amazon','fab fa-paypal','fab fa-stripe',
            'fab fa-twitch','fab fa-steam',
            'fab fa-wordpress','fab fa-blogger',
            'fab fa-figma','fab fa-trello','fab fa-jira',
            'fas fa-phone','fas fa-phone-alt','fas fa-mobile-alt',
            'fas fa-envelope','fas fa-at','fas fa-inbox',
            'fas fa-map-marker-alt','fas fa-location-arrow',
            'fas fa-globe','fas fa-link','fas fa-external-link-alt',
            'fas fa-comment','fas fa-comments','fas fa-comment-dots',
            'fas fa-share-alt','fas fa-rss','fas fa-wifi',
            'fas fa-headset','fas fa-fax','fas fa-paper-plane',
            'fas fa-address-book','fas fa-address-card',
            'fas fa-store','fas fa-shopping-cart',
            'fas fa-star','fas fa-heart','fas fa-thumbs-up',
            'fas fa-video','fas fa-camera','fas fa-music',
        ];

        $(document).ready(function(){
            $("body").on('change','#iconTypeSelect', function () {
                var selectedType = $(this).val();
                if (selectedType === 'image') {
                    $('#uploadIcon').closest('.file-upload').show();
                    $('.type-icone').hide();
                } else {
                    $('#uploadIcon').closest('.file-upload').hide();
                    $('.type-icone').show();
                }
                if (selectedType === 'font') {
                    $('#fontIconPickerGroup').show();
                } else {
                    $('#fontIconPickerGroup').hide();
                }
            });

            $("body").on('click', '#openIconPickerBtn', function(){
                var grid = $('#iconGrid');
                grid.empty();
                $.each(faIcons, function(i, cls){
                    grid.append(
                        '<div class="icon-pick-item" data-fa-class="'+cls+'" data-search="'+cls.toLowerCase()+'" '+
                        'style="width:52px;height:52px;display:flex;align-items:center;justify-content:center;'+
                        'border:1px solid #dee2e6;border-radius:6px;cursor:pointer;font-size:20px;transition:all .15s;" '+
                        'title="'+cls+'"><i class="'+cls+'"></i></div>'
                    );
                });
                $('#iconSearchInput').val('');
                $('#iconPickerModal').modal('show');
            });

            $("body").on('input', '#iconSearchInput', function(){
                var q = $(this).val().toLowerCase();
                $('#iconGrid .icon-pick-item').each(function(){
                    $(this).toggle($(this).data('search').indexOf(q) > -1);
                });
            });

            $("body").on('click', '.icon-pick-item', function(){
                var cls = $(this).data('fa-class');
                var tag = '<i class="'+cls+'"></i>';
                $('#iconTextarea').val(tag);
                $('#iconPreviewBox').html(tag);
                $('#iconPickerModal').modal('hide');
            });

            $("body").on('mouseenter', '.icon-pick-item', function(){
                $(this).css({'background':'#e8f0fe','border-color':'#4285f4','transform':'scale(1.15)'});
            }).on('mouseleave', '.icon-pick-item', function(){
                $(this).css({'background':'','border-color':'#dee2e6','transform':'scale(1)'});
            });
        });
    </script>
@endpush