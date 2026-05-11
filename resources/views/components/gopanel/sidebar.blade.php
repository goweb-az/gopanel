<div class="vertical-menu">

    <div data-simplebar class="h-100">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">

                @foreach ($items as $item)
                    @if (!$item->get('route') and is_null($item->get('inner')))
                        <li class="menu-title" key="t-menu">{!! __($item['title']) !!}</li>
                    @elseif($item->get('route') and !is_array($item->get('inner')))
                        <li class="{{ $item->get('is_active_route') ? 'mm-active' : '' }}">
                            <a @if ($item->get('target') == '_blank') target="_blank" @else wire:navigate @endif href="{{ route($item->get('route'), $item->get('params') ?? []) }}">
                                {!! $item->get('icon') !!}
                                <span>{{ __($item->get('title')) }}</span>
                                @if (!is_null($item->get("badge")))
                                    <span class="pc-badge">{!!$item->get("badge")!!}</span>
                                @endif
                            </a>
                        </li>
                    @else
                        <li class="{{ $item->get('isActiveGroup') ? 'mm-active' : '' }}">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                @if (!is_null($item->get('icon')))
                                    {!! $item->get('icon') !!}
                                @endif
                                <span>{!! __($item->get('title')) !!}</span>
                                @if (!is_null($item->get("badge")))
                                    {!!$item->get("badge")!!}
                                @endif
                            </a>
                            <ul class="sub-menu {{ $item->get('isActiveGroup') ? 'mm-show' : '' }}">
                                @foreach ($item->get('inner') as $inner)
                                    <li class="{{ $inner->get('isActiveGroup') ? 'mm-active' : '' }}">
                                        <a wire:navigate href="{{ route($inner->get('route')) }}" class="{{ $inner->get('isActiveGroup') ? 'active' : '' }}">
                                            @if ($inner->has("icon"))
                                                {!! $inner->get('icon') !!}
                                            @endif
                                            {!! __($inner->get('title')) !!}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endforeach

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
